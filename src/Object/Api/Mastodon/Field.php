<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Field
 *
 * @see https://docs.joinmastodon.org/entities/field/
 */
class Field extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var string (HTML) */
	protected $value;
	/** @var string|null (Datetime)*/
	protected $verified_at;

	public function __construct(string $name, string $value)
	{
		$this->name  = $name;
		$this->value = $value;
		// Link verification unsupported
		$this->verified_at = null;
	}
}
