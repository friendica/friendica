<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;

/**
 * APCu Cache.
 */
class APCuCache extends AbstractCache implements ICanCacheInMemory
{
	use CompareSetTrait;
	use CompareDeleteTrait;
	const NAME = 'apcu';

	/**
	 * @throws InvalidCacheDriverException
	 */
	public function __construct(string $hostname)
	{
		if (!self::isAvailable()) {
			throw new InvalidCacheDriverException('APCu is not available.');
		}

		parent::__construct($hostname);
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		$ns = $this->getCacheKey($prefix ?? '');
		$ns = preg_quote($ns, '/');

		$iterator = new \APCUIterator('/^' . $ns . '/', APC_ITER_KEY);

		$keys = [];
		foreach ($iterator as $item) {
			array_push($keys, $item['key']);
		}

		return $this->getOriginalKeys($keys);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		$cacheKey = $this->getCacheKey($key);

		$cached = apcu_fetch($cacheKey, $success);
		if (!$success) {
			return null;
		}

		$value = unserialize($cached);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($cached === serialize(false) || $value !== false) {
			return $value;
		}

		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);

		$cached = serialize($value);

		if ($ttl > 0) {
			return apcu_store(
				$cacheKey,
				$cached,
				$ttl
			);
		} else {
			return apcu_store(
				$cacheKey,
				$cached
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return apcu_delete($cacheKey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		if ($outdated) {
			return true;
		} else {
			$prefix = $this->getPrefix();
			$prefix = preg_quote($prefix, '/');

			$iterator = new \APCUIterator('/^' . $prefix . '/', APC_ITER_KEY);

			return apcu_delete($iterator);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);
		$cached   = serialize($value);

		return apcu_add($cacheKey, $cached);
	}

	public static function isAvailable(): bool
	{
		if (!extension_loaded('apcu')) {
			return false;
		} elseif (!ini_get('apc.enabled') && !ini_get('apc.enable_cli')) {
			return false;
		} elseif (version_compare(phpversion('apcu') ?: '0.0.0', '5.1.0', '<')) {
			return false;
		}

		return true;
	}

	/** {@inheritDoc} */
	public function getStats(): array
	{
		$apcu = apcu_cache_info();
		$sma  = apcu_sma_info();

		return [
			'entries'     => $apcu['num_entries'] ?? null,
			'used_memory' => $apcu['mem_size']    ?? null,
			'hits'        => $apcu['num_hits']    ?? null,
			'misses'      => $apcu['num_misses']  ?? null,
			'avail_mem'   => $sma['avail_mem']    ?? null,
		];
	}
}
