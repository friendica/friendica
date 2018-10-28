<?php
/**
 * @file src/Worker/Queue.php
 */
namespace Friendica\Worker;

use Friendica\Content\Text;
use Friendica\Core\Addon;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\PushSubscriber;
use Friendica\Model\Queue as QueueModel;
use Friendica\Model\User;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\PortableContact;
use Friendica\Protocol\Salmon;

require_once 'include/dba.php';
require_once 'include/items.php';

class Queue
{
	public static function execute($queue_id = 0)
	{
		$cachekey_deadguy = 'queue_run:deadguy:';
		$cachekey_server = 'queue_run:server:';

		$no_dead_check = Config::get('system', 'queue_no_dead_check', false);

		if (!$queue_id) {
			Text::logger('filling queue jobs - start');

			// Handling the pubsubhubbub requests
			PushSubscriber::requeue();

			$r = DBA::toArray(DBA::p("SELECT `id` FROM `queue` WHERE `next` < UTC_TIMESTAMP() ORDER BY `batch`, `cid`"));

			Addon::callHooks('queue_predeliver', $r);

			if (DBA::isResult($r)) {
				foreach ($r as $q_item) {
					Text::logger('Call queue for id ' . $q_item['id']);
					Worker::add(['priority' => PRIORITY_LOW, 'dont_fork' => true], "Queue", (int) $q_item['id']);
				}
			}
			Text::logger('filling queue jobs - end');
			return;
		}


		// delivering
		$q_item = DBA::selectFirst('queue', [], ['id' => $queue_id]);
		if (!DBA::isResult($q_item)) {
			return;
		}

		$contact = DBA::selectFirst('contact', [], ['id' => $q_item['cid']]);
		if (!DBA::isResult($contact)) {
			QueueModel::removeItem($q_item['id']);
			return;
		}

		if (empty($contact['notify']) || $contact['archive']) {
			QueueModel::removeItem($q_item['id']);
			return;
		}

		$dead = Cache::get($cachekey_deadguy . $contact['notify']);

		if (!is_null($dead) && $dead && !$no_dead_check) {
			Text::logger('queue: skipping known dead url: ' . $contact['notify']);
			QueueModel::updateTime($q_item['id']);
			return;
		}

		if (!$no_dead_check) {
			$server = PortableContact::detectServer($contact['url']);

			if ($server != "") {
				$vital = Cache::get($cachekey_server . $server);

				if (is_null($vital)) {
					Text::logger("Check server " . $server . " (" . $contact["network"] . ")");

					$vital = PortableContact::checkServer($server, $contact["network"], true);
					Cache::set($cachekey_server . $server, $vital, Cache::MINUTE);
				}

				if (!is_null($vital) && !$vital) {
					Text::logger('queue: skipping dead server: ' . $server);
					QueueModel::updateTime($q_item['id']);
					return;
				}
			}
		}

		$user = DBA::selectFirst('user', [], ['uid' => $contact['uid']]);
		if (!DBA::isResult($user)) {
			QueueModel::removeItem($q_item['id']);
			return;
		}

		$data   = $q_item['content'];
		$public = $q_item['batch'];
		$owner  = User::getOwnerDataById($user['uid']);

		$deliver_status = 0;

		switch ($contact['network']) {
			case Protocol::DFRN:
				Text::logger('queue: dfrndelivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = DFRN::deliver($owner, $contact, $data);

				if (($deliver_status >= 200) && ($deliver_status <= 299)) {
					QueueModel::removeItem($q_item['id']);
				} else {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, Cache::MINUTE);
				}
				break;

			case Protocol::OSTATUS:
				Text::logger('queue: slapdelivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = Salmon::slapper($owner, $contact['notify'], $data);

				if ($deliver_status == -1) {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, Cache::MINUTE);
				} else {
					QueueModel::removeItem($q_item['id']);
				}
				break;

			case Protocol::DIASPORA:
				Text::logger('queue: diaspora_delivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = Diaspora::transmit($owner, $contact, $data, $public, true, 'Queue:' . $q_item['id'], true);

				if ((($deliver_status >= 200) && ($deliver_status <= 299)) ||
					($contact['contact-type'] == Contact::ACCOUNT_TYPE_RELAY)) {
					QueueModel::removeItem($q_item['id']);
				} else {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, Cache::MINUTE);
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
		Text::logger('Deliver status ' . (int)$deliver_status . ' for item ' . $q_item['id'] . ' to ' . $contact['name'] . ' <' . $contact['url'] . '>');

		return;
	}
}
