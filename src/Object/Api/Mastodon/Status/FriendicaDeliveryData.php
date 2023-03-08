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

namespace Friendica\Object\Api\Mastodon\Status;

use Friendica\BaseDataTransferObject;

/**
 * Class FriendicaDeliveryData
 *
 * Additional fields on Mastodon Statuses for storing Friendica delivery data
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaDeliveryData extends BaseDataTransferObject
{
	/** @var int|null */
	protected $delivery_queue_count;

	/** @var int|null */
	protected $delivery_queue_done;

	/** @var int|null */
	protected $delivery_queue_failed;

	/**
	 * Creates a FriendicaDeliveryData object
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(?int $delivery_queue_count, ?int $delivery_queue_done, ?int $delivery_queue_failed)
	{
		$this->delivery_queue_count  = $delivery_queue_count;
		$this->delivery_queue_done   = $delivery_queue_done;
		$this->delivery_queue_failed = $delivery_queue_failed;
	}
}
