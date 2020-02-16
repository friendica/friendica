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
 * Entity class for table intro
 */
class Intro extends BaseEntity
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
	 */
	private $fid = '0';

	/**
	 * @var int
	 */
	private $contactId = '0';

	/**
	 * @var bool
	 */
	private $knowyou = '0';

	/**
	 * @var bool
	 */
	private $duplex = '0';

	/**
	 * @var string
	 */
	private $note;

	/**
	 * @var string
	 */
	private $hash = '';

	/**
	 * @var string
	 */
	private $datetime = '0001-01-01 00:00:00';

	/**
	 * @var bool
	 */
	private $blocked = '1';

	/**
	 * @var bool
	 */
	private $ignore = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'fid' => $this->fid,
			'contact-id' => $this->contactId,
			'knowyou' => $this->knowyou,
			'duplex' => $this->duplex,
			'note' => $this->note,
			'hash' => $this->hash,
			'datetime' => $this->datetime,
			'blocked' => $this->blocked,
			'ignore' => $this->ignore,
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
	 * Get
	 */
	public function getFid()
	{
		return $this->fid;
	}

	/**
	 * @param int $fid
	 * Set
	 */
	public function setFid(int $fid)
	{
		$this->fid = $fid;
	}

	/**
	 * Get Fcontact
	 *
	 * @return Fcontact
	 */
	public function getFcontact()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return int
	 * Get
	 */
	public function getContactId()
	{
		return $this->contactId;
	}

	/**
	 * @param int $contactId
	 * Set
	 */
	public function setContactId(int $contactId)
	{
		$this->contactId = $contactId;
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
	 * @return bool
	 * Get
	 */
	public function getKnowyou()
	{
		return $this->knowyou;
	}

	/**
	 * @param bool $knowyou
	 * Set
	 */
	public function setKnowyou(bool $knowyou)
	{
		$this->knowyou = $knowyou;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getDuplex()
	{
		return $this->duplex;
	}

	/**
	 * @param bool $duplex
	 * Set
	 */
	public function setDuplex(bool $duplex)
	{
		$this->duplex = $duplex;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * @param string $note
	 * Set
	 */
	public function setNote(string $note)
	{
		$this->note = $note;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getHash()
	{
		return $this->hash;
	}

	/**
	 * @param string $hash
	 * Set
	 */
	public function setHash(string $hash)
	{
		$this->hash = $hash;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getDatetime()
	{
		return $this->datetime;
	}

	/**
	 * @param string $datetime
	 * Set
	 */
	public function setDatetime(string $datetime)
	{
		$this->datetime = $datetime;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getBlocked()
	{
		return $this->blocked;
	}

	/**
	 * @param bool $blocked
	 * Set
	 */
	public function setBlocked(bool $blocked)
	{
		$this->blocked = $blocked;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getIgnore()
	{
		return $this->ignore;
	}

	/**
	 * @param bool $ignore
	 * Set
	 */
	public function setIgnore(bool $ignore)
	{
		$this->ignore = $ignore;
	}
}
