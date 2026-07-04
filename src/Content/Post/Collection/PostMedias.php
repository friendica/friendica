<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Post\Collection;

use Friendica\BaseCollection;
use Friendica\Content\Post\Entity;

class PostMedias extends BaseCollection
{
	/**
	 * @param Entity\PostMedia[] $entities
	 * @param int|null                   $totalCount
	 */
	public function __construct(array $entities = [], ?int $totalCount = null)
	{
		parent::__construct($entities, $totalCount);
	}

	/**
	 * @return Entity\PostMedia
	 */
	public function current(): Entity\PostMedia
	{
		return parent::current();
	}

	/**
	 * Determine whether all the collection's item have at least one set of dimensions provided
	 *
	 * @return bool
	 */
	public function haveDimensions(): bool
	{
		return array_reduce($this->getArrayCopy(), function (bool $carry, Entity\PostMedia $item) {
			return $carry && $item->hasDimensions();
		}, true);
	}
}
