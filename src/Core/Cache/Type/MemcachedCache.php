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
use Friendica\Core\Cache\Type\TraitMemcacheCommand;
use Friendica\Core\Cache\Enum\Type;
use Friendica\Core\Config\IConfig;
use Memcached;
use Psr\Log\LoggerInterface;

/**
 * Memcached Cache
 */
class MemcachedCache extends BaseCache implements IMemoryCache
{
	use TraitCompareSet;
	use TraitCompareDelete;
	use TraitMemcacheCommand;

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
	 * @param array $memcached_hosts
	 *
	 * @throws \Exception
	 */
	public function __construct(string $hostname, IConfig $config, LoggerInterface $logger)
	{
		if (!class_exists('Memcached', false)) {
			throw new Exception('Memcached class isn\'t available');
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
		$this->port = $memcached_hosts[0][1] ?? 11211;

		$this->memcached->addServers($memcached_hosts);

		if (count($this->memcached->getServerList()) == 0) {
			throw new Exception('Expected Memcached servers aren\'t available, config:' . var_export($memcached_hosts, true));
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		$keys = $this->getOriginalKeys($this->getMemcacheKeys());

		return $this->filterArrayKeysByPrefix($keys, $prefix);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		$return   = null;
		$cachekey = $this->getCacheKey($key);

		// We fetch with the hostname as key to avoid problems with other applications
		$value = $this->memcached->get($cachekey);

		if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
			$return = $value;
		} else {
			$this->logger->debug('Memcached \'get\' failed', ['result' => $this->memcached->getResultMessage()]);
		}

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);

		// We store with the hostname as key to avoid problems with other applications
		if ($ttl > 0) {
			return $this->memcached->set(
				$cachekey,
				$value,
				$ttl
			);
		} else {
			return $this->memcached->set(
				$cachekey,
				$value
			);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->delete($cachekey);
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
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
	public function add($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$cachekey = $this->getCacheKey($key);
		return $this->memcached->add($cachekey, $value, $ttl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::MEMCACHED;
	}
}
