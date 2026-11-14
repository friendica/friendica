<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
	public const DEFAULT_TYPE = Type\DatabaseCache::NAME;
	/** @var ICanCreateInstances */
	protected $instanceCreator;
	/** @var IManageConfigValues */
	protected $config;
	/** @var Profiler */
	protected $profiler;
	/** @var LoggerInterface */
	protected $logger;
	/**
	 * Strategies whose driver isn't available, shared by all factory instances.
	 * Dice registers a shared instance before calling its constructor, so a second create() returns the half-constructed driver instead of throwing again.
	 *
	 * @var array<string, true>
	 */
	private static array $unavailable = [];

	public function __construct(
		ICanCreateInstances $instanceCreator,
		IManageConfigValues $config,
		Profiler $profiler,
		LoggerInterface $logger,
	) {
		$this->config          = $config;
		$this->instanceCreator = $instanceCreator;
		$this->profiler        = $profiler;
		$this->logger          = $logger;
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
	 * Falls back to the default cache if the configured driver isn't available (e.g. a missing PHP extension).
	 *
	 * @param string $strategy The strategy, which cache instance should be used
	 *
	 * @return ICanCache
	 *
	 * @throws InvalidCacheDriverException In case the default cache driver isn't valid or not configured properly
	 * @throws CachePersistenceException In case the underlying cache has errors during persistence
	 */
	protected function create(string $strategy): ICanCache
	{
		if (isset(self::$unavailable[$strategy])) {
			$strategy = self::DEFAULT_TYPE;
		}

		try {
			/** @var ICanCache $cache */
			$cache = $this->instanceCreator->create(ICanCache::class, $strategy);
		} catch (InvalidCacheDriverException $exception) {
			if ($strategy === self::DEFAULT_TYPE) {
				throw $exception;
			}

			self::$unavailable[$strategy] = true;
			$this->logger->warning('Cache driver not available, falling back to the default cache.', ['driver' => $strategy, 'fallback' => self::DEFAULT_TYPE, 'exception' => $exception]);

			/** @var ICanCache $cache */
			$cache = $this->instanceCreator->create(ICanCache::class, self::DEFAULT_TYPE);
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
