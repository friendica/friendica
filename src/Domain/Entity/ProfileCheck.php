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
 * Entity class for table profile_check
 *
 * DFRN remote auth use
 */
class ProfileCheck extends BaseEntity
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
	 * @var int
	 * contact.id
	 */
	private $cid = '0';

	/**
	 * @var string
	 */
	private $dfrnId = '';

	/**
	 * @var string
	 */
	private $sec = '';

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
			'uid' => $this->uid,
			'cid' => $this->cid,
			'dfrn_id' => $this->dfrnId,
			'sec' => $this->sec,
			'expire' => $this->expire,
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
	 * @return int
	 * Get contact.id
	 */
	public function getCid()
	{
		return $this->cid;
	}

	/**
	 * @param int $cid
	 * Set contact.id
	 */
	public function setCid(int $cid)
	{
		$this->cid = $cid;
	}

	/**
	 * Get Contact
	 *
	 * @return Contact
	 */
	public function getContact()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
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
	 * @return string
	 * Get
	 */
	public function getSec()
	{
		return $this->sec;
	}

	/**
	 * @param string $sec
	 * Set
	 */
	public function setSec(string $sec)
	{
		$this->sec = $sec;
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
