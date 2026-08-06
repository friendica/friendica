<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before a notification entry is stored in the database
 *
 * Can be used by addons to modify the notification database fields or to intercept the storage.
 */
final class EnotifyStoreEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.enotify_store';

	/**
	 * @internal
	 *
	 * @param array{type: int, name: string, url: string, photo: string, msg: ?string, uid: int, link: string, iid: ?int, parent: ?int, seen: bool, verb: string, otype: string, name_cache: ?string, msg_cache: ?string, uri-id: ?int, parent-uri-id: ?int, date: string} $data
	 */
	public function __construct(
		private array $data,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array{type: int, name: string, url: string, photo: string, msg: ?string, uid: int, link: string, iid: ?int, parent: ?int, seen: bool, verb: string, otype: string, name_cache: ?string, msg_cache: ?string, uri-id: ?int, parent-uri-id: ?int, date: string} */
	public function getDataArray(): array
	{
		return $this->data;
	}

	/** @param array{type: int, name: string, url: string, photo: string, msg: ?string, uid: int, link: string, iid: ?int, parent: ?int, seen: bool, verb: string, otype: string, name_cache: ?string, msg_cache: ?string, uri-id: ?int, parent-uri-id: ?int, date: string} $data */
	public function setDataArray(array $data): void
	{
		$this->data = $data;
	}
}
