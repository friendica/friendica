<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Object\EMail\IEmail;

/**
 * Fired before an email is sent, to allow addons to prepare the email object.
 */
final class EmailerSendPrepareEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.emailer_send_prepare';

	/**
	 * @internal
	 */
	public function __construct(
		private ?IEmail $email = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getEmail(): ?IEmail
	{
		return $this->email;
	}

	public function setEmail(?IEmail $email): void
	{
		$this->email = $email;
	}
}
