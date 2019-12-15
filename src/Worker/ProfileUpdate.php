<?php
/**
 * @file src/Worker/ProfileUpdate.php
 * @brief Send updated profile data to Diaspora and ActivityPub
 */

namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\ActivityPub;

class ProfileUpdate {
	public static function execute($uid = 0) {
		if (empty($uid)) {
			return;
		}

		$a = DI::app();

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($uid);

		foreach ($inboxes as $inbox) {
			Logger::log('Profile update for user ' . $uid . ' to ' . $inbox .' via ActivityPub', Logger::DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
				'APDelivery', Delivery::PROFILEUPDATE, '', $inbox, $uid);
		}

		Diaspora::sendProfile($uid);
	}
}
