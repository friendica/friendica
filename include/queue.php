<?php

use \Friendica\Core\Config;

require_once("boot.php");
require_once('include/queue_fn.php');
require_once('include/dfrn.php');

function queue_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}

	if(is_null($db)){
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	require_once("include/session.php");
	require_once("include/datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');
	require_once('include/socgraph.php');

	Config::load();

	// Don't check this stuff if the function is called by the poller
	if (App::callstack() != "poller_run")
		if (App::is_already_running('queue', 'include/queue.php', 540))
			return;

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	if($argc > 1)
		$queue_id = intval($argv[1]);
	else
		$queue_id = 0;

	$deadguys = array();
	$deadservers = array();
	$serverlist = array();

	if (!$queue_id) {

		logger('queue: start');

		// Handling the pubsubhubbub requests
		proc_run(PRIORITY_HIGH,'include/pubsubpublish.php');

		$interval = ((get_config('system','delivery_interval') === false) ? 2 : intval(get_config('system','delivery_interval')));

		// If we are using the worker we don't need a delivery interval
		if (get_config("system", "worker"))
			$interval = false;

		$r = q("select * from deliverq where 1");
		if ($r) {
			foreach ($r as $rr) {
				logger('queue: deliverq');
				proc_run(PRIORITY_HIGH,'include/delivery.php',$rr['cmd'],$rr['item'],$rr['contact']);
				if($interval) {
					time_sleep_until(microtime(true) + (float) $interval);
				}
			}
		}

		$r = q("SELECT `queue`.*, `contact`.`name`, `contact`.`uid` FROM `queue`
			INNER JOIN `contact` ON `queue`.`cid` = `contact`.`id`
			WHERE `queue`.`created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
		if ($r) {
			foreach ($r as $rr) {
				logger('Removing expired queue item for ' . $rr['name'] . ', uid=' . $rr['uid']);
				logger('Expired queue data :' . $rr['content'], LOGGER_DATA);
			}
			q("DELETE FROM `queue` WHERE `created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
		}

		// For the first 12 hours we'll try to deliver every 15 minutes
		// After that, we'll only attempt delivery once per hour.

		$r = q("SELECT `id` FROM `queue` WHERE ((`created` > UTC_TIMESTAMP() - INTERVAL 12 HOUR && `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE) OR (`last` < UTC_TIMESTAMP() - INTERVAL 1 HOUR)) ORDER BY `cid`, `created`");
	} else {
		logger('queue: start for id '.$queue_id);

		$r = q("SELECT `id` FROM `queue` WHERE `id` = %d LIMIT 1",
			intval($queue_id)
		);
	}

	if (!$r){
		return;
	}

	if (!$queue_id)
		call_hooks('queue_predeliver', $a, $r);


	// delivery loop

	require_once('include/salmon.php');
	require_once('include/diaspora.php');

	foreach($r as $q_item) {

		// queue_predeliver hooks may have changed the queue db details,
		// so check again if this entry still needs processing

		if($queue_id)
			$qi = q("SELECT * FROM `queue` WHERE `id` = %d LIMIT 1",
				intval($queue_id));
		elseif (get_config("system", "worker")) {
			logger('Call queue for id '.$q_item['id']);
			proc_run(PRIORITY_LOW, "include/queue.php", $q_item['id']);
			continue;
		} else
			$qi = q("SELECT * FROM `queue` WHERE `id` = %d AND `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ",
				intval($q_item['id']));

		if(! count($qi))
			continue;


		$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($qi[0]['cid'])
		);
		if (! dbm::is_result($c)) {
			remove_queue_item($q_item['id']);
			continue;
		}
		if(in_array($c[0]['notify'],$deadguys)) {
			logger('queue: skipping known dead url: ' . $c[0]['notify']);
			update_queue_time($q_item['id']);
			continue;
		}

		$server = poco_detect_server($c[0]['url']);

		if (($server != "") AND !in_array($server, $serverlist)) {
			logger("Check server ".$server." (".$c[0]["network"].")");
			if (!poco_check_server($server, $c[0]["network"], true))
				$deadservers[] = $server;

			$serverlist[] = $server;
		}

		if (($server != "") AND in_array($server, $deadservers)) {
			logger('queue: skipping known dead server: '.$server);
			update_queue_time($q_item['id']);
			continue;
		}

		$u = q("SELECT `user`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`
			FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($c[0]['uid'])
		);
		if (! dbm::is_result($u)) {
			remove_queue_item($q_item['id']);
			continue;
		}

		$data      = $qi[0]['content'];
		$public    = $qi[0]['batch'];
		$contact   = $c[0];
		$owner     = $u[0];

		$deliver_status = 0;

		switch($contact['network']) {
			case NETWORK_DFRN:
				logger('queue: dfrndelivery: item '.$q_item['id'].' for '.$contact['name'].' <'.$contact['url'].'>');
				$deliver_status = dfrn::deliver($owner,$contact,$data);

				if($deliver_status == (-1)) {
					update_queue_time($q_item['id']);
					$deadguys[] = $contact['notify'];
				} else
					remove_queue_item($q_item['id']);

				break;
			case NETWORK_OSTATUS:
				if($contact['notify']) {
					logger('queue: slapdelivery: item '.$q_item['id'].' for '.$contact['name'].' <'.$contact['url'].'>');
					$deliver_status = slapper($owner,$contact['notify'],$data);

					if($deliver_status == (-1)) {
						update_queue_time($q_item['id']);
						$deadguys[] = $contact['notify'];
					} else
						remove_queue_item($q_item['id']);
				}
				break;
			case NETWORK_DIASPORA:
				if($contact['notify']) {
					logger('queue: diaspora_delivery: item '.$q_item['id'].' for '.$contact['name'].' <'.$contact['url'].'>');
					$deliver_status = Diaspora::transmit($owner,$contact,$data,$public,true);

					if($deliver_status == (-1)) {
						update_queue_time($q_item['id']);
						$deadguys[] = $contact['notify'];
					} else
						remove_queue_item($q_item['id']);

				}
				break;

			default:
				$params = array('owner' => $owner, 'contact' => $contact, 'queue' => $q_item, 'result' => false);
				call_hooks('queue_deliver', $a, $params);

				if($params['result'])
					remove_queue_item($q_item['id']);
				else
					update_queue_time($q_item['id']);

				break;

		}
		logger('Deliver status '.$deliver_status.' for item '.$q_item['id'].' to '.$contact['name'].' <'.$contact['url'].'>');
	}

	return;

}

if (array_search(__file__,get_included_files())===0){
  queue_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
