<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when looking up the avatar for a contact.
 *
 * Can be used by addons to provide an avatar URL (e.g. from a remote service)
 * by setting the resulting URL and reporting whether the lookup succeeded.
 */
final class AvatarLookupEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.avatar_lookup';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly int $size,
		private readonly string $email,
		private string $url = '',
		private bool $success = false,
	) {
		parent::__construct(self::NAME);
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function setUrl(string $url): void
	{
		$this->url = $url;
	}

	public function isSuccess(): bool
	{
		return $this->success;
	}

	public function setSuccess(bool $success): void
	{
		$this->success = $success;
	}
}
