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

namespace Friendica\Domain\Entity\Worker;

use Friendica\BaseEntity;

/**
 * Entity class for table worker-ipc
 *
 * Inter process communication between the frontend and the worker
 */
class Ipc extends BaseEntity
{
	/**
	 * @var int
	 */
	private $key;

	/**
	 * @var bool
	 * Flag for outstanding jobs
	 */
	private $jobs;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'key' => $this->key,
			'jobs' => $this->jobs,
		];
	}

	/**
	 * @return int
	 * Get
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return bool
	 * Get Flag for outstanding jobs
	 */
	public function getJobs()
	{
		return $this->jobs;
	}

	/**
	 * @param bool $jobs
	 * Set Flag for outstanding jobs
	 */
	public function setJobs(bool $jobs)
	{
		$this->jobs = $jobs;
	}
}
