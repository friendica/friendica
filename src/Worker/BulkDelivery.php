<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

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
				DI::logger()->debug('Delivery successful', $deliveryQueueItem->toArray());
			} else {
				DI::deliveryQueueItemRepo()->incrementFailed($deliveryQueueItem);
				$delivery_failure = true;

				if (!$server_failure) {
					$server_failure = !GServer::isReachableById($gsid);
				}
				DI::logger()->debug('Delivery failed', ['server_failure' => $server_failure, 'post' => $deliveryQueueItem]);
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
