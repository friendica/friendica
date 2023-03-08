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
use Memcached;
use Psr\Log\LoggerInterface;

/**
 * Memcached Cache
 */
class MemcachedCache extends AbstractCache implements ICanCacheInMemory
{
	use CompareSetTrait;
	use CompareDeleteTrait;
	use MemcacheCommandTrait;

	/**
	 * @var \Memcached
	 */
	private $memcached;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Due to limitations of the INI format, the expected configuration for Memcached servers is the following:
	 * array {
	 *   0 => "hostname, port(, weight)",
	 *   1 => ...
	 * }
	 *
	 * @param string              $hostname
	 * @param IManageConfigValues $config
	 * @param LoggerInterface     $logger
	 *
	 * @throws InvalidCacheDriverException
	 * @throws CachePersistenceException
	 */
	public function __construct(string $hostname, IManageConfigValues $config, LoggerInterface $logger)
	{
		if (!class_exists('Memcached', false)) {
			throw new InvalidCacheDriverException('Memcached class isn\'t available');
		}

		parent::__construct($hostname);

		$this->logger = $logger;

		$this->memcached = new Memcached();

		$memcached_hosts = $config->get('system', 'memcached_hosts');

		array_walk($memcached_hosts, function (&$value) {
			if (is_string($value)) {
				$value = array_map('trim', explode(',', $value));
			}
		});

		$this->server = $memcached_hosts[0][0] ?? 'localhost';
		$this->port   = $memcached_hosts[0][1] ?? 11211;

		$this->memcached->addServers($memcached_hosts);

		if (count($this->memcached->getServerList()) == 0) {
			throw new CachePersistenceException('Expected Memcached servers aren\'t available, config:' . var_export($memcached_hosts, true));
		}
	}

	/**
	 * Memcached doesn't allow spaces in keys
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
		$value = $this->memcached->get($cacheKey);

		if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
			return $value;
		} elseif ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
			$this->logger->debug('Try to use unknown key.', ['key' => $key]);
			return null;
		} else {
			throw new CachePersistenceException(sprintf('Cannot get cache entry with key %s', $key), new \MemcachedException($this->memcached->getResultMessage(), $this->memcached->getResultCode()));
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcached->set(
				$cacheKey,
				$value,
				$ttl
			);
		} else {
			return $this->memcached->set(
				$cacheKey,
				$value
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return $this->memcached->delete($cacheKey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		if ($outdated) {
			return true;
		} else {
			return $this->memcached->flush();
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$cacheKey = $this->getCacheKey($key);
		return $this->memcached->add($cacheKey, $value, $ttl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return Type::MEMCACHED;
	}
}
