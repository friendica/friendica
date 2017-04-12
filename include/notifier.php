<?php

use \Friendica\Core\Config;

require_once('include/queue_fn.php');
require_once('include/html2plain.php');
require_once('include/Scrape.php');
require_once('include/diaspora.php');
require_once('include/ostatus.php');
require_once('include/salmon.php');

/*
 * This file was at one time responsible for doing all deliveries, but this caused
 * big problems when the process was killed or stalled during the delivery process.
 * It now invokes separate queues that are delivering via delivery.php and pubsubpublish.php.
 */

/*
 * The notifier is typically called with:
 *
 *		proc_run(PRIORITY_HIGH, "include/notifier.php", COMMAND, ITEM_ID);
 *
 * where COMMAND is one of the following:
 *
 *		activity				(in diaspora.php, dfrn_confirm.php, profiles.php)
 *		comment-import			(in diaspora.php, items.php)
 *		comment-new				(in item.php)
 *		drop					(in diaspora.php, items.php, photos.php)
 *		edit_post				(in item.php)
 *		event					(in events.php)
 *		expire					(in items.php)
 *		like					(in like.php, poke.php)
 *		mail					(in message.php)
 *		suggest					(in fsuggest.php)
 *		tag						(in photos.php, poke.php, tagger.php)
 *		tgroup					(in items.php)
 *		wall-new				(in photos.php, item.php)
 *		removeme				(in Contact.php)
 * 		relocate				(in uimport.php)
 *
 * and ITEM_ID is the id of the item in the database that needs to be sent to others.
 */


