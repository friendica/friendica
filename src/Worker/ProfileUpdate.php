<?php
/**
 * @file src/Worker/ProfileUpdate.php
 * @brief Send updated profile data to Diaspora and ActivityPub
 */

namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Content\Text;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\ActivityPub;
use Friendica\Core\Worker;

class ProfileUpdate {
	public static function execute($uid = 0) {
		if (empty($uid)) {
			return;
		}

		$a = BaseObject::getApp();

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($uid);

		foreach ($inboxes as $inbox) {
			Text::logger('Profile update for user ' . $uid . ' to ' . $inbox .' via ActivityPub', LOGGER_DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
				'APDelivery', Delivery::PROFILEUPDATE, '', $inbox, $uid);
		}

		Diaspora::sendProfile($uid);
	}
}
