<?php
/**
 * @file src/Model/Term
 */
namespace Friendica\Model;

use Friendica\Core\System;
use Friendica\Database\DBA;

require_once 'boot.php';
require_once 'include/conversation.php';
require_once 'include/dba.php';

class Term
{
	public static function tagTextFromItemId($itemid)
	{
		$tag_text = '';
		$condition = ['otype' => TERM_OBJ_POST, 'oid' => $itemid, 'type' => [TERM_HASHTAG, TERM_MENTION]];
		$tags = DBA::select('term', [], $condition);
		while ($tag = DBA::fetch($tags)) {
			if ($tag_text != '') {
				$tag_text .= ',';
			}

			if ($tag['type'] == 1) {
				$tag_text .= '#';
			} else {
				$tag_text .= '@';
			}
			$tag_text .= '[url=' . $tag['url'] . ']' . $tag['term'] . '[/url]';
		}
		return $tag_text;
	}

	public static function fileTextFromItemId($itemid)
	{
		$file_text = '';
		$condition = ['otype' => TERM_OBJ_POST, 'oid' => $itemid, 'type' => [TERM_FILE, TERM_CATEGORY]];
		$tags = DBA::select('term', [], $condition);
		while ($tag = DBA::fetch($tags)) {
			if ($tag['type'] == TERM_CATEGORY) {
				$file_text .= '<' . $tag['term'] . '>';
			} else {
				$file_text .= '[' . $tag['term'] . ']';
			}
		}
		return $file_text;
	}

	public static function insertFromTagFieldByItemId($itemid, $tags)
	{
		$profile_base = System::baseUrl();
		$profile_data = parse_url($profile_base);
		$profile_path = defaults($profile_data, 'path', '');
		$profile_base_friendica = $profile_data['host'] . $profile_path . '/profile/';
		$profile_base_diaspora = $profile_data['host'] . $profile_path . '/u/';

		$fields = ['guid', 'uid', 'id', 'edited', 'deleted', 'created', 'received', 'title', 'body', 'parent'];
		$message = Item::selectFirst($fields, ['id' => $itemid]);
		if (!DBA::isResult($message)) {
			return;
		}

		$message['tag'] = $tags;

		// Clean up all tags
		DBA::delete('term', ['otype' => TERM_OBJ_POST, 'oid' => $itemid, 'type' => [TERM_HASHTAG, TERM_MENTION]]);

		if ($message['deleted']) {
			return;
		}

		$taglist = explode(',', $message['tag']);

		$tags_string = '';
		foreach ($taglist as $tag) {
			if ((substr(trim($tag), 0, 1) == '#') || (substr(trim($tag), 0, 1) == '@')) {
				$tags_string .= ' ' . trim($tag);
			} else {
				$tags_string .= ' #' . trim($tag);
			}
		}

		$data = ' ' . $message['title'] . ' ' . $message['body'] . ' ' . $tags_string . ' ';

		// ignore anything in a code block
		$data = preg_replace('/\[code\](.*?)\[\/code\]/sm', '', $data);

		$tags = [];

		$pattern = '/\W\#([^\[].*?)[\s\'".,:;\?!\[\]\/]/ism';
		if (preg_match_all($pattern, $data, $matches)) {
			foreach ($matches[1] as $match) {
				$tags['#' . $match] = '';
			}
		}

		$pattern = '/\W([\#@])\[url\=(.*?)\](.*?)\[\/url\]/ism';
		if (preg_match_all($pattern, $data, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$tags[$match[1] . trim($match[3], ',.:;[]/\"?!')] = $match[2];
			}
		}

		foreach ($tags as $tag => $link) {
			if (substr(trim($tag), 0, 1) == '#') {
				// try to ignore #039 or #1 or anything like that
				if (ctype_digit(substr(trim($tag), 1))) {
					continue;
				}

				// try to ignore html hex escapes, e.g. #x2317
				if ((substr(trim($tag), 1, 1) == 'x' || substr(trim($tag), 1, 1) == 'X') && ctype_digit(substr(trim($tag), 2))) {
					continue;
				}

				$type = TERM_HASHTAG;
				$term = substr($tag, 1);
			} elseif (substr(trim($tag), 0, 1) == '@') {
				$type = TERM_MENTION;
				$term = substr($tag, 1);
			} else { // This shouldn't happen
				$type = TERM_HASHTAG;
				$term = $tag;
			}

			if ($message['uid'] == 0) {
				$global = true;
				DBA::update('term', ['global' => true], ['otype' => TERM_OBJ_POST, 'guid' => $message['guid']]);
			} else {
				$global = DBA::exists('term', ['uid' => 0, 'otype' => TERM_OBJ_POST, 'guid' => $message['guid']]);
			}

			DBA::insert('term', [
				'uid'      => $message['uid'],
				'oid'      => $itemid,
				'otype'    => TERM_OBJ_POST,
				'type'     => $type,
				'term'     => $term,
				'url'      => $link,
				'guid'     => $message['guid'],
				'created'  => $message['created'],
				'received' => $message['received'],
				'global'   => $global
			]);

			// Search for mentions
			if ((substr($tag, 0, 1) == '@') && (strpos($link, $profile_base_friendica) || strpos($link, $profile_base_diaspora))) {
				$users = q("SELECT `uid` FROM `contact` WHERE self AND (`url` = '%s' OR `nurl` = '%s')", $link, $link);
				foreach ($users AS $user) {
					if ($user['uid'] == $message['uid']) {
						/// @todo This function is called frim Item::update - so we mustn't call that function here
						DBA::update('item', ['mention' => true], ['id' => $itemid]);
						DBA::update('thread', ['mention' => true], ['iid' => $message['parent']]);
					}
				}
			}
		}
	}

