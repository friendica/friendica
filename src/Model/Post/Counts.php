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

namespace Friendica\Model\Post;

use Friendica\Content\Smilies;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;

class Counts
{
	/**
	 * Insert or update a post-counts entry
	 *
	 * @param int $uri_id
	 */
	public static function update(int $uri_id, int $parent_uri_id, int $vid, string $verb, string $body = null)
	{
		if (!in_array($verb, [Activity::POST, Activity::LIKE, Activity::DISLIKE,
			Activity::ATTEND, Activity::ATTENDMAYBE, Activity::ATTENDNO,
			Activity::EMOJIREACT, Activity::ANNOUNCE, Activity::VIEW, Activity::READ])) {
			return true;
		}
	
		$condition = ['thr-parent-id' => $uri_id, 'vid' => $vid];

		if ($body == $verb) {
			$condition['body'] = null;
			$body              = '';
		} elseif ($verb == Activity::POST) {
			$condition['gravity'] = Item::GRAVITY_COMMENT;
			$body                 = '';
		} elseif (($verb != Activity::POST) && (mb_strlen($body) == 1) && Smilies::isEmojiPost($body)) {
			$condition['body'] = $body;
		} else {
			$body = '';
		}

		$fields = [
			'uri-id'        => $uri_id,
			'vid'           => $vid,
			'reaction'      => $body,
			'parent-uri-id' => $parent_uri_id,
			'count'         => Post::countPosts($condition),
		];

		if ($fields['count'] == 0) {
			return true;
		}

		return DBA::insert('post-counts', $fields, Database::INSERT_UPDATE);
	}

	public static function updateForPost(int $uri_id, int $parent_uri_id)
	{
		self::update($uri_id, $parent_uri_id, Verb::getID(Activity::POST), Activity::POST);

		$activities = DBA::p("SELECT `parent-uri-id`, `vid`, `verb`, `body` FROM `post-view` WHERE `thr-parent-id` = ? AND `gravity` = ? GROUP BY `parent-uri-id`, `vid`, `verb`, `body`", $uri_id, Item::GRAVITY_ACTIVITY);
		while ($activity = DBA::fetch($activities)) {
			self::update($uri_id, $activity['parent-uri-id'], $activity['vid'], $activity['verb'], $activity['body']);
		}
		DBA::close($activities);
	}

	/**
	 * Retrieves counts of the given condition
	 *
	 * @param array $condition
	 *
	 * @return array
	 */
	public static function get(array $condition): array
	{
		$counts = [];

		$activity_emoji = [
			Verb::getID(Activity::LIKE)        => '👍',
			Verb::getID(Activity::DISLIKE)     => '👎',
			Verb::getID(Activity::ATTEND)      => '✔️',
			Verb::getID(Activity::ATTENDMAYBE) => '❓',
			Verb::getID(Activity::ATTENDNO)    => '❌',
			Verb::getID(Activity::ANNOUNCE)    => '♻',
			Verb::getID(Activity::VIEW)        => '📺',
			Verb::getID(Activity::READ)        => '📖',
		];

		$vids = array_merge(array_keys($activity_emoji), [Verb::getID(Activity::EMOJIREACT), Verb::getID(Activity::POST)]);

		$condition  = DBA::mergeConditions($condition, ['vid' => $vids]);
		$countquery = DBA::select('post-counts', [], $condition);
		while ($count = DBA::fetch($countquery)) {
			if (!empty($count['reaction'])) {
				$count['vid'] = Verb::getID(Activity::EMOJIREACT);
			} elseif (!empty($activity_emoji[$count['vid']])) {
				$count['reaction'] = $activity_emoji[$count['vid']];
			}
			$counts[] = $count;
		}
		DBA::close($counts);
		return $counts;
	}
}
