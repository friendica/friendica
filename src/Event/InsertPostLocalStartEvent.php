<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a local post is being created, before any processing.
 *
 * Can be used by addons to modify the request data before the post is inserted.
 */
final class InsertPostLocalStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.insert_post_local_start';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $request
	 */
	public function __construct(
		private array $request,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getRequestArray(): array
	{
		return $this->request;
	}

	/** @param array<string, mixed> $request */
	public function setRequestArray(array $request): void
	{
		$this->request = $request;
	}
}
