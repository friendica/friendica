<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker\Repository;

use Friendica\BaseRepository;
use Friendica\Core\Worker\Entity\Process as ProcessEntity;
use Friendica\Core\Worker\Exception\ProcessPersistenceException;
use Friendica\Core\Worker\Factory\Process as ProcessFactory;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * functions for interacting with a process
 */
class Process extends BaseRepository
{
	const NODE_ENV = 'NODE_ENV';

	protected static $table_name = 'process';

	/** @var ProcessFactory */
	protected $factory;

	/** @var string */
	private $currentHost;

	public function __construct(Database $database, LoggerInterface $logger, ProcessFactory $factory, array $server)
	{
		parent::__construct($database, $logger, $factory);

		$this->currentHost = $factory->determineHost($server[self::NODE_ENV] ?? null);
	}

	/**
	 * Starts and Returns the process for a given PID at the current host
	 */
	public function create(int $pid, string $command): ProcessEntity
	{
		// Cleanup inactive process
		$this->deleteInactive();

		try {
			$this->db->transaction();

			if (!$this->db->exists(static::$table_name, ['pid' => $pid, 'hostname' => $this->currentHost])) {
				if (!$this->db->insert(static::$table_name, [
					'pid'      => $pid,
					'command'  => $command,
					'hostname' => $this->currentHost,
					'created'  => DateTimeFormat::utcNow()
				])) {
					throw new ProcessPersistenceException(sprintf('The process with PID %s already exists.', $pid));
				}
			}

			$fields = $this->_selectFirstRowAsArray(['pid' => $pid, 'hostname' => $this->currentHost]);

			$result = $this->factory->createFromTableRow($fields);

			$this->db->commit();

			return $result;
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException(sprintf('Cannot save process with PID %s.', $pid), $exception);
		}
	}

	public function delete(ProcessEntity $process)
	{
		try {
			if (!$this->db->delete(static::$table_name, [
				'pid'      => $process->pid,
				'hostname' => $this->currentHost,
			])) {
				throw new ProcessPersistenceException(sprintf('The process with PID %d doesn\'t exists.', $process->pid));
			}
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException(sprintf('Cannot delete process with PID %s.', $process->pid), $exception);
		}
	}

	/**
	 * Clean the process table of inactive physical processes
	 */
	private function deleteInactive()
	{
		$this->db->transaction();

		try {
			$processes = $this->db->select(static::$table_name, ['pid'], ['hostname' => $this->currentHost]);
			while ($process = $this->db->fetch($processes)) {
				if (!\posix_kill($process['pid'], 0)) {
					$this->db->delete(static::$table_name, ['pid' => $process['pid']]);
				}
			}
			$this->db->close($processes);
			$this->db->commit();
		} catch (\Exception $exception) {
			$this->db->rollback();
			throw new ProcessPersistenceException('Cannot delete inactive process', $exception);
		}
	}

	/**
	 * Returns the number of processes with a given command
	 *
	 * @param string $command
	 *
	 * @return int Number of processes
	 *
	 * @throws ProcessPersistenceException
	 */
	public function countCommand(string $command): int
	{
		try {
			return $this->count(['command' => strtolower($command)]);
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException('Cannot count ', $exception);
		}
	}
}
