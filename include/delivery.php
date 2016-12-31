<?php
require_once("boot.php");
require_once('include/queue_fn.php');
require_once('include/html2plain.php');
require_once("include/Scrape.php");
require_once('include/diaspora.php');
require_once("include/ostatus.php");
require_once("include/dfrn.php");

function delivery_run(&$argv, &$argc){
	global $a, $db;

	if (is_null($a)){
		$a = new App;
	}

	if (is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	}

	require_once("include/session.php");
	require_once("include/datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');
	require_once('include/email.php');

	load_config('config');
	load_config('system');

	load_hooks();

	if ($argc < 3)
		return;

	$a->set_baseurl(get_config('system','url'));

	logger('delivery: invoked: '. print_r($argv,true), LOGGER_DEBUG);

	$cmd        = $argv[1];
	$item_id    = intval($argv[2]);

	for($x = 3; $x < $argc; $x ++) {

		$contact_id = intval($argv[$x]);

		// Some other process may have delivered this item already.

		$r = q("SELECT * FROM `deliverq` WHERE `cmd` = '%s' AND `item` = %d AND `contact` = %d LIMIT 1",
			dbesc($cmd),
			dbesc($item_id),
			dbesc($contact_id)
		);
		if (!dbm::is_result($r)) {
			continue;
		}

		if ($a->maxload_reached())
			return;

		// It's ours to deliver. Remove it from the queue.

		q("DELETE FROM `deliverq` WHERE `cmd` = '%s' AND `item` = %d AND `contact` = %d",
			dbesc($cmd),
			dbesc($item_id),
			dbesc($contact_id)
		);

		if (!$item_id || !$contact_id)
			continue;

		$expire = false;
		$mail = false;
		$fsuggest = false;
		$relocate = false;
		$top_level = false;
		$recipients = array();
		$url_recipients = array();
		$followup = false;

		$normal_mode = true;

		$recipients[] = $contact_id;

		if ($cmd === 'mail') {
			$normal_mode = false;
			$mail = true;
			$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
					intval($item_id)
			);
			if (!count($message)){
				return;
			}
			$uid = $message[0]['uid'];
			$recipients[] = $message[0]['contact-id'];
			$item = $message[0];
		}
		elseif ($cmd === 'expire') {
			$normal_mode = false;
			$expire = true;
			$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1
				AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP() - INTERVAL 30 MINUTE",
				intval($item_id)
			);
			$uid = $item_id;
			$item_id = 0;
			if (!count($items))
				continue;
		}
		elseif ($cmd === 'suggest') {
			$normal_mode = false;
			$fsuggest = true;

			$suggest = q("SELECT * FROM `fsuggest` WHERE `id` = %d LIMIT 1",
				intval($item_id)
			);
			if (!count($suggest))
				return;
			$uid = $suggest[0]['uid'];
			$recipients[] = $suggest[0]['cid'];
			$item = $suggest[0];
		} elseif ($cmd === 'relocate') {
			$normal_mode = false;
			$relocate = true;
			$uid = $item_id;
		} else {
			// find ancestors
			$r = q("SELECT * FROM `item` WHERE `id` = %d and visible = 1 and moderated = 0 LIMIT 1",
				intval($item_id)
			);

			if ((!dbm::is_result($r)) || (!intval($r[0]['parent']))) {
				continue;
			}

			$target_item = $r[0];
			$parent_id = intval($r[0]['parent']);
			$uid = $r[0]['uid'];
			$updated = $r[0]['edited'];

			$items = q("SELECT `item`.*, `sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
				FROM `item` LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id` WHERE `parent` = %d and visible = 1 and moderated = 0 ORDER BY `id` ASC",
				intval($parent_id)
			);

			if (!count($items)) {
				continue;
			}

			$icontacts = null;
			$contacts_arr = array();
			foreach($items as $item)
				if (!in_array($item['contact-id'],$contacts_arr))
					$contacts_arr[] = intval($item['contact-id']);
			if (count($contacts_arr)) {
				$str_contacts = implode(',',$contacts_arr);
				$icontacts = q("SELECT * FROM `contact`
					WHERE `id` IN ( $str_contacts ) "
				);
			}
			if ( !($icontacts && count($icontacts)))
				continue;

			// avoid race condition with deleting entries

			if ($items[0]['deleted']) {
				foreach($items as $item)
					$item['deleted'] = 1;
			}

			if ((count($items) == 1) && ($items[0]['uri'] === $items[0]['parent-uri'])) {
				logger('delivery: top level post');
				$top_level = true;
			}
		}

		$r = q("SELECT `contact`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`,
			`user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`,
			`user`.`page-flags`, `user`.`account-type`, `user`.`prvnets`
			FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `contact`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
			intval($uid)
		);

		if (!dbm::is_result($r))
			continue;

		$owner = $r[0];

		$walltowall = ((($top_level) && ($owner['id'] != $items[0]['contact-id'])) ? true : false);

		$public_message = true;

		if (!($mail || $fsuggest || $relocate)) {
			require_once('include/group.php');

			$parent = $items[0];

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			// expire sends an entire group of expire messages and cannot be forwarded.
			// However the conversation owner will be a part of the conversation and will
			// be notified during this run.
			// Other DFRN conversation members will be alerted during polled updates.

			// Diaspora members currently are not notified of expirations, and other networks have
			// either limited or no ability to process deletions. We should at least fix Diaspora
			// by stringing togther an array of retractions and sending them onward.


			$localhost = $a->get_hostname();
			if (strpos($localhost,':'))
				$localhost = substr($localhost,0,strpos($localhost,':'));

			/**
			 *
			 * Be VERY CAREFUL if you make any changes to the following line. Seemingly innocuous changes
			 * have been known to cause runaway conditions which affected several servers, along with
			 * permissions issues.
			 *
			 */

			$relay_to_owner = false;

			if (!$top_level && ($parent['wall'] == 0) && !$expire && stristr($target_item['uri'],$localhost)) {
				$relay_to_owner = true;
			}

			if ($relay_to_owner) {
				logger('followup '.$target_item["guid"], LOGGER_DEBUG);
				// local followup to remote post
				$followup = true;
			}

			if ((strlen($parent['allow_cid']))
				|| (strlen($parent['allow_gid']))
				|| (strlen($parent['deny_cid']))
				|| (strlen($parent['deny_gid']))
				|| $parent["private"]) {
				$public_message = false; // private recipients, not public
			}

		}

		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `blocked` = 0 AND `pending` = 0",
			intval($contact_id)
		);

		if (dbm::is_result($r))
			$contact = $r[0];

		if ($contact['self'])
			continue;

		$deliver_status = 0;

		logger("main delivery by delivery: followup=$followup mail=$mail fsuggest=$fsuggest relocate=$relocate - network ".$contact['network']);

		switch($contact['network']) {

			case NETWORK_DFRN:
				logger('notifier: '.$target_item["guid"].' dfrndelivery: '.$contact['name']);

				if ($mail) {
					$item['body'] = fix_private_photos($item['body'],$owner['uid'],null,$message[0]['contact-id']);
					$atom = dfrn::mail($item, $owner);
				} elseif ($fsuggest) {
					$atom = dfrn::fsuggest($item, $owner);
					q("DELETE FROM `fsuggest` WHERE `id` = %d LIMIT 1", intval($item['id']));
				} elseif ($relocate)
					$atom = dfrn::relocate($owner, $uid);
				elseif ($followup) {
					$msgitems = array();
					foreach($items as $item) {  // there is only one item
						if (!$item['parent'])
							continue;
						if ($item['id'] == $item_id) {
							logger('followup: item: '. print_r($item,true), LOGGER_DATA);
							$msgitems[] = $item;
						}
					}
					$atom = dfrn::entries($msgitems,$owner);
				} else {
					$msgitems = array();
					foreach($items as $item) {
						if (!$item['parent'])
							continue;

						// private emails may be in included in public conversations. Filter them.
						if ($public_message && $item['private'])
							continue;

						$item_contact = get_item_contact($item,$icontacts);
						if (!$item_contact)
							continue;

						if ($normal_mode) {
							if ($item_id == $item['id'] || $item['id'] == $item['parent']) {
								$item["entry:comment-allow"] = true;
								$item["entry:cid"] = (($top_level) ? $contact['id'] : 0);
								$msgitems[] = $item;
							}
						} else {
							$item["entry:comment-allow"] = true;
							$msgitems[] = $item;
						}
					}
					$atom = dfrn::entries($msgitems,$owner);
				}

				logger('notifier entry: '.$contact["url"].' '.$target_item["guid"].' entry: '.$atom, LOGGER_DEBUG);

				logger('notifier: '.$atom, LOGGER_DATA);
				$basepath =  implode('/', array_slice(explode('/',$contact['url']),0,3));

				// perform local delivery if we are on the same site

				if (link_compare($basepath,App::get_baseurl())) {

					$nickname = basename($contact['url']);
					if ($contact['issued-id'])
						$sql_extra = sprintf(" AND `dfrn-id` = '%s' ", dbesc($contact['issued-id']));
					else
						$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($contact['dfrn-id']));

					$x = q("SELECT	`contact`.*, `contact`.`uid` AS `importer_uid`,
						`contact`.`pubkey` AS `cpubkey`,
						`contact`.`prvkey` AS `cprvkey`,
						`contact`.`thumb` AS `thumb`,
						`contact`.`url` as `url`,
						`contact`.`name` as `senderName`,
						`user`.*
						FROM `contact`
						INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
						WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0
						AND `contact`.`network` = '%s' AND `user`.`nickname` = '%s'
						$sql_extra
						AND `user`.`account_expired` = 0 AND `user`.`account_removed` = 0 LIMIT 1",
						dbesc(NETWORK_DFRN),
						dbesc($nickname)
					);

					if ($x && count($x)) {
						$write_flag = ((($x[0]['rel']) && ($x[0]['rel'] != CONTACT_IS_SHARING)) ? true : false);
						if ((($owner['page-flags'] == PAGE_COMMUNITY) || $write_flag) && !$x[0]['writable']) {
							q("UPDATE `contact` SET `writable` = 1 WHERE `id` = %d",
								intval($x[0]['id'])
							);
							$x[0]['writable'] = 1;
						}

						$ssl_policy = get_config('system','ssl_policy');
						fix_contact_ssl_policy($x[0],$ssl_policy);

						// If we are setup as a soapbox we aren't accepting top level posts from this person

						if (($x[0]['page-flags'] == PAGE_SOAPBOX) AND $top_level)
							break;

						logger('mod-delivery: local delivery');
						dfrn::import($atom, $x[0]);
						break;
					}
				}

				if (!was_recently_delayed($contact['id']))
					$deliver_status = dfrn::deliver($owner,$contact,$atom);
				else
					$deliver_status = (-1);

				logger('notifier: dfrn_delivery to '.$contact["url"].' with guid '.$target_item["guid"].' returns '.$deliver_status);

				if ($deliver_status == (-1)) {
					logger('notifier: delivery failed: queuing message');
					add_to_queue($contact['id'],NETWORK_DFRN,$atom);

					// The message could not be delivered. We mark the contact as "dead"
					mark_for_death($contact);
				} else {
					// We successfully delivered a message, the contact is alive
					unmark_for_death($contact);
				}

				break;

			case NETWORK_OSTATUS:
				// Do not send to otatus if we are not configured to send to public networks
				if ($owner['prvnets'])
					break;
				if (get_config('system','ostatus_disabled') || get_config('system','dfrn_only'))
					break;

				// There is currently no code here to distribute anything to OStatus.
				// This is done in "notifier.php" (See "url_recipients" and "push_notify")
				break;

			case NETWORK_MAIL:
			case NETWORK_MAIL2:

				if (get_config('system','dfrn_only'))
					break;
				// WARNING: does not currently convert to RFC2047 header encodings, etc.

				$addr = $contact['addr'];
				if (!strlen($addr))
					break;

				if ($cmd === 'wall-new' || $cmd === 'comment-new') {

					$it = null;
					if ($cmd === 'wall-new')
						$it = $items[0];
					else {
						$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
							intval($argv[2]),
							intval($uid)
						);
						if (dbm::is_result($r))
							$it = $r[0];
					}
					if (!$it)
						break;


					$local_user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
						intval($uid)
					);
					if (!count($local_user))
						break;

					$reply_to = '';
					$r1 = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
						intval($uid)
					);
					if ($r1 && $r1[0]['reply_to'])
						$reply_to = $r1[0]['reply_to'];

					$subject  = (($it['title']) ? email_header_encode($it['title'],'UTF-8') : t("\x28no subject\x29")) ;

					// only expose our real email address to true friends

					if (($contact['rel'] == CONTACT_IS_FRIEND) && !$contact['blocked']) {
						if ($reply_to) {
							$headers  = 'From: '.email_header_encode($local_user[0]['username'],'UTF-8').' <'.$reply_to.'>'."\n";
							$headers .= 'Sender: '.$local_user[0]['email']."\n";
						} else
							$headers  = 'From: '.email_header_encode($local_user[0]['username'],'UTF-8').' <'.$local_user[0]['email'].'>'."\n";
					} else
						$headers  = 'From: '. email_header_encode($local_user[0]['username'],'UTF-8') .' <'. t('noreply') .'@'.$a->get_hostname() .'>'. "\n";

					//if ($reply_to)
					//	$headers .= 'Reply-to: '.$reply_to . "\n";

					$headers .= 'Message-Id: <'. iri2msgid($it['uri']).'>'. "\n";

					//logger("Mail: uri: ".$it['uri']." parent-uri ".$it['parent-uri'], LOGGER_DEBUG);
					//logger("Mail: Data: ".print_r($it, true), LOGGER_DEBUG);
					//logger("Mail: Data: ".print_r($it, true), LOGGER_DATA);

					if ($it['uri'] !== $it['parent-uri']) {
						$headers .= "References: <".iri2msgid($it["parent-uri"]).">";

						// If Threading is enabled, write down the correct parent
						if (($it["thr-parent"] != "") and ($it["thr-parent"] != $it["parent-uri"]))
							$headers .= " <".iri2msgid($it["thr-parent"]).">";
						$headers .= "\n";

						if (!$it['title']) {
							$r = q("SELECT `title` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
								dbesc($it['parent-uri']),
								intval($uid));

							if (dbm::is_result($r) AND ($r[0]['title'] != ''))
								$subject = $r[0]['title'];
							else {
								$r = q("SELECT `title` FROM `item` WHERE `parent-uri` = '%s' AND `uid` = %d LIMIT 1",
									dbesc($it['parent-uri']),
									intval($uid));

								if (dbm::is_result($r) AND ($r[0]['title'] != ''))
									$subject = $r[0]['title'];
							}
						}
						if (strncasecmp($subject,'RE:',3))
							$subject = 'Re: '.$subject;
					}
					email_send($addr, $subject, $headers, $it);
				}
				break;

			case NETWORK_DIASPORA:
				if ($public_message)
					$loc = 'public batch '.$contact['batch'];
				else
					$loc = $contact['name'];

				logger('delivery: diaspora batch deliver: '.$loc);

				if (get_config('system','dfrn_only') || (!get_config('system','diaspora_enabled')))
					break;

				if ($mail) {
					Diaspora::send_mail($item,$owner,$contact);
					break;
				}

				if (!$normal_mode)
					break;

				if (!$contact['pubkey'] && !$public_message)
					break;

				$unsupported_activities = array(ACTIVITY_DISLIKE, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE);

				//don't transmit activities which are not supported by diaspora
				foreach($unsupported_activities as $act) {
					if (activity_match($target_item['verb'],$act)) {
						break 2;
					}
				}

				if (($target_item['deleted']) && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
					// top-level retraction
					logger('diaspora retract: '.$loc);
					Diaspora::send_retraction($target_item,$owner,$contact,$public_message);
					break;
				} elseif ($followup) {
					// send comments and likes to owner to relay
					logger('diaspora followup: '.$loc);
					Diaspora::send_followup($target_item,$owner,$contact,$public_message);
					break;
				} elseif ($target_item['uri'] !== $target_item['parent-uri']) {
					// we are the relay - send comments, likes and relayable_retractions to our conversants
					logger('diaspora relay: '.$loc);
					Diaspora::send_relay($target_item,$owner,$contact,$public_message);
					break;
				} elseif ($top_level && !$walltowall) {
					// currently no workable solution for sending walltowall
					logger('diaspora status: '.$loc);
					Diaspora::send_status($target_item,$owner,$contact,$public_message);
					break;
				}

				logger('delivery: diaspora unknown mode: '.$contact['name']);

				break;

			default:
				break;
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  delivery_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
