<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Image\Collection;

use Friendica\Content\Image\Entity;
use Friendica\BaseCollection;
use Friendica\Content\Image\Entity\MasonryImage;

class MasonryImageRow extends BaseCollection
{
	/** @var ?float */
	protected $heightRatio;

	/**
	 * @param MasonryImage[] $entities
	 * @param int|null       $totalCount
	 * @param float|null     $heightRatio
	 */
	public function __construct(
		array $entities = [],
		?int $totalCount = null,
		?float $heightRatio = null,
	) {
		parent::__construct($entities, $totalCount);

		$this->heightRatio = $heightRatio;
	}

	/**
	 * @return Entity\MasonryImage
	 */
	public function current(): Entity\MasonryImage
	{
		return parent::current();
	}

	public function getHeightRatio(): ?float
	{
		return $this->heightRatio;
	}
}
