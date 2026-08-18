<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before content filtering is applied to a post
 *
 * Can be used by addons to modify filter reasons before content filtering (e.g., bbcode to HTML conversion).
 */
final class PreparePostFilterContentEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.prepare_post_filter_content';

	/**
	 * @param array<string, mixed> $item
	 * @param array<string> $filterReasons
	 */
	/** @internal */
	public function __construct(
		private readonly array $item,
		private readonly int $uid,
		private array $filterReasons,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}

	public function getUserId(): int
	{
		return $this->uid;
	}

	/** @return array<string> */
	public function getFilterReasons(): array
	{
		return $this->filterReasons;
	}

	/** @param array<string> $filterReasons */
	public function setFilterReasons(array $filterReasons): void
	{
		$this->filterReasons = $filterReasons;
	}
}
