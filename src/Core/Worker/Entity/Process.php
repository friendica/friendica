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

namespace Friendica\Core\Worker\Entity;

use DateTime;
use Friendica\BaseEntity;

/**
 * @property-read int $pid
 * @property-read string $command
 * @property-read string $hostname
 * @property-read DateTime $created
 */
class Process extends BaseEntity
{
	/** @var int */
	protected $pid;
	/** @var string */
	protected $command;
	/** @var string */
	protected $hostname;
	/** @var DateTime */
	protected $created;

	/**
	 * @param int      $pid
	 * @param string   $command
	 * @param string   $hostname
	 * @param DateTime $created
	 */
	public function __construct(int $pid, string $command, string $hostname, DateTime $created)
	{
		$this->pid      = $pid;
		$this->command  = $command;
		$this->hostname = $hostname;
		$this->created  = $created;
	}
}
