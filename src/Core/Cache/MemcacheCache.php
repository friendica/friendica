<?php

namespace Friendica\Core\Cache;

use Exception;
use Friendica\Core\Config\IConfiguration;
use Memcache;

/**
 * Memcache Cache
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class MemcacheCache extends Cache implements IMemoryCache
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
	public function __construct(string $hostname, IConfiguration $config)
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
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES)
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
	public function add($key, $value, $ttl = Cache::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcache->add($cachekey, serialize($value), MEMCACHE_COMPRESSED, $ttl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return self::TYPE_MEMCACHE;
	}
}
