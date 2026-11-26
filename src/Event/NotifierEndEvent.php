<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after the notifier has processed an item
 *
 * Can be used by addons to react when an item has been fully processed by the notifier.
 */
final class NotifierEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.notifier_end';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $item
	 */
	public function __construct(
		private readonly array $item,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}
}
