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
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Memcache;

/**
 * Memcache Cache
 */
class MemcacheCache extends AbstractCache implements ICanCacheInMemory
{
	use CompareSetTrait;
	use CompareDeleteTrait;
	use MemcacheCommandTrait;

	/**
	 * @var Memcache
	 */
	private $memcache;

	/**
	 * @param string              $hostname
	 * @param IManageConfigValues $config
	 *
	 * @throws InvalidCacheDriverException
	 * @throws CachePersistenceException
	 */
	public function __construct(string $hostname, IManageConfigValues $config)
	{
		if (!class_exists('Memcache', false)) {
			throw new InvalidCacheDriverException('Memcache class isn\'t available');
		}

		parent::__construct($hostname);

		$this->memcache = new Memcache();

		$this->server = $config->get('system', 'memcache_host');
		$this->port   = $config->get('system', 'memcache_port');

		if (!@$this->memcache->connect($this->server, $this->port)) {
			throw new CachePersistenceException('Expected Memcache server at ' . $this->server . ':' . $this->port . ' isn\'t available');
		}
	}

	/**
	 * Memcache doesn't allow spaces in keys
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getCacheKey(string $key): string
	{
		return str_replace(' ', '_', parent::getCacheKey($key));
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		$keys = $this->getOriginalKeys($this->getMemcacheKeys());

		return $this->filterArrayKeysByPrefix($keys, $prefix);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		$cacheKey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$cached = $this->memcache->get($cacheKey);

		// @see http://php.net/manual/en/memcache.get.php#84275
		if (is_bool($cached) || is_double($cached) || is_long($cached)) {
			return null;
		}

		$value = @unserialize($cached);

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

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcache->set(
				$cacheKey,
				serialize($value),
				MEMCACHE_COMPRESSED,
				time() + $ttl
			);
		} else {
			return $this->memcache->set(
				$cacheKey,
				serialize($value),
				MEMCACHE_COMPRESSED
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return $this->memcache->delete($cacheKey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		if ($outdated) {
			return true;
		} else {
			return $this->memcache->flush();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return $this->memcache->add($cacheKey, serialize($value), MEMCACHE_COMPRESSED, $ttl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return Type::MEMCACHE;
	}
}
