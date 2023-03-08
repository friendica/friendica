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

namespace Friendica\Core\KeyValueStorage\Type;

use Friendica\Core\KeyValueStorage\Capabilities\IManageKeyValuePairs;

/**
 * An abstract helper class for Key-Value storage classes
 */
abstract class AbstractKeyValueStorage implements IManageKeyValuePairs
{
	/** {@inheritDoc} */
	public function get(string $key)
	{
		return $this->offsetGet($key);
	}

	/** {@inheritDoc} */
	public function set(string $key, $value): void
	{
		$this->offsetSet($key, $value);
	}

	/** {@inheritDoc} */
	public function delete(string $key): void
	{
		$this->offsetUnset($key);
	}
}
