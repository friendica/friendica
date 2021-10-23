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

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Cache\IMemoryCache;
use Friendica\Util\Profiler;

/**
 * This class wraps cache driver so they can get profiled - in case the profiler is enabled
 *
 * It is using the decorator pattern (@see
 */
class ProfilerCache implements ICache, IMemoryCache
{
	/**
	 * @var ICache The original cache driver
	 */
	private $cache;

	/**
	 * @var Profiler The profiler of Friendica
	 */
	private $profiler;

	public function __construct(ICache $cache, Profiler $profiler)
	{
		$this->cache    = $cache;
		$this->profiler = $profiler;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllKeys($prefix = null)
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->getAllKeys($prefix);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($key)
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->get($key);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->set($key, $value, $ttl);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete($key)
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->delete($key);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear($outdated = true)
	{
		$this->profiler->startRecording('cache');

		$return = $this->cache->clear($outdated);

		$this->profiler->stopRecording();

		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function add($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		if ($this->cache instanceof IMemoryCache) {
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
	public function compareSet($key, $oldValue, $newValue, $ttl = Duration::FIVE_MINUTES)
	{
		if ($this->cache instanceof IMemoryCache) {
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
	public function compareDelete($key, $value)
	{
		if ($this->cache instanceof IMemoryCache) {
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
	public function GetName()
	{
		return $this->cache->getName() . ' (with profiler)';
	}
}
