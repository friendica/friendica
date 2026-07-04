<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\BaseEntity;

/**
 * @property-read string $protString
 * @property-read int $protInt
 * @property-read \DateTime $protDateTime
 */
class EntityDouble extends BaseEntity
{
	protected $protString;
	protected $protInt;
	protected $protDateTime;

	public function __construct(string $protString, int $protInt, \DateTime $protDateTime, private readonly string $privString) // @phpstan-ignore property.onlyWritten
	{
		$this->protString   = $protString;
		$this->protInt      = $protInt;
		$this->protDateTime = $protDateTime;
	}
}
