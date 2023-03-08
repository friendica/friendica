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

namespace Friendica\Core\Config\Util;

/**
 * Serialize utils
 *
 * Retrieved from https://github.com/WordPress/wordpress-develop/blob/6.1/src/wp-includes/functions.php
 */
class SerializeUtil
{
	/**
	 * Checks if the value needs to get unserialized and returns the unserialized value
	 *
	 * @param mixed $value A possibly serialized value
	 *
	 * @return mixed The unserialized value
	 */
	public static function maybeUnserialize($value)
	{
		// This checks for possible multiple serialized values
		while (static::isSerialized($value)) {
			$oldValue = $value;
			$value = @unserialize($value);

			// If there's no change after the unserialize call, break the loop (avoid endless loops)
			if ($oldValue === $value) {
				break;
			}
		}

		return $value;
	}

	/**
	 * Checks value to find if it was serialized.
	 *
	 * If $data is not a string, then returned value will always be false.
	 * Serialized data is always a string.
	 *
	 * @param mixed $data   Value to check to see if was serialized.
	 * @param bool  $strict Optional. Whether to be strict about the end of the string. Default true.
	 *
	 * @return bool False if not serialized and true if it was.
	 * @since 6.1.0 Added Enum support.
	 *
	 * @since 2.0.5
	 */
	public static function isSerialized($data, bool $strict = true): bool
	{
		// If it isn't a string, it isn't serialized.
		if (!is_string($data)) {
			return false;
		}
		$data = trim($data);
		if ('N;' === $data) {
			return true;
		}
		if (strlen($data) < 4) {
			return false;
		}
		if (':' !== $data[1]) {
			return false;
		}
		if ($strict) {
			$lastc = substr($data, -1);
			if (';' !== $lastc && '}' !== $lastc) {
				return false;
			}
		} else {
			$semicolon = strpos($data, ';');
			$brace     = strpos($data, '}');
			// Either ; or } must exist.
			if (false === $semicolon && false === $brace) {
				return false;
			}
			// But neither must be in the first X characters.
			if (false !== $semicolon && $semicolon < 3) {
				return false;
			}
			if (false !== $brace && $brace < 4) {
				return false;
			}
		}
		$token = $data[0];
		switch ($token) {
			case 's':
				if ($strict) {
					if ('"' !== substr($data, -2, 1)) {
						return false;
					}
				} elseif (false === strpos($data, '"')) {
					return false;
				}
			// Or else fall through.
			// no break
			case 'a':
			case 'O':
			case 'E':
				return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';
				return (bool)preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
		}
		return false;
	}
}
