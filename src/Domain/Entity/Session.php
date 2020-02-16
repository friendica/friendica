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
 * Entity class for table session
 *
 * web session storage
 */
class Session extends BaseEntity
{
	/**
	 * @var string
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 */
	private $sid = '';

	/**
	 * @var string
	 */
	private $data;

	/**
	 * @var int
	 */
	private $expire = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'sid' => $this->sid,
			'data' => $this->data,
			'expire' => $this->expire,
		];
	}

	/**
	 * @return string
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
	public function getSid()
	{
		return $this->sid;
	}

	/**
	 * @param string $sid
	 * Set
	 */
	public function setSid(string $sid)
	{
		$this->sid = $sid;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param string $data
	 * Set
	 */
	public function setData(string $data)
	{
		$this->data = $data;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getExpire()
	{
		return $this->expire;
	}

	/**
	 * @param int $expire
	 * Set
	 */
	public function setExpire(int $expire)
	{
		$this->expire = $expire;
	}
}
