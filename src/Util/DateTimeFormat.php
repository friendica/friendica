<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use DateTime;
use DateTimeZone;
use Exception;
use Friendica\DI;

/**
 * Temporal class
 */
class DateTimeFormat
{
	public const ATOM  = 'Y-m-d\TH:i:s\Z';
	public const MYSQL = 'Y-m-d H:i:s';
	public const HTTP  = 'D, d M Y H:i:s \G\M\T';
	public const JSON  = 'Y-m-d\TH:i:s.v\Z';
	public const API   = 'D M d H:i:s +0000 Y';

	public static $localTimezone = 'UTC';

	public static function setLocalTimeZone(string $timezone)
	{
		self::$localTimezone = $timezone;
	}

	/**
	 * convert() shorthand for UTC.
	 *
	 * @param string $time   A date/time string
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function utc(string $time, string $format = self::MYSQL): string
	{
		return self::convert($time, 'UTC', 'UTC', $format);
	}

	/**
	 * convert() shorthand for local.
	 *
	 * @param string $time   A date/time string
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function local($time, $format = self::MYSQL)
	{
		return self::convert($time, self::$localTimezone, 'UTC', $format);
	}

	/**
	 * convert() shorthand for timezoned now.
	 *
	 * @param        $timezone
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function timezoneNow($timezone, $format = self::MYSQL)
	{
		return self::convert('now', $timezone, 'UTC', $format);
	}

	/**
	 * convert() shorthand for local now.
	 *
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function localNow($format = self::MYSQL)
	{
		return self::local('now', $format);
	}

	/**
	 * convert() shorthand for UTC now.
	 *
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 * @throws Exception
	 */
	public static function utcNow(string $format = self::MYSQL): string
	{
		return self::utc('now', $format);
	}

	/**
	 * General purpose date parse/convert/format function.
	 *
	 * @param string $s       Some parseable date/time string
	 * @param string $tz_to   Destination timezone
	 * @param string $tz_from Source timezone
	 * @param string $format  Output format recognised from php's DateTime class
	 *                        http://www.php.net/manual/en/datetime.format.php
	 *
	 * @return string Formatted date according to given format
	 * @throws Exception
	 */
	public static function convert(string $s = 'now', string $tz_to = 'UTC', string $tz_from = 'UTC', string $format = self::MYSQL): string
	{
		// Defaults to UTC if nothing is set, but throws an exception if set to empty string.
		// Provide some sane defaults regardless.
		if ($tz_from === '') {
			$tz_from = 'UTC';
		}

		if ($tz_to === '') {
			$tz_to = 'UTC';
		}

		if ($s === '') {
			$s = 'now';
		}

		if (is_numeric($s) && ($s > time() * 100)) {
			$s = number_format($s / 1000, 0, '.', '');
		}

		// Lowest possible datetime value
		if (substr($s, 0, 10) <= '0001-01-01') {
			$d = new DateTime('now', new DateTimeZone('UTC'));
			$d->setDate(1, 1, 1)->setTime(0, 0);
			return $d->format($format);
		}

		try {
			$from_obj = new DateTimeZone($tz_from);
		} catch (Exception $e) {
			$from_obj = new DateTimeZone('UTC');
		}

		try {
			$d = DateTime::createFromFormat('U', $s, $from_obj)
				?: new DateTime($s, $from_obj);
		} catch (Exception $e) {
			try {
				$d = new DateTime(self::fix($s), $from_obj);
			} catch (\Throwable $e) {
				DI::logger()->warning('DateTimeFormat::convert: exception: ' . $e->getMessage());
				$d = new DateTime('now', $from_obj);
			}
		}

		try {
			$to_obj = new DateTimeZone($tz_to);
		} catch (Exception) {
			$to_obj = new DateTimeZone('UTC');
		}

		$d->setTimezone($to_obj);

		return $d->format($format);
	}

