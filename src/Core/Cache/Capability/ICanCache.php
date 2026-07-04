<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Capability;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Exception\CachePersistenceException;

/**
 * Interface for caches
 */
interface ICanCache
{
	public const NAME = '';
	/**
	 * Lists all cache keys
	 *
	 * @param string|null $prefix optional a prefix to search
	 *
	 * @return array Empty if it isn't supported by the cache driver
	 */
	public function getAllKeys(?string $prefix = null): array;

	/**
	 * Fetches cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function get(string $key);

	/**
	 * Stores data in the cache identified by the key. The input $value can have multiple formats.
	 *
	 * @param string  $key   The cache key
	 * @param mixed   $value The value to store
	 * @param integer $ttl   The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Delete a key from the cache
	 *
	 * @param string $key The cache key
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function delete(string $key): bool;

	/**
	 * Remove outdated data from the cache
	 *
	 * @param boolean $outdated just remove outdated values
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function clear(bool $outdated = true): bool;

	/**
	 * Returns the name of the current cache
	 *
	 * @return string
	 */
	public function getName(): string;
}
