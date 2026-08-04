<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a new user account has been registered.
 */
final class AccountRegisterEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.account_register';

	public function __construct(
		private int $uid,
	) {
		parent::__construct(self::NAME);
	}

	public function getUserId(): int
	{
		return $this->uid;
	}

	public function setUserId(int $uid): void
	{
		$this->uid = $uid;
	}
}
