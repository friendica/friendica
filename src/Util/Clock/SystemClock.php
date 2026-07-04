<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util\Clock;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Inspired by lcobucci/clock
 * @see https://github.com/lcobucci/clock
 */
final readonly class SystemClock implements \Psr\Clock\ClockInterface
{
	public function __construct(private ?DateTimeZone $timezone = new DateTimeZone('UTC')) {}

	/**
	 * @inheritDoc
	 */
	public function now(): DateTimeImmutable
	{
		return new DateTimeImmutable('now', $this->timezone);
	}
}
