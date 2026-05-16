<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Role
 *
 * @see https://docs.joinmastodon.org/entities/Role/
 */
class Role extends BaseDataTransferObject
{
	/** @var integer */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string */
	protected $color;
	/** @var string */
	protected $permissions;
	/** @var bool */
	protected $highlighted;

	/**
	 * @param int    $id
	 * @param string $name
	 * @param string $color
	 * @param string $permissions
	 * @param bool   $highlighted
	 */
	public function __construct(int $id, string $name, string $color, string $permissions, bool $highlighted)
	{
		$this->id          = $id;
		$this->name        = $name;
		$this->color       = $color;
		$this->permissions = $permissions;
		$this->highlighted = $highlighted;
	}
}
