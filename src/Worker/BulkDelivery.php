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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\GServer;
use Friendica\Protocol\Delivery as ProtocolDelivery;

class BulkDelivery
{
	public static function execute(int $gsid)
	{
		$server_failure   = false;
		$delivery_failure = false;

		$deliveryQueueItems = DI::deliveryQueueItemRepo()->selectByServerId($gsid, DI::config()->get('system', 'worker_defer_limit'));
		foreach ($deliveryQueueItems as $deliveryQueueItem) {
			if (!$server_failure && ProtocolDelivery::deliver($deliveryQueueItem->command, $deliveryQueueItem->postUriId, $deliveryQueueItem->targetContactId, $deliveryQueueItem->senderUserId)) {
				DI::deliveryQueueItemRepo()->remove($deliveryQueueItem);
				Logger::debug('Delivery successful', $deliveryQueueItem->toArray());
			} else {
				DI::deliveryQueueItemRepo()->incrementFailed($deliveryQueueItem);
				$delivery_failure = true;

				if (!$server_failure) {
					$server_failure = !GServer::isReachableById($gsid);
				}
				Logger::debug('Delivery failed', ['server_failure' => $server_failure, 'post' => $deliveryQueueItem]);
			}
		}

		if ($server_failure) {
			Worker::defer();
		}

		if ($delivery_failure) {
			DI::deliveryQueueItemRepo()->removeFailedByServerId($gsid, DI::config()->get('system', 'worker_defer_limit'));
		}
	}
}
