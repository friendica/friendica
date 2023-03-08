<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
	public function testGetRelativeDate()
	{
		$clock = new FrozenClock();

		// "never" should be returned
		self::assertEquals(
			Temporal::getRelativeDate('', true, $clock),
			DI::l10n()->t('never')
		);

		// Format current date/time into "MySQL" format
		self::assertEquals(
			Temporal::getRelativeDate($clock->now()->format(DateTimeFormat::MYSQL), true, $clock),
			DI::l10n()->t('less than a second ago')
		);

		// Format current date/time - 1 minute into "MySQL" format
		$minuteAgo = date('Y-m-d H:i:s', $clock->now()->getTimestamp() - 60);
		$format    = DI::l10n()->t('%1$d %2$s ago');

		// Should be both equal
		self::assertEquals(
			Temporal::getRelativeDate($minuteAgo, true, $clock),
			sprintf($format, 1, DI::l10n()->t('minute'))
		);

		$almostAnHourAgoInterval = new \DateInterval('PT59M59S');
		$almostAnHourAgoInterval->invert = 1;
		$almostAnHourAgo = (clone $clock->now())->add($almostAnHourAgoInterval);

		self::assertEquals(
			Temporal::getRelativeDate($almostAnHourAgo->format(DateTimeFormat::MYSQL), true, $clock),
			sprintf($format, 59, DI::l10n()->t('minutes'))
		);

		$anHourAgoInterval = new \DateInterval('PT1H');
		$anHourAgoInterval->invert = 1;
		$anHourAgo = (clone $clock->now())->add($anHourAgoInterval);

		self::assertEquals(
			Temporal::getRelativeDate($anHourAgo->format(DateTimeFormat::MYSQL), true, $clock),
			sprintf($format, 1, DI::l10n()->t('hour'))
		);
	}
}
