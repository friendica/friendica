<?php
/**
 * @file src/Worker/Expire.php
 * @brief Expires old item entries
 */

namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;

class Expire
{
	public static function execute($param = '', $hook_function = '')
	{
		$a = DI::app();

		Hook::loadHooks();

		if ($param == 'delete') {
			Logger::log('Delete expired items', Logger::DEBUG);
			// physically remove anything that has been deleted for more than two months
			$condition = ["`deleted` AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY"];
			$rows = DBA::select('item', ['id'],  $condition);
			while ($row = DBA::fetch($rows)) {
				DBA::delete('item', ['id' => $row['id']]);
			}
			DBA::close($rows);

			// Normally we shouldn't have orphaned data at all.
			// If we do have some, then we have to check why.
			Logger::log('Deleting orphaned item activities - start', Logger::DEBUG);
			$condition = ["NOT EXISTS (SELECT `iaid` FROM `item` WHERE `item`.`iaid` = `item-activity`.`id`)"];
			DBA::delete('item-activity', $condition);
			Logger::log('Orphaned item activities deleted: ' . DBA::affectedRows(), Logger::DEBUG);

			Logger::log('Deleting orphaned item content - start', Logger::DEBUG);
			$condition = ["NOT EXISTS (SELECT `icid` FROM `item` WHERE `item`.`icid` = `item-content`.`id`)"];
			DBA::delete('item-content', $condition);
			Logger::log('Orphaned item content deleted: ' . DBA::affectedRows(), Logger::DEBUG);

			// make this optional as it could have a performance impact on large sites
			if (intval(Config::get('system', 'optimize_items'))) {
				DBA::e("OPTIMIZE TABLE `item`");
			}

			Logger::log('Delete expired items - done', Logger::DEBUG);
			return;
		} elseif (intval($param) > 0) {
			$user = DBA::selectFirst('user', ['uid', 'username', 'expire'], ['uid' => $param]);
			if (DBA::isResult($user)) {
				Logger::log('Expire items for user '.$user['uid'].' ('.$user['username'].') - interval: '.$user['expire'], Logger::DEBUG);
				Item::expire($user['uid'], $user['expire']);
				Logger::log('Expire items for user '.$user['uid'].' ('.$user['username'].') - done ', Logger::DEBUG);
			}
			return;
		} elseif ($param == 'hook' && !empty($hook_function)) {
			foreach (Hook::getByName('expire') as $hook) {
				if ($hook[1] == $hook_function) {
					Logger::log("Calling expire hook '" . $hook[1] . "'", Logger::DEBUG);
					Hook::callSingle($a, 'expire', $hook, $data);
				}
			}
			return;
		}

		Logger::log('expire: start');

		Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
				'Expire', 'delete');

		$r = DBA::p("SELECT `uid`, `username` FROM `user` WHERE `expire` != 0");
		while ($row = DBA::fetch($r)) {
			Logger::log('Calling expiry for user '.$row['uid'].' ('.$row['username'].')', Logger::DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'Expire', (int)$row['uid']);
		}
		DBA::close($r);

		Logger::log('expire: calling hooks');
		foreach (Hook::getByName('expire') as $hook) {
			Logger::log("Calling expire hook for '" . $hook[1] . "'", Logger::DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'Expire', 'hook', $hook[1]);
		}

		Logger::log('expire: end');

		return;
	}
}
