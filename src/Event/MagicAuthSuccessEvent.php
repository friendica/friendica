<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a magic-auth was successful.
 */
final class MagicAuthSuccessEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.magic_auth_success';

	public function __construct(
		private array $visitor,
		private readonly string $url,
	) {
		parent::__construct(self::NAME);
	}

	public function getVisitorArray(): array
	{
		return $this->visitor;
	}

	public function setVisitorArray(array $visitor): void
	{
		$this->visitor = $visitor;
	}

	public function getUrl(): string
	{
		return $this->url;
	}
}
