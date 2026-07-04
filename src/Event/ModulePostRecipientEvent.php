<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Allow modules to react on post recipient rendering.
 *
 * @internal
 */
final class ModulePostRecipientEvent extends Event
{
	public const MODULE_POST_RECIPIENT = 'friendica.module_post_recipient';

	/**
	 * @param class-string<\Friendica\BaseModule> $moduleClass
	 */
	public function __construct(string $name, private readonly string $moduleName, private readonly string $moduleClass, private string $html)
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

	public function getHtml(): string
	{
		return $this->html;
	}

	public function setHtml(string $html): void
	{
		$this->html = $html;
	}
}
