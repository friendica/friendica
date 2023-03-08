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
use Friendica\Util\DateTimeFormat;

/**
 * Class FriendicaExtension
 *
 * Additional fields on Mastodon Statuses for storing Friendica specific data
 *
 * @see https://docs.joinmastodon.org/entities/status
 */
class FriendicaExtension extends BaseDataTransferObject
{
	/** @var string */
	protected $title;

	/** @var string|null (Datetime) */
	protected $changed_at;

	/** @var string|null (Datetime) */
	protected $commented_at;

	/** @var string|null (Datetime) */
	protected $received_at;

	/** @var FriendicaDeliveryData */
	protected $delivery_data;
	/** @var int */
	protected $dislikes_count;


	/**
	 * Creates a status count object
	 *
	 * @param string $title
	 * @param string|null $changed_at
	 * @param string|null $commented_at
	 * @param string|null $edited_at
	 * @param string|null $received_at
	 * @param int $dislikes_count
	 * @param FriendicaDeliveryData $delivery_data
	 */
	public function __construct(
		string $title,
		?string $changed_at,
		?string $commented_at,
		?string $received_at,
		int $dislikes_count,
		FriendicaDeliveryData $delivery_data
	) {
		$this->title          = $title;
		$this->changed_at     = $changed_at ? DateTimeFormat::utc($changed_at, DateTimeFormat::JSON) : null;
		$this->commented_at   = $commented_at ? DateTimeFormat::utc($commented_at, DateTimeFormat::JSON) : null;
		$this->received_at    = $received_at ? DateTimeFormat::utc($received_at, DateTimeFormat::JSON) : null;
		$this->delivery_data  = $delivery_data;
		$this->dislikes_count = $dislikes_count;
	}

	/**
	 * Returns the current changed_at string or null if not set
	 * @return ?string
	 */
	public function changedAt(): ?string
	{
		return $this->changed_at;
	}

	/**
	 * Returns the current commented_at string or null if not set
	 * @return ?string
	 */
	public function commentedAt(): ?string
	{
		return $this->commented_at;
	}

	/**
	 * Returns the current received_at string or null if not set
	 * @return ?string
	 */
	public function receivedAt(): ?string
	{
		return $this->received_at;
	}
}
