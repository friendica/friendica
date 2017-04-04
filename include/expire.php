<?php

use Friendica\Core\Config;

function expire_run(&$argv, &$argc){
	global $a;

	require_once 'include/datetime.php';
	require_once 'include/items.php';
	require_once 'include/Contact.php';

	load_hooks();

	$r = q("DELETE FROM `item` WHERE `deleted` = 1 AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY");

	logger('Delete expired items - done', LOGGER_DEBUG);

	if (intval(get_config('system', 'optimize_items'))) {
		q("OPTIMIZE TABLE `item`");
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
