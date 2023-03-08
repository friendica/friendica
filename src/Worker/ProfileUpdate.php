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
use Friendica\Protocol\Delivery;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\ActivityPub;

/**
 * Send updated profile data to Diaspora and ActivityPub
 */
class ProfileUpdate {
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

		$a = DI::app();

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($uid);

		foreach ($inboxes as $inbox => $receivers) {
			Logger::info('Profile update for user ' . $uid . ' to ' . $inbox .' via ActivityPub');
			Worker::add(['priority' => $a->getQueueValue('priority'), 'created' => $a->getQueueValue('created'), 'dont_fork' => true],
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
