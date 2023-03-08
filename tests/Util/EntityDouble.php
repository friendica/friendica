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

namespace Friendica\Test\Util;

use Friendica\BaseEntity;

/**
 * @property-read string $protString
 * @property-read int $protInt
 * @property-read \DateTime $protDateTime
 */
class EntityDouble extends BaseEntity
{
	protected $protString;
	protected $protInt;
	protected $protDateTime;
	private $privString;

	public function __construct(string $protString, int $protInt, \DateTime $protDateTime, string $privString)
	{
		$this->protString   = $protString;
		$this->protInt      = $protInt;
		$this->protDateTime = $protDateTime;
		$this->privString   = $privString;
	}
}
