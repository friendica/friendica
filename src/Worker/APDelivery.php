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
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

class APDelivery
{
	/**
	 * Delivers ActivityPub messages
	 *
	 * @param string  $cmd       One of the Worker\Delivery constant values
	 * @param integer $item_id   0 if no item is involved (like Delivery::REMOVAL and Delivery::PROFILEUPDATE)
	 * @param string  $inbox     The URL of the recipient profile
	 * @param integer $uid       The ID of the user who triggered this delivery
	 * @param array   $receivers The contact IDs related to the inbox URL for contact archival housekeeping
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(string $cmd, int $item_id, string $inbox, int $uid, array $receivers = [])
	{
		if (ActivityPub\Transmitter::archivedInbox($inbox)) {
			Logger::info('Inbox is archived', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uid' => $uid]);
			if (in_array($cmd, [Delivery::POST])) {
				$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
				Post\DeliveryData::incrementQueueFailed($item['uri-id'] ?? 0);
			}
			return;
		}

		Logger::info('Invoked', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uid' => $uid]);

		$success = true;

		if ($cmd == Delivery::MAIL) {
			$data = ActivityPub\Transmitter::createActivityFromMail($item_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
			}
		} elseif ($cmd == Delivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($uid, $inbox, $item_id);
		} elseif ($cmd == Delivery::RELOCATION) {
			// @todo Implementation pending
		} elseif ($cmd == Delivery::POKE) {
			// Implementation not planned
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

		// This should never fail and is temporariy (until the move to the "post" structure)
		$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
		$uriid = $item['uri-id'] ?? 0;
		$gsid = null;

		foreach ($receivers as $receiver) {
			$contact = Contact::getById($receiver);
			if (empty($contact)) {
				continue;
			}

			$gsid = $gsid ?: $contact['gsid'];

			if ($success) {
				Contact::unmarkForArchival($contact);
			} else {
				Contact::markForArchival($contact);
			}
		}

		if (!empty($gsid)) {
			GServer::setProtocol($gsid, Post\DeliveryData::ACTIVITYPUB);
		}

		if (!$success && !Worker::defer() && in_array($cmd, [Delivery::POST])) {
			Post\DeliveryData::incrementQueueFailed($uriid);
		} elseif ($success && in_array($cmd, [Delivery::POST])) {
			Post\DeliveryData::incrementQueueDone($uriid, Post\DeliveryData::ACTIVITYPUB);
		}
	}
}
