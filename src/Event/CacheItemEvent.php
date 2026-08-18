<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when caching an item's rendered HTML
 *
 * Can be used by addons to modify the cached HTML output of a post.
 */
final class CacheItemEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.cache_item';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $item
	 */
	public function __construct(
		private readonly array $item,
		private string $renderedHtml,
		private string $renderedHash,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}

	public function getRenderedHtml(): string
	{
		return $this->renderedHtml;
	}

	public function setRenderedHtml(string $renderedHtml): void
	{
		$this->renderedHtml = $renderedHtml;
	}

	public function getRenderedHash(): string
	{
		return $this->renderedHash;
	}

	public function setRenderedHash(string $renderedHash): void
	{
		$this->renderedHash = $renderedHash;
	}
}
