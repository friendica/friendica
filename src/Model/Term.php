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

use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

/**
 * Class Term
 *
 * This Model class handles term table interactions.
 * This tables stores relevant terms related to posts, photos and searches, like hashtags, mentions and
 * user-applied categories.
 */
class Term
{
    const UNKNOWN           = 0;
    const HASHTAG           = 1;
    const MENTION           = 2;
    const CATEGORY          = 3;
    const PCATEGORY         = 4;
    const FILE              = 5;
    const SAVEDSEARCH       = 6;
    const CONVERSATION      = 7;
	/**
	 * An implicit mention is a mention in a comment body that is redundant with the threading information.
	 */
    const IMPLICIT_MENTION  = 8;
	/**
	 * An exclusive mention transfers the ownership of the post to the target account, usually a forum.
	 */
    const EXCLUSIVE_MENTION = 9;

    const TAG_CHARACTER = [
    	self::HASHTAG           => '#',
    	self::MENTION           => '@',
    	self::IMPLICIT_MENTION  => '%',
    	self::EXCLUSIVE_MENTION => '!',
    ];

    const OBJECT_TYPE_POST  = 1;
    const OBJECT_TYPE_PHOTO = 2;

	/**
	 * Returns a list of the most frequent global hashtags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @return array
	 * @throws \Exception
	 */
	public static function getGlobalTrendingHashtags(int $period, $limit = 10)
	{
		$tags = DI::cache()->get('global_trending_tags');

		if (!$tags) {
			$tagsStmt = DBA::p("SELECT t.`term`, COUNT(*) AS `score`
				FROM `term` t
				 JOIN `item` i ON i.`id` = t.`oid` AND i.`uid` = t.`uid`
				 JOIN `thread` ON `thread`.`iid` = i.`id`
				WHERE `thread`.`visible`
				  AND NOT `thread`.`deleted`
				  AND NOT `thread`.`moderated`
				  AND NOT `thread`.`private`
				  AND t.`uid` = 0
				  AND t.`otype` = ?
				  AND t.`type` = ?
				  AND t.`term` != ''
				  AND i.`received` > DATE_SUB(NOW(), INTERVAL ? HOUR)
				GROUP BY `term`
				ORDER BY `score` DESC
				LIMIT ?",
				Term::OBJECT_TYPE_POST,
				Term::HASHTAG,
				$period,
				$limit
			);

			if (DBA::isResult($tagsStmt)) {
				$tags = DBA::toArray($tagsStmt);
				DI::cache()->set('global_trending_tags', $tags, Duration::HOUR);
			}
		}

		return $tags ?: [];
	}

	/**
	 * Returns a list of the most frequent local hashtags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @return array
	 * @throws \Exception
	 */
	public static function getLocalTrendingHashtags(int $period, $limit = 10)
	{
		$tags = DI::cache()->get('local_trending_tags');

		if (!$tags) {
			$tagsStmt = DBA::p("SELECT t.`term`, COUNT(*) AS `score`
				FROM `term` t
				JOIN `item` i ON i.`id` = t.`oid` AND i.`uid` = t.`uid`
				JOIN `thread` ON `thread`.`iid` = i.`id`
				JOIN `user` ON `user`.`uid` = `thread`.`uid` AND NOT `user`.`hidewall`
				WHERE `thread`.`visible`
				  AND NOT `thread`.`deleted`
				  AND NOT `thread`.`moderated`
				  AND NOT `thread`.`private`
				  AND `thread`.`wall`
				  AND `thread`.`origin`
				  AND t.`otype` = ?
				  AND t.`type` = ?
				  AND t.`term` != ''
				  AND i.`received` > DATE_SUB(NOW(), INTERVAL ? HOUR)
				GROUP BY `term`
				ORDER BY `score` DESC
				LIMIT ?",
				Term::OBJECT_TYPE_POST,
				Term::HASHTAG,
				$period,
				$limit
			);

			if (DBA::isResult($tagsStmt)) {
				$tags = DBA::toArray($tagsStmt);
				DI::cache()->set('local_trending_tags', $tags, Duration::HOUR);
			}
		}

		return $tags ?: [];
	}

