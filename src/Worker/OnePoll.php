<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Email;
use Friendica\Protocol\Feed;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class OnePoll
{
	public static function execute($contact_id = 0, $command = '')
	{
		Logger::notice('Start polling/probing contact', ['id' => $contact_id]);

		$force = ($command == "force");

		if (empty($contact_id)) {
			Logger::notice('no contact provided');
			return;
		}

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);
		if (!DBA::isResult($contact)) {
			Logger::warning('Contact not found', ['id' => $contact_id]);
			return;
		}

		// We never probe mail contacts since their probing demands a mail from the contact in the inbox.
		// We don't probe feed accounts by default since they are polled in a higher frequency, but forced probes are okay.
		if ($force && ($contact['network'] == Protocol::FEED)) {
			$success = Contact::updateFromProbe($contact_id);
		} else {
			$success = true;
		}

		$importer_uid = $contact['uid'];

		$updated = DateTimeFormat::utcNow();

		// Possibly switch the remote contact to AP
		if ($success && ($contact['network'] === Protocol::OSTATUS)) {
			ActivityPub\Receiver::switchContact($contact['id'], $importer_uid, $contact['url']);
		}

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);

		if ($success && ($importer_uid != 0) && in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])
			&& in_array($contact['network'], [Protocol::FEED, Protocol::MAIL, Protocol::OSTATUS])) {
			$importer = User::getOwnerDataById($importer_uid);
			if (empty($importer)) {
				Logger::warning('No self contact for user', ['uid' => $importer_uid]);

				// set the last-update so we don't keep polling
				DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
				return;
			}

			Logger::info('Start polling/subscribing', ['protocol' => $contact['network'], 'id' => $contact['id']]);
			if ($contact['network'] === Protocol::FEED) {
				$success = self::pollFeed($contact, $importer);
			} elseif ($contact['network'] === Protocol::MAIL) {
				$success = self::pollMail($contact, $importer_uid, $updated);
			} else {
				$success = self::subscribeToHub($contact['url'], $importer, $contact, $contact['blocked'] ? 'unsubscribe' : 'subscribe');
			}
			if (!$success) {
				Logger::notice('Probing had been successful, polling/subscribing failed', ['protocol' => $contact['network'], 'id' => $contact['id'], 'url' => $contact['url']]);
			}
		}

		if ($success) {
			self::updateContact($contact, ['failed' => false, 'last-update' => $updated, 'success_update' => $updated]);
			Contact::unmarkForArchival($contact);	
		} else {
			self::updateContact($contact, ['failed' => true, 'last-update' => $updated, 'failure_update' => $updated]);
			Contact::markForArchival($contact);
		}

		Logger::notice('End');
		return;
	}

	/**
	 * Updates a personal contact entry and the public contact entry
	 *
	 * @param array $contact The personal contact entry
	 * @param array $fields  The fields that are updated
	 * @throws \Exception
	 */
	private static function updateContact(array $contact, array $fields)
	{
		if (in_array($contact['network'], [Protocol::FEED, Protocol::MAIL, Protocol::OSTATUS])) {
			// Update the user's contact
			DBA::update('contact', $fields, ['id' => $contact['id']]);

			// Update the public contact
			DBA::update('contact', $fields, ['uid' => 0, 'nurl' => $contact['nurl']]);

			// Update the rest of the contacts that aren't polled
			DBA::update('contact', $fields, ['rel' => Contact::FOLLOWER, 'nurl' => $contact['nurl']]);
		} else {
			// Update all contacts
			DBA::update('contact', $fields, ['nurl' => $contact['nurl']]);
		}
	}

	/**
	 * Poll Feed contacts
	 *
	 * @param  array $contact The personal contact entry
	 * @param  array $importer
	 *
	 * @return bool   Success
	 * @throws \Exception
	 */
	private static function pollFeed(array $contact, $importer)
	{
		// Are we allowed to import from this person?
		if ($contact['rel'] == Contact::FOLLOWER || $contact['blocked']) {
			Logger::notice('Contact is blocked or only a follower');
			return false;
		}

		$cookiejar = tempnam(get_temppath(), 'cookiejar-onepoll-');
		$curlResult = DI::httpRequest()->get($contact['poll'], ['cookiejar' => $cookiejar]);
		unlink($cookiejar);

		if ($curlResult->isTimeout()) {
			Logger::notice('Polling timed out', ['id' => $contact['id'], 'url' => $contact['poll']]);
			return false;
		}

		$xml = $curlResult->getBody();
		if (empty($xml)) {
			Logger::notice('Empty content', ['id' => $contact['id'], 'url' => $contact['poll']]);
			return false;
		}

		if (!strstr($xml, '<')) {
			Logger::notice('response did not contain XML.', ['id' => $contact['id'], 'url' => $contact['poll']]);
			return false;
		}

		Logger::notice('Consume feed of contact', ['id' => $contact['id'], 'url' => $contact['poll']]);

		return !empty(Feed::import($xml, $importer, $contact));
	}

	/**
	 * Poll Mail contacts
	 *
	 * @param  array   $contact      The personal contact entry
	 * @param  integer $importer_uid The UID of the importer
	 * @param  string  $updated      The updated date
	 * @throws \Exception
	 */
	private static function pollMail(array $contact, $importer_uid, $updated)
	{
		Logger::info('Fetching mails', ['addr' => $contact['addr']]);

		$mail_disabled = ((function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) ? 0 : 1);
		if ($mail_disabled) {
			Logger::notice('Mail is disabled');
			return false;
		}

		Logger::info('Mail is enabled');

		$mbox = null;
		$user = DBA::selectFirst('user', ['prvkey'], ['uid' => $importer_uid]);

		$condition = ["`server` != '' AND `uid` = ?", $importer_uid];
		$mailconf = DBA::selectFirst('mailacct', [], $condition);
		if (DBA::isResult($user) && DBA::isResult($mailconf)) {
			$mailbox = Email::constructMailboxName($mailconf);
			$password = '';
			openssl_private_decrypt(hex2bin($mailconf['pass']), $password, $user['prvkey']);
			$mbox = Email::connect($mailbox, $mailconf['user'], $password);
			unset($password);
			Logger::notice('Connect', ['user' => $mailconf['user']]);
			if ($mbox) {
				$fields = ['last_check' => $updated];
				DBA::update('mailacct', $fields, ['id' => $mailconf['id']]);
				Logger::notice('Connected', ['user' => $mailconf['user']]);
			} else {
				Logger::notice('Connection error', ['user' => $mailconf['user'], 'error' => imap_errors()]);
				return false;
			}
		}

		if (empty($mbox)) {
			return false;
		}

		$msgs = Email::poll($mbox, $contact['addr']);

		if (count($msgs)) {
			Logger::info('Parsing mails', ['count' => count($msgs), 'addr' => $contact['addr'], 'user' => $mailconf['user']]);

			$metas = Email::messageMeta($mbox, implode(',', $msgs));

			if (count($metas) != count($msgs)) {
				Logger::log("for " . $mailconf['user'] . " there are ". count($msgs) . " messages but received " . count($metas) . " metas", Logger::DEBUG);
			} else {
				$msgs = array_combine($msgs, $metas);

				foreach ($msgs as $msg_uid => $meta) {
					Logger::info('Parsing mail', ['message-uid' => $msg_uid]);

					$datarray = [];
					$datarray['uid'] = $importer_uid;
					$datarray['contact-id'] = $contact['id'];
					$datarray['verb'] = Activity::POST;
					$datarray['object-type'] = Activity\ObjectType::NOTE;
					$datarray['network'] = Protocol::MAIL;
					// $meta = Email::messageMeta($mbox, $msg_uid);

					$datarray['uri'] = Email::msgid2iri(trim($meta->message_id, '<>'));

					// Have we seen it before?
					$fields = ['deleted', 'id'];
					$condition = ['uid' => $importer_uid, 'uri' => $datarray['uri']];
					$item = Post::selectFirst($fields, $condition);
					if (DBA::isResult($item)) {
						Logger::log("Mail: Seen before ".$msg_uid." for ".$mailconf['user']." UID: ".$importer_uid." URI: ".$datarray['uri'],Logger::DEBUG);

						// Only delete when mails aren't automatically moved or deleted
						if (($mailconf['action'] != 1) && ($mailconf['action'] != 3))
							if ($meta->deleted && ! $item['deleted']) {
								$fields = ['deleted' => true, 'changed' => $updated];
								Item::update($fields, ['id' => $item['id']]);
							}

						switch ($mailconf['action']) {
							case 0:
								Logger::log("Mail: Seen before ".$msg_uid." for ".$mailconf['user'].". Doing nothing.", Logger::DEBUG);
								break;
							case 1:
								Logger::log("Mail: Deleting ".$msg_uid." for ".$mailconf['user']);
								imap_delete($mbox, $msg_uid, FT_UID);
								break;
							case 2:
								Logger::log("Mail: Mark as seen ".$msg_uid." for ".$mailconf['user']);
								imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
								break;
							case 3:
								Logger::log("Mail: Moving ".$msg_uid." to ".$mailconf['movetofolder']." for ".$mailconf['user']);
								imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
								if ($mailconf['movetofolder'] != "") {
									imap_mail_move($mbox, $msg_uid, $mailconf['movetofolder'], FT_UID);
								}
								break;
						}
						continue;
					}

					// look for a 'references' or an 'in-reply-to' header and try to match with a parent item we have locally.
					$raw_refs = (property_exists($meta, 'references') ? str_replace("\t", '', $meta->references) : '');
					if (!trim($raw_refs)) {
						$raw_refs = (property_exists($meta, 'in_reply_to') ? str_replace("\t", '', $meta->in_reply_to) : '');
					}
					$raw_refs = trim($raw_refs);  // Don't allow a blank reference in $refs_arr

					if ($raw_refs) {
						$refs_arr = explode(' ', $raw_refs);
						if (count($refs_arr)) {
							for ($x = 0; $x < count($refs_arr); $x ++) {
								$refs_arr[$x] = Email::msgid2iri(str_replace(['<', '>', ' '],['', '', ''], $refs_arr[$x]));
							}
						}
						$condition = ['uri' => $refs_arr, 'uid' => $importer_uid];
						$parent = Post::selectFirst(['uri'], $condition);
						if (DBA::isResult($parent)) {
							$datarray['thr-parent'] = $parent['uri'];
						}
					}

					// Decoding the header
					$subject = imap_mime_header_decode($meta->subject ?? '');
					$datarray['title'] = "";
					foreach ($subject as $subpart) {
						if ($subpart->charset != "default") {
							$datarray['title'] .= iconv($subpart->charset, 'UTF-8//IGNORE', $subpart->text);
						} else {
							$datarray['title'] .= $subpart->text;
						}
					}
					$datarray['title'] = Strings::escapeTags(trim($datarray['title']));

					//$datarray['title'] = Strings::escapeTags(trim($meta->subject));
					$datarray['created'] = DateTimeFormat::utc($meta->date);

					// Is it a reply?
					$reply = ((substr(strtolower($datarray['title']), 0, 3) == "re:") ||
						(substr(strtolower($datarray['title']), 0, 3) == "re-") ||
						($raw_refs != ""));

					// Remove Reply-signs in the subject
					$datarray['title'] = self::RemoveReply($datarray['title']);

					// If it seems to be a reply but a header couldn't be found take the last message with matching subject
					if (empty($datarray['thr-parent']) && $reply) {
						$condition = ['title' => $datarray['title'], 'uid' => $importer_uid, 'network' => Protocol::MAIL];
						$params = ['order' => ['created' => true]];
						$parent = Post::selectFirst(['uri'], $condition, $params);
						if (DBA::isResult($parent)) {
							$datarray['thr-parent'] = $parent['uri'];
						}
					}

					$headers = imap_headerinfo($mbox, $meta->msgno);

					$object = [];

					if (!empty($headers->from)) {
						$object['from'] = $headers->from;
					}

					if (!empty($headers->to)) {
						$object['to'] = $headers->to;
					}

					if (!empty($headers->reply_to)) {
						$object['reply_to'] = $headers->reply_to;
					}

					if (!empty($headers->sender)) {
						$object['sender'] = $headers->sender;
					}

					if (!empty($object)) {
						$datarray['object'] = json_encode($object);
					}

					$fromname = $frommail = $headers->from[0]->mailbox . '@' . $headers->from[0]->host;
					if (!empty($headers->from[0]->personal)) {
						$fromname = $headers->from[0]->personal;
					}

					$datarray['author-name'] = $fromname;
					$datarray['author-link'] = "mailto:".$frommail;
					$datarray['author-avatar'] = $contact['photo'];

					$datarray['owner-name'] = $contact['name'];
					$datarray['owner-link'] = "mailto:".$contact['addr'];
					$datarray['owner-avatar'] = $contact['photo'];

					if (empty($datarray['thr-parent']) || ($datarray['thr-parent'] === $datarray['uri'])) {
						$datarray['private'] = Item::PRIVATE;
					}

					if (!DI::pConfig()->get($importer_uid, 'system', 'allow_public_email_replies')) {
						$datarray['private'] = Item::PRIVATE;
						$datarray['allow_cid'] = '<' . $contact['id'] . '>';
					}

					$datarray = Email::getMessage($mbox, $msg_uid, $reply, $datarray);
					if (empty($datarray['body'])) {
						Logger::log("Mail: can't fetch msg ".$msg_uid." for ".$mailconf['user']);
						continue;
					}

					Logger::log("Mail: Importing ".$msg_uid." for ".$mailconf['user']);

					Item::insert($datarray);

					switch ($mailconf['action']) {
						case 0:
							Logger::log("Mail: Seen before ".$msg_uid." for ".$mailconf['user'].". Doing nothing.", Logger::DEBUG);
							break;
						case 1:
							Logger::log("Mail: Deleting ".$msg_uid." for ".$mailconf['user']);
							imap_delete($mbox, $msg_uid, FT_UID);
							break;
						case 2:
							Logger::log("Mail: Mark as seen ".$msg_uid." for ".$mailconf['user']);
							imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
							break;
						case 3:
							Logger::log("Mail: Moving ".$msg_uid." to ".$mailconf['movetofolder']." for ".$mailconf['user']);
							imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
							if ($mailconf['movetofolder'] != "") {
								imap_mail_move($mbox, $msg_uid, $mailconf['movetofolder'], FT_UID);
							}
							break;
					}
				}
			}
		} else {
			Logger::notice('No mails', ['user' => $mailconf['user']]);
		}


		Logger::info('Closing connection', ['user' => $mailconf['user']]);
		imap_close($mbox);

		return true;
	}

	private static function RemoveReply($subject)
	{
		while (in_array(strtolower(substr($subject, 0, 3)), ["re:", "aw:"])) {
			$subject = trim(substr($subject, 4));
		}

		return $subject;
	}

	/**
	 * @param string $url
	 * @param array  $importer
	 * @param array  $contact
	 * @param string $hubmode
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function subscribeToHub(string $url, array $importer, array $contact, string $hubmode = 'subscribe')
	{
		$push_url = DI::baseUrl() . '/pubsub/' . $importer['nick'] . '/' . $contact['id'];

		// Use a single verify token, even if multiple hubs
		$verify_token = $contact['hub-verify'] ?: Strings::getRandomHex();

		$params = 'hub.mode=' . $hubmode . '&hub.callback=' . urlencode($push_url) . '&hub.topic=' . urlencode($contact['poll']) . '&hub.verify=async&hub.verify_token=' . $verify_token;

		Logger::info('Hub subscription start', ['mode' => $hubmode, 'name' => $contact['name'], 'hub' => $url, 'endpoint' => $push_url, 'verifier' => $verify_token]);

		if (!strlen($contact['hub-verify']) || ($contact['hub-verify'] != $verify_token)) {
			DBA::update('contact', ['hub-verify' => $verify_token], ['id' => $contact['id']]);
		}

		$postResult = DI::httpRequest()->post($url, $params);

		Logger::info('Hub subscription done', ['result' => $postResult->getReturnCode()]);

		return $postResult->isSuccess();
	}
}
