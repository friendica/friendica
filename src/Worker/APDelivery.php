<?php
/**
 * @file src/Worker/APDelivery.php
 */
namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Protocol\ActivityPub;
use Friendica\Model\Item;
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
		logger('Invoked: ' . $cmd . ': ' . $item_id . ' to ' . $inbox, LOGGER_DEBUG);

		if ($cmd == Delivery::MAIL) {
		} elseif ($cmd == Delivery::SUGGESTION) {
		} elseif ($cmd == Delivery::RELOCATION) {
		} elseif ($cmd == Delivery::REMOVAL) {
			ActivityPub\Transmitter::sendProfileDeletion($uid, $inbox);
		} elseif ($cmd == Delivery::PROFILEUPDATE) {
			ActivityPub\Transmitter::sendProfileUpdate($uid, $inbox);
		} else {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($item_id);
			if (!empty($data)) {
				HTTPSignature::transmit($data, $inbox, $uid);
			}
		}
	}
}
