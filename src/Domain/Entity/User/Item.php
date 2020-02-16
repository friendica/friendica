<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity\User;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table user-item
 *
 * User specific item data
 */
class Item extends BaseEntity
{
	/**
	 * @var int
	 * Item id
	 */
	private $iid = '0';

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * @var bool
	 * Marker to hide an item from the user
	 */
	private $hidden = '0';

	/**
	 * @var bool
	 * Ignore this thread if set
	 */
	private $ignored;

	/**
	 * @var bool
	 * The item is pinned on the profile page
	 */
	private $pinned;

	/**
	 * @var string
	 */
	private $notificationType = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'iid' => $this->iid,
			'uid' => $this->uid,
			'hidden' => $this->hidden,
			'ignored' => $this->ignored,
			'pinned' => $this->pinned,
			'notification-type' => $this->notificationType,
		];
	}

	/**
	 * @return int
	 * Get Item id
	 */
	public function getIid()
	{
		return $this->iid;
	}

	/**
	 * Get Item
	 *
	 * @return Item
	 */
	public function getItem()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return int
	 * Get User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * Get User
	 *
	 * @return User
	 */
	public function getUser()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for uid is not implemented yet');
	}

	/**
	 * @return bool
	 * Get Marker to hide an item from the user
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * @param bool $hidden
	 * Set Marker to hide an item from the user
	 */
	public function setHidden(bool $hidden)
	{
		$this->hidden = $hidden;
	}

	/**
	 * @return bool
	 * Get Ignore this thread if set
	 */
	public function getIgnored()
	{
		return $this->ignored;
	}

	/**
	 * @param bool $ignored
	 * Set Ignore this thread if set
	 */
	public function setIgnored(bool $ignored)
	{
		$this->ignored = $ignored;
	}

	/**
	 * @return bool
	 * Get The item is pinned on the profile page
	 */
	public function getPinned()
	{
		return $this->pinned;
	}

	/**
	 * @param bool $pinned
	 * Set The item is pinned on the profile page
	 */
	public function setPinned(bool $pinned)
	{
		$this->pinned = $pinned;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNotificationType()
	{
		return $this->notificationType;
	}

	/**
	 * @param string $notificationType
	 * Set
	 */
	public function setNotificationType(string $notificationType)
	{
		$this->notificationType = $notificationType;
	}
}
