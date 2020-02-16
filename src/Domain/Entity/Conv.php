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
 * Entity class for table conv
 *
 * private messages
 */
class Conv extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 * A unique identifier for this conversation
	 */
	private $guid = '';

	/**
	 * @var string
	 * sender_handle;recipient_handle
	 */
	private $recips;

	/**
	 * @var int
	 * Owner User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 * handle of creator
	 */
	private $creator = '';

	/**
	 * @var string
	 * creation timestamp
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * edited timestamp
	 */
	private $updated = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * subject of initial message
	 */
	private $subject;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'guid' => $this->guid,
			'recips' => $this->recips,
			'uid' => $this->uid,
			'creator' => $this->creator,
			'created' => $this->created,
			'updated' => $this->updated,
			'subject' => $this->subject,
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
	 * Get A unique identifier for this conversation
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set A unique identifier for this conversation
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
	}

	/**
	 * @return string
	 * Get sender_handle;recipient_handle
	 */
	public function getRecips()
	{
		return $this->recips;
	}

	/**
	 * @param string $recips
	 * Set sender_handle;recipient_handle
	 */
	public function setRecips(string $recips)
	{
		$this->recips = $recips;
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
	 * @return string
	 * Get handle of creator
	 */
	public function getCreator()
	{
		return $this->creator;
	}

	/**
	 * @param string $creator
	 * Set handle of creator
	 */
	public function setCreator(string $creator)
	{
		$this->creator = $creator;
	}

	/**
	 * @return string
	 * Get creation timestamp
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set creation timestamp
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get edited timestamp
	 */
	public function getUpdated()
	{
		return $this->updated;
	}

	/**
	 * @param string $updated
	 * Set edited timestamp
	 */
	public function setUpdated(string $updated)
	{
		$this->updated = $updated;
	}

	/**
	 * @return string
	 * Get subject of initial message
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * @param string $subject
	 * Set subject of initial message
	 */
	public function setSubject(string $subject)
	{
		$this->subject = $subject;
	}
}
