<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Repository;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item as ItemModel;

/**
 * Repository for the interactions with posts, that are counted per viewer.
 */
final readonly class InteractionCounts
{
	private const SEPARATOR = "\xFF\xFF\xFF";

	public function __construct(private Database $dba) {}

	/**
	 * Get the grouped activities (reactions, reshares, comments) of the threads, as they are visible to the viewer
	 *
	 * @param array<int, int> $uriIds       URI-IDs of the threads
	 * @param int             $uid          The user ID of the viewer
	 * @param int             $selfContact  Public contact ID of the viewer
	 * @param string[]        $verbs        Verbs of the activities that are counted
	 * @param array<int, int> $targetUriIds Restrict the activities to these reacted posts, empty for all posts of the threads
	 * @return array<int, array{parent-uri-id: int, thr-parent-id: int, body: string, verb: string, gravity: int, private: int, title: string, self: int}>
	 */
	public function findActivities(array $uriIds, int $uid, int $selfContact, array $verbs, array $targetUriIds = []): array
	{
		$condition = ['parent-uri-id' => $uriIds, 'gravity' => [ItemModel::GRAVITY_ACTIVITY, ItemModel::GRAVITY_COMMENT], 'verb' => $verbs];
		if ($targetUriIds) {
			$condition['thr-parent-id'] = $targetUriIds;
		}
		$condition = DBA::mergeConditions($condition, ["NOT `deleted`"]);
		$condition = DBA::mergeConditions($condition, ["((`uid` = ? AND `global`) OR (`uid` = ? AND NOT `global`))", 0, $uid]);

		$sql = "SELECT `parent-uri-id`, `thr-parent-id`, `body`, `verb`, `gravity`, `private`, GROUP_CONCAT(REPLACE(`author-name`, '" . self::SEPARATOR . "', ' ') SEPARATOR '" . self::SEPARATOR . "' LIMIT 50) AS `title`, MAX(`author-id` = ?) AS `self` FROM `post-user-view` WHERE " . array_shift($condition) . " GROUP BY `parent-uri-id`, `thr-parent-id`, `verb`, BINARY `body`, `gravity`, `private`";

		return $this->dba->toArray($this->dba->p($sql, array_merge([$selfContact], $condition)));
	}

	/**
	 * Get the quote shares of the posts, without the ones of contacts the viewer blocked or ignored
	 *
	 * @param array<int, int> $uriIds URI-IDs of the quoted posts
	 * @param int             $uid    The user ID of the viewer
	 * @return array<int, array{quote-uri-id: int, total: int, title: string}>
	 */
	public function findQuoteShares(array $uriIds, int $uid): array
	{
		$condition = DBA::mergeConditions(['quote-uri-id' => $uriIds], ["NOT `quote-uri-id` IS NULL AND NOT `post`.`deleted` AND `post`.`private` IN (?, ?) AND NOT `contact`.`blocked`", ItemModel::PUBLIC, ItemModel::UNLISTED]);
		if ($uid) {
			$condition = DBA::mergeConditions($condition, ["NOT `post`.`author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored`))", $uid]);
		}

		$sql = "SELECT `quote-uri-id`, COUNT(*) AS `total`, GROUP_CONCAT(REPLACE(`name`, '" . self::SEPARATOR . "', ' ') SEPARATOR '" . self::SEPARATOR . "' LIMIT 50) AS `title` FROM `post-quote` INNER JOIN `post` ON `post`.`uri-id` = `post-quote`.`uri-id` INNER JOIN `contact` ON `post`.`author-id` = `contact`.`id` WHERE " . array_shift($condition) . " GROUP BY `quote-uri-id`";

		return $this->dba->toArray($this->dba->p($sql, $condition));
	}

	/**
	 * Separator of the names in the "title" fields of the results
	 */
	public function getSeparator(): string
	{
		return self::SEPARATOR;
	}
}
