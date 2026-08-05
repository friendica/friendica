<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a post is being prepared for display
 *
 * Can be used by addons to modify a post's HTML output.
 */
final class PreparePostEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.prepare_post';

	/**
	 * @param array<string, mixed> $item
	 * @param string $html
	 * @param bool $preview
	 * @param array<string> $filterReasons
	 */
	public function __construct(
		private readonly array $item,
		private string $html,
		private readonly bool $preview,
		private readonly array $filterReasons,
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

	public function isPreview(): bool
	{
		return $this->preview;
	}

	/** @return array<string> */
	public function getFilterReasons(): array
	{
		return $this->filterReasons;
	}
}
