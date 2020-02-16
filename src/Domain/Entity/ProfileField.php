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
 * Entity class for table profile_field
 *
 * Custom profile fields
 */
class ProfileField extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * Owner user id
	 */
	private $uid = '0';

	/**
	 * @var int
	 * Field ordering per user
	 */
	private $order = '1';

	/**
	 * @var int
	 * ID of the permission set of this profile field - 0 = public
	 */
	private $psid;

	/**
	 * @var string
	 * Label of the field
	 */
	private $label = '';

	/**
	 * @var string
	 * Value of the field
	 */
	private $value;

	/**
	 * @var string
	 * creation time
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * last edit time
	 */
	private $edited = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'order' => $this->order,
			'psid' => $this->psid,
			'label' => $this->label,
			'value' => $this->value,
			'created' => $this->created,
			'edited' => $this->edited,
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
	 * Get Owner user id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set Owner user id
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
	 * Get Field ordering per user
	 */
	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @param int $order
	 * Set Field ordering per user
	 */
	public function setOrder(int $order)
	{
		$this->order = $order;
	}

	/**
	 * @return int
	 * Get ID of the permission set of this profile field - 0 = public
	 */
	public function getPsid()
	{
		return $this->psid;
	}

	/**
	 * @param int $psid
	 * Set ID of the permission set of this profile field - 0 = public
	 */
	public function setPsid(int $psid)
	{
		$this->psid = $psid;
	}

	/**
	 * Get Permissionset
	 *
	 * @return Permissionset
	 */
	public function getPermissionset()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for id is not implemented yet');
	}

	/**
	 * @return string
	 * Get Label of the field
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $label
	 * Set Label of the field
	 */
	public function setLabel(string $label)
	{
		$this->label = $label;
	}

	/**
	 * @return string
	 * Get Value of the field
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $value
	 * Set Value of the field
	 */
	public function setValue(string $value)
	{
		$this->value = $value;
	}

	/**
	 * @return string
	 * Get creation time
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set creation time
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return string
	 * Get last edit time
	 */
	public function getEdited()
	{
		return $this->edited;
	}

	/**
	 * @param string $edited
	 * Set last edit time
	 */
	public function setEdited(string $edited)
	{
		$this->edited = $edited;
	}
}
