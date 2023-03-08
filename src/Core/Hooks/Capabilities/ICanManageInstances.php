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

namespace Friendica\Core\Hooks\Capabilities;

use Friendica\Core\Hooks\Exceptions\HookInstanceException;
use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;

/**
 * Managing special instance and decorator treatments for classes
 */
interface ICanManageInstances
{
	/**
	 * Register a class(strategy) for a given interface with a unique name.
	 *
	 * @see https://refactoring.guru/design-patterns/strategy
	 *
	 * @param string $interface  The interface, which the given class implements
	 * @param string $name       An arbitrary identifier for the given class, which will be used for factories, dependency injections etc.
	 * @param string $class      The fully-qualified given class name
	 * @param ?array  $arguments Additional arguments, which can be passed to the constructor
	 *
	 * @return $this This interface for chain-calls
	 *
	 * @throws HookRegisterArgumentException in case the given class for the interface isn't valid or already set
	 */
	public function registerStrategy(string $interface, string $name, string $class, array $arguments = null): self;

	/**
	 * Register a new decorator for a given class or interface
	 * @see https://refactoring.guru/design-patterns/decorator
	 *
	 * @note Decorator attach new behaviors to classes without changing them or without letting them know about it.
	 *
	 * @param string $class          The fully-qualified class or interface name, which gets decorated by a class
	 * @param string $decoratorClass The fully-qualified name of the class which mimics the given class or interface and adds new functionality
	 * @param array  $arguments      Additional arguments, which can be passed to the constructor of "decoratorClass"
	 *
	 * @return $this This interface for chain-calls
	 *
	 * @throws HookRegisterArgumentException in case the given class for the class or interface isn't valid
	 */
	public function registerDecorator(string $class, string $decoratorClass, array $arguments = []): self;

	/**
	 * Returns a new instance of a given class for the corresponding name
	 *
	 * The instance will be build based on the registered strategy and the (unique) name
	 *
	 * In case, there are registered decorators for this class as well, all decorators of the list will be wrapped
	 * around the instance before returning it
	 *
	 * @param string $class     The fully-qualified name of the given class or interface which will get returned
	 * @param string $name      An arbitrary identifier to find a concrete instance strategy.
	 * @param array  $arguments Additional arguments, which can be passed to the constructor of "$class" at runtime
	 *
	 * @return object The concrete instance of the type "$class"
	 *
	 * @throws HookInstanceException In case the class cannot get created
	 */
	public function getInstance(string $class, string $name, array $arguments = []): object;
}
