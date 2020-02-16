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
 * Entity class for table gcign
 *
 * contacts ignored by friend suggestions
 */
class Gcign extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * Local User id
	 */
	private $uid = '0';

	/**
	 * @var int
	 * gcontact.id of ignored contact
	 */
	private $gcid = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'gcid' => $this->gcid,
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
	 * Get Local User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set Local User id
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
	 * Get gcontact.id of ignored contact
	 */
	public function getGcid()
	{
		return $this->gcid;
	}

	/**
	 * @param int $gcid
	 * Set gcontact.id of ignored contact
	 */
	public function setGcid(int $gcid)
	{
		$this->gcid = $gcid;
	}

	/**
	 * Get Gcontact
	 *
	 * @return Gcontact
	 */
	public function getGcontact()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}
}
