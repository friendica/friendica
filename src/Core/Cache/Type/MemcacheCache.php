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
use Friendica\Core\Cache\Type\TraitCompareDelete;
use Friendica\Core\Cache\Type\TraitCompareSet;
use Friendica\Core\Cache\Type\TraitMemcacheCommand;
use Friendica\Core\Cache\Enum\Type;
use Friendica\Core\Config\IConfig;
use Memcache;

/**
 * Memcache Cache
 */
class MemcacheCache extends BaseCache implements IMemoryCache
{
	use TraitCompareSet;
	use TraitCompareDelete;
	use TraitMemcacheCommand;

	/**
	 * @var Memcache
	 */
	private $memcache;

	/**
	 * @throws Exception
	 */
	public function __construct(string $hostname, IConfig $config)
	{
		if (!class_exists('Memcache', false)) {
			throw new Exception('Memcache class isn\'t available');
		}

		parent::__construct($hostname);

		$this->memcache = new Memcache();

		$this->server = $config->get('system', 'memcache_host');;
		$this->port = $config->get('system', 'memcache_port');

		if (!@$this->memcache->connect($this->server, $this->port)) {
			throw new Exception('Expected Memcache server at ' . $this->server . ':' . $this->port . ' isn\'t available');
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		$keys = $this->getOriginalKeys($this->getMemcacheKeys());

		return $this->filterArrayKeysByPrefix($keys, $prefix);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$return   = null;
		$cachekey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$cached = $this->memcache->get($cachekey);

		// @see http://php.net/manual/en/memcache.get.php#84275
		if (is_bool($cached) || is_double($cached) || is_long($cached)) {
			return $return;
		}

		$value = @unserialize($cached);

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

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcache->set(
				$cachekey,
				serialize($value),
				MEMCACHE_COMPRESSED,
				time() + $ttl
			);
		} else {
			return $this->memcache->set(
				$cachekey,
				serialize($value),
				MEMCACHE_COMPRESSED
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcache->delete($cachekey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
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
	public function add($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcache->add($cachekey, serialize($value), MEMCACHE_COMPRESSED, $ttl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::MEMCACHE;
	}
}
