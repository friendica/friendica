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

namespace Friendica\Core\Hooks\Model;

use Dice\Dice;
use Friendica\Core\Hooks\Capabilities\IAmAStrategy;
use Friendica\Core\Hooks\Capabilities\ICanManageInstances;
use Friendica\Core\Hooks\Exceptions\HookInstanceException;
use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;

/** {@inheritDoc} */
class InstanceManager implements ICanManageInstances
{
	protected $instance          = [];
	protected $instanceArguments = [];
	protected $decorator         = [];

	/** @var Dice */
	protected $dice;

	public function __construct(Dice $dice)
	{
		$this->dice = $dice;
	}

	/** {@inheritDoc} */
	public function registerStrategy(string $interface, string $name, string $class, array $arguments = null): ICanManageInstances
	{
		if (!is_a($class, $interface, true)) {
			throw new HookRegisterArgumentException(sprintf('%s is not a valid class for the interface %s', $class, $interface));
		}

		if (!is_a($class, IAmAStrategy::class, true)) {
			throw new HookRegisterArgumentException(sprintf('%s does not inherit from the marker interface %s', $class, IAmAStrategy::class));
		}

		if (!empty($this->instance[$interface][$name])) {
			throw new HookRegisterArgumentException(sprintf('A class with the name %s is already set for the interface %s', $name, $interface));
		}

		$this->instance[$interface][$name]          = $class;
		$this->instanceArguments[$interface][$name] = $arguments;

		return $this;
	}

	/** {@inheritDoc} */
	public function registerDecorator(string $class, string $decoratorClass, array $arguments = []): ICanManageInstances
	{
		if (!is_a($decoratorClass, $class, true)) {
			throw new HookRegisterArgumentException(sprintf('%s is not a valid substitution for the given class or interface %s', $decoratorClass, $class));
		}

		$this->decorator[$class][] = [
			'class'     => $decoratorClass,
			'arguments' => $arguments,
		];

		return $this;
	}

	/** {@inheritDoc} */
	public function getInstance(string $class, string $name, array $arguments = []): object
	{
		if (empty($this->instance[$class][$name])) {
			throw new HookInstanceException(sprintf('The class with the name %s isn\'t registered for the class or interface %s', $name, $class));
		}

		$instance = $this->dice->create($this->instance[$class][$name], array_merge($this->instanceArguments[$class][$name] ?? [], $arguments));

		foreach ($this->decorator[$class] ?? [] as $decorator) {
			$this->dice = $this->dice->addRule($class, [
				'instanceOf'      => $decorator['class'],
				'constructParams' => empty($decorator['arguments']) ? null : $decorator['arguments'],
				/// @todo maybe support call structures for hooks as well in a later stage - could make factory calls easier
				'call'          => null,
				'substitutions' => [$class => $instance],
			]);

			$instance = $this->dice->create($class);
		}

		return $instance;
	}
}
