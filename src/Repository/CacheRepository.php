<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Repository;

use Friendica\Database\DatabaseException;
use Friendica\Entity\CacheEntity;

/**
 * Interface for a cache repository
 */
interface CacheRepository
{
	/**
	 * @throws DatabaseException
	 *
	 * @return array<string>
	 */
	public function getAllKeysValidUntil(string $expires): array;

	/**
	 * @throws DatabaseException
	 *
	 * @return array<string>
	 */
	public function getAllKeysValidUntilWithPrefix(string $expires, string $prefix): array;

	/**
	 * @throws DatabaseException
	 *
	 * @return CacheEntity|null
	 */
	public function findOneByKeyValidUntil(string $key, string $expires);
}
