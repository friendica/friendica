<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when trying to probe an item from a given URI
 *
 * Can be used by addons to fetch a post from an external service (e.g. a social connector) and report the fetched item id.
 */
final class FetchItemByLinkEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.fetch_item_by_link';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $uri,
		private readonly int $uid,
		private ?int $itemId,
	) {
		parent::__construct(self::NAME);
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getUserId(): int
	{
		return $this->uid;
	}

	public function getItemId(): ?int
	{
		return $this->itemId;
	}

	public function setItemId(?int $itemId): void
	{
		$this->itemId = $itemId;
	}
}
