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

namespace Friendica\Util;

/**
 * Array utility class
 */
class Arrays
{
	/**
	 * Private constructor
	 */
	private function __construct() {
		// Utitlities don't have instances
	}

	/**
	 * Implodes recursively a multi-dimensional array where a normal implode() will fail.
	 *
	 * @param array  $array Array to implode
	 * @param string $glue  Glue for imploded elements
	 * @return string String with elements from array
	 */
	public static function recursiveImplode(array $array, $glue) {
		// Init returned string
		$string = '';

		// Loop through all records
		foreach ($array as $element) {
			// Is an array found?
			if (is_array($element)) {
				// Invoke cursively
				$string .= '{' . self::recursiveImplode($element, $glue) . '}' . $glue;
			} else {
				// Append normally
				$string .= $element . $glue;
			}
		}

		// Remove last glue
		$string = trim($string, $glue);

		// Return it
		return $string;
	}

	/**
	 * walks recursively through an array with the possibility to change value and key
	 *
	 * @param array    $array    The array to walk through
	 * @param callable $callback The callback function
	 *
	 * @return array the transformed array
	 */
	public static function walkRecursive(array &$array, callable $callback)
	{
		$new_array = [];

		foreach ($array as $k => $v) {
			if (is_array($v)) {
				if ($callback($v, $k)) {
					$new_array[$k] = self::walkRecursive($v, $callback);
				}
			} else {
				if ($callback($v, $k)) {
					$new_array[$k] = $v;
				}
			}
		}
		$array = $new_array;

		return $array;
	}
}
