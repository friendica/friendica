<?php
/**
 * @file src/Database/DBM.php
 */
namespace Friendica\Database;

use dba;

require_once 'include/dba.php';

/**
 * @brief This class contain functions for the database management
 *
 * This class contains functions that doesn't need to know if pdo, mysqli or whatever is used.
 */
class DBM
{
	/**
	 * @brief Return a list of database processes
	 *
	 * @return array
	 *      'list' => List of processes, separated in their different states
	 *      'amount' => Number of concurrent database processes
	 */
	public static function processlist()
	{
		$r = q("SHOW PROCESSLIST");
		$s = [];

		$processes = 0;
		$states = [];
		foreach ($r as $process) {
			$state = trim($process["State"]);

			// Filter out all non blocking processes
			if (!in_array($state, ["", "init", "statistics", "updating"])) {
				++$states[$state];
				++$processes;
			}
		}

		$statelist = "";
		foreach ($states as $state => $usage) {
			if ($statelist != "") {
				$statelist .= ", ";
			}
			$statelist .= $state.": ".$usage;
		}
		return(["list" => $statelist, "amount" => $processes]);
	}

	/**
	 * Checks if $array is a filled array with at least one entry.
	 *
	 * @param mixed $array A filled array with at least one entry
	 *
	 * @return boolean Whether $array is a filled array or an object with rows
	 */
	public static function isResult($array)
	{
		// It could be a return value from an update statement
		if (is_bool($array)) {
			return $array;
		}

		if (is_object($array)) {
			return dba::num_rows($array) > 0;
		}

		return (is_array($array) && (count($array) > 0));
	}

	/**
	 * @brief Callback function for "esc_array"
	 *
	 * @param mixed   $value         Array value
	 * @param string  $key           Array key
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	private static function esc_array_callback(&$value, $key, $add_quotation)
	{
		if (!$add_quotation) {
			if (is_bool($value)) {
				$value = ($value ? '1' : '0');
			} else {
				$value = dbesc($value);
			}
			return;
		}

		if (is_bool($value)) {
			$value = ($value ? 'true' : 'false');
		} elseif (is_float($value) || is_integer($value)) {
			$value = (string)$value;
		} else {
			 $value = "'".dbesc($value)."'";
		}
	}

	/**
	 * @brief Escapes a whole array
	 *
	 * @param mixed   $arr           Array with values to be escaped
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	public static function esc_array(&$arr, $add_quotation = false)
	{
		array_walk($arr, 'self::esc_array_callback', $add_quotation);
	}

	/**
	 * Checks Converts any date string into a SQL compatible date string
	 *
	 * @param string $date a date string in any format
	 *
	 * @return string SQL style date string
	 */
	public static function date($date = 'now')
	{
		$timestamp = strtotime($date);

		// Don't allow lower date strings as '0001-01-01 00:00:00'
		if ($timestamp < -62135596800) {
			$timestamp = -62135596800;
		}

		return date('Y-m-d H:i:s', (int)$timestamp);
	}
}
