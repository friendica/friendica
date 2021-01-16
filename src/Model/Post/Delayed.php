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

namespace Friendica\Model\Post;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Util\DateTimeFormat;

class Delayed
{
	/**
	 * Insert a new delayed post
	 *
	 * @param string  $uri
	 * @param array   $item
	 * @param integer $notify
	 * @param bool    $unprepared
	 * @param string  $delayed
	 * @param array   $taglist
	 * @param array   $attachments
	 * @return bool insert success
	 */ 
	public static function add(string $uri, array $item, int $notify = 0, bool $unprepared = false, string $delayed = '', array $taglist = [], array $attachments = [])
	{
		if (empty($item['uid']) || self::exists($uri, $item['uid'])) {
			Logger::notice('No uid or already found');
			return false;
		}

		if (empty($delayed)) {
			$min_posting = DI::config()->get('system', 'minimum_posting_interval', 0);

			$last_publish = DI::pConfig()->get($item['uid'], 'system', 'last_publish', 0, true);
			$next_publish = max($last_publish + (60 * $min_posting), time());
			$delayed = date(DateTimeFormat::MYSQL, $next_publish);
		} else {
			$next_publish = strtotime($delayed);
		}

		Logger::notice('Adding post for delayed publishing', ['uid' => $item['uid'], 'delayed' => $delayed, 'uri' => $uri]);

		if (!Worker::add(['priority' => PRIORITY_HIGH, 'delayed' => $delayed], 'DelayedPublish', $item, $notify, $taglist, $attachments, $unprepared, $uri)) {
			return false;
		}

		DI::pConfig()->set($item['uid'], 'system', 'last_publish', $next_publish);

		return DBA::insert('delayed-post', ['uri' => $uri, 'uid' => $item['uid'], 'delayed' => $delayed], Database::INSERT_IGNORE);
	}

	/**
	 * Delete a delayed post
	 *
	 * @param string $uri
	 * @param int    $uid
	 *
	 * @return bool delete success
	 */
	private static function delete(string $uri, int $uid)
	{
		return DBA::delete('delayed-post', ['uri' => $uri, 'uid' => $uid]);
	}

	/**
	 * Check if an entry exists
	 *
	 * @param string $uri
	 * @param int    $uid
	 *
	 * @return bool "true" if an entry with that URI exists
	 */
	public static function exists(string $uri, int $uid)
	{
		return DBA::exists('delayed-post', ['uri' => $uri, 'uid' => $uid]);
	}

	/**
	 * Publish a delayed post
	 *
	 * @param array $item
	 * @param integer $notify
	 * @param array $taglist
	 * @param array $attachments
	 * @param bool  $unprepared
	 * @param string $uri
	 * @return bool
	 */
	public static function publish(array $item, int $notify = 0, array $taglist = [], array $attachments = [], bool $unprepared = false, string $uri = '')
	{
		if ($unprepared) {
			$_SESSION['authenticated'] = true;
			$_SESSION['uid'] = $item['uid'];

			$_REQUEST = $item;
			$_REQUEST['api_source'] = true;
			$_REQUEST['profile_uid'] = $item['uid'];
			$_REQUEST['title'] = $item['title'] ?? '';

			if (!empty($item['app'])) {
				$_REQUEST['source'] = $item['app'];
			}

			require_once 'mod/item.php';
			$id = item_post(DI::app());

			if (empty($uri) && !empty($item['extid'])) {
				$uri = $item['extid'];
			}

			Logger::notice('Unprepared post stored', ['id' => $id, 'uid' => $item['uid'], 'uri' => $uri]);
			if (self::exists($uri, $item['uid'])) {
				self::delete($uri, $item['uid']);
			}
	
			return $id;
		}
		$id = Item::insert($item, $notify);

		Logger::notice('Post stored', ['id' => $id, 'uid' => $item['uid'], 'cid' => $item['contact-id']]);

		if (empty($uri) && !empty($item['uri'])) {
			$uri = $item['uri'];
		}

		if (!empty($uri) && self::exists($uri, $item['uid'])) {
			self::delete($uri, $item['uid']);
		}

		if (!empty($id) && (!empty($taglist) || !empty($attachments))) {
			$feeditem = Post::selectFirst(['uri-id'], ['id' => $id]);

			foreach ($taglist as $tag) {
				Tag::store($feeditem['uri-id'], Tag::HASHTAG, $tag);
			}

			foreach ($attachments as $attachment) {
				$attachment['uri-id'] = $feeditem['uri-id'];
				Media::insert($attachment);
			}
		}

		return $id;
	}
}
