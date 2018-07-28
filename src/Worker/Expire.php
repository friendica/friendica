<?php
/**
 * @file src/Worker/Expire.php
 * @brief Expires old item entries
 */

namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Item;

require_once 'include/dba.php';

class Expire
{
	public static function execute($param = '', $hook_name = '')
	{
		$a = BaseObject::getApp();

		require_once 'include/items.php';

		Addon::loadHooks();

		if ($param == 'delete') {
			logger('Delete expired items', LOGGER_DEBUG);
			// physically remove anything that has been deleted for more than two months
			$condition = ["`deleted` AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY"];
			$rows = DBA::select('item', ['id'],  $condition);
			while ($row = DBA::fetch($rows)) {
				DBA::delete('item', ['id' => $row['id']]);
			}
			DBA::close($rows);

			// Normally we shouldn't have orphaned data at all.
			// If we do have some, then we have to check why.
			logger('Deleting orphaned item activities - start', LOGGER_DEBUG);
			$condition = ["NOT EXISTS (SELECT `iaid` FROM `item` WHERE `item`.`iaid` = `item-activity`.`id`)"];
			DBA::delete('item-activity', $condition);
			logger('Orphaned item activities deleted: ' . DBA::affectedRows(), LOGGER_DEBUG);

			logger('Deleting orphaned item content - start', LOGGER_DEBUG);
			$condition = ["NOT EXISTS (SELECT `icid` FROM `item` WHERE `item`.`icid` = `item-content`.`id`)"];
			DBA::delete('item-content', $condition);
			logger('Orphaned item content deleted: ' . DBA::affectedRows(), LOGGER_DEBUG);

			// make this optional as it could have a performance impact on large sites
			if (intval(Config::get('system', 'optimize_items'))) {
				DBA::e("OPTIMIZE TABLE `item`");
			}

			logger('Delete expired items - done', LOGGER_DEBUG);
			return;
		} elseif (intval($param) > 0) {
			$user = DBA::selectFirst('user', ['uid', 'username', 'expire'], ['uid' => $param]);
			if (DBA::isResult($user)) {
				logger('Expire items for user '.$user['uid'].' ('.$user['username'].') - interval: '.$user['expire'], LOGGER_DEBUG);
				Item::expire($user['uid'], $user['expire']);
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

		$r = DBA::p("SELECT `uid`, `username` FROM `user` WHERE `expire` != 0");
		while ($row = DBA::fetch($r)) {
			logger('Calling expiry for user '.$row['uid'].' ('.$row['username'].')', LOGGER_DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'Expire', (int)$row['uid']);
		}
		DBA::close($r);

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
