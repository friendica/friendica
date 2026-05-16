<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Lock\Type;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Lock\Enum\Type;
use Friendica\Core\Lock\Exception\LockPersistenceException;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

/**
 * Locking driver that stores the locks in the database
 */
class DatabaseLock extends AbstractLock
{
	/**
	 * The current ID of the process
	 *
	 * @var int
	 */
	private $pid;

	/**
	 * @var Database The database connection of Friendica
	 */
	private $dba;

	/**
	 * @param int|null $pid The id of the current process (null means determine automatically)
	 */
	public function __construct(Database $dba, ?int $pid = null)
	{
		$this->dba = $dba;
		$this->pid = $pid ?? getmypid();
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire(string $key, int $timeout = 120, int $ttl = Duration::FIVE_MINUTES): bool
	{
		$got_lock = false;
		$start    = time();

		try {
			do {
				$this->dba->lock('locks');
				$lock = $this->dba->selectFirst('locks', ['locked', 'pid'], [
					'`name` = ? AND `expires` >= ?', $key,DateTimeFormat::utcNow(),
				]);

				if ($this->dba->isResult($lock)) {
					if ($lock['locked']) {
						// We want to lock something that was already locked by us? So we got the lock.
						if ($lock['pid'] == $this->pid) {
							$got_lock = true;
						}
					}
					if (!$lock['locked']) {
						$this->dba->update('locks', [
							'locked'  => true,
							'pid'     => $this->pid,
							'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds'),
						], ['name' => $key]);
						$got_lock = true;
					}
				} else {
					$this->dba->insert('locks', [
						'name'    => $key,
						'locked'  => true,
						'pid'     => $this->pid,
						'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')]);
					$got_lock = true;
					$this->markAcquire($key);
				}

				$this->dba->unlock();

				if (!$got_lock && ($timeout > 0)) {
					usleep(random_int(100000, 2000000));
				}
			} while (!$got_lock && ((time() - $start) < $timeout));
		} catch (\Exception $exception) {
			throw new LockPersistenceException(sprintf('Cannot acquire lock for key %s', $key), $exception);
		}

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function release(string $key, bool $override = false): bool
	{
		if ($override) {
			$where = ['name' => $key];
		} else {
			$where = ['name' => $key, 'pid' => $this->pid];
		}

		try {
			if ($this->dba->exists('locks', $where)) {
				$return = $this->dba->delete('locks', $where);
			} else {
				$return = false;
			}
		} catch (\Exception $exception) {
			throw new LockPersistenceException(sprintf('Cannot release lock for key %s (override %b)', $key, $override), $exception);
		}

		$this->markRelease($key);

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseAll(bool $override = false): bool
	{
		$success = parent::releaseAll($override);

		if ($override) {
			$where = ['1 = 1'];
		} else {
			$where = ['pid' => $this->pid];
		}

		try {
			$return = $this->dba->delete('locks', $where);
		} catch (\Exception $exception) {
			throw new LockPersistenceException(sprintf('Cannot release all lock (override %b)', $override), $exception);
		}

		$this->acquiredLocks = [];

		return $return && $success;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked(string $key): bool
	{
		try {
			$lock = $this->dba->selectFirst('locks', ['locked'], [
				'`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);
		} catch (\Exception $exception) {
			throw new LockPersistenceException(sprintf('Cannot check lock state for key %s', $key), $exception);
		}

		if ($this->dba->isResult($lock)) {
			return $lock['locked'] !== false;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return Type::DATABASE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = ''): array
	{
		try {
			if (empty($prefix)) {
				$where = ['`expires` >= ?', DateTimeFormat::utcNow()];
			} else {
				$where = ['`expires` >= ? AND `name` LIKE CONCAT(?, \'%\')', DateTimeFormat::utcNow(), $prefix];
			}

			$stmt = $this->dba->select('locks', ['name'], $where);
		} catch (\Exception $exception) {
			throw new LockPersistenceException(sprintf('Cannot get lock with prefix %s', $prefix), $exception);
		}

		try {
			$keys = [];
			while ($key = $this->dba->fetch($stmt)) {
				array_push($keys, $key['name']);
			}
		} catch (\Exception $exception) {
			$this->dba->close($stmt);
			throw new LockPersistenceException(sprintf('Cannot get lock with prefix %s', $prefix), $exception);
		}

		$this->dba->close($stmt);

		return $keys;
	}
}
