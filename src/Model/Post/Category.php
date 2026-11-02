<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Model\Tag;

/**
 * Class Category
 *
 * This Model class handles category table interactions.
 * This tables stores user-applied categories related to posts.
 */
class Category
{
	public const UNKNOWN     = 0;
	public const CATEGORY    = 3;
	public const FILE        = 5;
	public const SUBCRIPTION = 10;

	/**
	 * Delete all categories and files from a given uri-id and user
	 *
	 * @param int $uri_id
	 * @param int $uid
	 * @return boolean success
	 * @throws \Exception
	 */
	public static function deleteByURIId(int $uri_id, int $uid)
	{
		return DBA::delete('post-category', ['uri-id' => $uri_id, 'uid' => $uid]);
	}

	/**
	 * Delete all categories and files from a given uri-id and user
	 *
	 * @param int $uri_id
	 * @param int $uid
	 * @return boolean success
	 * @throws \Exception
	 */
	public static function deleteFileByURIId(int $uri_id, int $uid, int $type, string $file)
	{
		$tagid = Tag::getID($file);
		if (empty($tagid)) {
			return false;
		}

		return DBA::delete('post-category', ['uri-id' => $uri_id, 'uid' => $uid, 'type' => $type, 'tid' => $tagid]);
	}
	/**
	 * Generates the legacy item.file field string from an item ID.
	 * Includes only file and category terms.
	 *
	 * @param int $uri_id
	 * @param int $uid
	 * @return string
	 * @throws \Exception
	 */
	public static function getTextByURIId(int $uri_id, int $uid)
	{
		$file_text = '';

		$tags = DBA::selectToArray('category-view', ['type', 'name'], ['uri-id' => $uri_id, 'uid' => $uid, 'type' => [Category::FILE, Category::CATEGORY]]);
		foreach ($tags as $tag) {
			if ($tag['type'] == self::CATEGORY) {
				$file_text .= '<' . $tag['name'] . '>';
			} else {
				$file_text .= '[' . $tag['name'] . ']';
			}
		}

		return $file_text;
	}

	/**
	 * Generates an array of files or categories of a given uri-id
	 *
	 * @param int $uid
	 * @param int $type
	 * @return array
	 * @throws \Exception
	 */
	public static function getArray(int $uid, int $type)
	{
		$tags = DBA::selectToArray(
			'category-view',
			['name'],
			['uid'      => $uid, 'type' => $type],
			['group_by' => ['name'], 'order' => ['name']],
		);
		if (empty($tags)) {
			return [];
		}

		return array_column($tags, 'name');
	}

	public static function existsForURIId(int $uri_id, int $uid)
	{
		return DBA::exists('post-category', ['uri-id' => $uri_id, 'uid' => $uid]);
	}

	/**
	 * Generates an array of files or categories of a given uri-id
	 *
	 * @param int $uri_id
	 * @param int $uid
	 * @param int $type
	 * @return array
	 * @throws \Exception
	 */
	public static function getArrayByURIId(int $uri_id, int $uid, int $type = self::CATEGORY)
	{
		$tags = DBA::selectToArray('category-view', ['type', 'name'], ['uri-id' => $uri_id, 'uid' => $uid, 'type' => $type]);
		if (empty($tags)) {
			return [];
		}

		return array_column($tags, 'name');
	}

	/**
	 * Generates a comma separated list of files or categories
	 *
	 * @param int $uri_id
	 * @param int $uid
	 * @param int $type
	 * @return string
	 * @throws \Exception
	 */
	public static function getCSVByURIId(int $uri_id, int $uid, int $type)
	{
		return implode(',', self::getArrayByURIId($uri_id, $uid, $type));
	}

	/**
	 * Inserts new terms for the provided item ID based on the legacy item.file field BBCode content.
	 * Deletes all previous file terms for the same item ID.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function storeTextByURIId(int $uri_id, int $uid, string $files)
	{
		$message = Post::selectFirst(['deleted'], ['uri-id' => $uri_id, 'uid' => $uid]);
		if (DBA::isResult($message)) {
			// Clean up all tags
			DBA::delete('post-category', ['uri-id' => $uri_id, 'uid' => $uid]);

			if ($message['deleted']) {
				return;
			}
		}

		if (preg_match_all("/\[(.*?)\]/ism", $files, $result)) {
			foreach ($result[1] as $file) {
				$tagid = Tag::getID($file);
				if (empty($tagid)) {
					continue;
				}

				self::storeByURIId($uri_id, $uid, self::FILE, $tagid);
			}
		}

		if (preg_match_all("/\<(.*?)\>/ism", $files, $result)) {
			foreach ($result[1] as $file) {
				self::storeFileByURIId($uri_id, $uid, self::CATEGORY, $file);
			}
		}
	}

	public static function storeFileByURIId(int $uri_id, int $uid, int $type, string $file, string $url = ''): bool
	{
		$tagid = Tag::getID($file, $url);
		if (empty($tagid)) {
			return false;
		}

		return self::storeByURIId($uri_id, $uid, $type, $tagid);
	}

	private static function storeByURIId(int $uri_id, int $uid, int $type, int $tagid): bool
	{
		return DBA::replace('post-category', [
			'uri-id' => $uri_id,
			'uid'    => $uid,
			'type'   => $type,
			'tid'    => $tagid,
		]);
	}
}
