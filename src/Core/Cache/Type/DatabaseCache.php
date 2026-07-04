<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public const NAME = 'database';

	public function __construct(string $hostname, private readonly Database $dba)
	{
		parent::__construct($hostname);
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
		} catch (\Exception $exception) {
			throw new CachePersistenceException(sprintf('Cannot fetch all keys with prefix %s', $prefix), $exception);
		}

		try {
			$keys = [];
			while ($key = $this->dba->fetch($stmt)) {
				array_push($keys, $key['k']);
			}
		} catch (\Exception $exception) {
			$this->dba->close($stmt);
			throw new CachePersistenceException(sprintf('Cannot fetch all keys with prefix %s', $prefix), $exception);
		}

		$this->dba->close($stmt);
		return $keys;
	}

	/**
	 * (@inheritdoc)
	 */
	public function get(string $key)
	{
		try {
			$cache = $this->dba->selectFirst('cache', ['v'], [
				'`k` = ? AND (`expires` >= ? OR `expires` = -1)', $key, DateTimeFormat::utcNow(),
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
					'updated' => DateTimeFormat::utcNow(),
				];
			} else {
				$fields = [
					'v'       => serialize($value),
					'expires' => -1,
					'updated' => DateTimeFormat::utcNow(),
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
}
