<?php
/**
 * @file src/Worker/Expire.php
 * @brief Expires old item entries
 */

namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';

class Expire {
	public static function execute($param = '', $hook_name = '') {
		global $a;

		require_once 'include/datetime.php';
		require_once 'include/items.php';

		Addon::loadHooks();

		if ($param == 'delete') {
			logger('Delete expired items', LOGGER_DEBUG);
			// physically remove anything that has been deleted for more than two months
			$r = dba::p("SELECT `id` FROM `item` WHERE `deleted` AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY");
			while ($row = dba::fetch($r)) {
				dba::delete('item', ['id' => $row['id']]);
			}
			dba::close($r);

			logger('Delete expired items - done', LOGGER_DEBUG);

			// make this optional as it could have a performance impact on large sites
			if (intval(Config::get('system', 'optimize_items'))) {
				dba::e("OPTIMIZE TABLE `item`");
			}
			return;
		} elseif (intval($param) > 0) {
			$user = dba::selectFirst('user', ['uid', 'username', 'expire'], ['uid' => $param]);
			if (DBM::is_result($user)) {
				logger('Expire items for user '.$user['uid'].' ('.$user['username'].') - interval: '.$user['expire'], LOGGER_DEBUG);
				item_expire($user['uid'], $user['expire']);
				logger('Expire items for user '.$user['uid'].' ('.$user['username'].') - done ', LOGGER_DEBUG);
			}
			return;
		} elseif (!empty($hook_name) && ($param == 'hook') && is_array($a->hooks) && array_key_exists("expire", $a->hooks)) {
			foreach ($a->hooks["expire"] as $hook) {
				if ($hook[1] == $hook_name) {
					logger("Calling expire hook '" . $hook[1] . "'", LOGGER_DEBUG);
					Addon::callSingleHook($a, $hook_name, $hook, $data);
				}
			}
			return;
		}

		logger('expire: start');

		Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
				'Expire', 'delete');

		$r = dba::p("SELECT `uid`, `username` FROM `user` WHERE `expire` != 0");
		while ($row = dba::fetch($r)) {
			logger('Calling expiry for user '.$row['uid'].' ('.$row['username'].')', LOGGER_DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'Expire', (int)$row['uid']);
		}
		dba::close($r);

		logger('expire: calling hooks');

		if (is_array($a->hooks) && array_key_exists('expire', $a->hooks)) {
			foreach ($a->hooks['expire'] as $hook) {
				logger("Calling expire hook for '" . $hook[1] . "'", LOGGER_DEBUG);
				Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
						'Expire', 'hook', $hook[1]);
			}
		}

		logger('expire: end');

		return;
	}
}
