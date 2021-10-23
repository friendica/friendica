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
use Friendica\Core\Cache\Enum\Type;

/**
 * APCu Cache.
 */
class APCuCache extends BaseCache implements IMemoryCache
{
	use TraitCompareSet;
	use TraitCompareDelete;

	/**
	 * @throws Exception
	 */
	public function __construct(string $hostname)
	{
		if (!self::isAvailable()) {
			throw new Exception('APCu is not available.');
		}

		parent::__construct($hostname);
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		$ns = $this->getCacheKey($prefix);
		$ns = preg_quote($ns, '/');

		if (class_exists('\APCIterator')) {
			$iterator = new \APCIterator('user', '/^' . $ns. '/', APC_ITER_KEY);
		} else {
			$iterator = new \APCUIterator('/^' . $ns . '/', APC_ITER_KEY);
		}

		$keys = [];
		foreach ($iterator as $item) {
			array_push($keys, $item['key']);
		}

		return $this->getOriginalKeys($keys);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$return = null;
		$cachekey = $this->getCacheKey($key);

		$cached = apcu_fetch($cachekey, $success);
		if (!$success) {
			return null;
		}

		$value = unserialize($cached);

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

		$cached = serialize($value);

		if ($ttl > 0) {
			return apcu_store(
				$cachekey,
				$cached,
				$ttl
			);
		} else {
			return apcu_store(
				$cachekey,
				$cached
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return apcu_delete($cachekey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		if ($outdated) {
			return true;
		} else {
			$prefix = $this->getPrefix();
			$prefix = preg_quote($prefix, '/');

			if (class_exists('\APCIterator')) {
				$iterator = new \APCIterator('user', '/^' . $prefix . '/', APC_ITER_KEY);
			} else {
				$iterator = new \APCUIterator('/^' . $prefix . '/', APC_ITER_KEY);
			}

			return apcu_delete($iterator);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		$cached = serialize($value);

		return apcu_add($cachekey, $cached);
	}

	public static function isAvailable()
	{
		if (!extension_loaded('apcu')) {
			return false;
		} elseif (!ini_get('apc.enabled') && !ini_get('apc.enable_cli')) {
			return false;
		} elseif (
			version_compare(phpversion('apc') ?: '0.0.0', '4.0.6') === -1 &&
			version_compare(phpversion('apcu') ?: '0.0.0', '5.1.0') === -1
		) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::APCU;
	}
}
