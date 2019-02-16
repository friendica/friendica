<?php
/**
 * @file src/Worker/ProfileUpdate.php
 * @brief Send updated profile data to Diaspora and ActivityPub
 */

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;

class ProfileUpdate extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{

		$uid = (isset($parameters[0]) && is_int($parameters[0])) ? $parameters[0] : 0;

		if (empty($uid)) {
			return;
		}

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($uid);

		foreach ($inboxes as $inbox) {
			$this->logger->info('Profile update for user ' . $uid . ' to ' . $inbox .' via ActivityPub');
			Worker::add(['priority' => $this->app->queue['priority'], 'created' => $this->app->queue['created'], 'dont_fork' => true],
				'APDelivery', Delivery::PROFILEUPDATE, '', $inbox, $uid);
		}

		Diaspora::sendProfile($uid);
	}
}
