<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Event is emitted when a ZRL init is triggered.
 */
final class ZrlInitEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.zrl_init';

	/** @internal */
	public function __construct(
		private readonly string $zrl,
		private readonly string $url,
	) {
		parent::__construct(self::NAME);
	}

	public function getZrlUrl(): string
	{
		return $this->zrl;
	}

	public function getUrl(): string
	{
		return $this->url;
	}
}
