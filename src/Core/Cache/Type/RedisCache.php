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
use Redis;

/**
 * Redis Cache. This driver is based on Memcache driver
 */
class RedisCache extends AbstractCache implements ICanCacheInMemory
{
	const NAME = 'redis';

	/**
	 * @var Redis
	 */
	private $redis;

	/**
	 * @throws InvalidCacheDriverException
	 * @throws CachePersistenceException
	 */
	public function __construct(string $hostname, IManageConfigValues $config)
	{
		if (!class_exists('Redis', false)) {
			throw new InvalidCacheDriverException('Redis class isn\'t available');
		}

		parent::__construct($hostname);

		$this->redis = new Redis();

		$redis_host = $config->get('system', 'redis_host');
		$redis_port = $config->get('system', 'redis_port');
		$redis_pw   = $config->get('system', 'redis_password');
		$redis_db   = (int)$config->get('system', 'redis_db', 0);

		try {
			if (is_numeric($redis_port) && $redis_port > -1) {
				$connection_string = $redis_host . ':' . $redis_port;
				if (!@$this->redis->connect($redis_host, $redis_port)) {
					throw new CachePersistenceException('Expected Redis server at ' . $connection_string . " isn't available");
				}
			} else {
				$connection_string = $redis_host;
				if (!@$this->redis->connect($redis_host)) {
					throw new CachePersistenceException('Expected Redis server at ' . $connection_string . ' isn\'t available');
				}
			}

			if (!empty($redis_pw) && !$this->redis->auth($redis_pw)) {
				throw new CachePersistenceException('Cannot authenticate redis server at ' . $connection_string);
			}

			if ($redis_db !== 0 && !$this->redis->select($redis_db)) {
				throw new CachePersistenceException('Cannot switch to redis db ' . $redis_db . ' at ' . $connection_string);
			}
		} catch (\RedisException $exception) {
			throw new CachePersistenceException('Redis connection fails unexpectedly', $exception);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		if (empty($prefix)) {
			$search = '*';
		} else {
			$search = $prefix . '*';
		}

		$list = $this->redis->keys($this->getCacheKey($search));

		return $this->getOriginalKeys($list);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		$return   = null;
		$cacheKey = $this->getCacheKey($key);

		$cached = $this->redis->get($cacheKey);
		if ($cached === false && !$this->redis->exists($cacheKey)) {
			return null;
		}

		$value = unserialize($cached);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($cached === serialize(false) || $value !== false) {
			$return = $value;
		}

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);

		$cached = serialize($value);

		if ($ttl > 0) {
			return $this->redis->setex(
				$cacheKey,
				$ttl,
				$cached
			);
		} else {
			return $this->redis->set(
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
		$this->redis->del($cacheKey);
		// Redis doesn't have an error state for del()
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		if ($outdated) {
			return true;
		} else {
			return $this->redis->flushAll();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);
		$cached   = serialize($value);

		return $this->redis->setnx($cacheKey, $cached);
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet(string $key, $oldValue, $newValue, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);

		$newCached = serialize($newValue);

		$this->redis->watch($cacheKey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $oldValue) {
			if ($ttl > 0) {
				$result = $this->redis->multi()->setex($cacheKey, $ttl, $newCached)->exec();
			} else {
				$result = $this->redis->multi()->set($cacheKey, $newCached)->exec();
			}
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareDelete(string $key, $value): bool
	{
		$cacheKey = $this->getCacheKey($key);

		$this->redis->watch($cacheKey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $value) {
			$this->redis->multi()->del($cacheKey)->exec();
			return true;
		}
		$this->redis->unwatch();
		return false;
	}

	/** {@inheritDoc} */
	public function getStats(): array
	{
		$info = $this->redis->info();

		return [
			'version'           => $info['redis_version']     ?? null,
			'entries'           => $this->redis->dbSize()     ?? null,
			'used_memory'       => $info['used_memory']       ?? null,
			'connected_clients' => $info['connected_clients'] ?? null,
			'uptime'            => $info['uptime_in_seconds'] ?? null,
			'hits'              => $info['keyspace_hits']     ?? null,
			'misses'            => $info['keyspace_misses']   ?? null,
			'evictions'         => $info['evicted_keys']      ?? null,
		];
	}
}
