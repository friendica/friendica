<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Collection\Api\Mastodon;

use Friendica\BaseCollection;
use Friendica\Object\Api\Mastodon\Field;

class Fields extends BaseCollection
{
	/**
	 * @param Field[]  $entities
	 * @param int|null $totalCount
	 */
	public function __construct(array $entities = [], ?int $totalCount = null)
	{
		parent::__construct($entities);

		$this->totalCount = $totalCount ?? count($entities);
	}
}
