<?php

declare(strict_types=1);

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Worker to update the posts of a user-defined channel.
 *
 * Responsible for rebuilding the `channel-post` cache for a given channel
 * and user. The worker respects the system `channel_cache` configuration
 * and uses repository conditions to select matching posts.
 *
 * @package Friendica\Worker
 */
final class UpdateChannelPosts
{
	/**
	 * Update the posts of a given user defined channel.
	 *
	 * Deletes existing cached entries and repopulates the channel cache
	 * based on the channel's matching condition.
	 *
	 * @param int $id Channel id.
	 * @param int $uid User id the channel belongs to.
	 * @return void
	 */
	public static function execute(int $id, int $uid)
	{
		if (!DI::config()->get('system', 'channel_cache')) {
			return;
		}
		$channel = DI::userDefinedChannel()->selectById($id, $uid);
		if (!$channel) {
			return;
		}

		DI::logger()->debug('Delete channel posts', ['channel' => $id, 'uid' => $uid]);
		DBA::delete('channel-post', ['channel' => $id, 'uid' => $uid]);

		$condition = DI::userDefinedChannel()->getCondition($channel, $uid);

		DI::logger()->debug('Start updating user defined channel', ['channel' => $id, 'uid' => $uid]);
		$order  = 'created';
		$table  = 'post-engagement';
		$fields = ['uri-id', 'created'];
		if (in_array($channel->circle, [-3, -4, -5])) {
			$orders    = ['-3' => 'created', '-4' => 'received', '-5' => 'commented'];
			$order     = $orders[$channel->circle];
			$table     = 'post-engagement-user-view';
			$fields    = ['uri-id', 'created', 'received', 'commented'];
			$condition = DBA::mergeConditions($condition, ['uid' => $uid]);
		}
		$rows   = 0;
		$params = ['order' => [$order => true]];
		$query  = DBA::select($table, $fields, $condition, $params);
		DI::logger()->debug('Start adding channel posts', ['channel' => $id, 'uid' => $uid]);
		while ($row = DBA::fetch($query)) {
			$rows++;
			$cache = [
				'channel'   => (int)$channel->code,
				'uid'       => $channel->uid,
				'uri-id'    => $row['uri-id'],
				'created'   => $row['created'],
				'received'  => $row['received']  ?? $row['created'],
				'commented' => $row['commented'] ?? $row['created'],
			];
			DBA::insert('channel-post', $cache, Database::INSERT_UPDATE);
		}
		DI::logger()->debug('Done updating user defined channel', ['channel' => $id, 'uid' => $uid, 'rows' => $rows]);
	}
}
