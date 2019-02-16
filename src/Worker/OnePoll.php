<?php
/**
 * @file src/Worker/OnePoll.php
 */
namespace Friendica\Worker;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Email;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;

class OnePoll extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		$contact_id = (isset($parameters[0]) && is_int($parameters[0])) ? $parameters[0] : 0;
		$command    =  isset($parameters[1]) ? $parameters[1] : '';

		$this->logger->info('Start for contact ' . $contact_id);

		$force      = false;

		if ($command == "force") {
			$force = true;
		}

		if (!$contact_id) {
			$this->logger->info('no contact');
			return;
		}


		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);
		if (!DBA::isResult($contact)) {
			$this->logger->info('Contact not found or cannot be used.');
			return;
		}

		$importer_uid = $contact['uid'];

		// Possibly switch the remote contact to AP
		if ($contact['network'] === Protocol::OSTATUS) {
			ActivityPub\Receiver::switchContact($contact['id'], $importer_uid, $contact['url']);
			$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);
		}

		// These three networks can be able to speak AP, so we are trying to fetch AP profile data here
		if (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DIASPORA, Protocol::DFRN])) {
			$apcontact = APContact::getByURL($contact['url'], true);

			$updated = DateTimeFormat::utcNow();
			if (($contact['network'] === Protocol::ACTIVITYPUB) && empty($apcontact)) {
				$this->updateContact($contact, ['last-update' => $updated, 'failure_update' => $updated]);
				Contact::markForArchival($contact);
				$this->logger->info('Contact archived');
				return;
			} elseif (!empty($apcontact)) {
				$fields = ['last-update' => $updated, 'success_update' => $updated];
				$this->updateContact($contact, $fields);
				Contact::unmarkForArchival($contact);
			}
		}

		// Diaspora users, archived users and followers are only checked if they still exist.
		if (($contact['network'] != Protocol::ACTIVITYPUB) && ($contact['archive'] || ($contact["network"] == Protocol::DIASPORA) || ($contact["rel"] == Contact::FOLLOWER))) {
			$last_updated = PortableContact::lastUpdated($contact["url"], true);
			$updated = DateTimeFormat::utcNow();

			if ($last_updated) {
				$this->logger->info('Contact '.$contact['id'].' had last update on '.$last_updated);

				// The last public item can be older than the last item we got
				if ($last_updated < $contact['last-item']) {
					$last_updated = $contact['last-item'];
				}

				$fields = ['last-item' => DateTimeFormat::utc($last_updated), 'last-update' => $updated, 'success_update' => $updated];
				$this->updateContact($contact, $fields);
				Contact::unmarkForArchival($contact);
			} else {
				$this->updateContact($contact, ['last-update' => $updated, 'failure_update' => $updated]);
				Contact::markForArchival($contact);
				$this->logger->info('Contact archived');
				return;
			}
		}

		// Update the contact entry
		if (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::DFRN])) {
			$updated = DateTimeFormat::utcNow();
			// Currently we can't check every AP implementation, so we don't do it at all
			if (($contact['network'] != Protocol::ACTIVITYPUB) && !PortableContact::reachable($contact['url'])) {
				$this->logger->info("Skipping probably dead contact ".$contact['url']);

				// set the last-update so we don't keep polling
				$this->updateContact($contact, ['last-update' => $updated]);
				return;
			}

			if (!Contact::updateFromProbe($contact["id"])) {
				// set the last-update so we don't keep polling
				$this->updateContact($contact, ['last-update' => $updated]);
				Contact::markForArchival($contact);
				$this->logger->info('Contact archived');
				return;
			} else {
				$fields = ['last-update' => $updated, 'success_update' => $updated];
				$this->updateContact($contact, $fields);
				Contact::unmarkForArchival($contact);
			}
		}

		// load current friends if possible.
		if (!empty($contact['poco']) && ($contact['success_update'] > $contact['failure_update'])) {
			$r = q("SELECT count(*) AS total FROM glink
				WHERE `cid` = %d AND updated > UTC_TIMESTAMP() - INTERVAL 1 DAY",
				intval($contact['id'])
			);
			if (DBA::isResult($r)) {
				if (!$r[0]['total']) {
					PortableContact::loadWorker($contact['id'], $importer_uid, 0, $contact['poco']);
				}
			}
		}

		// We don't poll our followers
		if ($contact["rel"] == Contact::FOLLOWER) {
			$this->logger->info("Don't poll follower");

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		// Don't poll if polling is deactivated (But we poll feeds and mails anyway)
		if (!in_array($contact['network'], [Protocol::FEED, Protocol::MAIL]) && Config::get('system', 'disable_polling')) {
			$this->logger->info('Polling is disabled');

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		// We don't poll AP contacts by now
		if ($contact['network'] === Protocol::ACTIVITYPUB) {
			$this->logger->info("Don't poll AP contact");

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		if ($importer_uid == 0) {
			$this->logger->info('Ignore public contacts');

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` INNER JOIN `user` on `contact`.`uid` = `user`.`uid` WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval($importer_uid)
		);

		if (!DBA::isResult($r)) {
			$this->logger->info('No self contact for user '.$importer_uid);

			// set the last-update so we don't keep polling
			DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			return;
		}

		$importer = $r[0];
		$url = '';
		$xml = false;

		if ($contact['subhub']) {
			$poll_interval = Config::get('system', 'pushpoll_frequency', 3);
			$contact['priority'] = intval($poll_interval);
			$hub_update = false;

			if (DateTimeFormat::utcNow() > DateTimeFormat::utc($contact['last-update'] . " + 1 day")) {
				$hub_update = true;
			}
		} else {
			$hub_update = false;
		}

		$last_update = (($contact['last-update'] <= DBA::NULL_DATETIME)
			? DateTimeFormat::utc('now - 7 days', DateTimeFormat::ATOM)
			: DateTimeFormat::utc($contact['last-update'], DateTimeFormat::ATOM)
		);

		$this->logger->info("poll: ({$contact['network']}-{$contact['id']}) IMPORTER: {$importer['name']}, CONTACT: {$contact['name']}");

		if ($contact['network'] === Protocol::DFRN) {
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

			$url = $contact['poll'] . '?dfrn_id=' . $idtosend
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
				. '&type=data&last_update=' . $last_update
				. '&perm=' . $perm;

			$curlResult = Network::curl($url);

			if (!$curlResult->isSuccess() && ($curlResult->getErrorNumber() == CURLE_OPERATION_TIMEDOUT)) {
				// set the last-update so we don't keep polling
				$this->updateContact($contact, ['last-update' => DateTimeFormat::utcNow()]);
				Contact::markForArchival($contact);
				$this->logger->info('Contact archived');
				return;
			}

			$handshake_xml = $curlResult->getBody();
			$html_code = $curlResult->getReturnCode();

			$this->logger->debug('handshake with url ' . $url . ' returns xml: ' . $handshake_xml);

			if (!strlen($handshake_xml) || ($html_code >= 400) || !$html_code) {
				// dead connection - might be a transient event, or this might
				// mean the software was uninstalled or the domain expired.
				// Will keep trying for one month.
				$this->logger->info("$url appears to be dead - marking for death ");

				// set the last-update so we don't keep polling
				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				$this->updateContact($contact, $fields);
				Contact::markForArchival($contact);
				return;
			}

			if (!strstr($handshake_xml, '<')) {
				$this->logger->info('response from ' . $url . ' did not contain XML.');

				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				$this->updateContact($contact, $fields);
				Contact::markForArchival($contact);
				return;
			}


			$res = XML::parseString($handshake_xml);

			if (intval($res->status) == 1) {
				// we may not be friends anymore. Will keep trying for one month.
				$this->logger->info("$url replied status 1 - marking for death ");

				// set the last-update so we don't keep polling
				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				$this->updateContact($contact, $fields);
				Contact::markForArchival($contact);
			} elseif ($contact['term-date'] > DBA::NULL_DATETIME) {
				Contact::unmarkForArchival($contact);
			}

			if ((intval($res->status) != 0) || !strlen($res->challenge) || !strlen($res->dfrn_id)) {
				// set the last-update so we don't keep polling
				DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				$this->logger->info('Contact status is ' . $res->status);
				return;
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
				$this->logger->info('Contact has got no ID - quitting');
				return;
			}

			if ($final_dfrn_id != $orig_id) {
				// did not decode properly - cannot trust this site
				$this->logger->info('ID did not decode: ' . $contact['id'] . ' orig: ' . $orig_id . ' final: ' . $final_dfrn_id);

				// set the last-update so we don't keep polling
				DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				Contact::markForArchival($contact);
				return;
			}

			$postvars['dfrn_id'] = $idtosend;
			$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
			$postvars['perm'] = 'rw';

			$xml = Network::post($contact['poll'], $postvars)->getBody();

		} elseif (($contact['network'] === Protocol::OSTATUS)
			|| ($contact['network'] === Protocol::DIASPORA)
			|| ($contact['network'] === Protocol::FEED)) {

			// Upgrading DB fields from an older Friendica version
			// Will only do this once per notify-enabled OStatus contact
			// or if relationship changes

			$stat_writeable = ((($contact['notify']) && ($contact['rel'] == Contact::FOLLOWER || $contact['rel'] == Contact::FRIEND)) ? 1 : 0);

			// Contacts from OStatus are always writable
			if ($contact['network'] === Protocol::OSTATUS) {
				$stat_writeable = 1;
			}

			if ($stat_writeable != $contact['writable']) {
				$fields = ['writable' => $stat_writeable];
				DBA::update('contact', $fields, ['id' => $contact['id']]);
			}

			// Are we allowed to import from this person?
			if ($contact['rel'] == Contact::FOLLOWER || $contact['blocked']) {
				// set the last-update so we don't keep polling
				DBA::update('contact', ['last-update' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
				$this->logger->info('Contact is blocked or only a follower');
				return;
			}

			$cookiejar = tempnam(get_temppath(), 'cookiejar-onepoll-');
			$curlResult = Network::curl($contact['poll'], false, $redirects, ['cookiejar' => $cookiejar]);
			unlink($cookiejar);

			if ($curlResult->isTimeout()) {
				// set the last-update so we don't keep polling
				$this->updateContact($contact, ['last-update' => DateTimeFormat::utcNow()]);
				Contact::markForArchival($contact);
				$this->logger->info('Contact archived');
				return;
			}

			$xml = $curlResult->getBody();

		} elseif ($contact['network'] === Protocol::MAIL) {
			$this->logger->info("Mail: Fetching for ".$contact['addr']);

			$mail_disabled = ((function_exists('imap_open') && !Config::get('system', 'imap_disabled')) ? 0 : 1);
			if ($mail_disabled) {
				// set the last-update so we don't keep polling
				$this->updateContact($contact, ['last-update' => DateTimeFormat::utcNow()]);
				Contact::markForArchival($contact);
				$this->logger->info('Contact archived');
				return;
			}

			$this->logger->info("Mail: Enabled");

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
				$this->logger->info("Mail: Connect to " . $mailconf['user']);
				if ($mbox) {
					$fields = ['last_check' => DateTimeFormat::utcNow()];
					DBA::update('mailacct', $fields, ['id' => $mailconf['id']]);
					$this->logger->info("Mail: Connected to " . $mailconf['user']);
				} else {
					$this->logger->info("Mail: Connection error ".$mailconf['user']." ".print_r(imap_errors(), true));
				}
			}

			if ($mbox) {
				$msgs = Email::poll($mbox, $contact['addr']);

				if (count($msgs)) {
					$this->logger->info("Mail: Parsing ".count($msgs)." mails from ".$contact['addr']." for ".$mailconf['user']);

					$metas = Email::messageMeta($mbox, implode(',', $msgs));

					if (count($metas) != count($msgs)) {
						$this->logger->info("for " . $mailconf['user'] . " there are ". count($msgs) . " messages but received " . count($metas) . " metas");
					} else {
						$msgs = array_combine($msgs, $metas);

						foreach ($msgs as $msg_uid => $meta) {
							$this->logger->debug("Mail: Parsing mail ".$msg_uid);

							$datarray = [];
							$datarray['verb'] = ACTIVITY_POST;
							$datarray['object-type'] = ACTIVITY_OBJ_NOTE;
							$datarray['network'] = Protocol::MAIL;
							// $meta = Email::messageMeta($mbox, $msg_uid);

							$datarray['uri'] = Email::msgid2iri(trim($meta->message_id, '<>'));

							// Have we seen it before?
							$fields = ['deleted', 'id'];
							$condition = ['uid' => $importer_uid, 'uri' => $datarray['uri']];
							$item = Item::selectFirst($fields, $condition);
							if (DBA::isResult($item)) {
								$this->logger->info("Mail: Seen before ".$msg_uid." for ".$mailconf['user']." UID: ".$importer_uid." URI: ".$datarray['uri']);

								// Only delete when mails aren't automatically moved or deleted
								if (($mailconf['action'] != 1) && ($mailconf['action'] != 3))
									if ($meta->deleted && ! $item['deleted']) {
										$fields = ['deleted' => true, 'changed' => DateTimeFormat::utcNow()];
										Item::update($fields, ['id' => $item['id']]);
									}

								switch ($mailconf['action']) {
									case 0:
										$this->logger->info("Mail: Seen before ".$msg_uid." for ".$mailconf['user'].". Doing nothing.");
										break;
									case 1:
										$this->logger->info("Mail: Deleting ".$msg_uid." for ".$mailconf['user']);
										imap_delete($mbox, $msg_uid, FT_UID);
										break;
									case 2:
										$this->logger->info("Mail: Mark as seen ".$msg_uid." for ".$mailconf['user']);
										imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
										break;
									case 3:
										$this->logger->info("Mail: Moving ".$msg_uid." to ".$mailconf['movetofolder']." for ".$mailconf['user']);
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
							$subject = imap_mime_header_decode($meta->subject);
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
							$datarray['title'] = $this->removeReply($datarray['title']);

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

							$r = Email::getMessage($mbox, $msg_uid, $reply);
							if (!$r) {
								$this->logger->info("Mail: can't fetch msg ".$msg_uid." for ".$mailconf['user']);
								continue;
							}
							$datarray['body'] = Strings::escapeHtml($r['body']);
							$datarray['body'] = BBCode::limitBodySize($datarray['body']);

							$this->logger->info("Mail: Importing ".$msg_uid." for ".$mailconf['user']);

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

							$fromarr = imap_rfc822_parse_adrlist($fromdecoded, $a->getHostName());

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
							if (($contact['network'] === Protocol::MAIL) && !PConfig::get($importer_uid, 'system', 'allow_public_email_replies')) {
								$datarray['private'] = 1;
								$datarray['allow_cid'] = '<' . $contact['id'] . '>';
							}

							Item::insert($datarray);

							switch ($mailconf['action']) {
								case 0:
									$this->logger->info("Mail: Seen before ".$msg_uid." for ".$mailconf['user'].". Doing nothing.");
									break;
								case 1:
									$this->logger->info("Mail: Deleting ".$msg_uid." for ".$mailconf['user']);
									imap_delete($mbox, $msg_uid, FT_UID);
									break;
								case 2:
									$this->logger->info("Mail: Mark as seen ".$msg_uid." for ".$mailconf['user']);
									imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
									break;
								case 3:
									$this->logger->info("Mail: Moving ".$msg_uid." to ".$mailconf['movetofolder']." for ".$mailconf['user']);
									imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
									if ($mailconf['movetofolder'] != "") {
										imap_mail_move($mbox, $msg_uid, $mailconf['movetofolder'], FT_UID);
									}
									break;
							}
						}
					}
				} else {
					$this->logger->info("Mail: no mails for ".$mailconf['user']);
				}

				$this->logger->info("Mail: closing connection for ".$mailconf['user']);
				imap_close($mbox);
			}
		}

		if ($xml) {
			$this->logger->debug('received xml : ' . $xml);
			if (!strstr($xml, '<')) {
				$this->logger->info('post_handshake: response from ' . $url . ' did not contain XML.');

				$fields = ['last-update' => DateTimeFormat::utcNow(), 'failure_update' => DateTimeFormat::utcNow()];
				$this->updateContact($contact, $fields);
				Contact::markForArchival($contact);
				return;
			}


			$this->logger->info("Consume feed of contact ".$contact['id']);

			consume_feed($xml, $importer, $contact, $hub);

			// do it a second time for DFRN so that any children find their parents.
			if ($contact['network'] === Protocol::DFRN) {
				consume_feed($xml, $importer, $contact, $hub);
			}

			$hubmode = 'subscribe';
			if ($contact['network'] === Protocol::DFRN || $contact['blocked']) {
				$hubmode = 'unsubscribe';
			}

			if (($contact['network'] === Protocol::OSTATUS ||  $contact['network'] == Protocol::FEED) && (! $contact['hub-verify'])) {
				$hub_update = true;
			}

			if ($force) {
				$hub_update = true;
			}

			$this->logger->info("Contact ".$contact['id']." returned hub: ".$hub." Network: ".$contact['network']." Relation: ".$contact['rel']." Update: ".$hub_update);

			if (strlen($hub) && $hub_update && (($contact['rel'] != Contact::FOLLOWER) || $contact['network'] == Protocol::FEED)) {
				$this->logger->info('hub ' . $hubmode . ' : ' . $hub . ' contact name : ' . $contact['name'] . ' local user : ' . $importer['name']);
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

			$this->updateContact($contact, ['last-update' => $updated, 'success_update' => $updated]);
			DBA::update('gcontact', ['last_contact' => $updated], ['nurl' => $contact['nurl']]);
			Contact::unmarkForArchival($contact);
		} elseif (in_array($contact["network"], [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED])) {
			$updated = DateTimeFormat::utcNow();

			$this->updateContact($contact, ['last-update' => $updated, 'failure_update' => $updated]);
			DBA::update('gcontact', ['last_failure' => $updated], ['nurl' => $contact['nurl']]);
			Contact::markForArchival($contact);
		} else {
			$this->updateContact($contact, ['last-update' => DateTimeFormat::utcNow()]);
		}

		$this->logger->info('End');
		return;
	}

	private function removeReply($subject)
	{
		while (in_array(strtolower(substr($subject, 0, 3)), ["re:", "aw:"])) {
			$subject = trim(substr($subject, 4));
		}

		return $subject;
	}

	/**
	 * @brief Updates a personal contact entry and the public contact entry
	 *
	 * @param array $contact The personal contact entry
	 * @param array $fields  The fields that are updated
	 * @throws \Exception
	 */
	private function updateContact(array $contact, array $fields)
	{
		DBA::update('contact', $fields, ['id' => $contact['id']]);
		DBA::update('contact', $fields, ['uid' => 0, 'nurl' => $contact['nurl']]);
	}
}
