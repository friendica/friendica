<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;

class Content
{
	/**
	 * Insert a new post-content entry
	 *
	 * @return bool   success
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-content', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;

		return DBA::insert('post-content', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Update a post content entry
	 *
	 * @param integer $uri_id
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, array $data = [], bool $insert_if_missing = false)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-content', $data);

		// Remove the key fields
		unset($fields['uri-id']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-content', $fields, ['uri-id' => $uri_id], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-content table
	 *
	 * @param array        $conditions Field condition(s)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete(array $conditions)
	{
		return DBA::delete('post-content', $conditions);
	}

	/**
	 * Check if a post-content entry exists for the given URI ID
	 *
	 * @param integer $uri_id
	 * @return boolean exists?
	 */
	public static function exists(int $uri_id): bool
	{
		return DBA::exists('post-content', ['uri-id' => $uri_id]);
	}

	/**
	 * Search posts for given content
	 *
	 * @param string $search
	 * @param integer $uid
	 * @param integer $start
	 * @param integer $limit
	 * @param integer $last_uriid
	 * @return array
	 */
	public static function getURIIdListBySearch(string $search, int $uid = 0, int $start = 0, int $limit = 100, int $last_uriid = 0)
	{
		$search = Post\Engagement::escapeKeywords($search);
		if ($uid != 0) {
			$condition = ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE) AND (NOT `restricted` OR `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `uid` = ?))", $search, $uid];
		} else {
			$condition = ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE) AND NOT `restricted`", $search];
		}

		if (!empty($last_uriid)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $last_uriid]);
		}

		$params = [
			'order' => ['uri-id' => true],
			'limit' => [$start, $limit]
		];

		$tags = DBA::select(SearchIndex::getSearchTable(), ['uri-id'], $condition, $params);

		$uriids = [];
		while ($tag = DBA::fetch($tags)) {
			$uriids[] = $tag['uri-id'];
		}
		DBA::close($tags);

		return $uriids;
	}

	public static function countBySearch(string $search, int $uid = 0)
	{
		$search = Post\Engagement::escapeKeywords($search);
		if ($uid != 0) {
			$condition = ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE) AND (NOT `restricted` OR `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `uid` = ?))", $search, $uid];
		} else {
			$condition = ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE) AND NOT `restricted`", $search];
		}
		return DBA::count(SearchIndex::getSearchTable(), $condition);
	}
}
