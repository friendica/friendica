<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Memcache;

/**
 * Memcache Cache
 */
class MemcacheCache extends AbstractCache implements ICanCacheInMemory
{
	use CompareSetTrait;
	use CompareDeleteTrait;
	use MemcacheCommandTrait;
	const NAME = 'memcache';

	/**
	 * @var Memcache
	 */
	private $memcache;

	/**
	 * @param string              $hostname
	 * @param IManageConfigValues $config
	 *
	 * @throws InvalidCacheDriverException
	 * @throws CachePersistenceException
	 */
	public function __construct(string $hostname, IManageConfigValues $config)
	{
		if (!class_exists('Memcache', false)) {
			throw new InvalidCacheDriverException('Memcache class isn\'t available');
		}

		parent::__construct($hostname);

		$this->memcache = new Memcache();

		$this->server = $config->get('system', 'memcache_host');
		$this->port   = $config->get('system', 'memcache_port');

		if (!@$this->memcache->connect($this->server, $this->port)) {
			throw new CachePersistenceException('Expected Memcache server at ' . $this->server . ':' . $this->port . ' isn\'t available');
		}
	}

	/**
	 * Memcache doesn't allow spaces in keys
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getCacheKey(string $key): string
	{
		return str_replace(' ', '_', parent::getCacheKey($key));
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		$keys = $this->getOriginalKeys($this->getMemcacheKeys());

		return $this->filterArrayKeysByPrefix($keys, $prefix);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		$cacheKey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$cached = $this->memcache->get($cacheKey);

		// @see http://php.net/manual/en/memcache.get.php#84275
		if (is_bool($cached) || is_double($cached) || is_long($cached)) {
			return null;
		}

		$value = @unserialize($cached);

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

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcache->set(
				$cacheKey,
				serialize($value),
				MEMCACHE_COMPRESSED,
				time() + $ttl
			);
		} else {
			return $this->memcache->set(
				$cacheKey,
				serialize($value),
				MEMCACHE_COMPRESSED
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return $this->memcache->delete($cacheKey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		if ($outdated) {
			return true;
		} else {
			return $this->memcache->flush();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return $this->memcache->add($cacheKey, serialize($value), MEMCACHE_COMPRESSED, $ttl);
	}

	/** {@inheritDoc} */
	public function getStats(): array
	{
		$stats = $this->memcache->getStats();

		return [
			'version'           => $stats['version']          ?? null,
			'entries'           => $stats['curr_items']       ?? null,
			'used_memory'       => $stats['bytes']            ?? null,
			'uptime'            => $stats['uptime']           ?? null,
			'connected_clients' => $stats['curr_connections'] ?? null,
			'hits'              => $stats['get_hits']         ?? null,
			'misses'            => $stats['get_misses']       ?? null,
			'evictions'         => $stats['evictions']        ?? null,
		];
	}
}
