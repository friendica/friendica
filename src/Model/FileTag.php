<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post\Category;

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
	 *
	 * @return string   The URL encoded string.
	 */
	public static function encode($s)
	{
		return str_replace(['<', '>', '[', ']'], ['%3c', '%3e', '%5b', '%5d'], $s);
	}

	/**
	 * URL decode <, >, left and right brackets
	 *
	 * @param string $s The URL encoded string to be decoded
	 *
	 * @return string   The decoded string.
	 */
	public static function decode($s)
	{
		return str_replace(['%3c', '%3e', '%5b', '%5d'], ['<', '>', '[', ']'], $s);
	}

	/**
	 * Query files for tag
	 *
	 * @param string $table The table to be queired.
	 * @param string $s     The search term
	 * @param string $type  Optional file type.
	 *
	 * @return string       Query string.
	 */
	public static function fileQuery($table, $s, $type = 'file')
	{
		if ($type == 'file') {
			$str = preg_quote('[' . str_replace('%', '%%', self::encode($s)) . ']');
		} else {
			$str = preg_quote('<' . str_replace('%', '%%', self::encode($s)) . '>');
		}

		return " AND " . (($table) ? DBA::escape($table) . '.' : '') . "file regexp '" . DBA::escape($str) . "' ";
	}

	/**
	 * Get file tags from array
	 *
	 * ex. given [music,video] return <music><video> or [music][video]
	 *
	 * @param array  $array A list of tags.
	 * @param string $type  Optional file type.
	 *
	 * @return string       A list of file tags.
	 */
	public static function arrayToFile(array $array, string $type = 'file')
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
	 *
	 * @return array        List of tag names.
	 */
	public static function fileToArray(string $file, string $type = 'file')
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

	/**
	 * Get file tags from list
	 *
	 * ex. given music,video return <music><video> or [music][video]
	 * @param string $list A comma delimited list of tags.
	 * @param string $type Optional file type.
	 *
	 * @return string       A list of file tags.
	 * @deprecated since 2019.06 use arrayToFile() instead
	 */
	public static function listToFile(string $list, string $type = 'file')
	{
		$list_array = explode(',', $list);

		return self::arrayToFile($list_array, $type);
	}

	/**
	 * Get list from file tags
	 *
	 * ex. given <music><video>[friends], return music,video or friends
	 * @param string $file File tags
	 * @param string $type Optional file type.
	 *
	 * @return string       Comma delimited list of tag names.
	 * @deprecated since 2019.06 use fileToArray() instead
	 */
	public static function fileToList(string $file, $type = 'file')
	{
		return implode(',', self::fileToArray($file, $type));
	}

	/**
	 * Update file tags in PConfig
	 *
	 * @param int    $uid      Unique Identity.
	 * @param string $file_old Categories previously associated with an item
	 * @param string $file_new New list of categories for an item
	 * @param string $type     Optional file type.
	 *
	 * @return boolean          A value indicating success or failure.
	 * @throws \Exception
	 */
	public static function updatePconfig(int $uid, string $file_old, string $file_new, string $type = 'file')
	{
		if (!intval($uid)) {
			return false;
		} elseif ($file_old == $file_new) {
			return true;
		}

		$saved = DI::pConfig()->get($uid, 'system', 'filetags');

		if (strlen($saved)) {
			if ($type == 'file') {
				$lbracket = '[';
				$rbracket = ']';
				$termtype = Category::FILE;
			} else {
				$lbracket = '<';
				$rbracket = '>';
				$termtype = Category::CATEGORY;
			}

			$filetags_updated = $saved;

			// check for new tags to be added as filetags in pconfig
			$new_tags = [];
			foreach (self::fileToArray($file_new, $type) as $tag) {
				if (!stristr($saved, $lbracket . self::encode($tag) . $rbracket)) {
					$new_tags[] = $tag;
				}
			}

			$filetags_updated .= self::arrayToFile($new_tags, $type);

			// check for deleted tags to be removed from filetags in pconfig
			$deleted_tags = [];
			foreach (self::fileToArray($file_old, $type) as $tag) {
				if (!stristr($file_new, $lbracket . self::encode($tag) . $rbracket)) {
					$deleted_tags[] = $tag;
				}
			}

			foreach ($deleted_tags as $key => $tag) {
				if (DBA::exists('category-view', ['name' => $tag, 'type' => $termtype, 'uid' => $uid])) {
					unset($deleted_tags[$key]);
				} else {
					$filetags_updated = str_replace($lbracket . self::encode($tag) . $rbracket, '', $filetags_updated);
				}
			}

			if ($saved != $filetags_updated) {
				DI::pConfig()->set($uid, 'system', 'filetags', $filetags_updated);
			}

			return true;
		} elseif (strlen($file_new)) {
			DI::pConfig()->set($uid, 'system', 'filetags', $file_new);
		}

		return true;
	}

	/**
	 * Add tag to file
	 *
	 * @param int    $uid     Unique identity.
	 * @param int    $item_id Item identity.
	 * @param string $file    File tag.
	 *
	 * @return boolean      A value indicating success or failure.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function saveFile($uid, $item_id, $file)
	{
		if (!intval($uid)) {
			return false;
		}

		$item = Post::selectFirst(['file'], ['id' => $item_id, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			if (!stristr($item['file'], '[' . self::encode($file) . ']')) {
				$fields = ['file' => $item['file'] . '[' . self::encode($file) . ']'];
				Item::update($fields, ['id' => $item_id]);
			}

			$saved = DI::pConfig()->get($uid, 'system', 'filetags');

			if (!strlen($saved) || !stristr($saved, '[' . self::encode($file) . ']')) {
				DI::pConfig()->set($uid, 'system', 'filetags', $saved . '[' . self::encode($file) . ']');
			}
		}

		return true;
	}

	/**
	 * Remove tag from file
	 *
	 * @param int     $uid     Unique identity.
	 * @param int     $item_id Item identity.
	 * @param string  $file    File tag.
	 * @param boolean $cat     Optional value indicating the term type (i.e. Category or File)
	 *
	 * @return boolean      A value indicating success or failure.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function unsaveFile($uid, $item_id, $file, $cat = false)
	{
		if (!intval($uid)) {
			return false;
		}

		if ($cat == true) {
			$pattern = '<' . self::encode($file) . '>';
			$termtype = Category::CATEGORY;
		} else {
			$pattern = '[' . self::encode($file) . ']';
			$termtype = Category::FILE;
		}

		$item = Post::selectFirst(['file'], ['id' => $item_id, 'uid' => $uid]);

		if (!DBA::isResult($item)) {
			return false;
		}

		$fields = ['file' => str_replace($pattern, '', $item['file'])];

		Item::update($fields, ['id' => $item_id]);

		if (!DBA::exists('category-view', ['name' => $file, 'type' => $termtype, 'uid' => $uid])) {
			$saved = DI::pConfig()->get($uid, 'system', 'filetags');
			DI::pConfig()->set($uid, 'system', 'filetags', str_replace($pattern, '', $saved));
		}

		return true;
	}
}
