<?php
/**
 * @file src/Worker/OnePoll.php
 */
namespace Friendica\Worker;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Protocol\Email;
use Friendica\Protocol\PortableContact;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\XML;
use dba;

require_once 'include/dba.php';

class OnePoll
{
	public static function execute($contact_id = 0, $command = '') {
		global $a;

		require_once 'include/items.php';

		logger('start');

		$manual_id  = 0;
		$generation = 0;
		$hub_update = false;
		$force      = false;
		$restart    = false;

		if ($command == "force") {
			$force = true;
		}

		if (!$contact_id) {
			logger('no contact');
			return;
		}

		$d = DateTimeFormat::utcNow();

		$contact = dba::selectFirst('contact', [], ['id' => $contact_id]);
		if (!DBM::is_result($contact)) {
			logger('Contact not found or cannot be used.');
			return;
		}

		$importer_uid = $contact['uid'];

		// load current friends if possible.
		if (($contact['poco'] != "") && ($contact['success_update'] > $contact['failure_update'])) {
			$r = q("SELECT count(*) AS total FROM glink
				WHERE `cid` = %d AND updated > UTC_TIMESTAMP() - INTERVAL 1 DAY",
				intval($contact['id'])
			);
			if (DBM::is_result($r)) {
				if (!$r[0]['total']) {
					PortableContact::loadWorker($contact['id'], $importer_uid, 0, $contact['poco']);
				}
			}
		}

		// Diaspora users, archived users and followers are only checked if they still exist.
		if ($contact['archive'] || ($contact["network"] == NETWORK_DIASPORA) || ($contact["rel"] == CONTACT_IS_FOLLOWER)) {
			$last_updated = PortableContact::lastUpdated($contact["url"], true);
			$updated = DateTimeFormat::utcNow();
			if ($last_updated) {
				logger('Contact '.$contact['id'].' had last update on '.$last_updated, LOGGER_DEBUG);

				// The last public item can be older than the last item we got
				if ($last_updated < $contact['last-item']) {
					$last_updated = $contact['last-item'];
				}

				$fields = ['last-item' => $last_updated, 'last-update' => $updated, 'success_update' => $updated];
				self::updateContact($contact, $fields);
				Contact::unmarkForArchival($contact);
			} else {
				self::updateContact($contact, ['last-update' => $updated, 'failure_update' => $updated]);
				Contact::markForArchival($contact);
				logger('Contact '.$contact['id'].' is marked for archival', LOGGER_DEBUG);
			}
			return;
		}

		$xml = false;

		$t = $contact['last-update'];

		if ($contact['subhub']) {
			$poll_interval = Config::get('system', 'pushpoll_frequency', 3);
			$contact['priority'] = intval($poll_interval);
			$hub_update = false;

			if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + 1 day")) {
				$hub_update = true;
			}
		} else {
			$hub_update = false;
		}

		$last_update = (($contact['last-update'] <= NULL_DATE)
			? DateTimeFormat::utc('now - 7 days', DateTimeFormat::ATOM)
			: DateTimeFormat::utc($contact['last-update'], DateTimeFormat::ATOM)
		);

		// Update the contact entry
		if (($contact['network'] === NETWORK_OSTATUS) || ($contact['network'] === NETWORK_DIASPORA) || ($contact['network'] === NETWORK_DFRN)) {
			if (!PortableContact::reachable($contact['url'])) {
				logger("Skipping probably dead contact ".$contact['url']);

				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				return;
			}

			if (!Contact::updateFromProbe($contact["id"])) {
				Contact::markForArchival($contact);
				logger('Contact is marked dead');

				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				return;
			} else {
				Contact::unmarkForArchival($contact);
			}
		}

