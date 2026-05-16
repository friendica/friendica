<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTestCase;
use Friendica\Util\DateTimeFormat;

class DateTimeFormatTest extends MockedTestCase
{
	public function dataYearMonth()
	{
		return [
			'validNormal' => [
				'input'  => '1990-10',
				'assert' => true,
			],
			'validOneCharMonth' => [
				'input'  => '1990-1',
				'assert' => true,
			],
			'validTwoCharMonth' => [
				'input'  => '1990-01',
				'assert' => true,
			],
			'invalidFormat' => [
				'input'  => '199-11',
				'assert' => false,
			],
			'invalidFormat2' => [
				'input'  => '1990-15',
				'assert' => false,
			],
			'invalidFormat3' => [
				'input'  => '99-101',
				'assert' => false,
			],
			'invalidFormat4' => [
				'input'  => '11-1990',
				'assert' => false,
			],
			'invalidFuture' => [
				'input'  => '3030-12',
				'assert' => false,
			],
			'invalidYear' => [
				'input'  => '-100-10',
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
				'dateString'   => 'Mo, 19 Sep 2022 14:51:00 +0200',
			],
			'2020-11-21T12:00:13.745339ZZ' => [
				'expectedDate' => '2020-11-21T12:00:13+00:00',
				'dateString'   => '2020-11-21T12:00:13.745339ZZ',
			],
			'2016-09-09T13:32:00ZZ' => [
				'expectedDate' => '2016-09-09T13:32:00+00:00',
				'dateString'   => '2016-09-09T13:32:00ZZ',
			],
			'Sun, 10/03/2021 - 12:41' => [
				'expectedDate' => '2021-10-03T12:41:00+00:00',
				'dateString'   => 'Sun, 10/03/2021 - 12:41',
			],
			'4:30 PM, Sep 13, 2022' => [
				'expectedDate' => '2022-09-13T16:30:00+00:00',
				'dateString'   => '4:30 PM, Sep 13, 2022',
			],
			'August 27, 2022 - 21:00' => [
				'expectedDate' => '2022-08-27T21:00:00+00:00',
				'dateString'   => 'August 27, 2022 - 21:00',
			],
			'2021-09-19T14:06:03&#x2B;00:00' => [
				'expectedDate' => '2021-09-19T14:06:03+00:00',
				'dateString'   => '2021-09-19T14:06:03&#x2B;00:00',
			],
			'Eastern Time timezone' => [
				'expectedDate' => '2022-09-30T00:00:00-05:00',
				'dateString'   => 'September 30, 2022, 12:00 a.m. ET',
			],
			'German date time string' => [
				'expectedDate' => '2022-10-05T16:34:00+02:00',
				'dateString'   => '05 Okt 2022 16:34:00 +0200',
			],
			'(Coordinated Universal Time)' => [
				'expectedDate' => '2022-12-30T14:29:10+00:00',
				'dateString'   => 'Fri Dec 30 2022 14:29:10 GMT+0000 (Coordinated Universal Time)',
			],
			'(India Standard Time)' => [
				'expectedDate' => '2026-04-07T11:40:30+05:30',
				'dateString'   => 'Tue Apr 07 2026 11:40:30 GMT+0530 (India Standard Time)',
			],
			'(India Standard Time) fallback without GMT prefix' => [
				'expectedDate' => '2026-04-07T11:40:30+05:30',
				'dateString'   => 'Tue Apr 07 2026 11:40:30 +0530 (India Standard Time)',
			],
			'Unambiguous day/month date string' => [
				'expectedDate' => '2026-03-19T07:26:00+00:00',
				'dateString'   => 'Thu, 19/03/2026, 07:26',
			],
			'Double HTML encode' => [
				'expectedDate' => '2015-05-22T08:48:00+12:00',
				'dateString'   => '2015-05-22T08:48:00&amp;#43;12:00',
			],
			'2023-04-02\T17:22:42+05:30' => [
				'expectedDate' => '2023-04-02T17:22:42+05:30',
				'dateString'   => '2023-04-02\T17:22:42+05:30',
			],
			'2025-03-07T08:54:14.341+01:00[Europe/Berlin]' => [
				'expectedDate' => '2025-03-07T08:54:14+01:00',
				'dateString'   => '2025-03-07T08:54:14.341+01:00[Europe/Berlin]',
			],
			'April 9, 2026 at 10:00am' => [
				'expectedDate' => '2026-04-09T10:00:00+00:00',
				'dateString'   => 'April 9, 2026 at 10:00am',
			],
			'Apr 09, 2026 @ 4:52 PM PDT' => [
				'expectedDate' => '2026-04-09T16:52:00+00:00',
				'dateString'   => 'Apr 09, 2026 @ 4:52 PM PDT',
			],
			'Fr., 10 Apr. 2026 14:46:27 +0200' => [
				'expectedDate' => '2026-04-10T14:46:27+02:00',
				'dateString'   => 'Fr., 10 Apr. 2026 14:46:27 +0200',
			],
			'April, 10 2026 11:52:50 +0000' => [
				'expectedDate' => '2026-04-10T11:52:50+00:00',
				'dateString'   => 'April, 10 2026 11:52:50 +0000',
			],
			'2026:04:08 17:36:01 :' => [
				'expectedDate' => '2026-04-08T17:36:01+00:00',
				'dateString'   => '2026:04:08 17:36:01 :',
			],
			'Mo, 02/09/2026, 13:03' => [
				'expectedDate' => '2026-02-09T13:03:00+00:00',
				'dateString'   => 'Mo, 02/09/2026, 13:03',
			],
			'Publicado: 25 Mar 2024, 08:42-03:00' => [
				'expectedDate' => '2024-03-25T08:42:00-03:00',
				'dateString'   => 'Publicado: 25 Mar 2024, 08:42-03:00',
			],
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
		$now  = DateTimeFormat::utcNow('U');
		$date = DateTimeFormat::utc('now - 3 days', 'U');

		$this->assertEquals(259200, $now - $date);
	}

	public function dataConvert()
	{
		return [
			'unix timestamp' => [
				'expected' => '2025-03-12 16:18:27',
				's'        => '1741796307',
			],
			'ATOM' => [
				'expected' => '2022-06-02 15:58:35',
				's'        => '2022-06-02T16:58:35+01:00',
			],
			'COOKIE' => [
				'expected' => '2022-06-02 14:58:35',
				's'        => 'Thursday, 02-Jun-2022 16:58:35 Africa/Cairo',
			],
			'ISO 8601/RFC 3339' => [
				'expected' => '2022-06-02 13:58:35',
				's'        => '2022-06-02T16:58:35+0300',
			],
			'RFC 822/RFC 1036' => [
				'expected' => '2022-06-02 12:58:35',
				's'        => 'Thu, 02 Jun 22 16:58:35 +0400',
			],
			'RFC 850' => [
				'expected' => '2022-06-02 11:58:35',
				's'        => 'Thursday, 02-Jun-22 16:58:35 Indian/Kerguelen',
			],
			'RFC 1123/RFC 2822/RSS' => [
				'expected' => '2022-06-02 10:58:35',
				's'        => 'Thu, 02 Jun 2022 16:58:35 +0600',
			],
			'RFC 3339/W3C' => [
				'expected' => '2025-03-07 01:54:14',
				's'        => '2025-03-07T08:54:14+07:00',
			],
			'RFC 3339 extended' => [
				'expected' => '2025-03-07 00:54:14',
				's'        => '2025-03-07T08:54:14.341+08:00',
			],
			'RFC 7231' => [
				'expected' => '2022-06-02 07:58:35',
				's'        => 'Thu, 02 Jun 2022 16:58:35 Asia/Tokyo',
			],
			'microsecond timestamp' => [
				'expected' => '2025-11-05 22:11:28',
				's'        => '1762380688000',
			],
		];
	}

	/**
	 * @dataProvider dataConvert
	 */
	public function testConvert($expected, string $s = 'now', string $tz_to = 'UTC', string $tz_from = 'UTC', string $format = DateTimeFormat::MYSQL)
	{
		$this->assertSame($expected, DateTimeFormat::convert($s, $tz_to, $tz_from, $format));
	}

	public function dataConvertNow()
	{
		return [
			'now missing' => [
			],
			'now empty' => [
				's' => '',
			],
			'now now' => [
				's' => 'now',
			],
		];
	}

	/**
	 * @dataProvider dataConvertNow
	 */
	public function testConvertNow(string $s = 'now', string $tz_to = 'UTC', string $tz_from = 'UTC', string $format = DateTimeFormat::MYSQL)
	{
		$this->assertSame(date(DateTimeFormat::MYSQL), DateTimeFormat::convert($s, $tz_to, $tz_from, $format));
	}


}
