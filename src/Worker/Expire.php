<?php
/**
 * @file src/Worker/Expire.php
 * @brief Expires old item entries
 */

namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Item;

class Expire extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function execute(array $parameters = [])
	{
		$param         = isset($parameters[0]) ? $parameters[0] : '';
		$hook_function = isset($parameters[1]) ? $parameters[1] : '';

		Hook::loadHooks();

		if ($param == 'delete') {
			$this->logger->info('Delete expired items');
			// physically remove anything that has been deleted for more than two months
			$condition = ["`deleted` AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY"];
			$rows = DBA::select('item', ['id'],  $condition);
			while ($row = DBA::fetch($rows)) {
				DBA::delete('item', ['id' => $row['id']]);
			}
			DBA::close($rows);

			// Normally we shouldn't have orphaned data at all.
			// If we do have some, then we have to check why.
			$this->logger->info('Deleting orphaned item activities - start');
			$condition = ["NOT EXISTS (SELECT `iaid` FROM `item` WHERE `item`.`iaid` = `item-activity`.`id`)"];
			DBA::delete('item-activity', $condition);
			$this->logger->info('Orphaned item activities deleted: ' . DBA::affectedRows());

			$this->logger->info('Deleting orphaned item content - start');
			$condition = ["NOT EXISTS (SELECT `icid` FROM `item` WHERE `item`.`icid` = `item-content`.`id`)"];
			DBA::delete('item-content', $condition);
			$this->logger->info('Orphaned item content deleted: ' . DBA::affectedRows());

			// make this optional as it could have a performance impact on large sites
			if (intval(Config::get('system', 'optimize_items'))) {
				DBA::e("OPTIMIZE TABLE `item`");
			}

			$this->logger->info('Delete expired items - done');
			return;
		} elseif (intval($param) > 0) {
			$user = DBA::selectFirst('user', ['uid', 'username', 'expire'], ['uid' => $param]);
			if (DBA::isResult($user)) {
				$this->logger->info('Expire items for user '.$user['uid'].' ('.$user['username'].') - interval: '.$user['expire']);
				Item::expire($user['uid'], $user['expire']);
				$this->logger->info('Expire items for user '.$user['uid'].' ('.$user['username'].') - done ');
			}
			return;
		} elseif ($param == 'hook' && !empty($hook_function)) {
			foreach (Hook::getByName('expire') as $hook) {
				if ($hook[1] == $hook_function) {
					$this->logger->info("Calling expire hook '" . $hook[1] . "'");
					Hook::callSingle($this->app, 'expire', $hook, $data);
				}
			}
			return;
		}

		$this->logger->info('expire: start');

		Worker::add(['priority' => $this->app->queue['priority'], 'created' => $this->app->queue['created'], 'dont_fork' => true],
				'Expire', 'delete');

		$r = DBA::p("SELECT `uid`, `username` FROM `user` WHERE `expire` != 0");
		while ($row = DBA::fetch($r)) {
			$this->logger->info('Calling expiry for user '.$row['uid'].' ('.$row['username'].')');
			Worker::add(['priority' => $this->app->queue['priority'], 'created' => $this->app->queue['created'], 'dont_fork' => true],
					'Expire', (int)$row['uid']);
		}
		DBA::close($r);

		$this->logger->info('expire: calling hooks');
		foreach (Hook::getByName('expire') as $hook) {
			$this->logger->info("Calling expire hook for '" . $hook[1] . "'");
			Worker::add(['priority' => $this->app->queue['priority'], 'created' => $this->app->queue['created'], 'dont_fork' => true],
					'Expire', 'hook', $hook[1]);
		}

		$this->logger->info('expire: end');

		return;
	}
}
