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
use Friendica\Core\Cache\Capability\ICanCacheInMemory;
use Friendica\Core\Cache\Enum\Type;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;

/**
 * APCu Cache.
 */
class APCuCache extends AbstractCache implements ICanCacheInMemory
{
	use CompareSetTrait;
	use CompareDeleteTrait;

	/**
	 * @param string $hostname
	 *
	 * @throws InvalidCacheDriverException
	 */
	public function __construct(string $hostname)
	{
		if (!self::isAvailable()) {
			throw new InvalidCacheDriverException('APCu is not available.');
		}

		parent::__construct($hostname);
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		$ns = $this->getCacheKey($prefix ?? '');
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
	public function get(string $key)
	{
		$cacheKey = $this->getCacheKey($key);

		$cached = apcu_fetch($cacheKey, $success);
		if (!$success) {
			return null;
		}

		$value = unserialize($cached);

		// Only return a value if the serialized value is valid.
		// We also check if the db entry is a serialized
		// boolean 'false' value (which we want to return).
		if ($cached === serialize(false) || $value !== false) {
			return $value;
		}

		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);

		$cached = serialize($value);

		if ($ttl > 0) {
			return apcu_store(
				$cacheKey,
				$cached,
				$ttl
			);
		} else {
			return apcu_store(
				$cacheKey,
				$cached
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return apcu_delete($cacheKey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
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
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);
		$cached   = serialize($value);

		return apcu_add($cacheKey, $cached);
	}

	public static function isAvailable(): bool
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
	public function getName(): string
	{
		return Type::APCU;
	}
}
