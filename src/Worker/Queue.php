<?php
/**
 * @file src/Worker/Queue.php
 */
namespace Friendica\Worker;

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Hook;
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

class Queue extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		$queue_id = (isset($parameters[0]) && is_int($parameters[0])) ? $parameters[0] : 0;

		$cachekey_deadguy = 'queue_run:deadguy:';
		$cachekey_server = 'queue_run:server:';

		$no_dead_check = Config::get('system', 'queue_no_dead_check', false);

		if (!$queue_id) {
			$this->logger->info('filling queue jobs - start');

			// Handling the pubsubhubbub requests
			PushSubscriber::requeue();

			$r = DBA::toArray(DBA::p("SELECT `id` FROM `queue` WHERE `next` < UTC_TIMESTAMP() ORDER BY `batch`, `cid`"));

			Hook::callAll('queue_predeliver', $r);

			if (DBA::isResult($r)) {
				foreach ($r as $q_item) {
					$this->logger->info('Call queue for id ' . $q_item['id']);
					Worker::add(['priority' => PRIORITY_LOW, 'dont_fork' => true], "Queue", (int) $q_item['id']);
				}
			}
			$this->logger->info('filling queue jobs - end');
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
			$this->logger->info('queue: skipping known dead url: ' . $contact['notify']);
			QueueModel::updateTime($q_item['id']);
			return;
		}

		if (!$no_dead_check) {
			$server = PortableContact::detectServer($contact['url']);

			if ($server != "") {
				$vital = Cache::get($cachekey_server . $server);

				if (is_null($vital)) {
					$this->logger->info("Check server " . $server . " (" . $contact["network"] . ")");

					$vital = PortableContact::checkServer($server, $contact["network"], true);
					Cache::set($cachekey_server . $server, $vital, Cache::MINUTE);
				}

				if (!is_null($vital) && !$vital) {
					$this->logger->info('queue: skipping dead server: ' . $server);
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
				$this->logger->info('queue: dfrndelivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = DFRN::deliver($owner, $contact, $data);

				if (($deliver_status >= 200) && ($deliver_status <= 299)) {
					QueueModel::removeItem($q_item['id']);
				} else {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, Cache::MINUTE);
				}
				break;

			case Protocol::OSTATUS:
				$this->logger->info('queue: slapdelivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = Salmon::slapper($owner, $contact['notify'], $data);

				if ($deliver_status == -1) {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, Cache::MINUTE);
				} else {
					QueueModel::removeItem($q_item['id']);
				}
				break;

			case Protocol::DIASPORA:
				$this->logger->info('queue: diaspora_delivery: item ' . $q_item['id'] . ' for ' . $contact['name'] . ' <' . $contact['url'] . '>');
				$deliver_status = Diaspora::transmit($owner, $contact, $data, $public, true, 'Queue:' . $q_item['id'], true);

				if ((($deliver_status >= 200) && ($deliver_status <= 299)) ||
					($contact['contact-type'] == Contact::TYPE_RELAY)) {
					QueueModel::removeItem($q_item['id']);
				} else {
					QueueModel::updateTime($q_item['id']);
					Cache::set($cachekey_deadguy . $contact['notify'], true, Cache::MINUTE);
				}
				break;

			default:
				$params = ['owner' => $owner, 'contact' => $contact, 'queue' => $q_item, 'result' => false];
				Hook::callAll('queue_deliver', $params);

				if ($params['result']) {
					QueueModel::removeItem($q_item['id']);
				} else {
					QueueModel::updateTime($q_item['id']);
				}
				break;
		}
		$this->logger->info('Deliver status ' . (int)$deliver_status . ' for item ' . $q_item['id'] . ' to ' . $contact['name'] . ' <' . $contact['url'] . '>');

		return;
	}
}