		if ($importer_uid == 0) {
			logger('Ignore public contacts');

			// set the last-update so we don't keep polling
			dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` INNER JOIN `user` on `contact`.`uid` = `user`.`uid` WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval($importer_uid)
		);

		if (!DBM::is_result($r)) {
			logger('No self contact for user '.$importer_uid);

			// set the last-update so we don't keep polling
			dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		$importer = $r[0];
		$url = '';

		logger("poll: ({$contact['network']}-{$contact['id']}) IMPORTER: {$importer['name']}, CONTACT: {$contact['name']}");

		if ($contact['network'] === NETWORK_DFRN) {
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
				dba::update('contact', $fields, ['id' => $contact['id']]);
			}

			$url = $contact['poll'] . '?dfrn_id=' . $idtosend
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
				. '&type=data&last_update=' . $last_update
				. '&perm=' . $perm ;

			$ret = Network::curl($url);

			if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				Contact::markForArchival($contact);
				return;
			}

			$handshake_xml = $ret['body'];

			$html_code = $a->get_curl_code();

			logger('handshake with url ' . $url . ' returns xml: ' . $handshake_xml, LOGGER_DATA);


			if (!strlen($handshake_xml) || ($html_code >= 400) || !$html_code) {
				logger("$url appears to be dead - marking for death ");

				// dead connection - might be a transient event, or this might
				// mean the software was uninstalled or the domain expired.
				// Will keep trying for one month.

				Contact::markForArchival($contact);

				// set the last-update so we don't keep polling
				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				self::updateContact($contact, $fields);
				return;
			}

			if (!strstr($handshake_xml, '<')) {
				logger('response from ' . $url . ' did not contain XML.');

				Contact::markForArchival($contact);

				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				self::updateContact($contact, $fields);
				return;
			}


			$res = XML::parseString($handshake_xml);

			if (intval($res->status) == 1) {
				logger("$url replied status 1 - marking for death ");

				// we may not be friends anymore. Will keep trying for one month.
				// set the last-update so we don't keep polling
				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				self::updateContact($contact, $fields);

				Contact::markForArchival($contact);
			} elseif ($contact['term-date'] > NULL_DATE) {
				logger("$url back from the dead - removing mark for death");
				Contact::unmarkForArchival($contact);
			}

			if ((intval($res->status) != 0) || !strlen($res->challenge) || !strlen($res->dfrn_id)) {
				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				return;
			}

			if (((float)$res->dfrn_version > 2.21) && ($contact['poco'] == '')) {
				$fields = ['poco' => str_replace('/profile/', '/poco/', $contact['url'])];
				dba::update('contact', $fields, ['id' => $contact['id']]);
			}

			$postvars = [];

			$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
			$challenge    = hex2bin((string) $res->challenge);

			$final_dfrn_id = '';

			if ($contact['duplex'] && strlen($contact['prvkey'])) {
				Crypto::opensslPrivateDecrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
				Crypto::opensslPrivateDecrypt($challenge, $postvars['challenge'], $contact['prvkey']);
			} else {
				openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
				openssl_public_decrypt($challenge, $postvars['challenge'], $contact['pubkey']);
			}

			$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

			if (strpos($final_dfrn_id, ':') == 1) {
				$final_dfrn_id = substr($final_dfrn_id, 2);
			}

			if ($final_dfrn_id != $orig_id) {
				// did not decode properly - cannot trust this site
				logger('ID did not decode: ' . $contact['id'] . ' orig: ' . $orig_id . ' final: ' . $final_dfrn_id);

				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				Contact::markForArchival($contact);
				return;
			}

			$postvars['dfrn_id'] = $idtosend;
			$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
			$postvars['perm'] = 'rw';

			$xml = Network::post($contact['poll'], $postvars);

		} elseif (($contact['network'] === NETWORK_OSTATUS)
			|| ($contact['network'] === NETWORK_DIASPORA)
			|| ($contact['network'] === NETWORK_FEED)) {

			// Upgrading DB fields from an older Friendica version
			// Will only do this once per notify-enabled OStatus contact
			// or if relationship changes

			$stat_writeable = ((($contact['notify']) && ($contact['rel'] == CONTACT_IS_FOLLOWER || $contact['rel'] == CONTACT_IS_FRIEND)) ? 1 : 0);

			// Contacts from OStatus are always writable
			if ($contact['network'] === NETWORK_OSTATUS) {
				$stat_writeable = 1;
			}

			if ($stat_writeable != $contact['writable']) {
				$fields = ['writable' => $stat_writeable];
				dba::update('contact', $fields, ['id' => $contact['id']]);
			}

			// Are we allowed to import from this person?

			if ($contact['rel'] == CONTACT_IS_FOLLOWER || $contact['blocked'] || $contact['readonly']) {
				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				return;
			}

			$cookiejar = tempnam(get_temppath(), 'cookiejar-onepoll-');
			$ret = Network::curl($contact['poll'], false, $redirects, ['cookiejar' => $cookiejar]);
			unlink($cookiejar);

			if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				Contact::markForArchival($contact);
				return;
			}

			$xml = $ret['body'];

		} elseif ($contact['network'] === NETWORK_MAIL) {

			logger("Mail: Fetching for ".$contact['addr'], LOGGER_DEBUG);

			$mail_disabled = ((function_exists('imap_open') && (! Config::get('system', 'imap_disabled'))) ? 0 : 1);
			if ($mail_disabled) {
				// set the last-update so we don't keep polling
				dba::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				Contact::markForArchival($contact);
				return;
			}

			logger("Mail: Enabled", LOGGER_DEBUG);

			$mbox = null;
			$user = dba::selectFirst('user', ['prvkey'], ['uid' => $importer_uid]);

			$condition = ["`server` != '' AND `uid` = ?", $importer_uid];
			$mailconf = dba::selectFirst('mailacct', [], $condition);
			if (DBM::is_result($user) && DBM::is_result($mailconf)) {
				$mailbox = Email::constructMailboxName($mailconf);
				$password = '';
				Crypto::opensslPrivateDecrypt(hex2bin($mailconf['pass']), $password, $user['prvkey']);
				$mbox = Email::connect($mailbox, $mailconf['user'], $password);
				unset($password);
				logger("Mail: Connect to " . $mailconf['user']);
				if ($mbox) {
					$fields = ['last_check' => DateTimeFormat::utcNow()];
					dba::update('mailacct', $fields, ['id' => $mailconf['id']]);
					logger("Mail: Connected to " . $mailconf['user']);
				} else {
					logger("Mail: Connection error ".$mailconf['user']." ".print_r(imap_errors(), true));
				}
			}

			if ($mbox) {
				$msgs = Email::poll($mbox, $contact['addr']);

				if (count($msgs)) {
					logger("Mail: Parsing ".count($msgs)." mails from ".$contact['addr']." for ".$mailconf['user'], LOGGER_DEBUG);

					$metas = Email::messageMeta($mbox, implode(',', $msgs));
					if (count($metas) != count($msgs)) {
						logger("for " . $mailconf['user'] . " there are ". count($msgs) . " messages but received " . count($metas) . " metas", LOGGER_DEBUG);
					} else {
						$msgs = array_combine($msgs, $metas);

						foreach ($msgs as $msg_uid => $meta) {
							logger("Mail: Parsing mail ".$msg_uid, LOGGER_DATA);

							$datarray = [];
							$datarray['verb'] = ACTIVITY_POST;
							$datarray['object-type'] = ACTIVITY_OBJ_NOTE;
							$datarray['network'] = NETWORK_MAIL;
							// $meta = Email::messageMeta($mbox, $msg_uid);

							$datarray['uri'] = Email::msgid2iri(trim($meta->message_id, '<>'));

							// Have we seen it before?
							$fields = ['deleted', 'id'];
							$condition = ['uid' => $importer_uid, 'uri' => $datarray['uri']];
							$item = dba::selectFirst('item', $fields, $condition);
							if (DBM::is_result($item)) {
								logger("Mail: Seen before ".$msg_uid." for ".$mailconf['user']." UID: ".$importer_uid." URI: ".$datarray['uri'],LOGGER_DEBUG);

								// Only delete when mails aren't automatically moved or deleted
								if (($mailconf['action'] != 1) && ($mailconf['action'] != 3))
									if ($meta->deleted && ! $item['deleted']) {
										$fields = ['deleted' => true, 'changed' => DateTimeFormat::utcNow()];
										Item::update($fields, ['id' => $item['id']]);
									}

								switch ($mailconf['action']) {
									case 0:
										logger("Mail: Seen before ".$msg_uid." for ".$mailconf['user'].". Doing nothing.", LOGGER_DEBUG);
										break;
									case 1:
										logger("Mail: Deleting ".$msg_uid." for ".$mailconf['user']);
										imap_delete($mbox, $msg_uid, FT_UID);
										break;
									case 2:
										logger("Mail: Mark as seen ".$msg_uid." for ".$mailconf['user']);
										imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
										break;
									case 3:
										logger("Mail: Moving ".$msg_uid." to ".$mailconf['movetofolder']." for ".$mailconf['user']);
										imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
										if ($mailconf['movetofolder'] != "") {
											imap_mail_move($mbox, $msg_uid, $mailconf['movetofolder'], FT_UID);
										}
										break;
								}
								continue;
							}


							// look for a 'references' or an 'in-reply-to' header and try to match with a parent item we have locally.
							$raw_refs = ((property_exists($meta, 'references')) ? str_replace("\t", '', $meta->references) : '');
							if (!trim($raw_refs)) {
								$raw_refs = ((property_exists($meta, 'in_reply_to')) ? str_replace("\t", '', $meta->in_reply_to) : '');
							}
							$raw_refs = trim($raw_refs);  // Don't allow a blank reference in $refs_arr

							if ($raw_refs) {
								$refs_arr = explode(' ', $raw_refs);
								if (count($refs_arr)) {
									for ($x = 0; $x < count($refs_arr); $x ++) {
										$refs_arr[$x] = "'" . Email::msgid2iri(str_replace(['<', '>', ' '],['', '', ''],dbesc($refs_arr[$x]))) . "'";
									}
								}
								$qstr = implode(',', $refs_arr);
								$r = q("SELECT `parent-uri` FROM `item` USE INDEX (`uid_uri`) WHERE `uri` IN ($qstr) AND `uid` = %d LIMIT 1",
									intval($importer_uid)
								);
								if (DBM::is_result($r)) {
									$datarray['parent-uri'] = $r[0]['parent-uri'];  // Set the parent as the top-level item
								}
							}

							// Decoding the header
							$subject = imap_mime_header_decode($meta->subject);
							$datarray['title'] = "";
							foreach ($subject as $subpart) {
								if ($subpart->charset != "default") {
									$datarray['title'] .= iconv($subpart->charset, 'UTF-8//IGNORE', $subpart->text);
								} else {
									$datarray['title'] .= $subpart->text;
								}
							}
							$datarray['title'] = notags(trim($datarray['title']));

							//$datarray['title'] = notags(trim($meta->subject));
							$datarray['created'] = DateTimeFormat::utc($meta->date);

							// Is it a reply?
							$reply = ((substr(strtolower($datarray['title']), 0, 3) == "re:") ||
								(substr(strtolower($datarray['title']), 0, 3) == "re-") ||
								($raw_refs != ""));

							// Remove Reply-signs in the subject
							$datarray['title'] = self::RemoveReply($datarray['title']);

							// If it seems to be a reply but a header couldn't be found take the last message with matching subject
							if (empty($datarray['parent-uri']) && $reply) {
								$r = q("SELECT `parent-uri` FROM `item` WHERE `title` = \"%s\" AND `uid` = %d AND `network` = '%s' ORDER BY `created` DESC LIMIT 1",
									dbesc(protect_sprintf($datarray['title'])),
									intval($importer_uid),
									dbesc(NETWORK_MAIL));
								if (DBM::is_result($r)) {
									$datarray['parent-uri'] = $r[0]['parent-uri'];
								}
							}

							if (empty($datarray['parent-uri'])) {
								$datarray['parent-uri'] = $datarray['uri'];
							}

							$r = Email::getMessage($mbox, $msg_uid, $reply);
							if (!$r) {
								logger("Mail: can't fetch msg ".$msg_uid." for ".$mailconf['user']);
								continue;
							}
							$datarray['body'] = escape_tags($r['body']);
							$datarray['body'] = BBCode::limitBodySize($datarray['body']);

							logger("Mail: Importing ".$msg_uid." for ".$mailconf['user']);

							/// @TODO Adding a gravatar for the original author would be cool

							$from = imap_mime_header_decode($meta->from);
							$fromdecoded = "";
							foreach ($from as $frompart) {
								if ($frompart->charset != "default") {
									$fromdecoded .= iconv($frompart->charset, 'UTF-8//IGNORE', $frompart->text);
								} else {
									$fromdecoded .= $frompart->text;
								}
							}

							$fromarr = imap_rfc822_parse_adrlist($fromdecoded, $a->get_hostname());

							$frommail = $fromarr[0]->mailbox."@".$fromarr[0]->host;

							if (isset($fromarr[0]->personal)) {
								$fromname = $fromarr[0]->personal;
							} else {
								$fromname = $frommail;
							}

							$datarray['author-name'] = $fromname;
							$datarray['author-link'] = "mailto:".$frommail;
							$datarray['author-avatar'] = $contact['photo'];

							$datarray['owner-name'] = $contact['name'];
							$datarray['owner-link'] = "mailto:".$contact['addr'];
							$datarray['owner-avatar'] = $contact['photo'];

							$datarray['uid'] = $importer_uid;
							$datarray['contact-id'] = $contact['id'];
							if ($datarray['parent-uri'] === $datarray['uri']) {
								$datarray['private'] = 1;
							}
							if (($contact['network'] === NETWORK_MAIL) && (!PConfig::get($importer_uid, 'system', 'allow_public_email_replies'))) {
								$datarray['private'] = 1;
								$datarray['allow_cid'] = '<' . $contact['id'] . '>';
							}

							$stored_item = Item::insert($datarray);

							switch ($mailconf['action']) {
								case 0:
									logger("Mail: Seen before ".$msg_uid." for ".$mailconf['user'].". Doing nothing.", LOGGER_DEBUG);
									break;
								case 1:
									logger("Mail: Deleting ".$msg_uid." for ".$mailconf['user']);
									imap_delete($mbox, $msg_uid, FT_UID);
									break;
								case 2:
									logger("Mail: Mark as seen ".$msg_uid." for ".$mailconf['user']);
									imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
									break;
								case 3:
									logger("Mail: Moving ".$msg_uid." to ".$mailconf['movetofolder']." for ".$mailconf['user']);
									imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
									if ($mailconf['movetofolder'] != "") {
										imap_mail_move($mbox, $msg_uid, $mailconf['movetofolder'], FT_UID);
									}
									break;
							}
						}
					}
				} else {
					logger("Mail: no mails for ".$mailconf['user']);
				}

				logger("Mail: closing connection for ".$mailconf['user']);
				imap_close($mbox);
			}
		}

		if ($xml) {
			logger('received xml : ' . $xml, LOGGER_DATA);
			if (!strstr($xml, '<')) {
				logger('post_handshake: response from ' . $url . ' did not contain XML.');

				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				self::updateContact($contact, $fields);
				Contact::markForArchival($contact);
				return;
			}


			logger("Consume feed of contact ".$contact['id']);

			consume_feed($xml, $importer, $contact, $hub);

			// do it a second time for DFRN so that any children find their parents.
			if ($contact['network'] === NETWORK_DFRN) {
				consume_feed($xml, $importer, $contact, $hub);
			}

			$hubmode = 'subscribe';
			if ($contact['network'] === NETWORK_DFRN || $contact['blocked'] || $contact['readonly']) {
				$hubmode = 'unsubscribe';
			}

			if (($contact['network'] === NETWORK_OSTATUS ||  $contact['network'] == NETWORK_FEED) && (! $contact['hub-verify'])) {
				$hub_update = true;
			}

			if ($force) {
				$hub_update = true;
			}

			logger("Contact ".$contact['id']." returned hub: ".$hub." Network: ".$contact['network']." Relation: ".$contact['rel']." Update: ".$hub_update);

			if (strlen($hub) && $hub_update && (($contact['rel'] != CONTACT_IS_FOLLOWER) || $contact['network'] == NETWORK_FEED)) {
				logger('hub ' . $hubmode . ' : ' . $hub . ' contact name : ' . $contact['name'] . ' local user : ' . $importer['name']);
				$hubs = explode(',', $hub);
				if (count($hubs)) {
					foreach ($hubs as $h) {
						$h = trim($h);
						if (!strlen($h)) {
							continue;
						}
						subscribe_to_hub($h, $importer, $contact, $hubmode);
					}
				}
			}

			$updated = DateTimeFormat::utcNow();

			self::updateContact($contact, ['last-update' => $updated, 'success_update' => $updated]);
			dba::update('gcontact', ['last_contact' => $updated], ['nurl' => $contact['nurl']]);
			Contact::unmarkForArchival($contact);
		} elseif (in_array($contact["network"], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, NETWORK_FEED])) {
			$updated = DateTimeFormat::utcNow();

			self::updateContact($contact, ['last-update' => $updated, 'failure_update' => $updated]);
			dba::update('gcontact', ['last_failure' => $updated], ['nurl' => $contact['nurl']]);
			Contact::markForArchival($contact);
		} else {
			$updated = DateTimeFormat::utcNow();
			dba::update('contact', ['last-update' => $updated], ['id' => $contact['id']]);
		}

		return;
	}

	private static function RemoveReply($subject) {
		while (in_array(strtolower(substr($subject, 0, 3)), ["re:", "aw:"])) {
			$subject = trim(substr($subject, 4));
		}

		return $subject;
	}

	/**
	 * @brief Updates a personal contact entry and the public contact entry
	 *
	 * @param array $contact The personal contact entry
	 * @param array $fields The fields that are updated
	 */
	private static function updateContact($contact, $fields) {
		dba::update('contact', $fields, ['id' => $contact['id']]);
		dba::update('contact', $fields, ['uid' => 0, 'nurl' => $contact['nurl']]);
	}
}
