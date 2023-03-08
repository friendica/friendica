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

namespace Friendica;

/**
 * These data transfer object classes are meant for API representations. As such, their members should be protected.
 * Then the JsonSerializable interface ensures the protected members will be included in a JSON encode situation.
 *
 * Constructors are supposed to take as arguments the Friendica dependencies/model/collection/data it needs to
 * populate the class members.
 */
abstract class BaseDataTransferObject implements \JsonSerializable
{
	/**
	 * Returns the current entity as an json array
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
