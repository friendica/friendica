<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
use Friendica\Util\EmojiReaction;

class Counts
{
	/**
	 * Maximum number of reaction activities that are evaluated per post
	 */
	private const MAX_REACTION_ACTIVITIES = 500;

	/**
	 * Insert or update a post-counts entry
	 *
	 * @param int $uri_id
	 */
	public static function update(int $uri_id, int $parent_uri_id, int $vid, string $verb, ?string $body = null)
	{
		if (!in_array($verb, [Activity::POST, Activity::LIKE, Activity::DISLIKE,
			Activity::ATTEND, Activity::ATTENDMAYBE, Activity::ATTENDNO,
			Activity::EMOJIREACT, Activity::ANNOUNCE, Activity::VIEW, Activity::READ])) {
			return true;
		}

		$condition = ['thr-parent-id' => $uri_id, 'vid' => $vid, 'deleted' => false, 'private' => [Item::PUBLIC, Item::UNLISTED]];

		if ($body == $verb) {
			$condition['body'] = null;
			$body              = '';
		} elseif ($verb == Activity::POST) {
			$condition['gravity'] = Item::GRAVITY_COMMENT;
			$body                 = '';
		} elseif ($body && EmojiReaction::isReaction($body)) {
			$condition = DBA::mergeConditions($condition, ['BINARY `body` = ?', $body]);
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

		unset($condition['private']);
		if ($fields['count'] === 0 && Post::countPosts($condition) === 0) {
			DBA::delete('post-counts', ['uri-id' => $uri_id, 'vid' => $vid, 'reaction' => $body]);
			return true;
		}

		return DBA::insert('post-counts', $fields, Database::INSERT_UPDATE);
	}

	public static function updateForPost(int $uri_id, int $parent_uri_id)
	{
		self::update($uri_id, $parent_uri_id, Verb::getID(Activity::POST), Activity::POST);

		$activities = DBA::p("SELECT `parent-uri-id`, `vid`, `verb`, `body` FROM `post-view` WHERE `thr-parent-id` = ? AND `gravity` = ? AND `vid` IS NOT NULL GROUP BY `parent-uri-id`, `vid`, `verb`, `body`", $uri_id, Item::GRAVITY_ACTIVITY);
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

		$activity_verbs = [
			Verb::getID(Activity::LIKE),
			Verb::getID(Activity::DISLIKE),
			Verb::getID(Activity::ATTEND),
			Verb::getID(Activity::ATTENDMAYBE),
			Verb::getID(Activity::ATTENDNO),
			Verb::getID(Activity::ANNOUNCE),
			Verb::getID(Activity::VIEW),
			Verb::getID(Activity::READ),
		];

		$verbs = array_merge($activity_verbs, [Verb::getID(Activity::EMOJIREACT), Verb::getID(Activity::POST)]);

		$condition  = DBA::mergeConditions($condition, ['vid' => $verbs]);
		$countquery = DBA::select('post-counts-view', [], $condition);
		while ($count = DBA::fetch($countquery)) {
			if (!empty($count['reaction'])) {
				$count['verb'] = Activity::EMOJIREACT;
				$count['vid']  = Verb::getID($count['verb']);
			} elseif (in_array($count['vid'], $activity_verbs)) {
				$count['reaction'] = $count['verb'];
			}
			$counts[] = $count;
		}
		DBA::close($countquery);
		return $counts;
	}

	/**
	 * Retrieves the emoji reactions on a post that are visible to the given user
	 *
	 * @param int $uri_id
	 * @param int $uid
	 *
	 * @return array List of reactions with the fields "name", "count", "me" and "account_ids"
	 */
	public static function getReactions(int $uri_id, int $uid): array
	{
		$self      = Contact::getPublicIdByUserId($uid);
		$reactions = [];

		$condition  = ['thr-parent-id' => $uri_id, 'gravity' => Item::GRAVITY_ACTIVITY, 'verb' => [Activity::LIKE, Activity::EMOJIREACT], 'deleted' => false];
		$condition  = DBA::mergeConditions($condition, ["`body` NOT IN ('', ?)", Activity::LIKE]);
		$activities = Post::selectForUser($uid, ['body', 'author-id'], $condition, ['order' => ['received'], 'limit' => self::MAX_REACTION_ACTIVITIES]);
		while ($activity = Post::fetch($activities)) {
			$body = (string) $activity['body'];
			if (!EmojiReaction::isReaction($body)) {
				continue;
			}
			$reactions[$body][$activity['author-id']] = true;
		}
		DBA::close($activities);

		$result = [];
		foreach ($reactions as $name => $authors) {
			$account_ids = array_keys($authors);

			$result[] = [
				'name'        => $name,
				'count'       => count($account_ids),
				'me'          => in_array($self, $account_ids),
				'account_ids' => $account_ids,
			];
		}
		return $result;
	}
}