	/**
	 * @param integer $itemid item id
	 * @return void
	 */
	public static function insertFromFileFieldByItemId($itemid, $files)
	{
		$message = Item::selectFirst(['uid', 'deleted'], ['id' => $itemid]);
		if (!DBA::isResult($message)) {
			return;
		}

		// Clean up all tags
		DBA::delete('term', ['otype' => TERM_OBJ_POST, 'oid' => $itemid, 'type' => [TERM_FILE, TERM_CATEGORY]]);

		if ($message["deleted"]) {
			return;
		}

		$message['file'] = $files;

		if (preg_match_all("/\[(.*?)\]/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				DBA::insert('term', [
					'uid' => $message["uid"],
					'oid' => $itemid,
					'otype' => TERM_OBJ_POST,
					'type' => TERM_FILE,
					'term' => $file
				]);
			}
		}

		if (preg_match_all("/\<(.*?)\>/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				DBA::insert('term', [
					'uid' => $message["uid"],
					'oid' => $itemid,
					'otype' => TERM_OBJ_POST,
					'type' => TERM_CATEGORY,
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
	 */
	public static function populateTagsFromItem(&$item)
	{
		$return = [
			'tags' => [],
			'hashtags' => [],
			'mentions' => [],
		];

		$searchpath = System::baseUrl() . "/search?tag=";

		$taglist = DBA::select(
			'term',
			['type', 'term', 'url'],
			["`otype` = ? AND `oid` = ? AND `type` IN (?, ?)", TERM_OBJ_POST, $item['id'], TERM_HASHTAG, TERM_MENTION],
			['order' => ['tid']]
		);

		while ($tag = DBA::fetch($taglist)) {
			if ($tag["url"] == "") {
				$tag["url"] = $searchpath . $tag["term"];
			}

			$orig_tag = $tag["url"];

			$author = ['uid' => 0, 'id' => $item['author-id'],
				'network' => $item['author-network'], 'url' => $item['author-link']];
			$tag["url"] = Contact::magicLinkByContact($author, $tag['url']);

			if ($tag["type"] == TERM_HASHTAG) {
				if ($orig_tag != $tag["url"]) {
					$item['body'] = str_replace($orig_tag, $tag["url"], $item['body']);
				}

				$return['hashtags'][] = "#<a href=\"" . $tag["url"] . "\" target=\"_blank\">" . $tag["term"] . "</a>";
				$prefix = "#";
			} elseif ($tag["type"] == TERM_MENTION) {
				$return['mentions'][] = "@<a href=\"" . $tag["url"] . "\" target=\"_blank\">" . $tag["term"] . "</a>";
				$prefix = "@";
			}

			$return['tags'][] = $prefix . "<a href=\"" . $tag["url"] . "\" target=\"_blank\">" . $tag["term"] . "</a>";
		}
		DBA::close($taglist);

		return $return;
	}
}
