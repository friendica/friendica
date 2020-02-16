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

namespace Friendica\Domain\Entity\User;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table user-contact
 *
 * User specific public contact data
 */
class Contact extends BaseEntity
{
	/**
	 * @var int
	 * Contact id of the linked public contact
	 */
	private $cid = '0';

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * @var bool
	 * Contact is completely blocked for this user
	 */
	private $blocked;

	/**
	 * @var bool
	 * Posts from this contact are ignored
	 */
	private $ignored;

	/**
	 * @var bool
	 * Posts from this contact are collapsed
	 */
	private $collapsed;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'cid' => $this->cid,
			'uid' => $this->uid,
			'blocked' => $this->blocked,
			'ignored' => $this->ignored,
			'collapsed' => $this->collapsed,
		];
	}

	/**
	 * @return int
	 * Get Contact id of the linked public contact
	 */
	public function getCid()
	{
		return $this->cid;
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
	 * @return int
	 * Get User id
	 */
	public function getUid()
	{
		return $this->uid;
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
	 * Get Contact is completely blocked for this user
	 */
	public function getBlocked()
	{
		return $this->blocked;
	}

	/**
	 * @param bool $blocked
	 * Set Contact is completely blocked for this user
	 */
	public function setBlocked(bool $blocked)
	{
		$this->blocked = $blocked;
	}

	/**
	 * @return bool
	 * Get Posts from this contact are ignored
	 */
	public function getIgnored()
	{
		return $this->ignored;
	}

	/**
	 * @param bool $ignored
	 * Set Posts from this contact are ignored
	 */
	public function setIgnored(bool $ignored)
	{
		$this->ignored = $ignored;
	}

	/**
	 * @return bool
	 * Get Posts from this contact are collapsed
	 */
	public function getCollapsed()
	{
		return $this->collapsed;
	}

	/**
	 * @param bool $collapsed
	 * Set Posts from this contact are collapsed
	 */
	public function setCollapsed(bool $collapsed)
	{
		$this->collapsed = $collapsed;
	}
}
