<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Hooks\Model;

use Dice\Dice;
use Friendica\Core\Hooks\Capability\ICanCreateInstances;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Exceptions\HookInstanceException;
use Friendica\Core\Hooks\Exceptions\HookRegisterArgumentException;
use Friendica\Core\Hooks\Util\StrategiesFileManager;

/**
 * This class represents an instance register, which uses Dice for creation
 *
 * @see Dice
 */
class DiceInstanceManager implements ICanCreateInstances, ICanRegisterStrategies
{
	protected $instance = [];

	/** @var Dice */
	protected $dice;

	public function __construct(Dice $dice, StrategiesFileManager $strategiesFileManager)
	{
		$this->dice = $dice;
		$strategiesFileManager->setupStrategies($this);
	}

	/** {@inheritDoc} */
	public function registerStrategy(string $interface, string $class, ?string $name = null): ICanRegisterStrategies
	{
		if (!empty($this->instance[$interface][strtolower((string) $name)])) {
			throw new HookRegisterArgumentException(sprintf('A class with the name %s is already set for the interface %s', $name, $interface));
		}

		$this->instance[$interface][strtolower((string) $name)] = $class;

		return $this;
	}

	/** {@inheritDoc} */
	public function create(string $class, string $strategy, array $arguments = []): object
	{
		if (empty($this->instance[$class][strtolower($strategy)])) {
			throw new HookInstanceException(sprintf('The class with the name %s isn\'t registered for the class or interface %s', $strategy, $class));
		}

		return $this->dice->create($this->instance[$class][strtolower($strategy)], $arguments);
	}
}
