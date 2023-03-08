<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

class PushSubscriber
{
	/**
	 * Send subscription notifications for the given user
	 *
	 * @param integer $uid User ID
	 * @param int     $default_priority
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function publishFeed(int $uid, int $default_priority = Worker::PRIORITY_HIGH)
	{
		$condition = ['push' => 0, 'uid' => $uid];
		DBA::update('push_subscriber', ['push' => 1, 'next_try' => DBA::NULL_DATETIME], $condition);

		self::requeue($default_priority);
	}

	/**
	 * start workers to transmit the feed data
	 *
	 * @param int $default_priority
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function requeue(int $default_priority = Worker::PRIORITY_HIGH)
	{
		// We'll push to each subscriber that has push > 0,
		// i.e. there has been an update (set in notifier.php).
		$subscribers = DBA::select('push_subscriber', ['id', 'push', 'callback_url', 'nickname'], ["`push` > 0 AND `next_try` < ?", DateTimeFormat::utcNow()]);

		while ($subscriber = DBA::fetch($subscribers)) {
			// We always handle retries with low priority
			if ($subscriber['push'] > 1) {
				$priority = Worker::PRIORITY_LOW;
			} else {
				$priority = $default_priority;
			}

			Logger::info('Publish feed to ' . $subscriber['callback_url'] . ' for ' . $subscriber['nickname'] . ' with priority ' . $priority);
			Worker::add($priority, 'PubSubPublish', (int)$subscriber['id']);
		}

		DBA::close($subscribers);
	}

	/**
	 * Renew the feed subscription
	 *
	 * @param integer $uid          User ID
	 * @param string  $nick         Priority for push workers
	 * @param integer $subscribe    Subscribe (Unsubscribe = false)
	 * @param string  $hub_callback Callback address
	 * @param string  $hub_topic    Feed topic
	 * @param string  $hub_secret   Subscription secret
	 * @return void
	 * @throws \Exception
	 */
	public static function renew(int $uid, string $nick, int $subscribe, string $hub_callback, string $hub_topic, string $hub_secret)
	{
		// fetch the old subscription if it exists
		$subscriber = DBA::selectFirst('push_subscriber', ['last_update', 'push'], ['callback_url' => $hub_callback]);

		// delete old subscription if it exists
		DBA::delete('push_subscriber', ['callback_url' => $hub_callback]);

		if ($subscribe) {
			// if we are just updating an old subscription, keep the
			// old values for last_update but reset the push
			if (DBA::isResult($subscriber)) {
				$last_update = $subscriber['last_update'];
				$push_flag = min($subscriber['push'], 1);
			} else {
				$last_update = DateTimeFormat::utcNow();
				$push_flag = 0;
			}

			// subscribe means adding the row to the table
			$fields = ['uid' => $uid, 'callback_url' => $hub_callback,
				'topic' => $hub_topic, 'nickname' => $nick, 'push' => $push_flag,
				'last_update' => $last_update, 'renewed' => DateTimeFormat::utcNow(),
				'secret' => $hub_secret];
			DBA::insert('push_subscriber', $fields);

			Logger::notice("Successfully subscribed [$hub_callback] for $nick");
		} else {
			Logger::notice("Successfully unsubscribed [$hub_callback] for $nick");
			// we do nothing here, since the row was already deleted
		}
	}

	/**
	 * Delay the push subscriber
	 *
	 * @param integer $id Subscriber ID
	 * @return void
	 * @throws \Exception
	 */
	public static function delay(int $id)
	{
		$subscriber = DBA::selectFirst('push_subscriber', ['push', 'callback_url', 'renewed', 'nickname'], ['id' => $id]);
		if (!DBA::isResult($subscriber)) {
			return;
		}

		$retrial = $subscriber['push'];

		if ($retrial > 14) {
			// End subscriptions if they weren't renewed for more than two months
			$days = round((time() -  strtotime($subscriber['renewed'])) / (60 * 60 * 24));

			if ($days > 60) {
				DBA::update('push_subscriber', ['push' => -1, 'next_try' => DBA::NULL_DATETIME], ['id' => $id]);
				Logger::info('Delivery error: Subscription ' . $subscriber['callback_url'] . ' for ' . $subscriber['nickname'] . ' is marked as ended.');
			} else {
				DBA::update('push_subscriber', ['push' => 0, 'next_try' => DBA::NULL_DATETIME], ['id' => $id]);
				Logger::info('Delivery error: Giving up ' . $subscriber['callback_url'] . ' for ' . $subscriber['nickname'] . ' for now.');
			}
		} else {
			// Calculate the delay until the next trial
			$delay = (($retrial + 3) ** 4) + (rand(1, 30) * ($retrial + 1));
			$next = DateTimeFormat::utc('now + ' . $delay . ' seconds');

			$retrial = $retrial + 1;

			DBA::update('push_subscriber', ['push' => $retrial, 'next_try' => $next], ['id' => $id]);
			Logger::info('Delivery error: Next try (' . $retrial . ') ' . $subscriber['callback_url'] . ' for ' . $subscriber['nickname'] . ' at ' . $next);
		}
	}

	/**
	 * Reset the push subscriber
	 *
	 * @param integer $id          Subscriber ID
	 * @param string  $last_update Date of last transmitted item
	 * @return void
	 * @throws \Exception
	 */
	public static function reset(int $id, string $last_update)
	{
		$subscriber = DBA::selectFirst('push_subscriber', ['callback_url', 'nickname'], ['id' => $id]);
		if (!DBA::isResult($subscriber)) {
			return;
		}

		// set last_update to the 'created' date of the last item, and reset push=0
		$fields = ['push' => 0, 'next_try' => DBA::NULL_DATETIME, 'last_update' => $last_update];
		DBA::update('push_subscriber', $fields, ['id' => $id]);
		Logger::info('Subscriber ' . $subscriber['callback_url'] . ' for ' . $subscriber['nickname'] . ' is marked as vital');

		$parts = parse_url($subscriber['callback_url']);
		unset($parts['path']);
		$server_url = Network::unparseURL($parts);
		$gsid = GServer::getID($server_url, true);
		if (!empty($gsid)) {
			GServer::setProtocol($gsid, Post\DeliveryData::OSTATUS);
		}
	}
}
