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
 * Entity class for table challenge
 */
class Challenge extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 */
	private $challenge = '';

	/**
	 * @var string
	 */
	private $dfrnId = '';

	/**
	 * @var int
	 */
	private $expire = '0';

	/**
	 * @var string
	 */
	private $type = '';

	/**
	 * @var string
	 */
	private $lastUpdate = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'challenge' => $this->challenge,
			'dfrn-id' => $this->dfrnId,
			'expire' => $this->expire,
			'type' => $this->type,
			'last_update' => $this->lastUpdate,
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
	public function getChallenge()
	{
		return $this->challenge;
	}

	/**
	 * @param string $challenge
	 * Set
	 */
	public function setChallenge(string $challenge)
	{
		$this->challenge = $challenge;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getDfrnId()
	{
		return $this->dfrnId;
	}

	/**
	 * @param string $dfrnId
	 * Set
	 */
	public function setDfrnId(string $dfrnId)
	{
		$this->dfrnId = $dfrnId;
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

	/**
	 * @return string
	 * Get
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * Set
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getLastUpdate()
	{
		return $this->lastUpdate;
	}

	/**
	 * @param string $lastUpdate
	 * Set
	 */
	public function setLastUpdate(string $lastUpdate)
	{
		$this->lastUpdate = $lastUpdate;
	}
}
