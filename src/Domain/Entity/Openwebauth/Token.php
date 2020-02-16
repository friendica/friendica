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

namespace Friendica\Domain\Entity\Openwebauth;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table openwebauth-token
 *
 * Store OpenWebAuth token to verify contacts
 */
class Token extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 * Verify type
	 */
	private $type = '';

	/**
	 * @var string
	 * A generated token
	 */
	private $token = '';

	/**
	 * @var string
	 */
	private $meta = '';

	/**
	 * @var string
	 * datetime of creation
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'type' => $this->type,
			'token' => $this->token,
			'meta' => $this->meta,
			'created' => $this->created,
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
	 * Get User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set User id
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
	 * @return string
	 * Get Verify type
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * Set Verify type
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 * Get A generated token
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 * Set A generated token
	 */
	public function setToken(string $token)
	{
		$this->token = $token;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getMeta()
	{
		return $this->meta;
	}

	/**
	 * @param string $meta
	 * Set
	 */
	public function setMeta(string $meta)
	{
		$this->meta = $meta;
	}

	/**
	 * @return string
	 * Get datetime of creation
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set datetime of creation
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}
}
