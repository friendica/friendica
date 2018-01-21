<?php
/**
 * @file src/Worker/Queue.php
 */
namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Queue as QueueModel;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\PortableContact;
use Friendica\Protocol\Salmon;
use dba;

require_once 'include/dba.php';
require_once 'include/datetime.php';
require_once 'include/items.php';
require_once 'include/bbcode.php';

class Queue
{
	public static function execute($queue_id = 0)
	{
		global $a;

		$cachekey_deadguy = 'queue_run:deadguy:';
		$cachekey_server = 'queue_run:server:';

		if (!$queue_id) {
			logger('queue: start');

			// Handling the pubsubhubbub requests
			Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], 'PubSubPublish');

			$r = q(
				"SELECT `queue`.*, `contact`.`name`, `contact`.`uid` FROM `queue`
				INNER JOIN `contact` ON `queue`.`cid` = `contact`.`id`
				WHERE `queue`.`created` < UTC_TIMESTAMP() - INTERVAL 3 DAY"
			);

			if (DBM::isResult($r)) {
				foreach ($r as $rr) {
					logger('Removing expired queue item for ' . $rr['name'] . ', uid=' . $rr['uid']);
					logger('Expired queue data: ' . $rr['content'], LOGGER_DATA);
				}
				q("DELETE FROM `queue` WHERE `created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
			}

			/*
			 * For the first 12 hours we'll try to deliver every 15 minutes
			 * After that, we'll only attempt delivery once per hour.
			 */
			$r = q("SELECT `id` FROM `queue` WHERE ((`created` > UTC_TIMESTAMP() - INTERVAL 12 HOUR AND `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE) OR (`last` < UTC_TIMESTAMP() - INTERVAL 1 HOUR)) ORDER BY `cid`, `created`");

			Addon::callHooks('queue_predeliver', $r);

			if (DBM::isResult($r)) {
				foreach ($r as $q_item) {
					logger('Call queue for id ' . $q_item['id']);
					Worker::add(['priority' => PRIORITY_LOW, 'dont_fork' => true], "Queue", (int) $q_item['id']);
				}
			}
			return;
		}


		// delivering
		$q_item = dba::selectFirst('queue', [], ['id' => $queue_id]);
		if (!DBM::isResult($q_item)) {
			return;
		}

		$contact = dba::selectFirst('contact', [], ['id' => $q_item['cid']]);
		if (!DBM::isResult($contact)) {
			QueueModel::removeItem($q_item['id']);
			return;
		}

		$dead = Cache::get($cachekey_deadguy . $contact['notify']);

		if (!is_null($dead) && $dead) {
			logger('queue: skipping known dead url: ' . $contact['notify']);
			QueueModel::updateTime($q_item['id']);
			return;
		}

		$server = PortableContact::detectServer($contact['url']);

		if ($server != "") {
			$vital = Cache::get($cachekey_server . $server);

			if (is_null($vital)) {
				logger("Check server " . $server . " (" . $contact["network"] . ")");

				$vital = PortableContact::checkServer($server, $contact["network"], true);
				Cache::set($cachekey_server . $server, $vital, CACHE_QUARTER_HOUR);
			}

			if (!is_null($vital) && !$vital) {
				logger('queue: skipping dead server: ' . $server);
				QueueModel::updateTime($q_item['id']);
				return;
			}
		}

		$user = dba::selectFirst('user', [], ['uid' => $contact['uid']]);
		if (!DBM::isResult($user)) {
			QueueModel::removeItem($q_item['id']);
			return;
		}

		$data   = $q_item['content'];
		$public = $q_item['batch'];
		$owner  = $user;

		$deliver_status = 0;

		switch ($contact['network']) {
			case NETWORK_DFRN:
				logger('queue: dfrndelivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = DFRN::deliver($owner, $contact, $data);

				if ($deliver_status == (-1)) {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, CACHE_QUARTER_HOUR);
				} else {
					QueueModel::removeItem($q_item['id']);
				}
				break;
			case NETWORK_OSTATUS:
				if ($contact['notify']) {
					logger('queue: slapdelivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
					$deliver_status = Salmon::slapper($owner, $contact['notify'], $data);

					if ($deliver_status == (-1)) {
						QueueModel::updateTime($q_item['id']);
						Cache::set($cachekey_deadguy . $contact['notify'], true, CACHE_QUARTER_HOUR);
					} else {
						QueueModel::removeItem($q_item['id']);
					}
				}
				break;
			case NETWORK_DIASPORA:
				if ($contact['notify']) {
					logger('queue: diaspora_delivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
					$deliver_status = Diaspora::transmit($owner, $contact, $data, $public, true);

					if ($deliver_status == (-1)) {
						QueueModel::updateTime($q_item['id']);
						Cache::set($cachekey_deadguy . $contact['notify'], true, CACHE_QUARTER_HOUR);
					} else {
						QueueModel::removeItem($q_item['id']);
					}
				}
				break;

			default:
				$params = ['owner' => $owner, 'contact' => $contact, 'queue' => $q_item, 'result' => false];
				Addon::callHooks('queue_deliver', $params);

				if ($params['result']) {
					QueueModel::removeItem($q_item['id']);
				} else {
					QueueModel::updateTime($q_item['id']);
				}
				break;
		}
		logger('Deliver status ' . (int) $deliver_status . ' for item ' . $q_item['id'] . ' to ' . $contact['name'] . ' <' . $contact['url'] . '>');

		return;
	}
}
