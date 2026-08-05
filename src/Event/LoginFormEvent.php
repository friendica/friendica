<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when the login form is displayed.
 */
final class LoginFormEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.login_form';

	/** @internal */
	public function __construct(
		private string $html,
	) {
		parent::__construct(self::NAME);
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
