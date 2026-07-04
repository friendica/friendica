<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Allow modules to react on initialization.
 *
 * @internal
 */
final class ModuleInitEvent extends Event
{
	public const MODULE_INIT = 'friendica.module_init';

	/**
	 * @param class-string<\Friendica\BaseModule> $moduleClass
	 */
	public function __construct(string $name, private readonly string $moduleName, private readonly string $moduleClass)
	{
		parent::__construct($name);
	}

	public function getModuleName(): string
	{
		return $this->moduleName;
	}

	/**
	 * @return class-string<\Friendica\BaseModule>
	 */
	public function getModuleClass(): string
	{
		return $this->moduleClass;
	}
}
