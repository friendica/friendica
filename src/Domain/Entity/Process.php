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
 * Entity class for table process
 *
 * Currently running system processes
 */
class Process extends BaseEntity
{
	/**
	 * @var int
	 */
	private $pid;

	/**
	 * @var string
	 */
	private $command = '';

	/**
	 * @var string
	 */
	private $created = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'pid' => $this->pid,
			'command' => $this->command,
			'created' => $this->created,
		];
	}

	/**
	 * @return int
	 * Get
	 */
	public function getPid()
	{
		return $this->pid;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * @param string $command
	 * Set
	 */
	public function setCommand(string $command)
	{
		$this->command = $command;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCreated()
	{
		return $this->created;
	}

	/**
	 * @param string $created
	 * Set
	 */
	public function setCreated(string $created)
	{
		$this->created = $created;
	}
}
