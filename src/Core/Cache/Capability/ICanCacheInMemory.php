<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Capability;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Exception\CachePersistenceException;

/**
 * This interface defines methods for Memory-Caches only
 */
interface ICanCacheInMemory extends ICanCache
{
	/**
	 * Sets a value if it's not already stored
	 *
	 * @param string $key   The cache key
	 * @param mixed  $value The old value we know from the cache
	 * @param int    $ttl   The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Compares if the old value is set and sets the new value
	 *
	 * @param string $key      The cache key
	 * @param mixed  $oldValue The old value we know from the cache
	 * @param mixed  $newValue The new value we want to set
	 * @param int    $ttl      The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function compareSet(string $key, $oldValue, $newValue, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Compares if the old value is set and removes it
	 *
	 * @param string $key   The cache key
	 * @param mixed  $value The old value we know and want to delete
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function compareDelete(string $key, $value): bool;

	/**
	 * Returns some basic statistics of the used Cache instance
	 *
	 * @return array Returns an associative array of statistics
	 */
	public function getStats(): array;
}
