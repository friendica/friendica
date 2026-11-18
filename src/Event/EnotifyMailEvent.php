<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a notification email is being sent
 *
 * Can be used by addons to modify the notification email content before it is sent.
 */
final class EnotifyMailEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.enotify_mail';

	/**
	 * @internal
	 *
	 * @param array{preamble: string, type: int, parent: int, source_name: ?string, source_link: ?string, source_photo: ?string, uid: int, hsitelink: string, tsitelink: string, itemlink: string, title: string, body: string, subject: string, headers: array<string, array<string>>} $data
	 */
	public function __construct(
		private array $data,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array{preamble: string, type: int, parent: int, source_name: ?string, source_link: ?string, source_photo: ?string, uid: int, hsitelink: string, tsitelink: string, itemlink: string, title: string, body: string, subject: string, headers: array<string, array<string>>} */
	public function getDataArray(): array
	{
		return $this->data;
	}

	/** @param array{preamble: string, type: int, parent: int, source_name: ?string, source_link: ?string, source_photo: ?string, uid: int, hsitelink: string, tsitelink: string, itemlink: string, title: string, body: string, subject: string, headers: array<string, array<string>>} $data */
	public function setDataArray(array $data): void
	{
		$this->data = $data;
	}
}
