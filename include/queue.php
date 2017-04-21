<?php

use \Friendica\Core\Config;

require_once('include/queue_fn.php');
require_once('include/dfrn.php');
require_once("include/datetime.php");
require_once('include/items.php');
require_once('include/bbcode.php');
require_once('include/socgraph.php');
require_once('include/cache.php');

function queue_run(&$argv, &$argc){
	global $a;

	if ($argc > 1) {
		$queue_id = intval($argv[1]);
	} else {
		$queue_id = 0;
	}

	$cachekey_deadguy = 'queue_run:deadguy:';
	$cachekey_server = 'queue_run:server:';

	if (!$queue_id) {

		logger('queue: start');

		// Handling the pubsubhubbub requests
		proc_run(PRIORITY_HIGH,'include/pubsubpublish.php');

		$r = q("SELECT `queue`.*, `contact`.`name`, `contact`.`uid` FROM `queue`
			INNER JOIN `contact` ON `queue`.`cid` = `contact`.`id`
			WHERE `queue`.`created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
		if ($r) {
			foreach ($r as $rr) {
				logger('Removing expired queue item for ' . $rr['name'] . ', uid=' . $rr['uid']);
				logger('Expired queue data: ' . $rr['content'], LOGGER_DATA);
			}
			q("DELETE FROM `queue` WHERE `created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
		}

		// For the first 12 hours we'll try to deliver every 15 minutes
		// After that, we'll only attempt delivery once per hour.

		$r = q("SELECT `id` FROM `queue` WHERE ((`created` > UTC_TIMESTAMP() - INTERVAL 12 HOUR && `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE) OR (`last` < UTC_TIMESTAMP() - INTERVAL 1 HOUR)) ORDER BY `cid`, `created`");

		call_hooks('queue_predeliver', $a, $r);

		if (dbm::is_result($r)) {
			foreach ($r as $q_item) {
				logger('Call queue for id '.$q_item['id']);
				proc_run(PRIORITY_LOW, "include/queue.php", $q_item['id']);
			}
		}
		return;
	}


	// delivering

	require_once('include/salmon.php');
	require_once('include/diaspora.php');

	$r = q("SELECT * FROM `queue` WHERE `id` = %d LIMIT 1",
		intval($queue_id));

	if (!dbm::is_result($r)) {
		return;
	}

	$q_item = $r[0];

	$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
		intval($q_item['cid'])
	);

	if (!dbm::is_result($c)) {
		remove_queue_item($q_item['id']);
		return;
	}

	$dead = Cache::get($cachekey_deadguy.$c[0]['notify']);

	if (!is_null($dead) AND $dead) {
		logger('queue: skipping known dead url: '.$c[0]['notify']);
		update_queue_time($q_item['id']);
		return;
	}

	$server = poco_detect_server($c[0]['url']);

	if ($server != "") {
		$vital = Cache::get($cachekey_server.$server);

		if (is_null($vital)) {
			logger("Check server ".$server." (".$c[0]["network"].")");

			$vital = poco_check_server($server, $c[0]["network"], true);
			Cache::set($cachekey_server.$server, $vital, CACHE_QUARTER_HOUR);
		}

		if (!is_null($vital) AND !$vital) {
			logger('queue: skipping dead server: '.$server);
			update_queue_time($q_item['id']);
			return;
		}
	}

	$u = q("SELECT `user`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey`
		FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($c[0]['uid'])
	);
	if (!dbm::is_result($u)) {
		remove_queue_item($q_item['id']);
		return;
	}

	$data      = $q_item['content'];
	$public    = $q_item['batch'];
	$contact   = $c[0];
	$owner     = $u[0];

	$deliver_status = 0;

	switch ($contact['network']) {
		case NETWORK_DFRN:
			logger('queue: dfrndelivery: item '.$q_item['id'].' for '.$contact['name'].' <'.$contact['url'].'>');
			$deliver_status = dfrn::deliver($owner, $contact, $data);

			if ($deliver_status == (-1)) {
				update_queue_time($q_item['id']);
				Cache::set($cachekey_deadguy.$contact['notify'], true, CACHE_QUARTER_HOUR);
			} else {
				remove_queue_item($q_item['id']);
			}
			break;
		case NETWORK_OSTATUS:
			if ($contact['notify']) {
				logger('queue: slapdelivery: item '.$q_item['id'].' for '.$contact['name'].' <'.$contact['url'].'>');
				$deliver_status = slapper($owner, $contact['notify'], $data);

				if ($deliver_status == (-1)) {
					update_queue_time($q_item['id']);
					Cache::set($cachekey_deadguy.$contact['notify'], true, CACHE_QUARTER_HOUR);
				} else {
					remove_queue_item($q_item['id']);
				}
			}
			break;
		case NETWORK_DIASPORA:
			if ($contact['notify']) {
				logger('queue: diaspora_delivery: item '.$q_item['id'].' for '.$contact['name'].' <'.$contact['url'].'>');
				$deliver_status = Diaspora::transmit($owner, $contact, $data, $public, true);

				if ($deliver_status == (-1)) {
					update_queue_time($q_item['id']);
					Cache::set($cachekey_deadguy.$contact['notify'], true, CACHE_QUARTER_HOUR);
				} else {
					remove_queue_item($q_item['id']);
				}
			}
			break;

		default:
			$params = array('owner' => $owner, 'contact' => $contact, 'queue' => $q_item, 'result' => false);
			call_hooks('queue_deliver', $a, $params);

			if ($params['result']) {
				remove_queue_item($q_item['id']);
			} else {
				update_queue_time($q_item['id']);
			}
			break;

	}
	logger('Deliver status '.(int)$deliver_status.' for item '.$q_item['id'].' to '.$contact['name'].' <'.$contact['url'].'>');

	return;
}
