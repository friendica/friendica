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

namespace Friendica\Core\Cache\Factory;

use Friendica\App\BaseURL;
use Friendica\Core\Cache\Enum;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Cache\Type;
use Friendica\Core\Config\Capability\IManageConfigValues;
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
class Cache
{
	/**
	 * @var string The default cache if nothing set
	 */
	const DEFAULT_TYPE = Enum\Type::DATABASE;

	/**
	 * @var IManageConfigValues The IConfiguration to read parameters out of the config
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

	public function __construct(BaseURL $baseURL, IManageConfigValues $config, Database $dba, Profiler $profiler, LoggerInterface $logger)
	{
		$this->hostname = $baseURL->getHost();
		$this->config   = $config;
		$this->dba      = $dba;
		$this->profiler = $profiler;
		$this->logger   = $logger;
	}

	/**
	 * This method creates a CacheDriver for distributed caching with the given cache driver name
	 *
	 * @param string|null $type The cache type to create (default is per config)
	 *
	 * @return ICanCache  The instance of the CacheDriver
	 *
	 * @throws InvalidCacheDriverException In case the underlying cache driver isn't valid or not configured properly
	 * @throws CachePersistenceException In case the underlying cache has errors during persistence
	 */
	public function createDistributed(string $type = null): ICanCache
	{
		if ($type === Enum\Type::APCU) {
			throw new InvalidCacheDriverException('apcu doesn\'t support distributed caching.');
		}

		return $this->create($type ?? $this->config->get('system', 'distributed_cache_driver', self::DEFAULT_TYPE));
	}

	/**
	 * This method creates a CacheDriver for local caching with the given cache driver name
	 *
	 * @param string|null $type The cache type to create (default is per config)
	 *
	 * @return ICanCache  The instance of the CacheDriver
	 *
	 * @throws InvalidCacheDriverException In case the underlying cache driver isn't valid or not configured properly
	 * @throws CachePersistenceException In case the underlying cache has errors during persistence
	 */
	public function createLocal(string $type = null): ICanCache
	{
		return $this->create($type ?? $this->config->get('system', 'cache_driver', self::DEFAULT_TYPE));
	}

	/**
	 * Creates a new Cache instance
	 *
	 * @param string $type The type of cache
	 *
	 * @return ICanCache
	 *
	 * @throws InvalidCacheDriverException In case the underlying cache driver isn't valid or not configured properly
	 * @throws CachePersistenceException In case the underlying cache has errors during persistence
	 */
	protected function create(string $type): ICanCache
	{
		switch ($type) {
			case Enum\Type::MEMCACHE:
				$cache = new Type\MemcacheCache($this->hostname, $this->config);
				break;
			case Enum\Type::MEMCACHED:
				$cache = new Type\MemcachedCache($this->hostname, $this->config, $this->logger);
				break;
			case Enum\Type::REDIS:
				$cache = new Type\RedisCache($this->hostname, $this->config);
				break;
			case Enum\Type::APCU:
				$cache = new Type\APCuCache($this->hostname);
				break;
			default:
				$cache = new Type\DatabaseCache($this->hostname, $this->dba);
		}

		$profiling = $this->config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerCache around the current cache
		if (isset($profiling) && $profiling !== false) {
			return new Type\ProfilerCacheDecorator($cache, $this->profiler);
		} else {
			return $cache;
		}
	}
}
