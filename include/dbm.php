<?php
/**
 * @brief This class contain functions for the database management
 *
 */
class dbm {
	/**
	 * @brief Return a list of database processes
	 *
	 * @return array
	 *      'list' => List of processes, separated in their different states
	 *      'amount' => Number of concurrent database processes
	 */
	public static function processlist() {
		$r = q("SHOW PROCESSLIST");
		$s = array();

		$processes = 0;
		$states = array();
		foreach ($r AS $process) {
			$state = trim($process["State"]);

			// Filter out all non blocking processes
			if (!in_array($state, array("", "init", "statistics", "updating"))) {
				++$states[$state];
				++$processes;
			}
		}

		$statelist = "";
		foreach ($states AS $state => $usage) {
			if ($statelist != "")
				$statelist .= ", ";
			$statelist .= $state.": ".$usage;
		}
		return(array("list" => $statelist, "amount" => $processes));
	}

	/**
	 * Checks if $array is a filled array with at least one entry.
	 *
	 * @param       $array  mixed   A filled array with at least one entry
	 * @return      Whether $array is a filled array
	 */
	public static function is_result($array) {
		// It could be a return value from an update statement
		if (is_bool($array)) {
			return $array;
		}
		return (is_array($array) && count($array) > 0);
	}

	/**
	 * @brief Callback function for "esc_array"
	 *
	 * @param mixed $value Array value
	 * @param string $key Array key
	 * @param boolean $add_quotation add quotation marks for string values
	 */
	private static function esc_array_callback(&$value, $key, $add_quotation) {

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
		} elseif (is_float($value) OR is_integer($value)) {
			$value = (string)$value;
		} else {
			 $value = "'".dbesc($value)."'";
		}
	}

	/**
	 * @brief Escapes a whole array
	 *
	 * @param mixed $arr Array with values to be escaped
	 * @param boolean $add_quotation add quotation marks for string values
	 */
	public static function esc_array(&$arr, $add_quotation = false) {
		array_walk($arr, 'self::esc_array_callback', $add_quotation);
	}
}
?>
