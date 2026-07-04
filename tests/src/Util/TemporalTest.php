<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\DI;
use Friendica\Util\Clock\FrozenClock;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;
use PHPUnit\Framework\TestCase;

/**
 * Temporal utility test class
 */
class TemporalTest extends TestCase
{
	/**
	 * Checks for getRelativeDate()
	 */
	public function testGetRelativeDate(): void
	{
		$clock = new FrozenClock();

		// "never" should be returned
		self::assertEquals(
			Temporal::getRelativeDate('', true, $clock),
			DI::l10n()->t('never'),
		);

		// Format current date/time into "MySQL" format
		self::assertEquals(
			Temporal::getRelativeDate($clock->now()->format(DateTimeFormat::MYSQL), true, $clock),
			DI::l10n()->t('less than a second ago'),
		);

		// Format current date/time - 1 minute into "MySQL" format
		$minuteAgo = date('Y-m-d H:i:s', $clock->now()->getTimestamp() - 60);
		$format    = DI::l10n()->t('%1$d %2$s ago');

		// Should be both equal
		self::assertEquals(
			Temporal::getRelativeDate($minuteAgo, true, $clock),
			sprintf($format, 1, DI::l10n()->t('minute')),
		);

		$almostAnHourAgoInterval         = new \DateInterval('PT59M59S');
		$almostAnHourAgoInterval->invert = 1;
		$almostAnHourAgo                 = (clone $clock->now())->add($almostAnHourAgoInterval);

		self::assertEquals(
			Temporal::getRelativeDate($almostAnHourAgo->format(DateTimeFormat::MYSQL), true, $clock),
			sprintf($format, 59, DI::l10n()->t('minutes')),
		);

		$anHourAgoInterval         = new \DateInterval('PT1H');
		$anHourAgoInterval->invert = 1;
		$anHourAgo                 = (clone $clock->now())->add($anHourAgoInterval);

		self::assertEquals(
			Temporal::getRelativeDate($anHourAgo->format(DateTimeFormat::MYSQL), true, $clock),
			sprintf($format, 1, DI::l10n()->t('hour')),
		);
	}
}
