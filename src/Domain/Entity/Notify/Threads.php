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

namespace Friendica\Domain\Entity\Notify;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table notify-threads
 */
class Threads extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 */
	private $notifyId = '0';

	/**
	 * @var int
	 */
	private $masterParentItem = '0';

	/**
	 * @var int
	 */
	private $parentItem = '0';

	/**
	 * @var int
	 * User id
	 */
	private $receiverUid = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'notify-id' => $this->notifyId,
			'master-parent-item' => $this->masterParentItem,
			'parent-item' => $this->parentItem,
			'receiver-uid' => $this->receiverUid,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getNotifyId()
	{
		return $this->notifyId;
	}

	/**
	 * @param int $notifyId
	 * Set
	 */
	public function setNotifyId(int $notifyId)
	{
		$this->notifyId = $notifyId;
	}

	/**
	 * Get Notify
	 *
	 * @return Notify
	 */
	public function getNotify()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return int
	 * Get
	 */
	public function getMasterParentItem()
	{
		return $this->masterParentItem;
	}

	/**
	 * @param int $masterParentItem
	 * Set
	 */
	public function setMasterParentItem(int $masterParentItem)
	{
		$this->masterParentItem = $masterParentItem;
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
	 * Get
	 */
	public function getParentItem()
	{
		return $this->parentItem;
	}

	/**
	 * @param int $parentItem
	 * Set
	 */
	public function setParentItem(int $parentItem)
	{
		$this->parentItem = $parentItem;
	}

	/**
	 * @return int
	 * Get User id
	 */
	public function getReceiverUid()
	{
		return $this->receiverUid;
	}

	/**
	 * @param int $receiverUid
	 * Set User id
	 */
	public function setReceiverUid(int $receiverUid)
	{
		$this->receiverUid = $receiverUid;
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
}
