<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a post is about to be inserted locally
 *
 * Can be used by addons to modify or reject a post before it's inserted locally.
 */
final class InsertPostRemoteEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.insert_post_remote';

	/** @internal */
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