function notifier_run(&$argv, &$argc){
	global $a;

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/bbcode.php');
	require_once('include/email.php');

	if ($argc < 3) {
		return;
	}

	logger('notifier: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

	$cmd = $argv[1];

	switch($cmd) {
		case 'mail':
		default:
			$item_id = intval($argv[2]);
			if (! $item_id) {
				return;
			}
			break;
	}

	$expire = false;
	$mail = false;
	$fsuggest = false;
	$relocate = false;
	$top_level = false;
	$recipients = array();
	$url_recipients = array();

	$normal_mode = true;

	if ($cmd === 'mail') {
		$normal_mode = false;
		$mail = true;
		$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
				intval($item_id)
		);
		if (! count($message)) {
			return;
		}
		$uid = $message[0]['uid'];
		$recipients[] = $message[0]['contact-id'];
		$item = $message[0];

	} elseif ($cmd === 'expire') {
		$normal_mode = false;
		$expire = true;
		$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1
			AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP() - INTERVAL 10 MINUTE",
			intval($item_id)
		);
		$uid = $item_id;
		$item_id = 0;
		if (! count($items)) {
			return;
		}
	} elseif ($cmd === 'suggest') {
		$normal_mode = false;
		$fsuggest = true;

		$suggest = q("SELECT * FROM `fsuggest` WHERE `id` = %d LIMIT 1",
			intval($item_id)
		);
		if (! count($suggest)) {
			return;
		}
		$uid = $suggest[0]['uid'];
		$recipients[] = $suggest[0]['cid'];
		$item = $suggest[0];
	} elseif ($cmd === 'removeme') {
		$r = q("SELECT `contact`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`,
				`user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`,
				`user`.`page-flags`, `user`.`prvnets`, `user`.`account-type`, `user`.`guid`
			FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`uid` = %d AND `contact`.`self` LIMIT 1",
				intval($item_id));
		if (!$r)
			return;

		$user = $r[0];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($item_id));
		if (!$r)
			return;

		$self = $r[0];

		$r = q("SELECT * FROM `contact` WHERE NOT `self` AND `uid` = %d", intval($item_id));
		if (!$r) {
			return;
		}
		require_once('include/Contact.php');
		foreach ($r as $contact) {
			terminate_friendship($user, $self, $contact);
		}
		return;
	} elseif ($cmd === 'relocate') {
		$normal_mode = false;
		$relocate = true;
		$uid = $item_id;

		$recipients_relocate = q("SELECT * FROM contact WHERE uid = %d  AND self = 0 AND network = '%s'" , intval($uid), NETWORK_DFRN);
	} else {
		// find ancestors
		$r = q("SELECT * FROM `item` WHERE `id` = %d and visible = 1 and moderated = 0 LIMIT 1",
			intval($item_id)
		);

		if ((! dbm::is_result($r)) || (! intval($r[0]['parent']))) {
			return;
		}

		$target_item = $r[0];
		$parent_id = intval($r[0]['parent']);
		$uid = $r[0]['uid'];
		$updated = $r[0]['edited'];

		$items = q("SELECT `item`.*, `sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
			FROM `item` LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id` WHERE `parent` = %d and visible = 1 and moderated = 0 ORDER BY `id` ASC",
			intval($parent_id)
		);

		if (! count($items)) {
			return;
		}

		// avoid race condition with deleting entries

		if ($items[0]['deleted']) {
			foreach ($items as $item) {
				$item['deleted'] = 1;
			}
		}

		if ((count($items) == 1) && ($items[0]['id'] === $target_item['id']) && ($items[0]['uri'] === $items[0]['parent-uri'])) {
			logger('notifier: top level post');
			$top_level = true;
		}

	}

	$r = q("SELECT `contact`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`,
		`user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`,
		`user`.`page-flags`, `user`.`prvnets`, `user`.`account-type`
		FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
		WHERE `contact`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($uid)
	);

	if (! dbm::is_result($r)) {
		return;
	}

	$owner = $r[0];

	$walltowall = ((($top_level) && ($owner['id'] != $items[0]['contact-id'])) ? true : false);

	$hub = get_config('system','huburl');

	// Should the post be transmitted to Diaspora?
	$diaspora_delivery = true;

	// If this is a public conversation, notify the feed hub
	$public_message = true;

	// Do a PuSH
	$push_notify = false;

	// fill this in with a single salmon slap if applicable
	$slap = '';

	if (! ($mail || $fsuggest || $relocate)) {

		$slap = ostatus::salmon($target_item,$owner);

		require_once('include/group.php');

		$parent = $items[0];

		$thr_parent = q("SELECT `network`, `author-link`, `owner-link` FROM `item` WHERE `uri` = '%s' AND `uid` = %d",
			dbesc($target_item["thr-parent"]), intval($target_item["uid"]));

		logger('GUID: '.$target_item["guid"].': Parent is '.$parent['network'].'. Thread parent is '.$thr_parent[0]['network'], LOGGER_DEBUG);

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


		$localhost = str_replace('www.','',$a->get_hostname());
		if (strpos($localhost,':')) {
			$localhost = substr($localhost,0,strpos($localhost,':'));
		}
		/**
		 *
		 * Be VERY CAREFUL if you make any changes to the following several lines. Seemingly innocuous changes
		 * have been known to cause runaway conditions which affected several servers, along with
		 * permissions issues.
		 *
		 */

		$relay_to_owner = false;

		if (!$top_level && ($parent['wall'] == 0) && !$expire && (stristr($target_item['uri'],$localhost))) {
			$relay_to_owner = true;
		}


		if (($cmd === 'uplink') && (intval($parent['forum_mode']) == 1) && !$top_level) {
			$relay_to_owner = true;
		}

		// until the 'origin' flag has been in use for several months
		// we will just use it as a fallback test
		// later we will be able to use it as the primary test of whether or not to relay.

		if (! $target_item['origin']) {
			$relay_to_owner = false;
		}
		if ($parent['origin']) {
			$relay_to_owner = false;
		}
		if ($relay_to_owner) {
			logger('notifier: followup '.$target_item["guid"], LOGGER_DEBUG);
			// local followup to remote post
			$followup = true;
			$public_message = false; // not public
			$conversant_str = dbesc($parent['contact-id']);
			$recipients = array($parent['contact-id']);
			$recipients_followup  = array($parent['contact-id']);

			//if (!$target_item['private'] AND $target_item['wall'] AND
			if (!$target_item['private'] AND
				(strlen($target_item['allow_cid'].$target_item['allow_gid'].
					$target_item['deny_cid'].$target_item['deny_gid']) == 0))
				$push_notify = true;

			if (($thr_parent AND ($thr_parent[0]['network'] == NETWORK_OSTATUS)) OR ($parent['network'] == NETWORK_OSTATUS)) {

				$push_notify = true;

				if ($parent["network"] == NETWORK_OSTATUS) {
					// Distribute the message to the DFRN contacts as if this wasn't a followup since OStatus can't relay comments
					// Currently it is work at progress
					$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s' AND NOT `blocked` AND NOT `pending` AND NOT `archive`",
						intval($uid),
						dbesc(NETWORK_DFRN)
					);
					if (dbm::is_result($r)) {
						foreach ($r as $rr) {
							$recipients_followup[] = $rr['id'];
						}
					}
				}
			}
			logger("Notify ".$target_item["guid"]." via PuSH: ".($push_notify?"Yes":"No"), LOGGER_DEBUG);
		} else {
			$followup = false;

			logger('Distributing directly '.$target_item["guid"], LOGGER_DEBUG);

			// don't send deletions onward for other people's stuff

			if ($target_item['deleted'] && (! intval($target_item['wall']))) {
				logger('notifier: ignoring delete notification for non-wall item');
				return;
			}

			if ((strlen($parent['allow_cid']))
				|| (strlen($parent['allow_gid']))
				|| (strlen($parent['deny_cid']))
				|| (strlen($parent['deny_gid']))) {
				$public_message = false; // private recipients, not public
			}

			$allow_people = expand_acl($parent['allow_cid']);
			$allow_groups = expand_groups(expand_acl($parent['allow_gid']),true);
			$deny_people  = expand_acl($parent['deny_cid']);
			$deny_groups  = expand_groups(expand_acl($parent['deny_gid']));

			// if our parent is a public forum (forum_mode == 1), uplink to the origional author causing
			// a delivery fork. private groups (forum_mode == 2) do not uplink

			if ((intval($parent['forum_mode']) == 1) && (! $top_level) && ($cmd !== 'uplink')) {
				proc_run(PRIORITY_HIGH,'include/notifier.php','uplink',$item_id);
			}

			$conversants = array();

			foreach ($items as $item) {
				$recipients[] = $item['contact-id'];
				$conversants[] = $item['contact-id'];
				// pull out additional tagged people to notify (if public message)
				if ($public_message && strlen($item['inform'])) {
					$people = explode(',',$item['inform']);
					foreach ($people as $person) {
						if (substr($person,0,4) === 'cid:') {
							$recipients[] = intval(substr($person,4));
							$conversants[] = intval(substr($person,4));
						} else {
							$url_recipients[] = substr($person,4);
						}
					}
				}
			}

			if (count($url_recipients))
				logger('notifier: '.$target_item["guid"].' url_recipients ' . print_r($url_recipients,true));

			$conversants = array_unique($conversants);


			$recipients = array_unique(array_merge($recipients,$allow_people,$allow_groups));
			$deny = array_unique(array_merge($deny_people,$deny_groups));
			$recipients = array_diff($recipients,$deny);

			$conversant_str = dbesc(implode(', ',$conversants));
		}

		// If the thread parent is OStatus then do some magic to distribute the messages.
		// We have not only to look at the parent, since it could be a Friendica thread.
		if (($thr_parent AND ($thr_parent[0]['network'] == NETWORK_OSTATUS)) OR ($parent['network'] == NETWORK_OSTATUS)) {

			$diaspora_delivery = false;

			logger('Some parent is OStatus for '.$target_item["guid"]." - Author: ".$thr_parent[0]['author-link']." - Owner: ".$thr_parent[0]['owner-link'], LOGGER_DEBUG);

			// Send a salmon to the parent author
			$r = q("SELECT `url`, `notify` FROM `contact` WHERE `nurl`='%s' AND `uid` IN (0, %d) AND `notify` != ''",
				dbesc(normalise_link($thr_parent[0]['author-link'])),
				intval($uid));
			if (dbm::is_result($r)) {
				$probed_contact = $r[0];
			} else {
				$probed_contact = probe_url($thr_parent[0]['author-link']);
			}

			if ($probed_contact["notify"] != "") {
				logger('Notify parent author '.$probed_contact["url"].': '.$probed_contact["notify"]);
				$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
			}

			// Send a salmon to the parent owner
			$r = q("SELECT `url`, `notify` FROM `contact` WHERE `nurl`='%s' AND `uid` IN (0, %d) AND `notify` != ''",
				dbesc(normalise_link($thr_parent[0]['owner-link'])),
				intval($uid));
			if (dbm::is_result($r)) {
				$probed_contact = $r[0];
			} else {
				$probed_contact = probe_url($thr_parent[0]['owner-link']);
			}

			if ($probed_contact["notify"] != "") {
				logger('Notify parent owner '.$probed_contact["url"].': '.$probed_contact["notify"]);
				$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
			}

			// Send a salmon notification to every person we mentioned in the post
			$arr = explode(',',$target_item['tag']);
			foreach ($arr as $x) {
				//logger('Checking tag '.$x, LOGGER_DEBUG);
				$matches = null;
				if (preg_match('/@\[url=([^\]]*)\]/',$x,$matches)) {
						$probed_contact = probe_url($matches[1]);
					if ($probed_contact["notify"] != "") {
						logger('Notify mentioned user '.$probed_contact["url"].': '.$probed_contact["notify"]);
						$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
					}
				}
			}

			// It only makes sense to distribute answers to OStatus messages to Friendica and OStatus - but not Diaspora
			$sql_extra = " AND `network` IN ('".NETWORK_OSTATUS."', '".NETWORK_DFRN."')";
		} else {
			$sql_extra = " AND `network` IN ('".NETWORK_OSTATUS."', '".NETWORK_DFRN."', '".NETWORK_DIASPORA."', '".NETWORK_MAIL."', '".NETWORK_MAIL2."')";
		}
	} else {
		$public_message = false;
	}

	// If this is a public message and pubmail is set on the parent, include all your email contacts

	$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	if (! $mail_disabled) {
		if ((! strlen($target_item['allow_cid'])) && (! strlen($target_item['allow_gid']))
			&& (! strlen($target_item['deny_cid'])) && (! strlen($target_item['deny_gid']))
			&& (intval($target_item['pubmail']))) {
			$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s'",
				intval($uid),
				dbesc(NETWORK_MAIL)
			);
			if (dbm::is_result($r)) {
				foreach ($r as $rr) {
					$recipients[] = $rr['id'];
				}
			}
		}
	}

	if ($followup) {
		$recip_str = implode(', ', $recipients_followup);
	} else {
		$recip_str = implode(', ', $recipients);
	}
	if ($relocate) {
		$r = $recipients_relocate;
	} else {
		$r = q("SELECT `id`, `url`, `network`, `self` FROM `contact`
			WHERE `id` IN (%s) AND NOT `blocked` AND NOT `pending` AND NOT `archive`".$sql_extra,
			dbesc($recip_str)
		);
	}

	// delivery loop

	if (dbm::is_result($r)) {
		foreach ($r as $contact) {
			if ($contact['self']) {
				continue;
			}
			logger("Deliver ".$target_item["guid"]." to ".$contact['url']." via network ".$contact['network'], LOGGER_DEBUG);

			proc_run(PRIORITY_HIGH,'include/delivery.php', $cmd, $item_id, $contact['id']);
		}
	}

	// send salmon slaps to mentioned remote tags (@foo@example.com) in OStatus posts
	// They are especially used for notifications to OStatus users that don't follow us.

	if ($slap && count($url_recipients) && ($public_message || $push_notify) && $normal_mode) {
		if (!get_config('system','dfrn_only')) {
			foreach ($url_recipients as $url) {
				if ($url) {
					logger('notifier: urldelivery: ' . $url);
					$deliver_status = slapper($owner,$url,$slap);
					/// @TODO Redeliver/queue these items on failure, though there is no contact record
				}
			}
		}
	}


	if ($public_message) {

		$r0 = array();
		$r1 = array();

		if ($diaspora_delivery) {
			if (!$followup) {
				$r0 = Diaspora::relay_list();
			}

			$r1 = q("SELECT DISTINCT(`batch`), `id`, `name`,`network` FROM `contact` WHERE `network` = '%s'
				AND `uid` = %d AND `rel` != %d AND NOT `blocked` AND NOT `pending` AND NOT `archive` GROUP BY `batch`, `id` ORDER BY rand()",
				dbesc(NETWORK_DIASPORA),
				intval($owner['uid']),
				intval(CONTACT_IS_SHARING)
			);
		}

		$r2 = q("SELECT `id`, `name`,`network` FROM `contact`
			WHERE `network` in ( '%s', '%s')  AND `uid` = %d AND NOT `blocked` AND NOT `pending` AND NOT `archive`
			AND `rel` != %d order by rand() ",
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_MAIL2),
			intval($owner['uid']),
			intval(CONTACT_IS_SHARING)
		);

		$r = array_merge($r2,$r1,$r0);

		if (dbm::is_result($r)) {
			logger('pubdeliver '.$target_item["guid"].': '.print_r($r,true), LOGGER_DEBUG);

			foreach ($r as $rr) {

				// except for Diaspora batch jobs
				// Don't deliver to folks who have already been delivered to

				if (($rr['network'] !== NETWORK_DIASPORA) && (in_array($rr['id'],$conversants))) {
					logger('notifier: already delivered id=' . $rr['id']);
					continue;
				}

				if ((! $mail) && (! $fsuggest) && (! $followup)) {
					logger('notifier: delivery agent: '.$rr['name'].' '.$rr['id'].' '.$rr['network'].' '.$target_item["guid"]);
					proc_run(PRIORITY_HIGH,'include/delivery.php',$cmd,$item_id,$rr['id']);
				}
			}
		}

		$push_notify = true;

	}

	// Notify PuSH subscribers (Used for OStatus distribution of regular posts)
	if ($push_notify AND strlen($hub)) {
		$hubs = explode(',', $hub);
		if (count($hubs)) {
			foreach ($hubs as $h) {
				$h = trim($h);
				if (! strlen($h)) {
					continue;
				}

				if ($h === '[internal]') {
					// Set push flag for PuSH subscribers to this topic,
					// they will be notified in queue.php
					q("UPDATE `push_subscriber` SET `push` = 1 ".
					  "WHERE `nickname` = '%s' AND `push` = 0", dbesc($owner['nickname']));

					logger('Activating internal PuSH for item '.$item_id, LOGGER_DEBUG);

				} else {

					$params = 'hub.mode=publish&hub.url=' . urlencode( App::get_baseurl() . '/dfrn_poll/' . $owner['nickname'] );
					post_url($h,$params);
					logger('publish for item '.$item_id.' ' . $h . ' ' . $params . ' returned ' . $a->get_curl_code());
				}
				if (count($hubs) > 1) {
					sleep(7);				// try and avoid multiple hubs responding at precisely the same time
				}
			}
		}

		// Handling the pubsubhubbub requests
		proc_run(PRIORITY_HIGH,'include/pubsubpublish.php');
	}

	logger('notifier: calling hooks', LOGGER_DEBUG);

	if ($normal_mode) {
		call_hooks('notifier_normal',$target_item);
	}

	call_hooks('notifier_end',$target_item);

	return;
}
