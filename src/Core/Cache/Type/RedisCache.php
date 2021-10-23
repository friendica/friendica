<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\Core\Cache\IMemoryCache;
use Friendica\Core\Cache\Enum\Type;
use Friendica\Core\Config\IConfig;
use Redis;

/**
 * Redis Cache. This driver is based on Memcache driver
 */
class RedisCache extends BaseCache implements IMemoryCache
{
	/**
	 * @var Redis
	 */
	private $redis;

	/**
	 * @throws Exception
	 */
	public function __construct(string $hostname, IConfig $config)
	{
		if (!class_exists('Redis', false)) {
			throw new Exception('Redis class isn\'t available');
		}

		parent::__construct($hostname);

		$this->redis = new Redis();

		$redis_host = $config->get('system', 'redis_host');
		$redis_port = $config->get('system', 'redis_port');
		$redis_pw   = $config->get('system', 'redis_password');
		$redis_db   = $config->get('system', 'redis_db', 0);

		if (isset($redis_port) && !@$this->redis->connect($redis_host, $redis_port)) {
			throw new Exception('Expected Redis server at ' . $redis_host . ':' . $redis_port . ' isn\'t available');
		} elseif (!@$this->redis->connect($redis_host)) {
			throw new Exception('Expected Redis server at ' . $redis_host . ' isn\'t available');
		}

		if (isset($redis_pw) && !$this->redis->auth($redis_pw)) {
			throw new Exception('Cannot authenticate redis server at ' . $redis_host . ':' . $redis_port);
		}

		if ($redis_db !== 0 && !$this->redis->select($redis_db)) {
			throw new Exception('Cannot switch to redis db ' . $redis_db . ' at ' . $redis_host . ':' . $redis_port);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
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
	public function get($key)
	{
		$return = null;
		$cachekey = $this->getCacheKey($key);

		$cached = $this->redis->get($cachekey);
		if ($cached === false && !$this->redis->exists($cachekey)) {
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
	public function set($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		$cached = serialize($value);

		if ($ttl > 0) {
			return $this->redis->setex(
				$cachekey,
				$ttl,
				$cached
			);
		} else {
			return $this->redis->set(
				$cachekey,
				$cached
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		$this->redis->del($cachekey);
		// Redis doesn't have an error state for del()
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
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
	public function add($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		$cached = serialize($value);

		return $this->redis->setnx($cachekey, $cached);
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		$newCached = serialize($newValue);

		$this->redis->watch($cachekey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $oldValue) {
			if ($ttl > 0) {
				$result = $this->redis->multi()
					->setex($cachekey, $ttl, $newCached)
					->exec();
			} else {
				$result = $this->redis->multi()
					->set($cachekey, $newCached)
					->exec();
			}
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareDelete($key, $value)
	{
		$cachekey = $this->getCacheKey($key);

		$this->redis->watch($cachekey);
		// If the old value isn't what we expected, somebody else changed the key meanwhile
		if ($this->get($key) === $value) {
			$result = $this->redis->multi()
				->del($cachekey)
				->exec();
			return $result !== false;
		}
		$this->redis->unwatch();
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::REDIS;
	}
}
