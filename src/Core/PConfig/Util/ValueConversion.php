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

namespace Friendica\Core\PConfig\Util;

/**
 * Util class to help to convert from/to (p)config values
 */
class ValueConversion
{
	/**
	 * Formats a DB value to a config value
	 * - null   = The db-value isn't set
	 * - bool   = The db-value is either '0' or '1'
	 * - array  = The db-value is a serialized array
	 * - string = The db-value is a string
	 *
	 * Keep in mind that there aren't any numeric/integer config values in the database
	 *
	 * @param string|null $value
	 *
	 * @return null|array|string
	 */
	public static function toConfigValue(?string $value)
	{
		if (!isset($value)) {
			return null;
		}

		switch (true) {
			// manage array value
			case preg_match("|^a:[0-9]+:{.*}$|s", $value):
				return unserialize($value);

			default:
				return $value;
		}
	}

	/**
	 * Formats a config value to a DB value (string)
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function toDbValue($value): string
	{
		// if not set, save an empty string
		if (!isset($value)) {
			return '';
		}

		switch (true) {
			// manage arrays
			case is_array($value):
				return serialize($value);

			default:
				return (string)$value;
		}
	}
}
