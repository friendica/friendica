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
}
