<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when the registration form is displayed.
 */
final class AccountRegisterFormEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.account_register_form';

	public function __construct(
		private string $template,
	) {
		parent::__construct(self::NAME);
	}

	public function getMarkupTemplate(): string
	{
		return $this->template;
	}

	public function setMarkupTemplate(string $template): void
	{
		$this->template = $template;
	}
}
