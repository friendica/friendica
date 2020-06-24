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
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Email;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;

class OnePoll
{
	public static function execute($contact_id = 0, $command = '')
	{
		Logger::log('Start for contact ' . $contact_id);

		$force = false;

		if ($command == "force") {
			$force = true;
		}

		if (!$contact_id) {
			Logger::log('no contact');
			return;
		}


		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);
		if (!DBA::isResult($contact)) {
			Logger::log('Contact not found or cannot be used.');
			return;
		}

		if (($contact['network'] != Protocol::MAIL) || $force) {
			Contact::updateFromProbe($contact_id, '', $force);
		}

		// Special treatment for wrongly detected local contacts
		if (!$force && ($contact['network'] != Protocol::DFRN) && Contact::isLocalById($contact_id)) {
			Contact::updateFromProbe($contact_id, Protocol::DFRN, true);
			$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);
		}

		if (($contact['network'] == Protocol::DFRN) && !Contact::isLegacyDFRNContact($contact)) {
			$protocol = Protocol::ACTIVITYPUB;
		} else {
			$protocol = $contact['network'];
		}

		$importer_uid = $contact['uid'];

		$updated = DateTimeFormat::utcNow();

		if ($importer_uid == 0) {
			Logger::log('Ignore public contacts');

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			return;
		}

		// Possibly switch the remote contact to AP
		if ($protocol === Protocol::OSTATUS) {
			ActivityPub\Receiver::switchContact($contact['id'], $importer_uid, $contact['url']);
			$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);
		}

		// load current friends if possible.
		if (!empty($contact['poco']) && ($contact['success_update'] > $contact['failure_update'])) {
			if (!DBA::exists('glink', ["`cid` = ? AND updated > UTC_TIMESTAMP() - INTERVAL 1 DAY", $contact['id']])) {
				PortableContact::loadWorker($contact['id'], $importer_uid, 0, $contact['poco']);
			}
		}

		// Don't poll if polling is deactivated (But we poll feeds and mails anyway)
		if (!in_array($protocol, [Protocol::FEED, Protocol::MAIL]) && DI::config()->get('system', 'disable_polling')) {
			Logger::log('Polling is disabled');

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			return;
		}

		// We don't poll AP contacts by now
		if ($protocol === Protocol::ACTIVITYPUB) {
			Logger::log("Don't poll AP contact");

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			return;
		}

		$importer = User::getOwnerDataById($importer_uid);

		if (empty($importer)) {
			Logger::log('No self contact for user '.$importer_uid);

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			return;
		}

		$url = '';
		$xml = false;

		if ($contact['subhub']) {
			$poll_interval = DI::config()->get('system', 'pushpoll_frequency', 3);
			$contact['priority'] = intval($poll_interval);
			$hub_update = false;

			if (DateTimeFormat::utcNow() > DateTimeFormat::utc($contact['last-update'] . " + 1 day")) {
				$hub_update = true;
			}
		} else {
			$hub_update = false;
		}

		Logger::log("poll: ({$protocol}-{$contact['id']}) IMPORTER: {$importer['name']}, CONTACT: {$contact['name']}");

		$xml = '';

		if ($protocol === Protocol::DFRN) {
			$xml = self::pollDFRN($contact, $updated);
		} elseif (($protocol === Protocol::OSTATUS)
			|| ($protocol === Protocol::DIASPORA)
			|| ($protocol === Protocol::FEED)) {
			$xml = self::pollFeed($contact, $protocol, $updated);
		} elseif ($protocol === Protocol::MAIL) {
			self::pollMail($contact, $importer_uid, $updated);
		}

		if (!empty($xml)) {
			Logger::log('received xml : ' . $xml, Logger::DATA);
			if (!strstr($xml, '<')) {
				Logger::log('post_handshake: response from ' . $url . ' did not contain XML.');

				$fields = ['last-update' => $updated, 'failure_update' => $updated];
				self::updateContact($contact, $fields);
				Contact::markForArchival($contact);
				return;
			}


			Logger::log("Consume feed of contact ".$contact['id']);

			consume_feed($xml, $importer, $contact, $hub);

			// do it a second time for DFRN so that any children find their parents.
			if ($protocol === Protocol::DFRN) {
				consume_feed($xml, $importer, $contact, $hub);
			}

			$hubmode = 'subscribe';
			if ($protocol === Protocol::DFRN || $contact['blocked']) {
				$hubmode = 'unsubscribe';
			}

			if (($protocol === Protocol::OSTATUS ||  $protocol == Protocol::FEED) && (! $contact['hub-verify'])) {
				$hub_update = true;
			}

			if ($force) {
				$hub_update = true;
			}

			Logger::log("Contact ".$contact['id']." returned hub: ".$hub." Network: ".$protocol." Relation: ".$contact['rel']." Update: ".$hub_update);

			if (strlen($hub) && $hub_update && (($contact['rel'] != Contact::FOLLOWER) || $protocol == Protocol::FEED)) {
				Logger::log('hub ' . $hubmode . ' : ' . $hub . ' contact name : ' . $contact['name'] . ' local user : ' . $importer['name']);
				$hubs = explode(',', $hub);

				if (count($hubs)) {
					foreach ($hubs as $h) {
						$h = trim($h);

						if (!strlen($h)) {
							continue;
						}

						self::subscribeToHub($h, $importer, $contact, $hubmode);
					}
				}
			}

			self::updateContact($contact, ['last-update' => $updated, 'success_update' => $updated]);
			Contact::unmarkForArchival($contact);
		} elseif (in_array($contact["network"], [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED])) {
			self::updateContact($contact, ['last-update' => $updated, 'failure_update' => $updated]);
			Contact::markForArchival($contact);
		} else {
			self::updateContact($contact, ['last-update' => $updated]);
		}

		Logger::log('End');
		return;
	}

	private static function RemoveReply($subject)
	{
		while (in_array(strtolower(substr($subject, 0, 3)), ["re:", "aw:"])) {
			$subject = trim(substr($subject, 4));
		}

		return $subject;
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
		DBA::update('contact', $fields, ['id' => $contact['id']]);
		DBA::update('contact', $fields, ['uid' => 0, 'nurl' => $contact['nurl']]);
	}

	/**
	 * Poll DFRN contacts
	 *
	 * @param  array  $contact The personal contact entry
	 * @param  string $updated The updated date
	 * @return string polled XML
	 * @throws \Exception
	 */
	private static function pollDFRN(array $contact, $updated)
	{
		$idtosend = $orig_id = (($contact['dfrn-id']) ? $contact['dfrn-id'] : $contact['issued-id']);
		if (intval($contact['duplex']) && $contact['dfrn-id']) {
			$idtosend = '0:' . $orig_id;
		}
		if (intval($contact['duplex']) && $contact['issued-id']) {
			$idtosend = '1:' . $orig_id;
		}

		// they have permission to write to us. We already filtered this in the contact query.
		$perm = 'rw';

		// But this may be our first communication, so set the writable flag if it isn't set already.
		if (!intval($contact['writable'])) {
			$fields = ['writable' => true];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		}

		$last_update = (($contact['last-update'] <= DBA::NULL_DATETIME)
			? DateTimeFormat::utc('now - 7 days', DateTimeFormat::ATOM)
			: DateTimeFormat::utc($contact['last-update'], DateTimeFormat::ATOM)
		);

		$url = $contact['poll'] . '?dfrn_id=' . $idtosend
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
			. '&type=data&last_update=' . $last_update
			. '&perm=' . $perm;

		$curlResult = Network::curl($url);

		if (!$curlResult->isSuccess() && ($curlResult->getErrorNumber() == CURLE_OPERATION_TIMEDOUT)) {
			// set the last-update so we don't keep polling
			self::updateContact($contact, ['last-update' => $updated]);
			Contact::markForArchival($contact);
			Logger::log('Contact archived');
			return false;
		}

		$handshake_xml = $curlResult->getBody();
		$html_code = $curlResult->getReturnCode();

		Logger::log('handshake with url ' . $url . ' returns xml: ' . $handshake_xml, Logger::DATA);

		if (!strlen($handshake_xml) || ($html_code >= 400) || !$html_code) {
			// dead connection - might be a transient event, or this might
			// mean the software was uninstalled or the domain expired.
			// Will keep trying for one month.
			Logger::log("$url appears to be dead - marking for death ");

			// set the last-update so we don't keep polling
			$fields = ['last-update' => $updated, 'failure_update' => $updated];
			self::updateContact($contact, $fields);
			Contact::markForArchival($contact);
			return false;
		}

		if (!strstr($handshake_xml, '<')) {
			Logger::log('response from ' . $url . ' did not contain XML.');

			$fields = ['last-update' => $updated, 'failure_update' => $updated];
			self::updateContact($contact, $fields);
			Contact::markForArchival($contact);
			return false;
		}

		$res = XML::parseString($handshake_xml);

		if (!is_object($res)) {
			Logger::info('Unparseable response', ['url' => $url]);

			$fields = ['last-update' => $updated, 'failure_update' => $updated];
			self::updateContact($contact, $fields);
			Contact::markForArchival($contact);
			return false;
		}

		if (intval($res->status) == 1) {
			// we may not be friends anymore. Will keep trying for one month.
			Logger::log("$url replied status 1 - marking for death ");

			// set the last-update so we don't keep polling
			$fields = ['last-update' => $updated, 'failure_update' => $updated];
			self::updateContact($contact, $fields);
			Contact::markForArchival($contact);
		} elseif ($contact['term-date'] > DBA::NULL_DATETIME) {
			Contact::unmarkForArchival($contact);
		}

		if ((intval($res->status) != 0) || !strlen($res->challenge) || !strlen($res->dfrn_id)) {
			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			Logger::log('Contact status is ' . $res->status);
			return false;
		}

		if (((float)$res->dfrn_version > 2.21) && ($contact['poco'] == '')) {
			$fields = ['poco' => str_replace('/profile/', '/poco/', $contact['url'])];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		}

		$postvars = [];

		$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
		$challenge    = hex2bin((string) $res->challenge);

		$final_dfrn_id = '';

		if ($contact['duplex'] && strlen($contact['prvkey'])) {
			openssl_private_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
			openssl_private_decrypt($challenge, $postvars['challenge'], $contact['prvkey']);
		} else {
			openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
			openssl_public_decrypt($challenge, $postvars['challenge'], $contact['pubkey']);
		}

		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

		if (strpos($final_dfrn_id, ':') == 1) {
			$final_dfrn_id = substr($final_dfrn_id, 2);
		}

		// There are issues with the legacy DFRN transport layer.
		// Since we mostly don't use it anyway, we won't dig into it deeper, but simply ignore it.
		if (empty($final_dfrn_id) || empty($orig_id)) {
			Logger::log('Contact has got no ID - quitting');
			return false;
		}

		if ($final_dfrn_id != $orig_id) {
			// did not decode properly - cannot trust this site
			Logger::log('ID did not decode: ' . $contact['id'] . ' orig: ' . $orig_id . ' final: ' . $final_dfrn_id);

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			Contact::markForArchival($contact);
			return false;
		}

		$postvars['dfrn_id'] = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		$postvars['perm'] = 'rw';

		return Network::post($contact['poll'], $postvars)->getBody();
	}

	/**
	 * Poll Feed/OStatus contacts
	 *
	 * @param  array  $contact The personal contact entry
	 * @param  string $protocol The used protocol of the contact
	 * @param  string $updated The updated date
	 * @return string polled XML
	 * @throws \Exception
	 */
	private static function pollFeed(array $contact, $protocol, $updated)
	{
		// Upgrading DB fields from an older Friendica version
		// Will only do this once per notify-enabled OStatus contact
		// or if relationship changes

		$stat_writeable = ((($contact['notify']) && ($contact['rel'] == Contact::FOLLOWER || $contact['rel'] == Contact::FRIEND)) ? 1 : 0);

		// Contacts from OStatus are always writable
		if ($protocol === Protocol::OSTATUS) {
			$stat_writeable = 1;
		}

		if ($stat_writeable != $contact['writable']) {
			$fields = ['writable' => $stat_writeable];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		}

		// Are we allowed to import from this person?
		if ($contact['rel'] == Contact::FOLLOWER || $contact['blocked']) {
			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
			Logger::log('Contact is blocked or only a follower');
			return false;
		}

		$cookiejar = tempnam(get_temppath(), 'cookiejar-onepoll-');
		$curlResult = Network::curl($contact['poll'], false, ['cookiejar' => $cookiejar]);
		unlink($cookiejar);

		if ($curlResult->isTimeout()) {
			// set the last-update so we don't keep polling
			self::updateContact($contact, ['last-update' => $updated]);
			Contact::markForArchival($contact);
			Logger::log('Contact archived');
			return false;
		}

		return $curlResult->getBody();
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
		Logger::log("Mail: Fetching for ".$contact['addr'], Logger::DEBUG);

		$mail_disabled = ((function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) ? 0 : 1);
		if ($mail_disabled) {
			// set the last-update so we don't keep polling
			self::updateContact($contact, ['last-update' => $updated]);
			Contact::markForArchival($contact);
			Logger::log('Contact archived');
			return;
		}

		Logger::log("Mail: Enabled", Logger::DEBUG);

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
			Logger::log("Mail: Connect to " . $mailconf['user']);
			if ($mbox) {
				$fields = ['last_check' => $updated];
				DBA::update('mailacct', $fields, ['id' => $mailconf['id']]);
				Logger::log("Mail: Connected to " . $mailconf['user']);
			} else {
				Logger::log("Mail: Connection error ".$mailconf['user']." ".print_r(imap_errors(), true));
			}
		}

		if (!$mbox) {
			return;
		}

		$msgs = Email::poll($mbox, $contact['addr']);

		if (count($msgs)) {
			Logger::log("Mail: Parsing ".count($msgs)." mails from ".$contact['addr']." for ".$mailconf['user'], Logger::DEBUG);

			$metas = Email::messageMeta($mbox, implode(',', $msgs));

			if (count($metas) != count($msgs)) {
				Logger::log("for " . $mailconf['user'] . " there are ". count($msgs) . " messages but received " . count($metas) . " metas", Logger::DEBUG);
			} else {
				$msgs = array_combine($msgs, $metas);

				foreach ($msgs as $msg_uid => $meta) {
					Logger::log("Mail: Parsing mail ".$msg_uid, Logger::DATA);

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
					$item = Item::selectFirst($fields, $condition);
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
						$parent = Item::selectFirst(['parent-uri'], $condition);
						if (DBA::isResult($parent)) {
							$datarray['parent-uri'] = $parent['parent-uri'];  // Set the parent as the top-level item
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
					if (empty($datarray['parent-uri']) && $reply) {
						$condition = ['title' => $datarray['title'], 'uid' => $importer_uid, 'network' => Protocol::MAIL];
						$params = ['order' => ['created' => true]];
						$parent = Item::selectFirst(['parent-uri'], $condition, $params);
						if (DBA::isResult($parent)) {
							$datarray['parent-uri'] = $parent['parent-uri'];
						}
					}

					if (empty($datarray['parent-uri'])) {
						$datarray['parent-uri'] = $datarray['uri'];
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

					if ($datarray['parent-uri'] === $datarray['uri']) {
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
			Logger::log("Mail: no mails for ".$mailconf['user']);
		}

		Logger::log("Mail: closing connection for ".$mailconf['user']);
		imap_close($mbox);
	}


	/**
	 * @param string $url
	 * @param array  $importer
	 * @param array  $contact
	 * @param string $hubmode
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function subscribeToHub(string $url, array $importer, array $contact, $hubmode = 'subscribe')
	{
		/*
		 * Diaspora has different message-ids in feeds than they do
		 * through the direct Diaspora protocol. If we try and use
		 * the feed, we'll get duplicates. So don't.
		 */
		if ($contact['network'] === Protocol::DIASPORA) {
			return;
		}

		// Without an importer we don't have a user id - so we quit
		if (empty($importer)) {
			return;
		}

		$user = DBA::selectFirst('user', ['nickname'], ['uid' => $importer['uid']]);

		// No user, no nickname, we quit
		if (!DBA::isResult($user)) {
			return;
		}

		$push_url = DI::baseUrl() . '/pubsub/' . $user['nickname'] . '/' . $contact['id'];

		// Use a single verify token, even if multiple hubs
		$verify_token = ((strlen($contact['hub-verify'])) ? $contact['hub-verify'] : Strings::getRandomHex());

		$params = 'hub.mode=' . $hubmode . '&hub.callback=' . urlencode($push_url) . '&hub.topic=' . urlencode($contact['poll']) . '&hub.verify=async&hub.verify_token=' . $verify_token;

		Logger::log('subscribe_to_hub: ' . $hubmode . ' ' . $contact['name'] . ' to hub ' . $url . ' endpoint: ' . $push_url . ' with verifier ' . $verify_token);

		if (!strlen($contact['hub-verify']) || ($contact['hub-verify'] != $verify_token)) {
			DBA::update('contact', ['hub-verify' => $verify_token], ['id' => $contact['id']]);
		}

		$postResult = Network::post($url, $params);

		Logger::log('subscribe_to_hub: returns: ' . $postResult->getReturnCode(), Logger::DEBUG);

		return;

	}
}
