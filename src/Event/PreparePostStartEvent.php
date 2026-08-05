<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before a post is being prepared for display
 *
 * Can be used by addons to modify a post before it's prepared for display.
 */
final class PreparePostStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.prepare_post_start';

	public function __construct(private array $item)
	{
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
