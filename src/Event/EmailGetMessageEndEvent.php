<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when an email message has been fully fetched, to allow addons to modify the final message data.
 */
final class EmailGetMessageEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.email_get_message_end';

	/**
	 * @internal
	 */
	public function __construct(
		private array $item = [],
	) {
		parent::__construct(self::NAME);
	}

	public function getItemArray(): array
	{
		return $this->item;
	}

	public function setItemArray(array $item): void
	{
		$this->item = $item;
	}
}
