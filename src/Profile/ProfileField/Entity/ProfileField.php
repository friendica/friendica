<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
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
 */

namespace Friendica\Profile\ProfileField\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Profile\ProfileField\Exception\ProfileFieldNotFoundException;
use Friendica\Security\PermissionSet\Entity\PermissionSet;

/**
 * Custom profile field entity class.
 *
 * Custom profile fields are user-created arbitrary profile fields that can be assigned a permission set to restrict its
 * display to specific Friendica contacts as it requires magic authentication to work.
 *
 * @property-read int|null  $id
 * @property-read int       $uid
 * @property-read int       $order
 * @property-read string    $label
 * @property-read string    $value
 * @property-read \DateTime $created
 * @property-read \DateTime $edited
 * @property PermissionSet  $permissionSet
 */
class ProfileField extends BaseEntity
{
	/** @var int|null */
	protected $id;
	/** @var PermissionSet */
	protected $permissionSet;
	/** @var int */
	protected $uid;
	/** @var int */
	protected $order;
	/** @var string */
	protected $label;
	/** @var string */
	protected $value;
	/** @var \DateTime */
	protected $created;
	/** @var \DateTime */
	protected $edited;

	public function __construct(int $uid, int $order, string $label, string $value, \DateTime $created, \DateTime $edited, PermissionSet $permissionSet, int $id = null)
	{
		$this->permissionSet = $permissionSet;
		$this->uid           = $uid;
		$this->order         = $order;
		$this->label         = $label;
		$this->value         = $value;
		$this->created       = $created;
		$this->edited        = $edited;
		$this->id            = $id;
	}

	/**
	 * @throws ProfileFieldNotFoundException
	 */
	public function __get($name)
	{
		try {
			return parent::__get($name);
		} catch (InternalServerErrorException $exception) {
			throw new ProfileFieldNotFoundException($exception->getMessage());
		}
	}

	/**
	 * Updates a ProfileField
	 *
	 * @param string        $value         The current or changed value
	 * @param int           $order         The current or changed order
	 * @param PermissionSet $permissionSet The current or changed PermissionSet
	 */
	public function update(string $value, int $order, PermissionSet $permissionSet)
	{
		$this->value         = $value;
		$this->order         = $order;
		$this->permissionSet = $permissionSet;
		$this->edited        = new \DateTime('now', new \DateTimeZone('UTC'));
	}

	/**
	 * Sets the order of the ProfileField
	 *
	 * @param int $order
	 */
	public function setOrder(int $order)
	{
		$this->order  = $order;
		$this->edited = new \DateTime('now', new \DateTimeZone('UTC'));
	}
}
