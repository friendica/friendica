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
 * Entity class for table group_member
 *
 * privacy groups, member info
 */
class GroupMember extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * groups.id of the associated group
	 */
	private $gid = '0';

	/**
	 * @var int
	 * contact.id of the member assigned to the associated group
	 */
	private $contactId = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'gid' => $this->gid,
			'contact-id' => $this->contactId,
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
	 * Get groups.id of the associated group
	 */
	public function getGid()
	{
		return $this->gid;
	}

	/**
	 * @param int $gid
	 * Set groups.id of the associated group
	 */
	public function setGid(int $gid)
	{
		$this->gid = $gid;
	}

	/**
	 * Get Group
	 *
	 * @return Group
	 */
	public function getGroup()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return int
	 * Get contact.id of the member assigned to the associated group
	 */
	public function getContactId()
	{
		return $this->contactId;
	}

	/**
	 * @param int $contactId
	 * Set contact.id of the member assigned to the associated group
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
}
