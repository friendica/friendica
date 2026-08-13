<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before a profile URL is submitted to the global directory, to allow addons to change or suppress the submission.
 */
final class GlobalDirUpdateEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.global_dir_update';

	/**
	 * @internal
	 */
	public function __construct(
		private string $url,
	) {
		parent::__construct(self::NAME);
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function setUrl(string $url): void
	{
		$this->url = $url;
	}
}
