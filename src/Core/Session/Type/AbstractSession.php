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

namespace Friendica\Core\Session\Type;

use Friendica\Core\Session\Capability\IHandleSessions;

/**
 * Contains the base methods for $_SESSION interaction
 */
class AbstractSession implements IHandleSessions
{
	/**
	 * {@inheritDoc}
	 */
	public function start(): IHandleSessions
	{
		return $this;
	}

	/**
	 * {@inheritDoc}}
	 */
	public function exists(string $name): bool
	{
		return isset($_SESSION[$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $name, $defaults = null)
	{
		return $_SESSION[$name] ?? $defaults;
	}

	/**
	 * {@inheritDoc}
	 */
	public function pop(string $name, $defaults = null)
	{
		$value = $defaults;
		if ($this->exists($name)) {
			$value = $this->get($name);
			$this->remove($name);
		}

		return $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMultiple(array $values)
	{
		$_SESSION = $values + $_SESSION;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(string $name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear()
	{
		$_SESSION = [];
	}
}
