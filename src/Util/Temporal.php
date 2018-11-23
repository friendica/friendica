<?php

/**
 * @file src/Util/Temporal.php
 */

namespace Friendica\Util;

use DateTime;
use DateTimeZone;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;

require_once 'boot.php';
require_once 'include/text.php';

/**
 * @brief Temporal class
 */
class Temporal
{
	/**
	 * @brief Two-level sort for timezones.
	 *
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	private static function timezoneCompareCallback($a, $b)
	{
		if (strstr($a, '/') && strstr($b, '/')) {
			if (L10n::t($a) == L10n::t($b)) {
				return 0;
			}
			return (L10n::t($a) < L10n::t($b)) ? -1 : 1;
		}

		if (strstr($a, '/')) {
			return -1;
		} elseif (strstr($b, '/')) {
			return 1;
		} elseif (L10n::t($a) == L10n::t($b)) {
			return 0;
		}

		return (L10n::t($a) < L10n::t($b)) ? -1 : 1;
	}

	/**
	 * @brief Emit a timezone selector grouped (primarily) by continent
	 *
	 * @param string $current Timezone
	 * @return string Parsed HTML output
	 */
	public static function getTimezoneSelect($current = 'America/Los_Angeles')
	{
		$timezone_identifiers = DateTimeZone::listIdentifiers();

		$o = '<select id="timezone_select" name="timezone">';

		usort($timezone_identifiers, [__CLASS__, 'timezoneCompareCallback']);
		$continent = '';
		foreach ($timezone_identifiers as $value) {
			$ex = explode("/", $value);
			if (count($ex) > 1) {
				if ($ex[0] != $continent) {
					if ($continent != '') {
						$o .= '</optgroup>';
					}
					$continent = $ex[0];
					$o .= '<optgroup label="' . L10n::t($continent) . '">';
				}
				if (count($ex) > 2) {
					$city = substr($value, strpos($value, '/') + 1);
				} else {
					$city = $ex[1];
				}
			} else {
				$city = $ex[0];
				if ($continent != L10n::t('Miscellaneous')) {
					$o .= '</optgroup>';
					$continent = L10n::t('Miscellaneous');
					$o .= '<optgroup label="' . L10n::t($continent) . '">';
				}
			}
			$city = str_replace('_', ' ', L10n::t($city));
			$selected = (($value == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"$value\" $selected >$city</option>";
		}
		$o .= '</optgroup></select>';
		return $o;
	}

	/**
	 * @brief Generating a Timezone selector
	 *
	 * Return a select using 'field_select_raw' template, with timezones
	 * grouped (primarily) by continent
	 * arguments follow convention as other field_* template array:
	 * 'name', 'label', $value, 'help'
	 *
	 * @param string $name Name of the selector
	 * @param string $label Label for the selector
	 * @param string $current Timezone
	 * @param string $help Help text
	 *
	 * @return string Parsed HTML
	 */
	public static function getTimezoneField($name = 'timezone', $label = '', $current = 'America/Los_Angeles', $help = '')
	{
		$options = self::getTimezoneSelect($current);
		$options = str_replace('<select id="timezone_select" name="timezone">', '', $options);
		$options = str_replace('</select>', '', $options);

		$tpl = Renderer::getMarkupTemplate('field_select_raw.tpl');
		return Renderer::replaceMacros($tpl, [
			'$field' => [$name, $label, $current, $help, $options],
		]);
	}

	/**
	 * @brief Wrapper for date selector, tailored for use in birthday fields.
	 *
	 * @param string $dob Date of Birth
	 * @return string Formatted HTML
	 */
	public static function getDateofBirthField($dob)
	{
		$a = get_app();

		list($year, $month, $day) = sscanf($dob, '%4d-%2d-%2d');

		if ($dob < '0000-01-01') {
			$value = '';
		} else {
			$value = DateTimeFormat::utc(($year > 1000) ? $dob : '1000-' . $month . '-' . $day, 'Y-m-d');
		}

		$age = (intval($value) ? self::getAgeByTimezone($value, $a->user["timezone"], $a->user["timezone"]) : "");

		$tpl = Renderer::getMarkupTemplate("field_input.tpl");
		$o = Renderer::replaceMacros($tpl,
			[
			'$field' => [
				'dob',
				L10n::t('Birthday:'),
				$value,
				intval($age) > 0 ? L10n::t('Age: ') . $age : "",
				'',
				'placeholder="' . L10n::t('YYYY-MM-DD or MM-DD') . '"'
			]
		]);

		return $o;
	}

	/**
	 * @brief Returns a date selector
	 *
	 * @param string $min     Unix timestamp of minimum date
	 * @param string $max     Unix timestap of maximum date
	 * @param string $default Unix timestamp of default date
	 * @param string $id      ID and name of datetimepicker (defaults to "datetimepicker")
	 *
	 * @return string Parsed HTML output.
	 */
	public static function getDateField($min, $max, $default, $id = 'datepicker')
	{
		return self::getDateTimeField($min, $max, $default, '', $id, true, false, '', '');
	}

	/**
	 * @brief Returns a time selector
	 *
	 * @param string $h  Already selected hour
	 * @param string $m  Already selected minute
	 * @param string $id ID and name of datetimepicker (defaults to "timepicker")
	 *
	 * @return string Parsed HTML output.
	 */
	public static function getTimeField($h, $m, $id = 'timepicker')
	{
		return self::getDateTimeField(new DateTime(), new DateTime(), new DateTime("$h:$m"), '', $id, false, true);
	}

	/**
	 * @brief Returns a datetime selector.
	 *
	 * @param DateTime $minDate     Minimum date
	 * @param DateTime $maxDate     Maximum date
	 * @param DateTime $defaultDate Default date
	 * @param string   $id          Id and name of datetimepicker (defaults to "datetimepicker")
	 * @param bool     $pickdate    true to show date picker (default)
	 * @param bool     $picktime    true to show time picker (default)
	 * @param string   $minfrom     set minimum date from picker with id $minfrom (none by default)
	 * @param string   $maxfrom     set maximum date from picker with id $maxfrom (none by default)
	 * @param bool     $required    default false
	 *
	 * @return string Parsed HTML output.
	 *
	 * @todo Once browser support is better this could probably be replaced with
	 * native HTML5 date picker.
	 */
	public static function getDateTimeField(
		DateTime $minDate,
		DateTime $maxDate,
		DateTime $defaultDate,
		$label,
		$id       = 'datetimepicker',
		$pickdate = true,
		$picktime = true,
		$minfrom  = '',
		$maxfrom  = '',
		$required = false)
	{
		// First day of the week (0 = Sunday)
		$firstDay = PConfig::get(local_user(), 'system', 'first_day_of_week', 0);

		$lang = substr(L10n::getCurrentLang(), 0, 2);

		// Check if the detected language is supported by the picker
		if (!in_array($lang,
				["ar", "ro", "id", "bg", "fa", "ru", "uk", "en", "el", "de", "nl", "tr", "fr", "es", "th", "pl", "pt", "ch", "se", "kr",
				"it", "da", "no", "ja", "vi", "sl", "cs", "hu"])) {
			$lang = 'en';
		}

		$o = '';
		$dateformat = '';

		if ($pickdate) {
			$dateformat .= 'Y-m-d';
		}

		if ($pickdate && $picktime) {
			$dateformat .= ' ';
		}

		if ($picktime) {
			$dateformat .= 'H:i';
		}

		$input_text = $defaultDate ? date($dateformat, $defaultDate->getTimestamp()) : '';

		$readable_format = str_replace(['Y', 'm', 'd', 'H', 'i'], ['yyyy', 'mm', 'dd', 'HH', 'MM'], $dateformat);

		$tpl = Renderer::getMarkupTemplate('field_datetime.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$field' => [
				$id,
				$label,
				$input_text,
				'',
				$required ? '*' : '',
				'placeholder="' . $readable_format . '"'
			],
			'$datetimepicker' => [
				'minDate' => $minDate,
				'maxDate' => $maxDate,
				'defaultDate' => $defaultDate,
				'dateformat' => $dateformat,
				'firstDay' => $firstDay,
				'lang' => $lang,
				'minfrom' => $minfrom,
				'maxfrom' => $maxfrom,
			]
		]);

		return $o;
	}

	/**
	 * @brief Returns a relative date string.
	 *
	 * Implements "3 seconds ago" etc.
	 * Based on $posted_date, (UTC).
	 * Results relative to current timezone.
	 * Limited to range of timestamps.
	 *
	 * @param string $posted_date MySQL-formatted date string (YYYY-MM-DD HH:MM:SS)
	 * @param string $format (optional) Parsed with sprintf()
	 *    <tt>%1$d %2$s ago</tt>, e.g. 22 hours ago, 1 minute ago
	 *
	 * @return string with relative date
	 */
	public static function getRelativeDate($posted_date, $format = null)
	{
		$localtime = $posted_date . ' UTC';

		$abs = strtotime($localtime);

		if (is_null($posted_date) || $posted_date <= DBA::NULL_DATETIME || $abs === false) {
			return L10n::t('never');
		}

		$isfuture = false;
		$etime = time() - $abs;

		if ($etime < 1 && $etime >= 0) {
			return L10n::t('less than a second ago');
		}

		if ($etime < 0){
			$etime = -$etime;
			$isfuture = true;
		}

		$a = [12 * 30 * 24 * 60 * 60 => [L10n::t('year'), L10n::t('years')],
			30 * 24 * 60 * 60 => [L10n::t('month'), L10n::t('months')],
			7 * 24 * 60 * 60 => [L10n::t('week'), L10n::t('weeks')],
			24 * 60 * 60 => [L10n::t('day'), L10n::t('days')],
			60 * 60 => [L10n::t('hour'), L10n::t('hours')],
			60 => [L10n::t('minute'), L10n::t('minutes')],
			1 => [L10n::t('second'), L10n::t('seconds')]
		];

		foreach ($a as $secs => $str) {
			$d = $etime / $secs;
			if ($d >= 1) {
				$r = round($d);
				// translators - e.g. 22 hours ago, 1 minute ago
				if (!$format) {
					if($isfuture){
						$format = L10n::t('in %1$d %2$s');
					}
					else {
						$format = ('%1$d %2$s');
					}
				}

				return sprintf($format, $r, (($r == 1) ? $str[0] : $str[1]));
			}
		}
	}

	/**
	 * @brief Returns timezone correct age in years.
	 *
	 * Returns the age in years, given a date of birth, the timezone of the person
	 * whose date of birth is provided, and the timezone of the person viewing the
	 * result.
	 *
	 * Why? Bear with me. Let's say I live in Mittagong, Australia, and my birthday
	 * is on New Year's. You live in San Bruno, California.
	 * When exactly are you going to see my age increase?
	 *
	 * A: 5:00 AM Dec 31 San Bruno time. That's precisely when I start celebrating
	 * and become a year older. If you wish me happy birthday on January 1
	 * (San Bruno time), you'll be a day late.
	 *
	 * @param string $dob Date of Birth
	 * @param string $owner_tz (optional) Timezone of the person of interest
	 * @param string $viewer_tz (optional) Timezone of the person viewing
	 *
	 * @return int Age in years
	 */
	public static function getAgeByTimezone($dob, $owner_tz = '', $viewer_tz = '')
	{
		if (!intval($dob)) {
			return 0;
		}
		if (!$owner_tz) {
			$owner_tz = date_default_timezone_get();
		}
		if (!$viewer_tz) {
			$viewer_tz = date_default_timezone_get();
		}

		$birthdate = DateTimeFormat::convert($dob . ' 00:00:00+00:00', $owner_tz, 'UTC', 'Y-m-d');
		list($year, $month, $day) = explode("-", $birthdate);
		$year_diff  = DateTimeFormat::timezoneNow($viewer_tz, 'Y') - $year;
		$curr_month = DateTimeFormat::timezoneNow($viewer_tz, 'm');
		$curr_day   = DateTimeFormat::timezoneNow($viewer_tz, 'd');

		if (($curr_month < $month) || (($curr_month == $month) && ($curr_day < $day))) {
			$year_diff--;
		}

		return $year_diff;
	}

	/**
	 * @brief Get days of a month in a given year.
	 *
	 * Returns number of days in the month of the given year.
	 * $m = 1 is 'January' to match human usage.
	 *
	 * @param int $y Year
	 * @param int $m Month (1=January, 12=December)
	 *
	 * @return int Number of days in the given month
	 */
	public static function getDaysInMonth($y, $m)
	{
		return date('t', mktime(0, 0, 0, $m, 1, $y));
	}

	/**
	 * @brief Returns the first day in month for a given month, year.
	 *
	 * Months start at 1.
	 *
	 * @param int $y Year
	 * @param int $m Month (1=January, 12=December)
	 *
	 * @return string day 0 = Sunday through 6 = Saturday
	 */
	private static function getFirstDayInMonth($y, $m)
	{
		$d = sprintf('%04d-%02d-01 00:00', intval($y), intval($m));

		return DateTimeFormat::utc($d, 'w');
	}

	/**
	 * @brief Output a calendar for the given month, year.
	 *
	 * If $links are provided (array), e.g. $links[12] => 'http://mylink' ,
	 * date 12 will be linked appropriately. Today's date is also noted by
	 * altering td class.
	 * Months count from 1.
	 *
	 * @param int    $y Year
	 * @param int    $m Month
	 * @param array  $links (default null)
	 * @param string $class
	 *
	 * @return string
	 *
	 * @todo Provide (prev, next) links, define class variations for different size calendars
	 */
	public static function getCalendarTable($y = 0, $m = 0, $links = null, $class = '')
	{
		// month table - start at 1 to match human usage.
		$mtab = [' ',
			'January', 'February', 'March',
			'April', 'May', 'June',
			'July', 'August', 'September',
			'October', 'November', 'December'
		];

		$thisyear = DateTimeFormat::localNow('Y');
		$thismonth = DateTimeFormat::localNow('m');
		if (!$y) {
			$y = $thisyear;
		}

		if (!$m) {
			$m = intval($thismonth);
		}

		$dn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
		$f = self::getFirstDayInMonth($y, $m);
		$l = self::getDaysInMonth($y, $m);
		$d = 1;
		$dow = 0;
		$started = false;

		if (($y == $thisyear) && ($m == $thismonth)) {
			$tddate = intval(DateTimeFormat::localNow('j'));
		}

		$str_month = L10n::getDay($mtab[$m]);
		$o = '<table class="calendar' . $class . '">';
		$o .= "<caption>$str_month $y</caption><tr>";
		for ($a = 0; $a < 7; $a ++) {
			$o .= '<th>' . mb_substr(L10n::getDay($dn[$a]), 0, 3, 'UTF-8') . '</th>';
		}

		$o .= '</tr><tr>';

		while ($d <= $l) {
			if (($dow == $f) && (!$started)) {
				$started = true;
			}

			$today = (((isset($tddate)) && ($tddate == $d)) ? "class=\"today\" " : '');
			$o .= "<td $today>";
			$day = str_replace(' ', '&nbsp;', sprintf('%2.2d', $d));
			if ($started) {
				if (x($links, $d) !== false) {
					$o .= "<a href=\"{$links[$d]}\">$day</a>";
				} else {
					$o .= $day;
				}

				$d ++;
			} else {
				$o .= '&nbsp;';
			}

			$o .= '</td>';
			$dow ++;
			if (($dow == 7) && ($d <= $l)) {
				$dow = 0;
				$o .= '</tr><tr>';
			}
		}

		if ($dow) {
			for ($a = $dow; $a < 7; $a ++) {
				$o .= '<td>&nbsp;</td>';
			}
		}

		$o .= '</tr></table>' . "\r\n";

		return $o;
	}
}
