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

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table group
 *
 * privacy groups, group info
 */
class Group extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var bool
	 * 1 indicates the member list is not private
	 */
	private $visible = '0';

	/**
	 * @var bool
	 * 1 indicates the group has been deleted
	 */
	private $deleted = '0';

	/**
	 * @var string
	 * human readable name of group
	 */
	private $name = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'visible' => $this->visible,
			'deleted' => $this->deleted,
			'name' => $this->name,
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
	 * Get Owner User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set Owner User id
	 */
	public function setUid(int $uid)
	{
		$this->uid = $uid;
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
	 * Get 1 indicates the member list is not private
	 */
	public function getVisible()
	{
		return $this->visible;
	}

	/**
	 * @param bool $visible
	 * Set 1 indicates the member list is not private
	 */
	public function setVisible(bool $visible)
	{
		$this->visible = $visible;
	}

	/**
	 * @return bool
	 * Get 1 indicates the group has been deleted
	 */
	public function getDeleted()
	{
		return $this->deleted;
	}

	/**
	 * @param bool $deleted
	 * Set 1 indicates the group has been deleted
	 */
	public function setDeleted(bool $deleted)
	{
		$this->deleted = $deleted;
	}

	/**
	 * @return string
	 * Get human readable name of group
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * Set human readable name of group
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}
}
