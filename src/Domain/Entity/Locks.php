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

/**
 * Entity class for table locks
 */
class Locks extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var bool
	 */
	private $locked = '0';

	/**
	 * @var int
	 * Process ID
	 */
	private $pid = '0';

	/**
	 * @var string
	 * datetime of cache expiration
	 */
	private $expires = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'locked' => $this->locked,
			'pid' => $this->pid,
			'expires' => $this->expires,
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
	 * @return string
	 * Get
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * Set
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getLocked()
	{
		return $this->locked;
	}

	/**
	 * @param bool $locked
	 * Set
	 */
	public function setLocked(bool $locked)
	{
		$this->locked = $locked;
	}

	/**
	 * @return int
	 * Get Process ID
	 */
	public function getPid()
	{
		return $this->pid;
	}

	/**
	 * @param int $pid
	 * Set Process ID
	 */
	public function setPid(int $pid)
	{
		$this->pid = $pid;
	}

	/**
	 * @return string
	 * Get datetime of cache expiration
	 */
	public function getExpires()
	{
		return $this->expires;
	}

	/**
	 * @param string $expires
	 * Set datetime of cache expiration
	 */
	public function setExpires(string $expires)
	{
		$this->expires = $expires;
	}
}
