<?php

use Friendica\Core\Config;

function expire_run(&$argv, &$argc){
	global $a;

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/Contact.php');

	load_hooks();

	if (($argc == 2) && ($argv[1] == 'delete')) {
		logger('Delete expired items', LOGGER_DEBUG);
		// physically remove anything that has been deleted for more than two months
		$r = dba::p("SELECT `id` FROM `item` WHERE `deleted` AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY");
		while ($row = dba::fetch($r)) {
			dba::delete('item', array('id' => $row['id']));
		}
		dba::close($r);

		logger('Delete expired items - done', LOGGER_DEBUG);

		// make this optional as it could have a performance impact on large sites
		if (intval(get_config('system', 'optimize_items'))) {
			q("OPTIMIZE TABLE `item`");
		}
		return;
	} elseif (($argc == 2) && (intval($argv[1]) > 0)) {
		$user = dba::select('user', array('uid', 'username', 'expire'), array('uid' => $argv[1]), array('limit' => 1));
		if (dbm::is_result($user)) {
			logger('Expire items for user '.$user['uid'].' ('.$user['username'].') - interval: '.$user['expire'], LOGGER_DEBUG);
			item_expire($user['uid'], $user['expire']);
			logger('Expire items for user '.$user['uid'].' ('.$user['username'].') - done ', LOGGER_DEBUG);
		}
		return;
	} elseif (($argc == 3) && ($argv[1] == 'hook') && is_array($a->hooks) && array_key_exists("expire", $a->hooks)) {
		foreach ($a->hooks["expire"] as $hook) {
			if ($hook[1] == $argv[2]) {
				logger("Calling expire hook '" . $hook[1] . "'", LOGGER_DEBUG);
				call_single_hook($a, $name, $hook, $data);
			}
		}
		return;
	}

	logger('expire: start');

	proc_run(array('priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true),
			'include/expire.php', 'delete');

	$r = dba::p("SELECT `uid`, `username` FROM `user` WHERE `expire` != 0");
	while ($row = dba::fetch($r)) {
		logger('Calling expiry for user '.$row['uid'].' ('.$row['username'].')', LOGGER_DEBUG);
		proc_run(array('priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true),
				'include/expire.php', (int)$row['uid']);
	}
	dba::close($r);

	logger('expire: calling hooks');

	if (is_array($a->hooks) && array_key_exists('expire', $a->hooks)) {
		foreach ($a->hooks['expire'] as $hook) {
			logger("Calling expire hook for '" . $hook[1] . "'", LOGGER_DEBUG);
			proc_run(array('priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true),
					'include/expire.php', 'hook', $hook[1]);
		}
	}

	logger('expire: end');

	return;
}
