<?php

require_once("boot.php");


function poller_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}
  
	if(is_null($db)) {
	    @include(".htconfig.php");
    	require_once("include/dba.php");
	    $db = new dba($db_host, $db_user, $db_pass, $db_data);
    	unset($db_host, $db_user, $db_pass, $db_data);
  	};


	require_once('include/session.php');
	require_once('include/datetime.php');
	require_once('library/simplepie/simplepie.inc');
	require_once('include/items.php');
	require_once('include/Contact.php');
	require_once('include/email.php');
	require_once('include/socgraph.php');
	require_once('include/pidfile.php');

	load_config('config');
	load_config('system');

	$maxsysload = intval(get_config('system','maxloadavg'));
	if($maxsysload < 1)
		$maxsysload = 50;
	if(function_exists('sys_getloadavg')) {
		$load = sys_getloadavg();
		if(intval($load[0]) > $maxsysload) {
			logger('system: load ' . $load . ' too high. Poller deferred to next scheduled run.');
			return;
		}
	}

	$lockpath = get_config('system','lockpath');
	if ($lockpath != '') {
		$pidfile = new pidfile($lockpath, 'poller.lck');
		if($pidfile->is_already_running()) {
			logger("poller: Already running");
			exit;
		}
	}



	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	logger('poller: start');
	
	// run queue delivery process in the background

	proc_run('php',"include/queue.php");
	
	// expire any expired accounts

	q("UPDATE user SET `account_expired` = 1 where `account_expired` = 0 
		AND `account_expires_on` != '0000-00-00 00:00:00' 
		AND `account_expires_on` < UTC_TIMESTAMP() ");
	
	// delete user and contact records for recently removed accounts

	$r = q("SELECT * FROM `user` WHERE `account_removed` = 1 AND `account_expires_on` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	if ($r) {
		foreach($r as $user) {
			q("DELETE FROM `contact` WHERE `uid` = %d", intval($user['uid']));
			q("DELETE FROM `user` WHERE `uid` = %d", intval($user['uid']));
		}
	}
  
	$abandon_days = intval(get_config('system','account_abandon_days'));
	if($abandon_days < 1)
		$abandon_days = 0;

	

	// once daily run birthday_updates and then expire in background

	$d1 = get_config('system','last_expire_day');
	$d2 = intval(datetime_convert('UTC','UTC','now','d'));

	if($d2 != intval($d1)) {

		update_contact_birthdays();

		update_suggestions();

		set_config('system','last_expire_day',$d2);
		proc_run('php','include/expire.php');
	}

	$last = get_config('system','cache_last_cleared');

 	if($last) {
		$next = $last + (3600); // Once per hour
		$clear_cache = ($next <= time());
        } else
		$clear_cache = true;

	if ($clear_cache) {
		// clear old cache
		Cache::clear();

		// clear old item cache files
		clear_cache();

		// clear cache for photos
		clear_cache($a->get_basepath(), $a->get_basepath()."/photo");

		set_config('system','cache_last_cleared', time());
	}

	$manual_id  = 0;
	$generation = 0;
	$hub_update = false;
	$force      = false;
	$restart    = false;

	if(($argc > 1) && ($argv[1] == 'force'))
		$force = true;

	if(($argc > 1) && ($argv[1] == 'restart')) {
		$restart = true;
		$generation = intval($argv[2]);
		if(! $generation)
			killme();
	}

	if(($argc > 1) && intval($argv[1])) {
		$manual_id = intval($argv[1]);
		$force     = true;
	}

	$interval = intval(get_config('system','poll_interval'));
	if(! $interval) 
		$interval = ((get_config('system','delivery_interval') === false) ? 3 : intval(get_config('system','delivery_interval')));

	$sql_extra = (($manual_id) ? " AND `id` = $manual_id " : "");

	reload_plugins();

	$d = datetime_convert();

	if(! $restart)
		proc_run('php','include/cronhooks.php');

	// Only poll from those with suitable relationships,
	// and which have a polling address and ignore Diaspora since 
	// we are unable to match those posts with a Diaspora GUID and prevent duplicates.

	$abandon_sql = (($abandon_days) 
		? sprintf(" AND `user`.`login_date` > UTC_TIMESTAMP() - INTERVAL %d DAY ", intval($abandon_days)) 
		: '' 
	);

	$contacts = q("SELECT `contact`.`id` FROM `contact` LEFT JOIN `user` ON `user`.`uid` = `contact`.`uid` 
		WHERE ( `rel` = %d OR `rel` = %d ) AND `poll` != ''
		AND NOT `network` IN ( '%s', '%s' )
		$sql_extra 
		AND `self` = 0 AND `contact`.`blocked` = 0 AND `contact`.`readonly` = 0 
		AND `contact`.`archive` = 0 
		AND `user`.`account_expired` = 0 AND `user`.`account_removed` = 0 $abandon_sql ORDER BY RAND()",
		intval(CONTACT_IS_SHARING),
		intval(CONTACT_IS_FRIEND),
		dbesc(NETWORK_DIASPORA),
		dbesc(NETWORK_FACEBOOK)
	);

	if(! count($contacts)) {
		return;
	}

	foreach($contacts as $c) {

		$res = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($c['id'])
		);

		if((! $res) || (! count($res)))
			continue;

		foreach($res as $contact) {

			$xml = false;

			if($manual_id)
				$contact['last-update'] = '0000-00-00 00:00:00';

			if($contact['network'] === NETWORK_DFRN)
				$contact['priority'] = 2;

			if(!get_config('system','ostatus_use_priority') and ($contact['network'] === NETWORK_OSTATUS))
				$contact['priority'] = 2;

			if($contact['priority'] || $contact['subhub']) {

				$hub_update = true;
				$update     = false;

				$t = $contact['last-update'];

				// We should be getting everything via a hub. But just to be sure, let's check once a day.
				// (You can make this more or less frequent if desired by setting 'pushpoll_frequency' appropriately)
				// This also lets us update our subscription to the hub, and add or replace hubs in case it
				// changed. We will only update hubs once a day, regardless of 'pushpoll_frequency'. 


				if($contact['subhub']) {
					$poll_interval = get_config('system','pushpoll_frequency');
					$contact['priority'] = (($poll_interval !== false) ? intval($poll_interval) : 3);
					$hub_update = false;
	
					if((datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day")) || $force)
							$hub_update = true;
				}
				else
					$hub_update = false;

				/**
				 * Based on $contact['priority'], should we poll this site now? Or later?
				 */			

				switch ($contact['priority']) {
					case 5:
						if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 month"))
							$update = true;
						break;					
					case 4:
						if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 week"))
							$update = true;
						break;
					case 3:
						if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 day"))
							$update = true;
						break;
					case 2:
						if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 12 hour"))
							$update = true;
						break;
					case 1:
					default:
						if(datetime_convert('UTC','UTC', 'now') > datetime_convert('UTC','UTC', $t . " + 1 hour"))
							$update = true;
						break;
				}
				if((! $update) && (! $force))
					continue;
			}

			proc_run('php','include/onepoll.php',$contact['id']);
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  poller_run($argv,$argc);
  killme();
}
