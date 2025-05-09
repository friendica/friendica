<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Util\Profiler;

/**
 * This class wraps cache driver, so they can get profiled - in case the profiler is enabled
 *
 * It is using the decorator pattern (@see https://en.wikipedia.org/wiki/Decorator_pattern )
 */
class ProfilerCacheDecorator implements ICanCache, ICanCacheInMemory
{
	/**
	 * @var ICanCache The original cache driver
	 */
	private $cache;

	/**
	 * @var Profiler The profiler of Friendica
	 */
	private $profiler;

	public function __construct(ICanCache $cache, Profiler $profiler)
	{
		$this->cache    = $cache;
		$this->profiler = $profiler;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->getAllKeys($prefix);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $key)
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->get($key);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->set($key, $value, $ttl);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(string $key): bool
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->delete($key);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear(bool $outdated = true): bool
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->clear($outdated);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		if ($this->cache instanceof ICanCacheInMemory) {
			$this->profiler->startRecording('cache');

			$return = $this->cache->add($key, $value, $ttl);

			$this->profiler->stopRecording();

			return $return;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function compareSet(string $key, $oldValue, $newValue, int $ttl = Duration::FIVE_MINUTES): bool
	{
		if ($this->cache instanceof ICanCacheInMemory) {
			$this->profiler->startRecording('cache');

			$return = $this->cache->compareSet($key, $oldValue, $newValue, $ttl);

			$this->profiler->stopRecording();

			return $return;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function compareDelete(string $key, $value): bool
	{
		if ($this->cache instanceof ICanCacheInMemory) {
			$this->profiler->startRecording('cache');

			$return = $this->cache->compareDelete($key, $value);

			$this->profiler->stopRecording();

			return $return;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function GetName(): string
	{
		return $this->cache->getName() . ' (with profiler)';
	}

	/** {@inheritDoc} */
	public function getStats(): array
	{
		if ($this->cache instanceof ICanCacheInMemory) {
			return $this->cache->getStats();
		} else {
			return [];
		}
	}
}
