<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;

/**
 * Entity class for table workerqueue
 *
 * Background tasks queue entries
 */
class Workerqueue extends BaseEntity
{
	/**
	 * @var int
	 * Auto incremented worker task id
	 */
	private $id;

	/**
	 * @var string
	 * Task command
	 */
	private $parameter;

	/**
	 * @var string
	 * Task priority
	 */
	private $priority = '0';

	/**
	 * @var string
	 * Creation date
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * @var int
	 * Process id of the worker
	 */
	private $pid = '0';

	/**
	 * @var string
	 * Execution date
	 */
	private $executed = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Next retrial date
	 */
	private $nextTry = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Retrial counter
	 */
	private $retrial = '0';

	/**
	 * @var bool
	 * Marked 1 when the task was done - will be deleted later
	 */
	private $done = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'parameter' => $this->parameter,
			'priority' => $this->priority,
			'created' => $this->created,
			'pid' => $this->pid,
			'executed' => $this->executed,
			'next_try' => $this->nextTry,
			'retrial' => $this->retrial,
			'done' => $this->done,
		];
	}

	/**
	 * @return int
	 * Get Auto incremented worker task id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 * Get Task command
	 */
	public function getParameter()
	{
		return $this->parameter;
	}

	/**
	 * @param string $parameter
	 * Set Task command
	 */
	public function setParameter(string $parameter)
	{
		$this->parameter = $parameter;
	}

	/**
	 * @return string
	 * Get Task priority
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param string $priority
	 * Set Task priority
	 */
	public function setPriority(string $priority)
	{
		$this->priority = $priority;
	}

	/**
	 * @return string
	 * Get Creation date
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set Creation date
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}

	/**
	 * @return int
	 * Get Process id of the worker
	 */
	public function getPid()
	{
		return $this->pid;
	}

	/**
	 * @param int $pid
	 * Set Process id of the worker
	 */
	public function setPid(int $pid)
	{
		$this->pid = $pid;
	}

	/**
	 * @return string
	 * Get Execution date
	 */
	public function getExecuted()
	{
		return $this->executed;
	}

	/**
	 * @param string $executed
	 * Set Execution date
	 */
	public function setExecuted(string $executed)
	{
		$this->executed = $executed;
	}

	/**
	 * @return string
	 * Get Next retrial date
	 */
	public function getNextTry()
	{
		return $this->nextTry;
	}

	/**
	 * @param string $nextTry
	 * Set Next retrial date
	 */
	public function setNextTry(string $nextTry)
	{
		$this->nextTry = $nextTry;
	}

	/**
	 * @return string
	 * Get Retrial counter
	 */
	public function getRetrial()
	{
		return $this->retrial;
	}

	/**
	 * @param string $retrial
	 * Set Retrial counter
	 */
	public function setRetrial(string $retrial)
	{
		$this->retrial = $retrial;
	}

	/**
	 * @return bool
	 * Get Marked 1 when the task was done - will be deleted later
	 */
	public function getDone()
	{
		return $this->done;
	}

	/**
	 * @param bool $done
	 * Set Marked 1 when the task was done - will be deleted later
	 */
	public function setDone(bool $done)
	{
		$this->done = $done;
	}
}
