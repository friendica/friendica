<?php

/**
 * @file src/Util/DateTimeFormat.php
 */

namespace Friendica\Util;

use DateTime;
use DateTimeZone;
use Exception;
use Friendica\Content\Text;

/**
 * @brief Temporal class
 */
class DateTimeFormat
{
	const ATOM = 'Y-m-d\TH:i:s\Z';
	const MYSQL = 'Y-m-d H:i:s';

	/**
	 * convert() shorthand for UTC.
	 *
	 * @param string $time   A date/time string
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 */
	public static function utc($time, $format = self::MYSQL)
	{
		return self::convert($time, 'UTC', 'UTC', $format);
	}

	/**
	 * convert() shorthand for local.
	 *
	 * @param string $time   A date/time string
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
	 */
	public static function local($time, $format = self::MYSQL)
	{
		return self::convert($time, date_default_timezone_get(), 'UTC', $format);
	}

	/**
	 * convert() shorthand for timezoned now.
	 *
	 * @param string $format DateTime format string or Temporal constant
	 * @return string
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
	 */
	public static function utcNow($format = self::MYSQL)
	{
		return self::utc('now', $format);
	}

	/**
	 * @brief General purpose date parse/convert/format function.
	 *
	 * @param string $s       Some parseable date/time string
	 * @param string $tz_to   Destination timezone
	 * @param string $tz_from Source timezone
	 * @param string $format  Output format recognised from php's DateTime class
	 *   http://www.php.net/manual/en/datetime.format.php
	 *
	 * @return string Formatted date according to given format
	 */
	public static function convert($s = 'now', $tz_to = 'UTC', $tz_from = 'UTC', $format = self::MYSQL)
	{
		// Defaults to UTC if nothing is set, but throws an exception if set to empty string.
		// Provide some sane defaults regardless.
		if ($tz_from === '') {
			$tz_from = 'UTC';
		}

		if ($tz_to === '') {
			$tz_to = 'UTC';
		}

		if (($s === '') || (!is_string($s))) {
			$s = 'now';
		}

		/*
		 * Slight hackish adjustment so that 'zero' datetime actually returns what is intended
		 * otherwise we end up with -0001-11-30 ...
		 * add 32 days so that we at least get year 00, and then hack around the fact that
		 * months and days always start with 1.
		 */
		if (substr($s, 0, 10) <= '0001-01-01') {
			if ($s < '0000-00-00') {
				$s = '0000-00-00';
			}
			$d = new DateTime($s . ' + 32 days', new DateTimeZone('UTC'));
			return str_replace('1', '0', $d->format($format));
		}

		try {
			$from_obj = new DateTimeZone($tz_from);
		} catch (Exception $e) {
			$from_obj = new DateTimeZone('UTC');
		}

		try {
			$d = new DateTime($s, $from_obj);
		} catch (Exception $e) {
			Text::logger('DateTimeFormat::convert: exception: ' . $e->getMessage());
			$d = new DateTime('now', $from_obj);
		}

		try {
			$to_obj = new DateTimeZone($tz_to);
		} catch (Exception $e) {
			$to_obj = new DateTimeZone('UTC');
		}

		$d->setTimeZone($to_obj);

		return $d->format($format);
	}
}
