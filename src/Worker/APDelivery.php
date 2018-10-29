<?php
/**
 * @file src/Worker/APDelivery.php
 */
namespace Friendica\Worker;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Protocol\ActivityPub;
use Friendica\Model\Item;
use Friendica\Core\Worker;
use Friendica\Util\HTTPSignature;

class APDelivery extends BaseObject
{
	/**
	 * @brief Delivers ActivityPub messages
	 *
	 * @param string $cmd
	 * @param integer $item_id
	 * @param string $inbox
	 * @param integer $uid
	 */
	public static function execute($cmd, $item_id, $inbox, $uid)
	{
		App::logger('Invoked: ' . $cmd . ': ' . $item_id . ' to ' . $inbox, LOGGER_DEBUG);

		$success = true;

		if ($cmd == Delivery::MAIL) {
		} elseif ($cmd == Delivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($uid, $inbox, $item_id);
		} elseif ($cmd == Delivery::RELOCATION) {
		} elseif ($cmd == Delivery::REMOVAL) {
			$success = ActivityPub\Transmitter::sendProfileDeletion($uid, $inbox);
		} elseif ($cmd == Delivery::PROFILEUPDATE) {
			$success = ActivityPub\Transmitter::sendProfileUpdate($uid, $inbox);
		} else {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($item_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
			}
		}

		if (!$success) {
			Worker::defer();
		}
	}
}