	/**
	 * Fix weird date formats.
	 *
	 * Note: This method isn't meant to sanitize valid date/time strings, for example it will mangle relative date
	 * strings like "now - 3 days".
	 *
	 * @see \Friendica\Test\src\Util\DateTimeFormatTest::dataFix() for a list of examples handled by this method.
	 * @param string $dateString
	 * @return string
	 */
	public static function fix(string $dateString): string
	{
		$search  = ['Mär', 'März', 'Mai', 'Juni', 'Juli', 'Okt', 'Dez', 'ET' , 'ZZ', ' - ', '&#x2B;', '&amp;#43;', ' (Coordinated Universal Time)', '\\'];
		$replace = ['Mar', 'Mar' , 'May', 'Jun' , 'Jul' , 'Oct', 'Dec', 'EST', 'Z' , ', ' , '+'     , '+'        , ''                             , ''];

		$dateString = str_replace($search, $replace, $dateString);

		$pregPatterns = [
			['#^(\w+), ((?:1[3-9]|2\d|3[0-1]))/(0?[1-9]|1[0-2])/(\d{4}), (\d{1,2}:\d{2}(?::\d{2})?)$#', '$4-$3-$2 $5'], // Thu, 19/03/2026, 07:26 (more specific, must be first)
			['#(\w+)\s+(\d+),\s+(\d{4})\s+at\s+(\d+:\d+)(am|pm)#i', '$2 $1 $3 $4 $5'], // April 9, 2026 at 10:00am
			['#(\w+)\s+(\d+),\s+(\d{4})\s+@\s+(\d+:\d+)\s+(AM|PM)\s+([A-Z]+)#i', '$2 $1 $3 $4 $5'], // Apr 09, 2026 @ 4:52 PM PDT
			['#(\w+)\.,\s+(\d+)\s+(\w+)\.\s+(\d{4})\s+(\d+:\d+:\d+)\s+([\+\-]\d{4})#', '$2 $3 $4 $5 $6'], // Fr., 10 Apr. 2026 14:46:27 +0200
			['#(\w+),\s+(\d+)\s+(\d{4})\s+(\d+:\d+:\d+)\s+([\+\-]\d{4})#', '$2 $1 $3 $4 $5'], // April, 10 2026 11:52:50 +0000
			['#^(\d{4}):(\d{2}):(\d{2})\s+(\d{2}:\d{2}:\d{2})\s+:?$#', '$1-$2-$3 $4'], // 2026:04:08 17:36:01 :
			['#\w+,\s+(\d{1,2})/(\d{1,2})/(\d{4}),\s+(\d{1,2}:\d{2})#', '$3-$1-$2 $4'], // Mo, 02/09/2026, 13:03
			['#^[^:]*:\s+(\d{1,2})\s+(\w+)\s+(\d{4}),\s+(\d{2}:\d{2})([-+]\d{2}:\d{2})#', '$1 $2 $3 $4 $5$6'], // Publicado: 25 Mar 2024, 08:42-03:00
			['#(\w+), (\d+ \w+ \d+) (\d+:\d+:\d+) (.+)#', '$2 $3 $4'],
			['#(\d+:\d+) (\w+), (\w+) (\d+), (\d+)#', '$1 $2 $3 $4 $5'],
			['#(GMT[+-]\d{4}) \([^)]*\)#', '$1'], // Tue Apr 07 2026 11:40:30 GMT+0530 (India Standard Time)
			['#\s+\((?:[A-Za-z]+(?: [A-Za-z]+){0,5})\)$#', ''], // Tue Apr 07 2026 11:40:30 (India Standard Time)
			['#\[[^\]]*\]#', ''], // 2025-03-07T08:54:14.341+01:00[Europe/Berlin]
		];

		foreach ($pregPatterns as $pattern) {
			$dateString = preg_replace($pattern[0], $pattern[1], (string) $dateString);
		}

		return $dateString;
	}

	/**
	 * Checks, if the given string is a date with the pattern YYYY-MM
	 *
	 * @param string $dateString The given date
	 *
	 * @return boolean True, if the date is a valid pattern
	 */
	public function isYearMonth(string $dateString)
	{
		// Check format (2019-01, 2019-1, 2019-10)
		if (!preg_match('/^([12]\d{3}-(1[0-2]|0[1-9]|\d))$/', $dateString)) {
			return false;
		}

		$date = DateTime::createFromFormat('Y-m', $dateString);

		if (!$date) {
			return false;
		}

		if ($date > new DateTime()) {
			return false;
		}

		return true;
	}

	/**
	 * Checks, if the given string is a date with the pattern YYYY-MM-DD
	 *
	 * @param string $dateString The given date
	 *
	 * @return boolean True, if the date is a valid pattern
	 */
	public function isYearMonthDay(string $dateString)
	{
		$date = DateTime::createFromFormat('Y-m-d', $dateString);
		if (!$date) {
			return false;
		}

		if (DateTime::getLastErrors()['error_count'] || DateTime::getLastErrors()['warning_count']) {
			return false;
		}

		return true;
	}
}
