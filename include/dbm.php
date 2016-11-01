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
}
?>
