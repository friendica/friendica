<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database\Entity;

/**
 * Entity for a row in the cache table
 */
interface CacheEntity
{
	/**
	 * @return mixed
	 */
	public function getValue();
}
