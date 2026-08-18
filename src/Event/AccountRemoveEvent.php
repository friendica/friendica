<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a user account is being removed.
 */
final class AccountRemoveEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.account_remove';

	/** @internal */
	public function __construct(
		private array $user,
	) {
		parent::__construct(self::NAME);
	}

	public function getUserArray(): array
	{
		return $this->user;
	}

	public function setUserArray(array $user): void
	{
		$this->user = $user;
	}
}
