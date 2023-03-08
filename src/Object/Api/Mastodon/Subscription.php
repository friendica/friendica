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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Subscription
 *
 * @see https://docs.joinmastodon.org/entities/pushsubscription
 */
class Subscription extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string|null (URL)*/
	protected $endpoint;
	/** @var array */
	protected $alerts;
	/** @var string */
	protected $server_key;

	/**
	 * Creates a subscription record from an item record.
	 *
	 * @param array  $subscription
	 * @param string $vapid
	 */
	public function __construct(array $subscription, string $vapid)
	{
		$this->id       = (string)$subscription['id'];
		$this->endpoint = $subscription['endpoint'];
		$this->alerts   = [
			Notification::TYPE_FOLLOW  => (bool)$subscription[Notification::TYPE_FOLLOW],
			Notification::TYPE_LIKE    => (bool)$subscription[Notification::TYPE_LIKE],
			Notification::TYPE_RESHARE => (bool)$subscription[Notification::TYPE_RESHARE],
			Notification::TYPE_MENTION => (bool)$subscription[Notification::TYPE_MENTION],
			Notification::TYPE_POLL    => (bool)$subscription[Notification::TYPE_POLL],
		];

		$this->server_key = $vapid;
	}
}
