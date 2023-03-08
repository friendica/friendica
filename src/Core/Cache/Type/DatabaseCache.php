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

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

/**
 * Database Cache
 */
class DatabaseCache extends AbstractCache implements ICanCache
{
	/**
	 * @var Database
	 */
	private $dba;

	public function __construct(string $hostname, Database $dba)
	{
		parent::__construct($hostname);

		$this->dba = $dba;
	}

	/**
	 * (@inheritdoc)
	 *
	 * @throws CachePersistenceException
	 */
	public function getAllKeys(?string $prefix = null): array
	{
		try {
			if (empty($prefix)) {
				$where = ['`expires` >= ?', DateTimeFormat::utcNow()];
			} else {
				$where = ['`expires` >= ? AND `k` LIKE CONCAT(?, \'%\')', DateTimeFormat::utcNow(), $prefix];
			}

			$stmt = $this->dba->select('cache', ['k'], $where);

			$keys = [];
			while ($key = $this->dba->fetch($stmt)) {
				array_push($keys, $key['k']);
			}
		} catch (\Exception $exception) {
			throw new CachePersistenceException(sprintf('Cannot fetch all keys with prefix %s', $prefix), $exception);
		} finally {
			$this->dba->close($stmt);
		}

		return $keys;
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		try {
			$cache = $this->dba->selectFirst('cache', ['v'], [
				'`k` = ? AND (`expires` >= ? OR `expires` = -1)', $key, DateTimeFormat::utcNow()
			]);

			if ($this->dba->isResult($cache)) {
				$cached = $cache['v'];
				$value  = @unserialize($cached);

				// Only return a value if the serialized value is valid.
				// We also check if the db entry is a serialized
				// boolean 'false' value (which we want to return).
				if ($cached === serialize(false) || $value !== false) {
					return $value;
				}
			}
		} catch (\Exception $exception) {
			throw new CachePersistenceException(sprintf('Cannot get cache entry with key %s', $key), $exception);
		}

		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set(string $key, $value, int $ttl = Enum\Duration::FIVE_MINUTES): bool
	{
		try {
			if ($ttl > 0) {
				$fields = [
					'v'       => serialize($value),
					'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds'),
					'updated' => DateTimeFormat::utcNow()
				];
			} else {
				$fields = [
					'v'       => serialize($value),
					'expires' => -1,
					'updated' => DateTimeFormat::utcNow()
				];
			}

			return $this->dba->update('cache', $fields, ['k' => $key], true);
		} catch (\Exception $exception) {
			throw new CachePersistenceException(sprintf('Cannot set cache entry with key %s', $key), $exception);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete(string $key): bool
	{
		try {
			return $this->dba->delete('cache', ['k' => $key]);
		} catch (\Exception $exception) {
			throw new CachePersistenceException(sprintf('Cannot delete cache entry with key %s', $key), $exception);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear(bool $outdated = true): bool
	{
		try {
			if ($outdated) {
				return $this->dba->delete('cache', ['`expires` < ?', DateTimeFormat::utcNow()]);
			} else {
				return $this->dba->delete('cache', ['`k` IS NOT NULL ']);
			}
		} catch (\Exception $exception) {
			throw new CachePersistenceException('Cannot clear cache', $exception);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return Enum\Type::DATABASE;
	}
}
