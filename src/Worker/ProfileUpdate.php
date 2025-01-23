<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Protocol\Delivery;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\ActivityPub;

/**
 * Send updated profile data to Diaspora and ActivityPub
 */
class ProfileUpdate
{
	/**
	 * Sends updated profile data to Diaspora and ActivityPub
	 *
	 * @param int $uid User id (optional, default: 0)
	 * @return void
	 */
	public static function execute(int $uid = 0)
	{
		if (empty($uid)) {
			return;
		}

		$appHelper = DI::appHelper();

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($uid);

		foreach ($inboxes as $inbox => $receivers) {
			DI::logger()->info('Profile update for user ' . $uid . ' to ' . $inbox .' via ActivityPub');
			Worker::add(
				['priority' => $appHelper->getQueueValue('priority'), 'created' => $appHelper->getQueueValue('created'), 'dont_fork' => true],
				'APDelivery',
				Delivery::PROFILEUPDATE,
				0,
				$inbox,
				$uid,
				$receivers
			);
		}

		Diaspora::sendProfile($uid);
	}
}
