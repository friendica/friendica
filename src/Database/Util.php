<?php

namespace Friendica\Database;

class Util
{
	/**
	 * Convert parameter array to an universal form
	 *
	 * @param array $args Parameter array
	 *
	 * @return array universalized parameter array
	 */
	public static function getParameters(array $args)
	{
		unset($args[0]);

		// When the second function parameter is an array then use this as the parameter array
		if ((count($args) > 0) && (is_array($args[1]))) {
			return $args[1];
		} else {
			return $args;
		}
	}

	/**
	 * @brief beautifies the query - useful for "SHOW PROCESSLIST"
	 *
	 * This is safe when we bind the parameters later.
	 * The parameter values aren't part of the SQL.
	 *
	 * @param string $sql An SQL string without the values
	 * @return string The input SQL string modified if necessary.
	 */
	public static function cleanQuery($sql) {
		$search = ["\t", "\n", "\r", "  "];
		$replace = [' ', ' ', ' ', ' '];
		do {
			$oldsql = $sql;
			$sql = str_replace($search, $replace, $sql);
		} while ($oldsql != $sql);

		return $sql;
	}
}
