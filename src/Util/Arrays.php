<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

/**
 * Array utility class
 */
class Arrays
{
	/**
	 * Private constructor
	 */
	private function __construct()
	{
		// Utilities don't have instances
	}

	/**
	 * Implodes recursively a multi-dimensional array where a normal implode() will fail.
	 *
	 * @param array  $array Array to implode
	 * @param string $glue  Glue for imploded elements
	 * @return string String with elements from array
	 */
	public static function recursiveImplode(array $array, $glue)
	{
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
