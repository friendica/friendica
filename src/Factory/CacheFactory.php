<?php

namespace Friendica\Factory;

use Friendica\App\BaseURL;
use Friendica\Core\Cache;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Config\IConfiguration;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Class CacheFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a CacheDriver
 */
class CacheFactory
{
	/**
	 * @var string The default cache if nothing set
	 */
	const DEFAULT_TYPE = Cache\Cache::TYPE_DATABASE;

	/**
	 * @var IConfiguration The IConfiguration to read parameters out of the config
	 */
	private $config;

	/**
	 * @var Database The database connection in case that the cache is used the dba connection
	 */
	private $dba;

	/**
	 * @var string The hostname, used as Prefix for Caching
	 */
	private $hostname;

	/**
	 * @var Profiler The optional profiler if the cached should be profiled
	 */
	private $profiler;

	/**
	 * @var LoggerInterface The Friendica Logger
	 */
	private $logger;

	public function __construct(BaseURL $baseURL, IConfiguration $config, Database $dba, Profiler $profiler, LoggerInterface $logger)
	{
		$this->hostname = $baseURL->getHostname();
		$this->config   = $config;
		$this->dba      = $dba;
		$this->profiler = $profiler;
		$this->logger   = $logger;
	}

	/**
	 * This method creates a CacheDriver for the given cache driver name
	 *
	 * @param string $type The cache type to create (default is per config)
	 *
	 * @return ICache  The instance of the CacheDriver
	 * @throws \Exception    The exception if something went wrong during the CacheDriver creation
	 */
	public function create(string $type = null)
	{
		if (empty($type)) {
			$type = $this->config->get('system', 'cache_driver', self::DEFAULT_TYPE);
		}

		switch ($type) {
			case Cache\Cache::TYPE_MEMCACHE:
				$cache = new Cache\MemcacheCache($this->hostname, $this->config);
				break;
			case Cache\Cache::TYPE_MEMCACHED:
				$cache = new Cache\MemcachedCache($this->hostname, $this->config, $this->logger);
				break;
			case Cache\Cache::TYPE_REDIS:
				$cache = new Cache\RedisCache($this->hostname, $this->config);
				break;
			case Cache\Cache::TYPE_APCU:
				$cache = new Cache\APCuCache($this->hostname);
				break;
			default:
				$cache = new Cache\DatabaseCache($this->hostname, $this->dba);
		}

		$profiling = $this->config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerCache around the current cache
		if (isset($profiling) && $profiling !== false) {
			return new Cache\ProfilerCache($cache, $this->profiler);
		} else {
			return $cache;
		}
	}
}
