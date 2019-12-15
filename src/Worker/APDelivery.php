<?php
/**
 * @file src/Worker/APDelivery.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Model\ItemDeliveryData;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

class APDelivery
{
	/**
	 * @brief Delivers ActivityPub messages
	 *
	 * @param string  $cmd
	 * @param integer $target_id
	 * @param string  $inbox
	 * @param integer $uid
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute($cmd, $target_id, $inbox, $uid)
	{
		Logger::log('Invoked: ' . $cmd . ': ' . $target_id . ' to ' . $inbox, Logger::DEBUG);

		$success = true;

		if ($cmd == Delivery::MAIL) {
			$data = ActivityPub\Transmitter::createActivityFromMail($target_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
			}
		} elseif ($cmd == Delivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($uid, $inbox, $target_id);
		} elseif ($cmd == Delivery::RELOCATION) {
			// @todo Implementation pending
		} elseif ($cmd == Delivery::POKE) {
			// Implementation not planned
		} elseif ($cmd == Delivery::REMOVAL) {
			$success = ActivityPub\Transmitter::sendProfileDeletion($uid, $inbox);
		} elseif ($cmd == Delivery::PROFILEUPDATE) {
			$success = ActivityPub\Transmitter::sendProfileUpdate($uid, $inbox);
		} else {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($target_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
			}
		}

		if (!$success && !Worker::defer() && in_array($cmd, [Delivery::POST])) {
			ItemDeliveryData::incrementQueueFailed($target_id);
		} elseif ($success && in_array($cmd, [Delivery::POST])) {
			ItemDeliveryData::incrementQueueDone($target_id, ItemDeliveryData::ACTIVITYPUB);
		}
	}
}
