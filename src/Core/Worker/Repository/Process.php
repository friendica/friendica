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

namespace Friendica\Core\Worker\Repository;

use Friendica\BaseRepository;
use Friendica\Core\Worker\Exception\ProcessPersistenceException;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;
use Friendica\Core\Worker\Factory;
use Friendica\Core\Worker\Entity;
use Psr\Log\LoggerInterface;

/**
 * functions for interacting with a process
 */
class Process extends BaseRepository
{
	const NODE_ENV = 'NODE_ENV';

	protected static $table_name = 'process';

	/** @var Factory\Process */
	protected $factory;

	/** @var string */
	private $currentHost;

	public function __construct(Database $database, LoggerInterface $logger, Factory\Process $factory, array $server)
	{
		parent::__construct($database, $logger, $factory);

		$this->currentHost = $factory->determineHost($server[self::NODE_ENV] ?? null);
	}

	/**
	 * Starts and Returns the process for a given PID at the current host
	 *
	 * @param int    $pid
	 * @param string $command
	 *
	 * @return Entity\Process
	 */
	public function create(int $pid, string $command): Entity\Process
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

			$result = $this->_selectOne(['pid' => $pid, 'hostname' => $this->currentHost]);

			$this->db->commit();

			return $result;
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException(sprintf('Cannot save process with PID %s.', $pid), $exception);
		}
	}

	public function delete(Entity\Process $process)
	{
		try {
			if (!$this->db->delete(static::$table_name, [
				'pid'      => $process->pid,
				'hostname' => $this->currentHost,
			])) {
				throw new ProcessPersistenceException(sprintf('The process with PID %s doesn\'t exists.', $process->pi));
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
				if (!posix_kill($process['pid'], 0)) {
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
