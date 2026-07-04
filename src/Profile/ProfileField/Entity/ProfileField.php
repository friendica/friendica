<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
 * @property-read int       $uriId
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
	protected $uriId;
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

	public function __construct(
		int $uid,
		int $order,
		string $label,
		string $value,
		\DateTime $created,
		\DateTime $edited,
		PermissionSet $permissionSet,
		?int $id = null,
		?int $uriId = null,
	) {
		$this->permissionSet = $permissionSet;
		$this->uid           = $uid;
		$this->order         = $order;
		$this->label         = $label;
		$this->value         = $value;
		$this->created       = $created;
		$this->edited        = $edited;
		$this->id            = $id;
		$this->uriId         = $uriId;
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
