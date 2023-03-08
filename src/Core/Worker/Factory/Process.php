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

namespace Friendica\Core\Worker\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Core\Worker\Entity;

class Process extends BaseFactory implements ICanCreateFromTableRow
{
	public function determineHost(?string $hostname = null): string
	{
		return strtolower($hostname ?? php_uname('n'));
	}

	public function createFromTableRow(array $row): Entity\Process
	{
		return new Entity\Process(
			$row['pid'],
			$row['command'],
			$this->determineHost($row['hostname'] ?? null),
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC'))
		);
	}
}
