<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Post\Repository;

use Friendica\Database\Database;

/**
 * Read repository for the posts of a user that use a local photo or attachment.
 */
final readonly class MediaUsage
{
	public const DEFAULT_LIMIT = 100;

	public function __construct(private Database $db) {}

	/**
	 * @return array{guid: string, title: string, body: string, created: string}[] posts, newest first
	 */
	public function selectPostsByPhoto(string $resourceId, int $uid, int $limit = self::DEFAULT_LIMIT): array
	{
		$url = '%/photo/' . $resourceId . '-%';
		return $this->selectPosts(['(`url` LIKE ? OR `preview` LIKE ?)', $url, $url], $uid, $limit);
	}

	/**
	 * @return array{guid: string, title: string, body: string, created: string}[] posts, newest first
	 */
	public function selectPostsByAttachment(int $attachId, int $uid, int $limit = self::DEFAULT_LIMIT): array
	{
		return $this->selectPosts(['`attach-id` = ?', $attachId], $uid, $limit);
	}

	/**
	 * @param array{0: string, ...} $mediaCondition condition on `post-media`
	 *
	 * @return array{guid: string, title: string, body: string, created: string}[]
	 */
	private function selectPosts(array $mediaCondition, int $uid, int $limit): array
	{
		// Starting from the user's own posts keeps the leading-wildcard LIKE away from the whole post-media table.
		$condition    = $mediaCondition;
		$condition[0] = '(' . $condition[0] . ') AND `uri-id` IN (SELECT `uri-id` FROM `post-origin` WHERE `uid` = ?)';
		$condition[]  = $uid;

		$uriIds = array_unique(array_column($this->db->selectToArray('post-media', ['uri-id'], $condition), 'uri-id'));
		if (empty($uriIds)) {
			return [];
		}

		return $this->db->selectToArray(
			'post-user-view',
			['guid', 'title', 'body', 'created'],
			['uri-id' => $uriIds, 'uid' => $uid, 'deleted' => false],
			['order'  => ['created' => true], 'limit' => $limit],
		);
	}
}
