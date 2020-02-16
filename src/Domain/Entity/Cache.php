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
 * Entity class for table cache
 *
 * Stores temporary data
 */
class Cache extends BaseEntity
{
	/**
	 * @var string
	 * cache key
	 */
	private $k;

	/**
	 * @var string
	 * cached serialized value
	 */
	private $v;

	/**
	 * @var string
	 * datetime of cache expiration
	 */
	private $expires = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * datetime of cache insertion
	 */
	private $updated = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'k' => $this->k,
			'v' => $this->v,
			'expires' => $this->expires,
			'updated' => $this->updated,
		];
	}

	/**
	 * @return string
	 * Get cache key
	 */
	public function getK()
	{
		return $this->k;
	}

	/**
	 * @return string
	 * Get cached serialized value
	 */
	public function getV()
	{
		return $this->v;
	}

	/**
	 * @param string $v
	 * Set cached serialized value
	 */
	public function setV(string $v)
	{
		$this->v = $v;
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

	/**
	 * @return string
	 * Get datetime of cache insertion
	 */
	public function getUpdated()
	{
		return $this->updated;
	}

	/**
	 * @param string $updated
	 * Set datetime of cache insertion
	 */
	public function setUpdated(string $updated)
	{
		$this->updated = $updated;
	}
}