	/**
	 * Generates the legacy item.tag field comma-separated BBCode string from an item ID.
	 * Includes only hashtags, implicit and explicit mentions.
	 *
	 * @param int $item_id
	 * @return string
	 * @throws \Exception
	 */
	public static function tagTextFromItemId($item_id)
	{
		$tag_list = [];
		$tags = self::tagArrayFromItemId($item_id, [self::HASHTAG, self::MENTION, self::IMPLICIT_MENTION]);
		foreach ($tags as $tag) {
			$tag_list[] = self::TAG_CHARACTER[$tag['type']] . '[url=' . $tag['url'] . ']' . $tag['term'] . '[/url]';
		}

		return implode(',', $tag_list);
	}

	/**
	 * Retrieves the terms from the provided type(s) associated with the provided item ID.
	 *
	 * @param int       $item_id
	 * @param int|array $type
	 * @return array
	 * @throws \Exception
	 */
	public static function tagArrayFromItemId($item_id, $type = [self::HASHTAG, self::MENTION])
	{
		$condition = ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => $type];
		$tags = DBA::select('term', ['type', 'term', 'url'], $condition);
		if (!DBA::isResult($tags)) {
			return [];
		}

		return DBA::toArray($tags);
	}

	/**
	 * Generates the legacy item.file field string from an item ID.
	 * Includes only file and category terms.
	 *
	 * @param int $item_id
	 * @return string
	 * @throws \Exception
	 */
	public static function fileTextFromItemId($item_id)
	{
		$file_text = '';
		$tags = self::tagArrayFromItemId($item_id, [self::FILE, self::CATEGORY]);
		foreach ($tags as $tag) {
			if ($tag['type'] == self::CATEGORY) {
				$file_text .= '<' . $tag['term'] . '>';
			} else {
				$file_text .= '[' . $tag['term'] . ']';
			}
		}

		return $file_text;
	}

	/**
	 * Inserts new terms for the provided item ID based on the legacy item.tag field BBCode content.
	 * Deletes all previous tag terms for the same item ID.
	 * Sets both the item.mention and thread.mentions field flags if a mention concerning the item UID is found.
	 *
	 * @param int    $item_id
	 * @param string $tag_str
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function insertFromTagFieldByItemId($item_id, $tag_str)
	{
		$profile_base = DI::baseUrl();
		$profile_data = parse_url($profile_base);
		$profile_path = $profile_data['path'] ?? '';
		$profile_base_friendica = $profile_data['host'] . $profile_path . '/profile/';
		$profile_base_diaspora = $profile_data['host'] . $profile_path . '/u/';

		$fields = ['guid', 'uid', 'id', 'edited', 'deleted', 'created', 'received', 'title', 'body', 'parent'];
		$item = Item::selectFirst($fields, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			return;
		}

		$item['tag'] = $tag_str;

		// Clean up all tags
		self::deleteByItemId($item_id);

		if ($item['deleted']) {
			return;
		}

		$taglist = explode(',', $item['tag']);

		$tags_string = '';
		foreach ($taglist as $tag) {
			if (Strings::startsWith($tag, self::TAG_CHARACTER)) {
				$tags_string .= ' ' . trim($tag);
			} else {
				$tags_string .= ' #' . trim($tag);
			}
		}

		$data = ' ' . $item['title'] . ' ' . $item['body'] . ' ' . $tags_string . ' ';

		// ignore anything in a code block
		$data = preg_replace('/\[code\](.*?)\[\/code\]/sm', '', $data);

		$tags = [];

		$pattern = '/\W\#([^\[].*?)[\s\'".,:;\?!\[\]\/]/ism';
		if (preg_match_all($pattern, $data, $matches)) {
			foreach ($matches[1] as $match) {
				$tags['#' . $match] = '';
			}
		}

		$pattern = '/\W([\#@!%])\[url\=(.*?)\](.*?)\[\/url\]/ism';
		if (preg_match_all($pattern, $data, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {

				if (in_array($match[1], [
					self::TAG_CHARACTER[self::MENTION],
					self::TAG_CHARACTER[self::IMPLICIT_MENTION],
					self::TAG_CHARACTER[self::EXCLUSIVE_MENTION]
				])) {
					$contact = Contact::getDetailsByURL($match[2], 0);
					if (!empty($contact['addr'])) {
						$match[3] = $contact['addr'];
					}

					if (!empty($contact['url'])) {
						$match[2] = $contact['url'];
					}
				}

				$tags[$match[2]] = $match[1] . trim($match[3], ',.:;[]/\"?!');
			}
		}

		foreach ($tags as $link => $tag) {
			if (self::isType($tag, self::HASHTAG)) {
				// try to ignore #039 or #1 or anything like that
				if (ctype_digit(substr(trim($tag), 1))) {
					continue;
				}

				// try to ignore html hex escapes, e.g. #x2317
				if ((substr(trim($tag), 1, 1) == 'x' || substr(trim($tag), 1, 1) == 'X') && ctype_digit(substr(trim($tag), 2))) {
					continue;
				}

				$type = self::HASHTAG;
				$term = substr($tag, 1);
				$link = '';
			} elseif (self::isType($tag, self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION)) {
				if (self::isType($tag, self::MENTION, self::EXCLUSIVE_MENTION)) {
					$type = self::MENTION;
				} else {
					$type = self::IMPLICIT_MENTION;
				}

				$contact = Contact::getDetailsByURL($link, 0);
				if (!empty($contact['name'])) {
					$term = $contact['name'];
				} else {
					$term = substr($tag, 1);
				}
			} else { // This shouldn't happen
				$type = self::HASHTAG;
				$term = $tag;
				$link = '';

				Logger::notice('Unknown term type', ['tag' => $tag]);
			}

			if (DBA::exists('term', ['uid' => $item['uid'], 'otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'term' => $term, 'type' => $type])) {
				continue;
			}

			if ($item['uid'] == 0) {
				$global = true;
				DBA::update('term', ['global' => true], ['otype' => self::OBJECT_TYPE_POST, 'guid' => $item['guid']]);
			} else {
				$global = DBA::exists('term', ['uid' => 0, 'otype' => self::OBJECT_TYPE_POST, 'guid' => $item['guid']]);
			}

			DBA::insert('term', [
				'uid'      => $item['uid'],
				'oid'      => $item_id,
				'otype'    => self::OBJECT_TYPE_POST,
				'type'     => $type,
				'term'     => $term,
				'url'      => $link,
				'guid'     => $item['guid'],
				'created'  => $item['created'],
				'received' => $item['received'],
				'global'   => $global
			]);

			// Search for mentions
			if (self::isType($tag, self::MENTION, self::EXCLUSIVE_MENTION)
				&& (
					strpos($link, $profile_base_friendica) !== false
					|| strpos($link, $profile_base_diaspora) !== false
				)
			) {
				$users_stmt = DBA::p("SELECT `uid` FROM `contact` WHERE self AND (`url` = ? OR `nurl` = ?)", $link, $link);
				$users = DBA::toArray($users_stmt);
				foreach ($users AS $user) {
					if ($user['uid'] == $item['uid']) {
						/// @todo This function is called from Item::update - so we mustn't call that function here
						DBA::update('item', ['mention' => true], ['id' => $item_id]);
						DBA::update('thread', ['mention' => true], ['iid' => $item['parent']]);
					}
				}
			}
		}
	}

	/**
	 * Inserts new terms for the provided item ID based on the legacy item.file field BBCode content.
	 * Deletes all previous file terms for the same item ID.
	 *
	 * @param integer $item_id item id
	 * @param         $files
	 * @return void
	 * @throws \Exception
	 */
	public static function insertFromFileFieldByItemId($item_id, $files)
	{
		$message = Item::selectFirst(['uid', 'deleted'], ['id' => $item_id]);
		if (!DBA::isResult($message)) {
			return;
		}

		// Clean up all tags
		DBA::delete('term', ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => [self::FILE, self::CATEGORY]]);

		if ($message["deleted"]) {
			return;
		}

		$message['file'] = $files;

		if (preg_match_all("/\[(.*?)\]/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				DBA::insert('term', [
					'uid' => $message["uid"],
					'oid' => $item_id,
					'otype' => self::OBJECT_TYPE_POST,
					'type' => self::FILE,
					'term' => $file
				]);
			}
		}

		if (preg_match_all("/\<(.*?)\>/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				DBA::insert('term', [
					'uid' => $message["uid"],
					'oid' => $item_id,
					'otype' => self::OBJECT_TYPE_POST,
					'type' => self::CATEGORY,
					'term' => $file
				]);
			}
		}
	}

	/**
	 * Sorts an item's tags into mentions, hashtags and other tags. Generate personalized URLs by user and modify the
	 * provided item's body with them.
	 *
	 * @param array $item
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function populateTagsFromItem(&$item)
	{
		$return = [
			'tags' => [],
			'hashtags' => [],
			'mentions' => [],
			'implicit_mentions' => [],
		];

		$searchpath = DI::baseUrl() . "/search?tag=";

		$taglist = DBA::select(
			'term',
			['type', 'term', 'url'],
			['otype' => self::OBJECT_TYPE_POST, 'oid' => $item['id'], 'type' => [self::HASHTAG, self::MENTION, self::IMPLICIT_MENTION]],
			['order' => ['tid']]
		);
		while ($tag = DBA::fetch($taglist)) {
			if ($tag['url'] == '') {
				$tag['url'] = $searchpath . rawurlencode($tag['term']);
			}

			$orig_tag = $tag['url'];

			$prefix = self::TAG_CHARACTER[$tag['type']];
			switch($tag['type']) {
				case self::HASHTAG:
					if ($orig_tag != $tag['url']) {
						$item['body'] = str_replace($orig_tag, $tag['url'], $item['body']);
					}

					$return['hashtags'][] = $prefix . '<a href="' . $tag['url'] . '">' . htmlspecialchars($tag['term']) . '</a>';
					$return['tags'][] = $prefix . '<a href="' . $tag['url'] . '">' . htmlspecialchars($tag['term']) . '</a>';
					break;
				case self::MENTION:
					$tag['url'] = Contact::magicLink($tag['url']);
					$return['mentions'][] = $prefix . '<a href="' . $tag['url'] . '">' . htmlspecialchars($tag['term']) . '</a>';
					$return['tags'][] = $prefix . '<a href="' . $tag['url'] . '">' . htmlspecialchars($tag['term']) . '</a>';
					break;
				case self::IMPLICIT_MENTION:
					$return['implicit_mentions'][] = $prefix . $tag['term'];
					break;
			}
		}
		DBA::close($taglist);

		return $return;
	}

	/**
	 * Delete tags of the specific type(s) from an item
	 *
	 * @param int       $item_id
	 * @param int|array $type
	 * @throws \Exception
	 */
	public static function deleteByItemId($item_id, $type = [self::HASHTAG, self::MENTION, self::IMPLICIT_MENTION])
	{
		if (empty($item_id)) {
			return;
		}

		// Clean up all tags
		DBA::delete('term', ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => $type]);
	}

	/**
	 * Check if the provided tag is of one of the provided term types.
	 *
	 * @param string $tag
	 * @param int    ...$types
	 * @return bool
	 */
	public static function isType($tag, ...$types)
	{
		$tag_chars = [];
		foreach ($types as $type) {
			if (array_key_exists($type, self::TAG_CHARACTER)) {
				$tag_chars[] = self::TAG_CHARACTER[$type];
			}
		}

		return Strings::startsWith($tag, $tag_chars);
	}
}
