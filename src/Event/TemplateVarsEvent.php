<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when template variables are being set.
 *
 * Can be used by addons to modify the template variables.
 */
final class TemplateVarsEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.template_vars';

	/**
	 * @internal
	 *
	 * @param string $template
	 * @param array<string, mixed> $vars
	 */
	public function __construct(
		private readonly string $template,
		private array $vars,
	) {
		parent::__construct(self::NAME);
	}

	public function getTemplate(): string
	{
		return $this->template;
	}

	/** @return array<string, mixed> */
	public function getVars(): array
	{
		return $this->vars;
	}

	/** @param array<string, mixed> $vars */
	public function setVars(array $vars): void
	{
		$this->vars = $vars;
	}
}
