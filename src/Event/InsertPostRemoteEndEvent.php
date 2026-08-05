<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after a remote post has been inserted locally
 *
 * Can be used by addons to modify a post after it's been inserted locally from a remote source.
 */
final class InsertPostRemoteEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.insert_post_remote_end';

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
