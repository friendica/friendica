<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after a post has been prepared for display
 *
 * Can be used by addons to modify a post's HTML output after final processing.
 */
final class PreparePostEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.prepare_post_end';

	/**
	 * @param array<string, mixed> $item
	 * @param string $html
	 */
	/** @internal */
	public function __construct(
		private readonly array $item,
		private string $html,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}

	public function getHtml(): string
	{
		return $this->html;
	}

	public function setHtml(string $html): void
	{
		$this->html = $html;
	}
}
