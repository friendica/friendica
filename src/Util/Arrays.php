<?php
/**
 * @file src/Util/Arrays.php
 * @author Roland Haeder<https://f.haeder.net/profile/roland>
 */
namespace Friendica\Util;

/**
 * @brief Array utility class
 */
class Arrays
{
	/**
	 * @brief Private constructor
	 */
	private function __construct () {
		// Utitlities don't have instances
	}

	/**
	 * @briefs Implodes recursively a multi-dimensional array where a normal implode() will fail.
	 *
	 * @param array  $array Array to implode
	 * @param string $glue  Glue for imploded elements
	 * @return string String with elements from array
	 */
	public static function recursiveImplode (array $array, $glue) {
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
}
