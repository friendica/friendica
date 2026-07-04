<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Factory;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Cache\Type;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Util\Profiler;

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
	public const DEFAULT_TYPE = Type\DatabaseCache::NAME;
	/** @var ICanCreateInstances */
	protected $instanceCreator;
	/** @var IManageConfigValues */
	protected $config;
	/** @var Profiler */
	protected $profiler;

	public function __construct(
		ICanCreateInstances $instanceCreator,
		IManageConfigValues $config,
		Profiler $profiler,
	) {
		$this->config          = $config;
		$this->instanceCreator = $instanceCreator;
		$this->profiler        = $profiler;
	}

	/**
	 * This method creates a CacheDriver for distributed caching
	 *
	 * @return ICanCache  The instance of the CacheDriver
	 *
	 * @throws InvalidCacheDriverException In case the underlying cache driver isn't valid or not configured properly
	 * @throws CachePersistenceException In case the underlying cache has errors during persistence
	 */
	public function createDistributed(): ICanCache
	{
		return $this->create($this->config->get('system', 'distributed_cache_driver', self::DEFAULT_TYPE));
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
	public function createLocal(?string $type = null): ICanCache
	{
		return $this->create($type ?? $this->config->get('system', 'cache_driver', self::DEFAULT_TYPE));
	}

	/**
	 * Creates a new Cache instance
	 *
	 * @param string $strategy The strategy, which cache instance should be used
	 *
	 * @return ICanCache
	 *
	 * @throws InvalidCacheDriverException In case the underlying cache driver isn't valid or not configured properly
	 * @throws CachePersistenceException In case the underlying cache has errors during persistence
	 */
	protected function create(string $strategy): ICanCache
	{
		/** @var ICanCache $cache */
		$cache = $this->instanceCreator->create(ICanCache::class, $strategy);

		$profiling = $this->config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerCache around the current cache
		if (isset($profiling) && $profiling !== false) {
			return new Type\ProfilerCacheDecorator($cache, $this->profiler);
		} else {
			return $cache;
		}
	}
}
