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

namespace Friendica\Model;

/**
 * This class handles FileTag related functions
 *
 * post categories and "save to file" use the same item.file table for storage.
 * We will differentiate the different uses by wrapping categories in angle brackets
 * and save to file categories in square brackets.
 * To do this we need to escape these characters if they appear in our tag.
 */
class FileTag
{
	/**
	 * URL encode <, >, left and right brackets
	 *
	 * @param string $s String to be URL encoded.
	 * @return string   The URL encoded string.
	 */
	private static function encode(string $s): string
	{
		return str_replace(['<', '>', '[', ']'], ['%3c', '%3e', '%5b', '%5d'], $s);
	}

	/**
	 * URL decode <, >, left and right brackets
	 *
	 * @param string $s The URL encoded string to be decoded
	 * @return string   The decoded string.
	 */
	private static function decode(string $s): string
	{
		return str_replace(['%3c', '%3e', '%5b', '%5d'], ['<', '>', '[', ']'], $s);
	}

	/**
	 * Get file tags from array
	 *
	 * ex. given [music,video] return <music><video> or [music][video]
	 *
	 * @param array  $array A list of tags.
	 * @param string $type  Optional file type.
	 * @return string       A list of file tags.
	 */
	public static function arrayToFile(array $array, string $type = 'file'): string
	{
		$tag_list = '';
		if ($type == 'file') {
			$lbracket = '[';
			$rbracket = ']';
		} else {
			$lbracket = '<';
			$rbracket = '>';
		}

		foreach ($array as $item) {
			if (strlen($item)) {
				$tag_list .= $lbracket . self::encode(trim($item)) . $rbracket;
			}
		}

		return $tag_list;
	}

	/**
	 * Get tag list from file tags
	 *
	 * ex. given <music><video>[friends], return [music,video] or [friends]
	 *
	 * @param string $file File tags
	 * @param string $type Optional file type.
	 * @return array        List of tag names.
	 */
	public static function fileToArray(string $file, string $type = 'file'): array
	{
		$matches = [];
		$return = [];

		if ($type == 'file') {
			$cnt = preg_match_all('/\[(.*?)\]/', $file, $matches, PREG_SET_ORDER);
		} else {
			$cnt = preg_match_all('/<(.*?)>/', $file, $matches, PREG_SET_ORDER);
		}

		if ($cnt) {
			foreach ($matches as $match) {
				$return[] = self::decode($match[1]);
			}
		}

		return $return;
	}
}
