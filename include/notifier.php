<?php
require_once("boot.php");
require_once('include/queue_fn.php');
require_once('include/html2plain.php');
require_once("include/Scrape.php");
require_once('include/diaspora.php');

/*
 * This file was at one time responsible for doing all deliveries, but this caused
 * big problems on shared hosting systems, where the process might get killed by the
 * hosting provider and nothing would get delivered.
 * It now only delivers one message under certain cases, and invokes a queued
 * delivery mechanism (include/deliver.php) to deliver individual contacts at
 * controlled intervals.
 * This has a much better chance of surviving random processes getting killed
 * by the hosting provider.
 * A lot of this code is duplicated in include/deliver.php until we have time to go back
 * and re-structure the delivery procedure based on the obstacles that have been thrown at
 * us by hosting providers.
 */

/*
 * The notifier is typically called with:
 *
 *		proc_run('php', "include/notifier.php", COMMAND, ITEM_ID);
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
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}

	if(is_null($db)) {
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

	if($argc < 3)
		return;

	$a->set_baseurl(get_config('system','url'));

	logger('notifier: invoked: ' . print_r($argv,true), LOGGER_DEBUG);

	$cmd = $argv[1];

	switch($cmd) {
		case 'mail':
		default:
			$item_id = intval($argv[2]);
			if(! $item_id){
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

	if($cmd === 'mail') {
		$normal_mode = false;
		$mail = true;
		$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
				intval($item_id)
		);
		if(! count($message)){
			return;
		}
		$uid = $message[0]['uid'];
		$recipients[] = $message[0]['contact-id'];
		$item = $message[0];

	}
	elseif($cmd === 'expire') {
		$normal_mode = false;
		$expire = true;
		$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1
			AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP() - INTERVAL 10 MINUTE",
			intval($item_id)
		);
		$uid = $item_id;
		$item_id = 0;
		if(! count($items))
			return;
	}
	elseif($cmd === 'suggest') {
		$normal_mode = false;
		$fsuggest = true;

		$suggest = q("SELECT * FROM `fsuggest` WHERE `id` = %d LIMIT 1",
			intval($item_id)
		);
		if(! count($suggest))
			return;
		$uid = $suggest[0]['uid'];
		$recipients[] = $suggest[0]['cid'];
		$item = $suggest[0];
	} elseif($cmd === 'removeme') {
		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", intval($item_id));
		if (! $r)
			return;

		$user = $r[0];
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1", intval($item_id));
		if (! $r)
			return;

		$self = $r[0];
		$r = q("SELECT * FROM `contact` WHERE `self` = 0 AND `uid` = %d", intval($item_id));
		if(! $r)
			return;

		require_once('include/Contact.php');
		foreach($r as $contact) {
			terminate_friendship($user, $self, $contact);
		}
		return;
	} elseif($cmd === 'relocate') {
		$normal_mode = false;
		$relocate = true;
		$uid = $item_id;
	} else {
		// find ancestors
		$r = q("SELECT * FROM `item` WHERE `id` = %d and visible = 1 and moderated = 0 LIMIT 1",
			intval($item_id)
		);

		if((! count($r)) || (! intval($r[0]['parent']))) {
			return;
		}

		$target_item = $r[0];
		$parent_id = intval($r[0]['parent']);
		$uid = $r[0]['uid'];
		$updated = $r[0]['edited'];

		// POSSIBLE CLEANUP --> The following seems superfluous. We've already checked for "if (! intval($r[0]['parent']))" a few lines up
		if(! $parent_id)
			return;

		$items = q("SELECT `item`.*, `sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
			FROM `item` LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id` WHERE `parent` = %d and visible = 1 and moderated = 0 ORDER BY `id` ASC",
			intval($parent_id)
		);

		if(! count($items)) {
			return;
		}

		// avoid race condition with deleting entries

		if($items[0]['deleted']) {
			foreach($items as $item)
				$item['deleted'] = 1;
		}

		if((count($items) == 1) && ($items[0]['id'] === $target_item['id']) && ($items[0]['uri'] === $items[0]['parent-uri'])) {
			logger('notifier: top level post');
			$top_level = true;
		}

	}

	$r = q("SELECT `contact`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`,
		`user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`,
		`user`.`page-flags`, `user`.`prvnets`
		FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
		WHERE `contact`.`uid` = %d AND `contact`.`self` = 1 LIMIT 1",
		intval($uid)
	);

	if(! count($r))
		return;

	$owner = $r[0];

	$walltowall = ((($top_level) && ($owner['id'] != $items[0]['contact-id'])) ? true : false);

	$hub = get_config('system','huburl');

	// If this is a public conversation, notify the feed hub
	$public_message = true;

	// Do a PuSH
	$push_notify = false;

	// fill this in with a single salmon slap if applicable
	$slap = '';

	if(! ($mail || $fsuggest || $relocate)) {

		require_once('include/group.php');

		$parent = $items[0];

		$thr_parent = q("SELECT `network` FROM `item` WHERE `uri` = '%s' AND `uid` = %d",
			dbesc($target_item["thr-parent"]), intval($target_item["uid"]));

		logger('Parent is '.$parent['network'].'. Thread parent is '.$thr_parent[0]['network'], LOGGER_DEBUG);

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
		if(strpos($localhost,':'))
			$localhost = substr($localhost,0,strpos($localhost,':'));

		/**
		 *
		 * Be VERY CAREFUL if you make any changes to the following several lines. Seemingly innocuous changes
		 * have been known to cause runaway conditions which affected several servers, along with
		 * permissions issues.
		 *
		 */

		$relay_to_owner = false;

		if((! $top_level) && ($parent['wall'] == 0) && (! $expire) && (stristr($target_item['uri'],$localhost))) {
			$relay_to_owner = true;
		}


		if(($cmd === 'uplink') && (intval($parent['forum_mode']) == 1) && (! $top_level)) {
			$relay_to_owner = true;
		}

		// until the 'origin' flag has been in use for several months
		// we will just use it as a fallback test
		// later we will be able to use it as the primary test of whether or not to relay.

		if(! $target_item['origin'])
			$relay_to_owner = false;

		if($parent['origin'])
			$relay_to_owner = false;

		if($relay_to_owner) {
			logger('notifier: followup '.$target_item["guid"], LOGGER_DEBUG);
			// local followup to remote post
			$followup = true;
			$public_message = false; // not public
			$conversant_str = dbesc($parent['contact-id']);
			$recipients = array($parent['contact-id']);

			if (!$target_item['private'] AND $target_item['wall'] AND
				(strlen($target_item['allow_cid'].$target_item['allow_gid'].
					$target_item['deny_cid'].$target_item['deny_gid']) == 0))
				$push_notify = true;

			// We notify Friendica users in the thread when it is an OStatus thread.
			// Hopefully this transfers the messages to the other Friendica servers. (Untested)
			if (($thr_parent AND ($thr_parent[0]['network'] == NETWORK_OSTATUS)) OR ($parent['network'] == NETWORK_OSTATUS)) {

				$push_notify = true;

				if ($parent["network"] == NETWORK_OSTATUS) {
					$r = q("SELECT `author-link` FROM `item` WHERE `parent` = %d AND `author-link` != '%s'",
						intval($target_item["parent"]), dbesc($owner['url']));
					foreach($r as $parent_item) {
						$probed_contact = probe_url($parent_item["author-link"]);
						if (($probed_contact["notify"] != "") AND ($probed_contact["network"] == NETWORK_DFRN)) {
							logger('Notify Friendica user '.$probed_contact["url"].': '.$probed_contact["notify"]);
							$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
						}
					}
				}

				if (count($url_recipients))
					logger("url_recipients ".print_r($url_recipients,true));
			}
		} else {
			$followup = false;

			logger('Distributing directly '.$target_item["guid"], LOGGER_DEBUG);

			// don't send deletions onward for other people's stuff

			if($target_item['deleted'] && (! intval($target_item['wall']))) {
				logger('notifier: ignoring delete notification for non-wall item');
				return;
			}

			if((strlen($parent['allow_cid']))
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

			if((intval($parent['forum_mode']) == 1) && (! $top_level) && ($cmd !== 'uplink')) {
				proc_run('php','include/notifier.php','uplink',$item_id);
			}

			$conversants = array();

			foreach($items as $item) {
				$recipients[] = $item['contact-id'];
				$conversants[] = $item['contact-id'];
				// pull out additional tagged people to notify (if public message)
				if($public_message && strlen($item['inform'])) {
					$people = explode(',',$item['inform']);
					foreach($people as $person) {
						if(substr($person,0,4) === 'cid:') {
							$recipients[] = intval(substr($person,4));
							$conversants[] = intval(substr($person,4));
						}
						else {
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

			logger('Some parent is OStatus for '.$target_item["guid"], LOGGER_DEBUG);

			// Send a salmon notification to every person we mentioned in the post
			$arr = explode(',',$target_item['tag']);
			foreach($arr as $x) {
				//logger('Checking tag '.$x, LOGGER_DEBUG);
				$matches = null;
				if(preg_match('/@\[url=([^\]]*)\]/',$x,$matches)) {
						$probed_contact = probe_url($matches[1]);
					if ($probed_contact["notify"] != "") {
						logger('Notify mentioned user '.$probed_contact["url"].': '.$probed_contact["notify"]);
						$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
					}
				}
			}

			// It only makes sense to distribute answers to OStatus messages to Friendica and OStatus - but not Diaspora
			$sql_extra = " AND `network` IN ('".NETWORK_OSTATUS."', '".NETWORK_DFRN."')";
		} else
			$sql_extra = "";

		$r = q("SELECT * FROM `contact` WHERE `id` IN ($conversant_str) AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0".$sql_extra);

		if(count($r))
			$contacts = $r;
	}

	$feed_template = get_markup_template('atom_feed.tpl');
	$mail_template = get_markup_template('atom_mail.tpl');

	$atom = '';
	$slaps = array();

	$hubxml = feed_hublinks();

	$birthday = feed_birthday($owner['uid'],$owner['timezone']);

	if(strlen($birthday))
		$birthday = '<dfrn:birthday>' . xmlify($birthday) . '</dfrn:birthday>';

	$atom .= replace_macros($feed_template, array(
			'$version'      => xmlify(FRIENDICA_VERSION),
			'$feed_id'      => xmlify($a->get_baseurl() . '/profile/' . $owner['nickname'] ),
			'$feed_title'   => xmlify($owner['name']),
			'$feed_updated' => xmlify(datetime_convert('UTC', 'UTC', $updated . '+00:00' , ATOM_TIME)) ,
			'$hub'          => $hubxml,
			'$salmon'       => '',  // private feed, we don't use salmon here
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$photo'        => xmlify($owner['photo']),
			'$thumb'        => xmlify($owner['thumb']),
			'$picdate'      => xmlify(datetime_convert('UTC','UTC',$owner['avatar-date'] . '+00:00' , ATOM_TIME)) ,
			'$uridate'      => xmlify(datetime_convert('UTC','UTC',$owner['uri-date']    . '+00:00' , ATOM_TIME)) ,
			'$namdate'      => xmlify(datetime_convert('UTC','UTC',$owner['name-date']   . '+00:00' , ATOM_TIME)) ,
			'$birthday'     => $birthday,
			'$community'    => (($owner['page-flags'] == PAGE_COMMUNITY) ? '<dfrn:community>1</dfrn:community>' : '')

	));

	if($mail) {
		$public_message = false;  // mail is  not public

		$body = fix_private_photos($item['body'],$owner['uid'],null,$message[0]['contact-id']);

		$atom .= replace_macros($mail_template, array(
			'$name'         => xmlify($owner['name']),
			'$profile_page' => xmlify($owner['url']),
			'$thumb'        => xmlify($owner['thumb']),
			'$item_id'      => xmlify($item['uri']),
			'$subject'      => xmlify($item['title']),
			'$created'      => xmlify(datetime_convert('UTC', 'UTC', $item['created'] . '+00:00' , ATOM_TIME)),
			'$content'      => xmlify($body),
			'$parent_id'    => xmlify($item['parent-uri'])
		));
	} elseif($fsuggest) {
		$public_message = false;  // suggestions are not public

		$sugg_template = get_markup_template('atom_suggest.tpl');

		$atom .= replace_macros($sugg_template, array(
			'$name'         => xmlify($item['name']),
			'$url'          => xmlify($item['url']),
			'$photo'        => xmlify($item['photo']),
			'$request'      => xmlify($item['request']),
			'$note'         => xmlify($item['note'])
		));

		// We don't need this any more

		q("DELETE FROM `fsuggest` WHERE `id` = %d LIMIT 1",
			intval($item['id'])
		);

	} elseif($relocate) {
		$public_message = false;  // suggestions are not public

		$sugg_template = get_markup_template('atom_relocate.tpl');

		/* get site pubkey. this could be a new installation with no site keys*/
		$pubkey = get_config('system','site_pubkey');
		if(! $pubkey) {
			$res = new_keypair(1024);
			set_config('system','site_prvkey', $res['prvkey']);
			set_config('system','site_pubkey', $res['pubkey']);
		}

		$rp = q("SELECT `resource-id` , `scale`, type FROM `photo` 
						WHERE `profile` = 1 AND `uid` = %d ORDER BY scale;", $uid);
		$photos = array();
		$ext = Photo::supportedTypes();
		foreach($rp as $p){
			$photos[$p['scale']] = $a->get_baseurl().'/photo/'.$p['resource-id'].'-'.$p['scale'].'.'.$ext[$p['type']];
		}
		unset($rp, $ext);

		$atom .= replace_macros($sugg_template, array(
					'$name' => xmlify($owner['name']),
					'$photo' => xmlify($photos[4]),
					'$thumb' => xmlify($photos[5]),
					'$micro' => xmlify($photos[6]),
					'$url' => xmlify($owner['url']),
					'$request' => xmlify($owner['request']),
					'$confirm' => xmlify($owner['confirm']),
					'$notify' => xmlify($owner['notify']),
					'$poll' => xmlify($owner['poll']),
					'$sitepubkey' => xmlify(get_config('system','site_pubkey')),
					//'$pubkey' => xmlify($owner['pubkey']),
					//'$prvkey' => xmlify($owner['prvkey']),
			));
		$recipients_relocate = q("SELECT * FROM contact WHERE uid = %d  AND self = 0 AND network = '%s'" , intval($uid), NETWORK_DFRN);
		unset($photos);
	} else {

		$slap = atom_entry($target_item,'html',null,$owner,false);

		if($followup) {
			foreach($items as $item) {  // there is only one item
				if(! $item['parent'])
					continue;
				if($item['id'] == $item_id) {
					logger('notifier: followup: item: ' . print_r($item,true), LOGGER_DATA);
					//$slap  = atom_entry($item,'html',null,$owner,false);
					$atom .= atom_entry($item,'text',null,$owner,false);
				}
			}
		} else {
			foreach($items as $item) {

				if(! $item['parent'])
					continue;

				// private emails may be in included in public conversations. Filter them.

				if(($public_message) && $item['private'] == 1)
					continue;


				$contact = get_item_contact($item,$contacts);

				if(! $contact)
					continue;

				if($normal_mode) {

					// we only need the current item, but include the parent because without it
					// older sites without a corresponding dfrn_notify change may do the wrong thing.

				    if($item_id == $item['id'] || $item['id'] == $item['parent'])
						$atom .= atom_entry($item,'text',null,$owner,true);
				} else
					$atom .= atom_entry($item,'text',null,$owner,true);

				if(($top_level) && ($public_message) && ($item['author-link'] === $item['owner-link']) && (! $expire))
					$slaps[] = atom_entry($item,'html',null,$owner,true);
			}
		}
	}
	$atom .= '</feed>' . "\r\n";

	logger('notifier: ' . $atom, LOGGER_DATA);

	logger('notifier: slaps: ' . print_r($slaps,true), LOGGER_DATA);

	// If this is a public message and pubmail is set on the parent, include all your email contacts

	$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	if(! $mail_disabled) {
		if((! strlen($target_item['allow_cid'])) && (! strlen($target_item['allow_gid']))
			&& (! strlen($target_item['deny_cid'])) && (! strlen($target_item['deny_gid']))
			&& (intval($target_item['pubmail']))) {
			$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `network` = '%s'",
				intval($uid),
				dbesc(NETWORK_MAIL)
			);
			if(count($r)) {
				foreach($r as $rr)
					$recipients[] = $rr['id'];
			}
		}
	}

	if($followup)
		$recip_str = $parent['contact-id'];
	else
		$recip_str = implode(', ', $recipients);

	if ($relocate)
		$r = $recipients_relocate;
	else
		$r = q("SELECT * FROM `contact` WHERE `id` IN ( %s ) AND `blocked` = 0 AND `pending` = 0 ",
			dbesc($recip_str)
		);


	require_once('include/salmon.php');

	$interval = ((get_config('system','delivery_interval') === false) ? 2 : intval(get_config('system','delivery_interval')));

	// If we are using the worker we don't need a delivery interval
	if (get_config("system", "worker"))
		$interval = false;

	// delivery loop

	if(count($r)) {

		foreach($r as $contact) {
			if((! $mail) && (! $fsuggest) && (! $followup) && (!$relocate) && (! $contact['self'])) {
				if(($contact['network'] === NETWORK_DIASPORA) && ($public_message))
					continue;
				q("insert into deliverq ( `cmd`,`item`,`contact` ) values ('%s', %d, %d )",
					dbesc($cmd),
					intval($item_id),
					intval($contact['id'])
				);
			}
		}


		// This controls the number of deliveries to execute with each separate delivery process.
		// By default we'll perform one delivery per process. Assuming a hostile shared hosting
		// provider, this provides the greatest chance of deliveries if processes start getting
		// killed. We can also space them out with the delivery_interval to also help avoid them
		// getting whacked.

		// If $deliveries_per_process > 1, we will chain this number of multiple deliveries
		// together into a single process. This will reduce the overall number of processes
		// spawned for each delivery, but they will run longer.

		// When using the workerqueue, we don't need this functionality.

		$deliveries_per_process = intval(get_config('system','delivery_batch_count'));
		if (($deliveries_per_process <= 0) OR get_config("system", "worker"))
			$deliveries_per_process = 1;

		$this_batch = array();

		for($x = 0; $x < count($r); $x ++) {
			$contact = $r[$x];

			if($contact['self'])
				continue;

			logger("Deliver ".$target_item["guid"]." to ".$contact['url'], LOGGER_DEBUG);

			// potentially more than one recipient. Start a new process and space them out a bit.
			// we will deliver single recipient types of message and email recipients here.

			if((! $mail) && (! $fsuggest) && (!$relocate) && (! $followup)) {

				$this_batch[] = $contact['id'];

				if(count($this_batch) == $deliveries_per_process) {
					proc_run('php','include/delivery.php',$cmd,$item_id,$this_batch);
					$this_batch = array();
					if($interval)
						@time_sleep_until(microtime(true) + (float) $interval);
				}
				continue;
			}
			// be sure to pick up any stragglers
			if(count($this_batch))
				proc_run('php','include/delivery.php',$cmd,$item_id,$this_batch);


			$deliver_status = 0;

			logger("main delivery by notifier: followup=$followup mail=$mail fsuggest=$fsuggest relocate=$relocate");

			switch($contact['network']) {
				case NETWORK_DFRN:

					// perform local delivery if we are on the same site

					$basepath =  implode('/', array_slice(explode('/',$contact['url']),0,3));

					if(link_compare($basepath,$a->get_baseurl())) {

						$nickname = basename($contact['url']);
						if($contact['issued-id'])
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
							WHERE `contact`.`blocked` = 0 AND `contact`.`archive` = 0
							AND `contact`.`pending` = 0
							AND `contact`.`network` = '%s' AND `user`.`nickname` = '%s'
							$sql_extra
							AND `user`.`account_expired` = 0 AND `user`.`account_removed` = 0 LIMIT 1",
							dbesc(NETWORK_DFRN),
							dbesc($nickname)
						);

						if($x && count($x)) {
							$write_flag = ((($x[0]['rel']) && ($x[0]['rel'] != CONTACT_IS_SHARING)) ? true : false);
							if((($owner['page-flags'] == PAGE_COMMUNITY) || ($write_flag)) && (! $x[0]['writable'])) {
								q("update contact set writable = 1 where id = %d",
									intval($x[0]['id'])
								);
								$x[0]['writable'] = 1;
							}

							// if contact's ssl policy changed, which we just determined
							// is on our own server, update our contact links

							$ssl_policy = get_config('system','ssl_policy');
							fix_contact_ssl_policy($x[0],$ssl_policy);

							// If we are setup as a soapbox we aren't accepting top level posts from this person

							if (($x[0]['page-flags'] == PAGE_SOAPBOX) AND $top_level)
								break;

							require_once('library/simplepie/simplepie.inc');
							logger('mod-delivery: local delivery');
							local_delivery($x[0],$atom);
							break;
						}
					}

					logger('notifier: dfrndelivery: ' . $contact['name']);
					$deliver_status = dfrn_deliver($owner,$contact,$atom);

					logger('notifier: dfrn_delivery returns ' . $deliver_status);

					if($deliver_status == (-1)) {
						logger('notifier: delivery failed: queuing message');
						// queue message for redelivery
						add_to_queue($contact['id'],NETWORK_DFRN,$atom);
					}
					break;
				case NETWORK_OSTATUS:

					// Do not send to ostatus if we are not configured to send to public networks
					if($owner['prvnets'])
						break;

					if(get_config('system','ostatus_disabled') || get_config('system','dfrn_only'))
						break;

					if($followup && $contact['notify']) {
						logger('slapdelivery followup item '.$item_id.' to ' . $contact['name']);
						$deliver_status = slapper($owner,$contact['notify'],$slap);

						if($deliver_status == (-1)) {
							// queue message for redelivery
							add_to_queue($contact['id'],NETWORK_OSTATUS,$slap);
						}
					} else {

						// only send salmon if public - e.g. if it's ok to notify
						// a public hub, it's ok to send a salmon

						if((count($slaps)) && ($public_message) && (! $expire)) {
							logger('slapdelivery item '.$item_id.' to ' . $contact['name']);
							foreach($slaps as $slappy) {
								if($contact['notify']) {
									$deliver_status = slapper($owner,$contact['notify'],$slappy);
									if($deliver_status == (-1)) {
										// queue message for redelivery
										add_to_queue($contact['id'],NETWORK_OSTATUS,$slappy);
									}
								}
							}
						}
					}
					break;

				case NETWORK_MAIL:
				case NETWORK_MAIL2:

					if(get_config('system','dfrn_only'))
						break;

					// WARNING: does not currently convert to RFC2047 header encodings, etc.

					$addr = $contact['addr'];
					if(! strlen($addr))
						break;

					if($cmd === 'wall-new' || $cmd === 'comment-new') {

						$it = null;
						if($cmd === 'wall-new') 
							$it = $items[0];
						else {
							$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1", 
								intval($argv[2]),
								intval($uid)
							);
							if(count($r))
								$it = $r[0];
						}
						if(! $it)
							break;



						$local_user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
							intval($uid)
						);
						if(! count($local_user))
							break;

						$reply_to = '';
						$r1 = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
							intval($uid)
						);
						if($r1 && $r1[0]['reply_to'])
							$reply_to = $r1[0]['reply_to'];

						$subject  = (($it['title']) ? email_header_encode($it['title'],'UTF-8') : t("\x28no subject\x29")) ;

						// only expose our real email address to true friends
						if(($contact['rel'] == CONTACT_IS_FRIEND) && (! $contact['blocked']))
							if($reply_to) {
								$headers  = 'From: ' . email_header_encode($local_user[0]['username'],'UTF-8') . ' <' . $reply_to . '>' . "\n";
								$headers .= 'Sender: '.$local_user[0]['email']."\n";
							} else
								$headers  = 'From: ' . email_header_encode($local_user[0]['username'],'UTF-8') . ' <' . $local_user[0]['email'] . '>' . "\n";
						else
							$headers  = 'From: ' . email_header_encode($local_user[0]['username'],'UTF-8') . ' <' . t('noreply') . '@' . $a->get_hostname() . '>' . "\n";

						//if($reply_to)
						//	$headers .= 'Reply-to: ' . $reply_to . "\n";

						$headers .= 'Message-Id: <' . iri2msgid($it['uri']) . '>' . "\n";

						if($it['uri'] !== $it['parent-uri']) {
							$headers .= "References: <".iri2msgid($it["parent-uri"]).">";

							// If Threading is enabled, write down the correct parent
							if (($it["thr-parent"] != "") and ($it["thr-parent"] != $it["parent-uri"]))
								$headers .= " <".iri2msgid($it["thr-parent"]).">";
							$headers .= "\n";

							if(!$it['title']) {
								$r = q("SELECT `title` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
									dbesc($it['parent-uri']),
									intval($uid));

								if(count($r) AND ($r[0]['title'] != ''))
									$subject = $r[0]['title'];
								else {
									$r = q("SELECT `title` FROM `item` WHERE `parent-uri` = '%s' AND `uid` = %d LIMIT 1",
										dbesc($it['parent-uri']),
										intval($uid));

									if(count($r) AND ($r[0]['title'] != ''))
										$subject = $r[0]['title'];
								}
							}
							if(strncasecmp($subject,'RE:',3))
								$subject = 'Re: '.$subject;
						}
						email_send($addr, $subject, $headers, $it);
					}
					break;
				case NETWORK_DIASPORA:
					if(get_config('system','dfrn_only') || (! get_config('system','diaspora_enabled')))
						break;

					if($mail) {
						diaspora_send_mail($item,$owner,$contact);
						break;
					}

					if(! $normal_mode)
						break;

					// special handling for followup to public post
					// all other public posts processed as public batches further below

					if($public_message) {
						if($followup)
							diaspora_send_followup($target_item,$owner,$contact, true);
						break;
					}

					if(! $contact['pubkey'])
						break;

					$unsupported_activities = array(ACTIVITY_DISLIKE, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE);

					//don't transmit activities which are not supported by diaspora
					foreach($unsupported_activities as $act) {
						if(activity_match($target_item['verb'],$act)) {
							break 2;
						}
					}

					if(($target_item['deleted']) && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
						// send both top-level retractions and relayable retractions for owner to relay
						diaspora_send_retraction($target_item,$owner,$contact);
						break;
					}
					elseif($followup) {
						// send comments and likes to owner to relay
						diaspora_send_followup($target_item,$owner,$contact);
						break;
					}
					elseif($target_item['uri'] !== $target_item['parent-uri']) {
						// we are the relay - send comments, likes and relayable_retractions
						// (of comments and likes) to our conversants
						diaspora_send_relay($target_item,$owner,$contact);
						break;
					}
					elseif(($top_level) && (! $walltowall)) {
						// currently no workable solution for sending walltowall
						diaspora_send_status($target_item,$owner,$contact);
						break;
					}

					break;

				case NETWORK_FEED:
				case NETWORK_FACEBOOK:
					if(get_config('system','dfrn_only'))
						break;
				case NETWORK_PUMPIO:
					if(get_config('system','dfrn_only'))
						break;
				default:
					break;
			}
		}
	}

	// send additional slaps to mentioned remote tags (@foo@example.com)

	//if($slap && count($url_recipients) && ($followup || $top_level) && ($public_message || $push_notify) && (! $expire)) {
	if($slap && count($url_recipients) && ($public_message || $push_notify) && (!$expire)) {
		if(! get_config('system','dfrn_only')) {
			foreach($url_recipients as $url) {
				if($url) {
					logger('notifier: urldelivery: ' . $url);
					$deliver_status = slapper($owner,$url,$slap);
					// TODO: redeliver/queue these items on failure, though there is no contact record
				}
			}
		}
	}


	if($public_message) {

		if (!$followup)
			$r0 = diaspora_fetch_relay();
		else
			$r0 = array();

		$r1 = q("SELECT DISTINCT(`batch`), `id`, `name`,`network` FROM `contact` WHERE `network` = '%s'
			AND `uid` = %d AND `rel` != %d group by `batch` ORDER BY rand() ",
			dbesc(NETWORK_DIASPORA),
			intval($owner['uid']),
			intval(CONTACT_IS_SHARING)
		);

		$r2 = q("SELECT `id`, `name`,`network` FROM `contact`
			WHERE `network` in ( '%s', '%s')  AND `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0
			AND `rel` != %d order by rand() ",
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_MAIL2),
			intval($owner['uid']),
			intval(CONTACT_IS_SHARING)
		);

		$r = array_merge($r2,$r1,$r0);

		if(count($r)) {
			logger('pubdeliver: ' . print_r($r,true), LOGGER_DEBUG);

			// throw everything into the queue in case we get killed

			foreach($r as $rr) {
				if((! $mail) && (! $fsuggest) && (! $followup)) {
					q("insert into deliverq ( `cmd`,`item`,`contact` ) values ('%s', %d, %d )",
						dbesc($cmd),
						intval($item_id),
						intval($rr['id'])
					);
				}
			}

			foreach($r as $rr) {

				// except for Diaspora batch jobs
				// Don't deliver to folks who have already been delivered to

				if(($rr['network'] !== NETWORK_DIASPORA) && (in_array($rr['id'],$conversants))) {
					logger('notifier: already delivered id=' . $rr['id']);
					continue;
				}

				if((! $mail) && (! $fsuggest) && (! $followup)) {
					logger('notifier: delivery agent: ' . $rr['name'] . ' ' . $rr['id']);
					proc_run('php','include/delivery.php',$cmd,$item_id,$rr['id']);
					if($interval)
						@time_sleep_until(microtime(true) + (float) $interval);
				}
			}
		}

		$push_notify = true;

	}


	if($push_notify AND strlen($hub)) {
		$hubs = explode(',', $hub);
		if(count($hubs)) {
			foreach($hubs as $h) {
				$h = trim($h);
				if(! strlen($h))
					continue;

				if ($h === '[internal]') {
					// Set push flag for PuSH subscribers to this topic,
					// they will be notified in queue.php
					q("UPDATE `push_subscriber` SET `push` = 1 " .
					  "WHERE `nickname` = '%s'", dbesc($owner['nickname']));

					logger('Activating internal PuSH for item '.$item_id, LOGGER_DEBUG);

				} else {

					$params = 'hub.mode=publish&hub.url=' . urlencode( $a->get_baseurl() . '/dfrn_poll/' . $owner['nickname'] );
					post_url($h,$params);
					logger('publish for item '.$item_id.' ' . $h . ' ' . $params . ' returned ' . $a->get_curl_code());
				}
				if(count($hubs) > 1)
					sleep(7);				// try and avoid multiple hubs responding at precisely the same time
			}
		}

		// Handling the pubsubhubbub requests
		proc_run('php','include/pubsubpublish.php');
	}

	// If the item was deleted, clean up the `sign` table
	if($target_item['deleted']) {
		$r = q("DELETE FROM sign where `retract_iid` = %d",
			intval($target_item['id'])
		);
	}

	logger('notifier: calling hooks', LOGGER_DEBUG);

	if($normal_mode)
		call_hooks('notifier_normal',$target_item);

	call_hooks('notifier_end',$target_item);

	return;
}


if (array_search(__file__,get_included_files())===0){
	notifier_run($_SERVER["argv"],$_SERVER["argc"]);
	killme();
}
