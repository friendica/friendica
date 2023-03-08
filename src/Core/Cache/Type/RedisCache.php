<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Cache\Type;

use Exception;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Enum\Type;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Redis;

/**
 * Redis Cache. This driver is based on Memcache driver
 */
class RedisCache extends AbstractCache implements ICanCacheInMemory
{
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
		$redis_db   = $config->get('system', 'redis_db', 0);

		if (!empty($redis_port) && !@$this->redis->connect($redis_host, $redis_port)) {
			throw new CachePersistenceException('Expected Redis server at ' . $redis_host . ':' . $redis_port . ' isn\'t available');
		} elseif (!@$this->redis->connect($redis_host)) {
			throw new CachePersistenceException('Expected Redis server at ' . $redis_host . ' isn\'t available');
		}

		if (!empty($redis_pw) && !$this->redis->auth($redis_pw)) {
			throw new CachePersistenceException('Cannot authenticate redis server at ' . $redis_host . ':' . $redis_port);
		}

		if ($redis_db !== 0 && !$this->redis->select($redis_db)) {
			throw new CachePersistenceException('Cannot switch to redis db ' . $redis_db . ' at ' . $redis_host . ':' . $redis_port);
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

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return Type::REDIS;
	}
}
