<?php

use \Friendica\Core\Config;

require_once('include/follow.php');

function RemoveReply($subject) {
	while (in_array(strtolower(substr($subject, 0, 3)), array("re:", "aw:")))
		$subject = trim(substr($subject, 4));

	return($subject);
}

function onepoll_run(&$argv, &$argc){
	global $a;

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/Contact.php');
	require_once('include/email.php');
	require_once('include/socgraph.php');
	require_once('include/queue_fn.php');

	logger('onepoll: start');

	$manual_id  = 0;
	$generation = 0;
	$hub_update = false;
	$force      = false;
	$restart    = false;

	if (($argc > 1) && (intval($argv[1]))) {
		$contact_id = intval($argv[1]);
	}

	if (($argc > 2) && ($argv[2] == "force")) {
		$force = true;
	}

	if (! $contact_id) {
		logger('onepoll: no contact');
		return;
	}

	$d = datetime_convert();

	// Only poll from those with suitable relationships,
	// and which have a polling address and ignore Diaspora since
	// we are unable to match those posts with a Diaspora GUID and prevent duplicates.

	$contacts = q("SELECT `contact`.* FROM `contact`
		WHERE ( `rel` = %d OR `rel` = %d ) AND `poll` != ''
		AND NOT `network` IN ( '%s', '%s' )
		AND `contact`.`id` = %d
		AND `self` = 0 AND `contact`.`blocked` = 0 AND `contact`.`readonly` = 0
		AND `contact`.`archive` = 0 LIMIT 1",
		intval(CONTACT_IS_SHARING),
		intval(CONTACT_IS_FRIEND),
		dbesc(NETWORK_FACEBOOK),
		dbesc(NETWORK_PUMPIO),
		intval($contact_id)
	);

	if (! count($contacts)) {
		return;
	}

	$contact = $contacts[0];

	// load current friends if possible.
	if (($contact['poco'] != "") AND ($contact['success_update'] > $contact['failure_update'])) {
		$r = q("SELECT count(*) as total from glink
			where `cid` = %d and updated > UTC_TIMESTAMP() - INTERVAL 1 DAY",
			intval($contact['id'])
		);
		if (dbm::is_result($r)) {
			if (!$r[0]['total']) {
				poco_load($contact['id'],$importer_uid,0,$contact['poco']);
			}
		}
	}

	/// @TODO Check why we don't poll the Diaspora feed at the moment (some guid problem in the items?)
	/// @TODO Check whether this is possible with Redmatrix
	if ($contact["network"] == NETWORK_DIASPORA) {
		if (poco_do_update($contact["created"], $contact["last-item"], $contact["failure_update"], $contact["success_update"])) {
			$last_updated = poco_last_updated($contact["url"]);
			$updated = datetime_convert();
			if ($last_updated) {
				q("UPDATE `contact` SET `last-item` = '%s', `last-update` = '%s', `success_update` = '%s' WHERE `id` = %d",
					dbesc($last_updated),
					dbesc($updated),
					dbesc($updated),
					intval($contact['id'])
				);
			} else {
				q("UPDATE `contact` SET `last-update` = '%s', `failure_update` = '%s' WHERE `id` = %d",
					dbesc($updated),
					dbesc($updated),
					intval($contact['id'])
				);
			}
		}
		return;
	}

	$xml = false;

	$t = $contact['last-update'];

	if ($contact['subhub']) {
		$poll_interval = get_config('system','pushpoll_frequency');
		$contact['priority'] = (($poll_interval !== false) ? intval($poll_interval) : 3);
		$hub_update = false;

		if (datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day")) {
			$hub_update = true;
		}
	} else {
		$hub_update = false;
	}

	$importer_uid = $contact['uid'];

	$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` INNER JOIN `user` on `contact`.`uid` = `user`.`uid` WHERE `user`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($importer_uid)
	);
	if (!dbm::is_result($r)) {
		return;
	}

	$importer = $r[0];

	logger("onepoll: poll: ({$contact['id']}) IMPORTER: {$importer['name']}, CONTACT: {$contact['name']}");

	$last_update = (($contact['last-update'] <= NULL_DATE)
		? datetime_convert('UTC','UTC','now - 7 days', ATOM_TIME)
		: datetime_convert('UTC','UTC',$contact['last-update'], ATOM_TIME)
	);

	// Update the contact entry
	if (($contact['network'] === NETWORK_OSTATUS) || ($contact['network'] === NETWORK_DIASPORA) || ($contact['network'] === NETWORK_DFRN)) {
		if (!poco_reachable($contact['url'])) {
			logger("Skipping probably dead contact ".$contact['url']);
			return;
		}

		if (!update_contact($contact["id"])) {
			mark_for_death($contact);
			return;
		} else {
			unmark_for_death($contact);
		}
	}

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

		if (! intval($contact['writable'])) {
			q("UPDATE `contact` SET `writable` = 1 WHERE `id` = %d", intval($contact['id']));
		}

		$url = $contact['poll'] . '?dfrn_id=' . $idtosend
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
			. '&type=data&last_update=' . $last_update
			. '&perm=' . $perm ;

		$ret = z_fetch_url($url);

		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return;
		}

		$handshake_xml = $ret['body'];

		$html_code = $a->get_curl_code();

		logger('onepoll: handshake with url ' . $url . ' returns xml: ' . $handshake_xml, LOGGER_DATA);


		if ((! strlen($handshake_xml)) || ($html_code >= 400) || (! $html_code)) {
			logger("poller: $url appears to be dead - marking for death ");

			// dead connection - might be a transient event, or this might
			// mean the software was uninstalled or the domain expired.
			// Will keep trying for one month.

			mark_for_death($contact);

			// set the last-update so we don't keep polling
			$r = q("UPDATE `contact` SET `last-update` = '%s', `failure_update` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($contact['id'])
			);

			return;
		}

		if (! strstr($handshake_xml,'<')) {
			logger('poller: response from ' . $url . ' did not contain XML.');

			mark_for_death($contact);

			$r = q("UPDATE `contact` SET `last-update` = '%s', `failure_update` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			return;
		}


		$res = parse_xml_string($handshake_xml);

		if (intval($res->status) == 1) {
			logger("poller: $url replied status 1 - marking for death ");

			// we may not be friends anymore. Will keep trying for one month.
			// set the last-update so we don't keep polling


			$r = q("UPDATE `contact` SET `last-update` = '%s', `failure_update` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			mark_for_death($contact);
		} elseif ($contact['term-date'] > NULL_DATE) {
			logger("poller: $url back from the dead - removing mark for death");
			unmark_for_death($contact);
		}

		if ((intval($res->status) != 0) || (! strlen($res->challenge)) || (! strlen($res->dfrn_id))) {
			return;
		}

		if (((float) $res->dfrn_version > 2.21) && ($contact['poco'] == '')) {
			q("update contact set poco = '%s' where id = %d",
				dbesc(str_replace('/profile/','/poco/', $contact['url'])),
				intval($contact['id'])
			);
		}

		$postvars = array();

		$sent_dfrn_id = hex2bin((string) $res->dfrn_id);
		$challenge    = hex2bin((string) $res->challenge);

		$final_dfrn_id = '';

		if (($contact['duplex']) && strlen($contact['prvkey'])) {
			openssl_private_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['prvkey']);
			openssl_private_decrypt($challenge,$postvars['challenge'],$contact['prvkey']);
		} else {
			openssl_public_decrypt($sent_dfrn_id,$final_dfrn_id,$contact['pubkey']);
			openssl_public_decrypt($challenge,$postvars['challenge'],$contact['pubkey']);
		}

		$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

		if (strpos($final_dfrn_id,':') == 1) {
			$final_dfrn_id = substr($final_dfrn_id,2);
		}

		if ($final_dfrn_id != $orig_id) {
			logger('poller: ID did not decode: ' . $contact['id'] . ' orig: ' . $orig_id . ' final: ' . $final_dfrn_id);
			// did not decode properly - cannot trust this site
			return;
		}

		$postvars['dfrn_id'] = $idtosend;
		$postvars['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		$postvars['perm'] = 'rw';

		$xml = post_url($contact['poll'],$postvars);

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
			q("UPDATE `contact` SET `writable` = %d WHERE `id` = %d",
				intval($stat_writeable),
				intval($contact['id'])
			);
		}

		// Are we allowed to import from this person?

		if ($contact['rel'] == CONTACT_IS_FOLLOWER || $contact['blocked'] || $contact['readonly']) {
			return;
		}

		$cookiejar = tempnam(get_temppath(), 'cookiejar-onepoll-');
		$ret = z_fetch_url($contact['poll'], false, $redirects, array('cookiejar' => $cookiejar));

		if ($ret['errno'] == CURLE_OPERATION_TIMEDOUT) {
			return;
		}

		$xml = $ret['body'];

		unlink($cookiejar);
	} elseif ($contact['network'] === NETWORK_MAIL || $contact['network'] === NETWORK_MAIL2) {

		logger("Mail: Fetching", LOGGER_DEBUG);

		$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);
		if ($mail_disabled)
			return;

		logger("Mail: Enabled", LOGGER_DEBUG);

		$mbox = null;
		$x = q("SELECT `prvkey` FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($importer_uid)
		);
		$mailconf = q("SELECT * FROM `mailacct` WHERE `server` != '' AND `uid` = %d LIMIT 1",
			intval($importer_uid)
		);
		if (count($x) && count($mailconf)) {
		    $mailbox = construct_mailbox_name($mailconf[0]);
			$password = '';
			openssl_private_decrypt(hex2bin($mailconf[0]['pass']),$password,$x[0]['prvkey']);
			$mbox = email_connect($mailbox,$mailconf[0]['user'],$password);
			unset($password);
			logger("Mail: Connect to " . $mailconf[0]['user']);
			if ($mbox) {
				q("UPDATE `mailacct` SET `last_check` = '%s' WHERE `id` = %d AND `uid` = %d",
					dbesc(datetime_convert()),
					intval($mailconf[0]['id']),
					intval($importer_uid)
				);
				logger("Mail: Connected to " . $mailconf[0]['user']);
			} else {
				logger("Mail: Connection error ".$mailconf[0]['user']." ".print_r(imap_errors(), true));
			}
		}
		if ($mbox) {

			$msgs = email_poll($mbox,$contact['addr']);

			if (count($msgs)) {
				logger("Mail: Parsing ".count($msgs)." mails for ".$mailconf[0]['user'], LOGGER_DEBUG);

				$metas = email_msg_meta($mbox,implode(',',$msgs));
				if (count($metas) != count($msgs)) {
					logger("onepoll: for " . $mailconf[0]['user'] . " there are ". count($msgs) . " messages but received " . count($metas) . " metas", LOGGER_DEBUG);
				} else {
					$msgs = array_combine($msgs, $metas);

					foreach ($msgs as $msg_uid => $meta) {
						logger("Mail: Parsing mail ".$msg_uid, LOGGER_DATA);

						$datarray = array();
						$datarray['verb'] = ACTIVITY_POST;
						$datarray['object-type'] = ACTIVITY_OBJ_NOTE;
	//					$meta = email_msg_meta($mbox,$msg_uid);
	//					$headers = email_msg_headers($mbox,$msg_uid);

						$datarray['uri'] = msgid2iri(trim($meta->message_id,'<>'));

						// Have we seen it before?
						$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `uri` = '%s' LIMIT 1",
							intval($importer_uid),
							dbesc($datarray['uri'])
						);

						if (dbm::is_result($r)) {
							logger("Mail: Seen before ".$msg_uid." for ".$mailconf[0]['user']." UID: ".$importer_uid." URI: ".$datarray['uri'],LOGGER_DEBUG);

							// Only delete when mails aren't automatically moved or deleted
							if (($mailconf[0]['action'] != 1) AND ($mailconf[0]['action'] != 3))
								if ($meta->deleted && ! $r[0]['deleted']) {
									q("UPDATE `item` SET `deleted` = 1, `changed` = '%s' WHERE `id` = %d",
										dbesc(datetime_convert()),
										intval($r[0]['id'])
									);
								}

							switch ($mailconf[0]['action']) {
								case 0:
									logger("Mail: Seen before ".$msg_uid." for ".$mailconf[0]['user'].". Doing nothing.", LOGGER_DEBUG);
									break;
								case 1:
									logger("Mail: Deleting ".$msg_uid." for ".$mailconf[0]['user']);
									imap_delete($mbox, $msg_uid, FT_UID);
									break;
								case 2:
									logger("Mail: Mark as seen ".$msg_uid." for ".$mailconf[0]['user']);
									imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
									break;
								case 3:
									logger("Mail: Moving ".$msg_uid." to ".$mailconf[0]['movetofolder']." for ".$mailconf[0]['user']);
									imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
									if ($mailconf[0]['movetofolder'] != "")
										imap_mail_move($mbox, $msg_uid, $mailconf[0]['movetofolder'], FT_UID);
									break;
							}
							continue;
						}


						// look for a 'references' or an 'in-reply-to' header and try to match with a parent item we have locally.

	//					$raw_refs = ((x($headers,'references')) ? str_replace("\t",'',$headers['references']) : '');
						$raw_refs = ((property_exists($meta,'references')) ? str_replace("\t",'',$meta->references) : '');
						if (! trim($raw_refs))
							$raw_refs = ((property_exists($meta,'in_reply_to')) ? str_replace("\t",'',$meta->in_reply_to) : '');
						$raw_refs = trim($raw_refs);  // Don't allow a blank reference in $refs_arr

						if ($raw_refs) {
							$refs_arr = explode(' ', $raw_refs);
							if (count($refs_arr)) {
								for ($x = 0; $x < count($refs_arr); $x ++) {
									$refs_arr[$x] = "'" . msgid2iri(str_replace(array('<','>',' '),array('','',''),dbesc($refs_arr[$x]))) . "'";
								}
							}
							$qstr = implode(',',$refs_arr);
							$r = q("SELECT `uri` , `parent-uri` FROM `item` USE INDEX (`uid_uri`) WHERE `uri` IN ($qstr) AND `uid` = %d LIMIT 1",
								intval($importer_uid)
							);
							if (dbm::is_result($r)) {
								$datarray['parent-uri'] = $r[0]['parent-uri'];  // Set the parent as the top-level item
								//$datarray['parent-uri'] = $r[0]['uri'];
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
						$datarray['created'] = datetime_convert('UTC','UTC',$meta->date);

						// Is it a reply?
						$reply = ((substr(strtolower($datarray['title']), 0, 3) == "re:") or
							(substr(strtolower($datarray['title']), 0, 3) == "re-") or
							($raw_refs != ""));

						// Remove Reply-signs in the subject
						$datarray['title'] = RemoveReply($datarray['title']);

						// If it seems to be a reply but a header couldn't be found take the last message with matching subject
						if (!x($datarray,'parent-uri') and $reply) {
							$r = q("SELECT `uri` , `parent-uri` FROM `item` WHERE `title` = \"%s\" AND `uid` = %d AND `network` = '%s' ORDER BY `created` DESC LIMIT 1",
								dbesc(protect_sprintf($datarray['title'])),
								intval($importer_uid),
								dbesc(NETWORK_MAIL));
							if (dbm::is_result($r))
								$datarray['parent-uri'] = $r[0]['parent-uri'];
						}

						if (! x($datarray,'parent-uri'))
							$datarray['parent-uri'] = $datarray['uri'];


						$r = email_get_msg($mbox,$msg_uid, $reply);
						if (! $r) {
							logger("Mail: can't fetch msg ".$msg_uid." for ".$mailconf[0]['user']);
							continue;
						}
						$datarray['body'] = escape_tags($r['body']);
						$datarray['body'] = limit_body_size($datarray['body']);

						logger("Mail: Importing ".$msg_uid." for ".$mailconf[0]['user']);

						// some mailing lists have the original author as 'from' - add this sender info to msg body.
						/// @TODO Adding a gravatar for the original author would be cool

						if (! stristr($meta->from,$contact['addr'])) {
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

							//$datarray['body'] = "[b]".t('From: ') . escape_tags($fromdecoded) . "[/b]\n\n" . $datarray['body'];

							$datarray['author-name'] = $fromname;
							$datarray['author-link'] = "mailto:".$frommail;
							$datarray['author-avatar'] = $contact['photo'];

							$datarray['owner-name'] = $contact['name'];
							$datarray['owner-link'] = "mailto:".$contact['addr'];
							$datarray['owner-avatar'] = $contact['photo'];

						} else {
							$datarray['author-name'] = $contact['name'];
							$datarray['author-link'] = 'mailbox';
							$datarray['author-avatar'] = $contact['photo'];
						}

						$datarray['uid'] = $importer_uid;
						$datarray['contact-id'] = $contact['id'];
						if ($datarray['parent-uri'] === $datarray['uri'])
							$datarray['private'] = 1;
						if (($contact['network'] === NETWORK_MAIL) && (! get_pconfig($importer_uid,'system','allow_public_email_replies'))) {
							$datarray['private'] = 1;
							$datarray['allow_cid'] = '<' . $contact['id'] . '>';
						}

						$stored_item = item_store($datarray);
						q("UPDATE `item` SET `last-child` = 0 WHERE `parent-uri` = '%s' AND `uid` = %d",
							dbesc($datarray['parent-uri']),
							intval($importer_uid)
						);
						q("UPDATE `item` SET `last-child` = 1 WHERE `id` = %d",
							intval($stored_item)
						);
						switch ($mailconf[0]['action']) {
							case 0:
								logger("Mail: Seen before ".$msg_uid." for ".$mailconf[0]['user'].". Doing nothing.", LOGGER_DEBUG);
								break;
							case 1:
								logger("Mail: Deleting ".$msg_uid." for ".$mailconf[0]['user']);
								imap_delete($mbox, $msg_uid, FT_UID);
								break;
							case 2:
								logger("Mail: Mark as seen ".$msg_uid." for ".$mailconf[0]['user']);
								imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
								break;
							case 3:
								logger("Mail: Moving ".$msg_uid." to ".$mailconf[0]['movetofolder']." for ".$mailconf[0]['user']);
								imap_setflag_full($mbox, $msg_uid, "\\Seen", ST_UID);
								if ($mailconf[0]['movetofolder'] != "")
									imap_mail_move($mbox, $msg_uid, $mailconf[0]['movetofolder'], FT_UID);
								break;
						}
					}
				}
			} else {
				logger("Mail: no mails for ".$mailconf[0]['user']);
			}

			logger("Mail: closing connection for ".$mailconf[0]['user']);
			imap_close($mbox);
		}
	} elseif ($contact['network'] === NETWORK_FACEBOOK) {
		// This is picked up by the Facebook plugin on a cron hook.
		// Ignored here.
	} elseif ($contact['network'] === NETWORK_PUMPIO) {
		// This is picked up by the pump.io plugin on a cron hook.
		// Ignored here.
	}

	if ($xml) {
		logger('poller: received xml : ' . $xml, LOGGER_DATA);
		if (! strstr($xml,'<')) {
			logger('poller: post_handshake: response from ' . $url . ' did not contain XML.');
			$r = q("UPDATE `contact` SET `last-update` = '%s',  `failure_update` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($contact['id'])
			);
			return;
		}


		logger("Consume feed of contact ".$contact['id']);

		consume_feed($xml,$importer,$contact,$hub,1,1);

		// do it twice. Ensures that children of parents which may be later in the stream aren't tossed

		consume_feed($xml,$importer,$contact,$hub,1,2);

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

		if ((strlen($hub)) && ($hub_update) && (($contact['rel'] != CONTACT_IS_FOLLOWER) || $contact['network'] == NETWORK_FEED) ) {
			logger('poller: hub ' . $hubmode . ' : ' . $hub . ' contact name : ' . $contact['name'] . ' local user : ' . $importer['name']);
			$hubs = explode(',', $hub);
			if (count($hubs)) {
				foreach ($hubs as $h) {
					$h = trim($h);
					if (! strlen($h)) {
						continue;
					}
					subscribe_to_hub($h,$importer,$contact,$hubmode);
				}
			}
		}

		$updated = datetime_convert();

		$r = q("UPDATE `contact` SET `last-update` = '%s', `success_update` = '%s' WHERE `id` = %d",
			dbesc($updated),
			dbesc($updated),
			intval($contact['id'])
		);

		q("UPDATE `gcontact` SET `last_contact` = '%s' WHERE `nurl` = '%s'",
			dbesc($updated),
			dbesc($contact['nurl'])
		);

	} elseif (in_array($contact["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, NETWORK_FEED))) {
		$updated = datetime_convert();

		$r = q("UPDATE `contact` SET `last-update` = '%s', `failure_update` = '%s' WHERE `id` = %d",
			dbesc($updated),
			dbesc($updated),
			intval($contact['id'])
		);

		q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
			dbesc($updated),
			dbesc($contact['nurl'])
		);
	} else {
		$r = q("UPDATE `contact` SET `last-update` = '%s' WHERE `id` = %d",
			dbesc($updated),
			intval($contact['id'])
		);
	}

	return;
}
