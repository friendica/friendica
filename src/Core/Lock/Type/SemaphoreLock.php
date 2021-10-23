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

namespace Friendica\Core\Lock\Type;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Lock\Enum\Type;
use function get_temppath;

class SemaphoreLock extends BaseLock
{
	private static $semaphore = [];

	public function __construct()
	{
		if (!function_exists('sem_get')) {
			throw new \Exception('Semaphore lock not supported');
		}
	}

	/**
	 * (@inheritdoc)
	 */
	private static function semaphoreKey($key)
	{
		$success = true;

		$temp = get_temppath();

		$file = $temp . '/' . $key . '.sem';

		if (!file_exists($file)) {
			$success = !empty(file_put_contents($file, $key));
		}

		return $success ? ftok($file, 'f') : false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire($key, $timeout = 120, $ttl = Duration::FIVE_MINUTES)
	{
		self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
		if (!empty(self::$semaphore[$key])) {
			if ((bool)sem_acquire(self::$semaphore[$key], ($timeout === 0))) {
				$this->markAcquire($key);
				return true;
			}
		}

		return false;
	}

	/**
	 * (@inheritdoc)
	 *
	 * @param bool $override not necessary parameter for semaphore locks since the lock lives as long as the execution
	 *                       of the using function
	 */
	public function release($key, $override = false)
	{
		$success = false;

		if (!empty(self::$semaphore[$key])) {
			try {
				$success = @sem_release(self::$semaphore[$key]);
				unset(self::$semaphore[$key]);
				$this->markRelease($key);
			} catch (\Exception $exception) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		return isset(self::$semaphore[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::SEMAPHORE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = '')
	{
		// We can just return our own semaphore keys, since we don't know
		// the state of other semaphores, even if the .sem files exists
		$keys = array_keys(self::$semaphore);

		if (empty($prefix)) {
			return $keys;
		} else {
			$result = [];

			foreach ($keys as $key) {
				if (strpos($key, $prefix) === 0) {
					array_push($result, $key);
				}
			}

			return $result;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll($override = false)
	{
		// Semaphores are just alive during a run, so there is no need to release
		// You can just release your own locks
		return parent::releaseAll($override);
	}
}
