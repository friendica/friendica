<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a notification is created and an email is sent
 *
 * Can be used by addons to modify the notification data before it is stored or sent.
 */
final class EnotifyEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.enotify';

	/**
	 * @internal
	 *
	 * @param array{params: array<string, mixed>, subject: string, preamble: string, epreamble: string, body: string, sitelink: string, tsitelink: string, hsitelink: string, itemlink: string} $data
	 */
	public function __construct(
		private array $data,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array{params: array<string, mixed>, subject: string, preamble: string, epreamble: string, body: string, sitelink: string, tsitelink: string, hsitelink: string, itemlink: string} */
	public function getDataArray(): array
	{
		return $this->data;
	}

	/** @param array{params: array<string, mixed>, subject: string, preamble: string, epreamble: string, body: string, sitelink: string, tsitelink: string, hsitelink: string, itemlink: string} $data */
	public function setDataArray(array $data): void
	{
		$this->data = $data;
	}
}
