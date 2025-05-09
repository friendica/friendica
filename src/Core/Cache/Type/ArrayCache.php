<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Enum;

/**
 * Implementation of the IMemoryCache mainly for testing purpose
 */
class ArrayCache extends AbstractCache implements ICanCacheInMemory
{
	use CompareDeleteTrait;
	const NAME = 'array';

	/** @var array Array with the cached data */
	protected $cachedData = [];

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		return $this->filterArrayKeysByPrefix(array_keys($this->cachedData), $prefix);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		if (isset($this->cachedData[$key])) {
			return $this->cachedData[$key];
		}
		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set(string $key, $value, int $ttl = Enum\Duration::FIVE_MINUTES): bool
	{
		$this->cachedData[$key] = $value;
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		unset($this->cachedData[$key]);
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		// Array doesn't support TTL so just don't delete something
		if ($outdated) {
			return true;
		}

		$this->cachedData = [];
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function add(string $key, $value, int $ttl = Enum\Duration::FIVE_MINUTES): bool
	{
		if (isset($this->cachedData[$key])) {
			return false;
		} else {
			return $this->set($key, $value, $ttl);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet(string $key, $oldValue, $newValue, int $ttl = Enum\Duration::FIVE_MINUTES): bool
	{
		if ($this->get($key) === $oldValue) {
			return $this->set($key, $newValue);
		} else {
			return false;
		}
	}

	/** {@inheritDoc} */
	public function getStats(): array
	{
		return [];
	}
}
