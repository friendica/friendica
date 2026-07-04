<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Model for DB specific logic for the search entity
 */
class Search
{
	/**
	 * Returns the list of user defined tags (e.g. #Friendica)
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getUserTags(): array
	{
		$user_condition = ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `user`.`uid` > ?", 0];

		$abandon_days = intval(DI::config()->get('system', 'account_abandon_days'));
		if (!empty($abandon_days)) {
			$user_condition = DBA::mergeConditions($user_condition, ["`last-activity` > ?", DateTimeFormat::utc('now - ' . $abandon_days . ' days')]);
		}

		$condition    = $user_condition;
		$condition[0] = "SELECT DISTINCT(`term`) FROM `search` INNER JOIN `user` ON `search`.`uid` = `user`.`uid` WHERE " . $user_condition[0];
		$sql          = array_shift($condition);
		$termsStmt    = DBA::p($sql, $condition);

		$tags = [];
		while ($term = DBA::fetch($termsStmt)) {
			$tags[] = trim(mb_strtolower((string) $term['term']), '#');
		}
		DBA::close($termsStmt);

		$condition    = $user_condition;
		$condition[0] = "SELECT `include-tags` FROM `channel` INNER JOIN `user` ON `channel`.`uid` = `user`.`uid` WHERE " . $user_condition[0];
		$sql          = array_shift($condition);
		$channels     = DBA::p($sql, $condition);
		while ($channel = DBA::fetch($channels)) {
			foreach (explode(',', (string) $channel['include-tags']) as $tag) {
				$tag = trim(mb_strtolower($tag));
				if (empty($tag)) {
					continue;
				}
				if (!in_array($tag, $tags)) {
					$tags[] = $tag;
				}
			}
		}
		DBA::close($channels);

		sort($tags);

		return $tags;
	}
}
