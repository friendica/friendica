<?php
/**
 * @file src/Worker/APDelivery.php
 */
namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Model\ItemDeliveryData;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

class APDelivery extends AbstractWorker
{
	/**
	 * @brief Delivers ActivityPub messages
	 *
	 * {@inheritdoc}
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 4)) {
			return;
		}

		list($cmd, $target_id, $inbox, $uid) = $parameters;

		$this->logger->debug('Invoked: ' . $target_id . ' to ' . $inbox, ['cmd' => $cmd]);

		$success = true;

		if ($cmd == Delivery::MAIL) {
		} elseif ($cmd == Delivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($uid, $inbox, $target_id);
		} elseif ($cmd == Delivery::RELOCATION) {
		} elseif ($cmd == Delivery::REMOVAL) {
			$success = ActivityPub\Transmitter::sendProfileDeletion($uid, $inbox);
		} elseif ($cmd == Delivery::PROFILEUPDATE) {
			$success = ActivityPub\Transmitter::sendProfileUpdate($uid, $inbox);
		} else {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($target_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
				if ($success && in_array($cmd, [Delivery::POST, Delivery::COMMENT])) {
					ItemDeliveryData::incrementQueueDone($target_id);
				}
			}
		}

		if (!$success) {
			Worker::defer();
		}
	}
}
