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
 * Entity class for table hook
 *
 * addon hook registry
 */
class Hook extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var string
	 * name of hook
	 */
	private $hook = '';

	/**
	 * @var string
	 * relative filename of hook handler
	 */
	private $file = '';

	/**
	 * @var string
	 * function name of hook handler
	 */
	private $function = '';

	/**
	 * @var string
	 * not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order
	 */
	private $priority = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'hook' => $this->hook,
			'file' => $this->file,
			'function' => $this->function,
			'priority' => $this->priority,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 * Get name of hook
	 */
	public function getHook()
	{
		return $this->hook;
	}

	/**
	 * @param string $hook
	 * Set name of hook
	 */
	public function setHook(string $hook)
	{
		$this->hook = $hook;
	}

	/**
	 * @return string
	 * Get relative filename of hook handler
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param string $file
	 * Set relative filename of hook handler
	 */
	public function setFile(string $file)
	{
		$this->file = $file;
	}

	/**
	 * @return string
	 * Get function name of hook handler
	 */
	public function getFunction()
	{
		return $this->function;
	}

	/**
	 * @param string $function
	 * Set function name of hook handler
	 */
	public function setFunction(string $function)
	{
		$this->function = $function;
	}

	/**
	 * @return string
	 * Get not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @param string $priority
	 * Set not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order
	 */
	public function setPriority(string $priority)
	{
		$this->priority = $priority;
	}
}
