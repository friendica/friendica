<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Allow modules to react on POST requests.
 *
 * @internal
 */
final class ModulePostEvent extends Event
{
	public const MODULE_POST = 'friendica.module_post';

	/**
	 * @param class-string<\Friendica\BaseModule> $moduleClass
	 */
	public function __construct(string $name, private readonly string $moduleName, private readonly string $moduleClass, private array $post)
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

	public function getPost(): array
	{
		return $this->post;
	}

	public function setPost(array $post): void
	{
		$this->post = $post;
	}
}
