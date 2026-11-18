<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a user has logged in.
 */
final class LoggedInEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.logged_in';

	/** @internal */
	public function __construct(
		private readonly array $record,
	) {
		parent::__construct(self::NAME);
	}

	public function getRecordArray(): array
	{
		return $this->record;
	}
}
