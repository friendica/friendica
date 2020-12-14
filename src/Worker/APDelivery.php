<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

class APDelivery
{
	/**
	 * Delivers ActivityPub messages
	 *
	 * @param string  $cmd
	 * @param integer $target_id
	 * @param string  $inbox
	 * @param integer $uid
	 * @param array   $receivers
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute($cmd, $target_id, $inbox, $uid, $receivers = [])
	{
		if (ActivityPub\Transmitter::archivedInbox($inbox)) {
			Logger::info('Inbox is archived', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $target_id, 'uid' => $uid]);
			if (in_array($cmd, [Delivery::POST])) {
				$item = Item::selectFirst(['uri-id'], ['id' => $target_id]);
				Post\DeliveryData::incrementQueueFailed($item['uri-id'] ?? 0);
			}
			return;
		}

		Logger::info('Invoked', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $target_id, 'uid' => $uid]);

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

		// This should never fail and is temporariy (until the move to the "post" structure)
		$item = Item::selectFirst(['uri-id'], ['id' => $target_id]);
		$uriid = $item['uri-id'] ?? 0;

		if (is_array($receivers)) {
			foreach ($receivers as $receiver) {
				$contact = Contact::getById($receiver);
				if (empty($contact)) {
					continue;
				}

				if ($success) {
					Contact::unmarkForArchival($contact);
				} else {
					Contact::markForArchival($contact);
				}
			}
		}

		if (!$success && !Worker::defer() && in_array($cmd, [Delivery::POST])) {
			Post\DeliveryData::incrementQueueFailed($uriid);
		} elseif ($success && in_array($cmd, [Delivery::POST])) {
			Post\DeliveryData::incrementQueueDone($uriid, Post\DeliveryData::ACTIVITYPUB);
		}
	}
}
