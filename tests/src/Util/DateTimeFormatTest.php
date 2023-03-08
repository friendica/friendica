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

use Friendica\Test\MockedTest;
use Friendica\Util\DateTimeFormat;

class DateTimeFormatTest extends MockedTest
{
	public function dataYearMonth()
	{
		return [
			'validNormal' => [
				'input' => '1990-10',
				'assert' => true,
			],
			'validOneCharMonth' => [
				'input' => '1990-1',
				'assert' => true,
			],
			'validTwoCharMonth' => [
				'input' => '1990-01',
				'assert' => true,
			],
			'invalidFormat' => [
				'input' => '199-11',
				'assert' => false,
			],
			'invalidFormat2' => [
				'input' => '1990-15',
				'assert' => false,
			],
			'invalidFormat3' => [
				'input' => '99-101',
				'assert' => false,
			],
			'invalidFormat4' => [
				'input' => '11-1990',
				'assert' => false,
			],
			'invalidFuture' => [
				'input' => '3030-12',
				'assert' => false,
			],
			'invalidYear' => [
				'input' => '-100-10',
				'assert' => false,
			],
		];
	}

	/**
	 * @dataProvider dataYearMonth
	 */
	public function testIsYearMonth(string $input, bool $assert)
	{
		$dtFormat = new DateTimeFormat();

		self::assertEquals($assert, $dtFormat->isYearMonth($input));
	}

	/**
	 * Test the DateTimeFormat::API output.
	 *
	 * @return void
	 */
	public function testApiDate()
	{
		self::assertEquals('Wed Oct 10 00:00:00 +0000 1990', DateTimeFormat::utc('1990-10-10', DateTimeFormat::API));
	}

	public function dataFix(): array
	{
		return [
			'Mo, 19 Sep 2022 14:51:00 +0200' => [
				'expectedDate' => '2022-09-19T14:51:00+02:00',
				'dateString' => 'Mo, 19 Sep 2022 14:51:00 +0200',
			],
			'2020-11-21T12:00:13.745339ZZ' => [
				'expectedDate' => '2020-11-21T12:00:13+00:00',
				'dateString' => '2020-11-21T12:00:13.745339ZZ',
			],
			'2016-09-09T13:32:00ZZ' => [
				'expectedDate' => '2016-09-09T13:32:00+00:00',
				'dateString' => '2016-09-09T13:32:00ZZ',
			],
			'Sun, 10/03/2021 - 12:41' => [
				'expectedDate' => '2021-10-03T12:41:00+00:00',
				'dateString' => 'Sun, 10/03/2021 - 12:41',
			],
			'4:30 PM, Sep 13, 2022' => [
				'expectedDate' => '2022-09-13T16:30:00+00:00',
				'dateString' => '4:30 PM, Sep 13, 2022',
			],
			'August 27, 2022 - 21:00' => [
				'expectedDate' => '2022-08-27T21:00:00+00:00',
				'dateString' => 'August 27, 2022 - 21:00',
			],
			'2021-09-19T14:06:03&#x2B;00:00' => [
				'expectedDate' => '2021-09-19T14:06:03+00:00',
				'dateString' => '2021-09-19T14:06:03&#x2B;00:00',
			],
			'Eastern Time timezone' => [
				'expectedDate' => '2022-09-30T00:00:00-05:00',
				'dateString' => 'September 30, 2022, 12:00 a.m. ET',
			],
			'German date time string' => [
				'expectedDate' => '2022-10-05T16:34:00+02:00',
				'dateString' => '05 Okt 2022 16:34:00 +0200',
			],
			'(Coordinated Universal Time)' => [
				'expectedDate' => '2022-12-30T14:29:10+00:00',
				'dateString' => 'Fri Dec 30 2022 14:29:10 GMT+0000 (Coordinated Universal Time)',
			],
			'Double HTML encode' => [
				'expectedDate' => '2015-05-22T08:48:00+12:00',
				'dateString' => '2015-05-22T08:48:00&amp;#43;12:00'
			]
		];
	}

	/**
	 * @dataProvider dataFix
	 *
	 * @param $expectedDate
	 * @param $dateString
	 * @return void
	 * @throws \Exception
	 */
	public function testFix($expectedDate, $dateString)
	{
		$fixed = DateTimeFormat::fix($dateString);

		$this->assertEquals($expectedDate, (new \DateTime($fixed))->format('c'));
	}

	/**
	 * This test is meant to ensure DateTimeFormat::fix() isn't called on relative date/time strings
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function testConvertRelative()
	{
		$now = DateTimeFormat::utcNow('U');
		$date = DateTimeFormat::utc('now - 3 days', 'U');

		$this->assertEquals(259200, $now - $date);
	}
}
