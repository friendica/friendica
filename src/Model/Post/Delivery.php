<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\ItemURI;

class Delivery
{
	/**
	 * Add a post to an inbox
	 */
	public static function add(int $uri_id, int $uid, string $inbox, string $created, string $command, array $receivers)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = ['uri-id' => $uri_id, 'uid' => $uid, 'inbox-id' => ItemURI::getIdByURI($inbox),
			'created'          => $created, 'command' => $command, 'receivers' => json_encode($receivers)];

		DBA::insert('post-delivery', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Remove post from an inbox after delivery
	 *
	 * @param integer $uri_id
	 * @param string  $inbox
	 */
	public static function remove(int $uri_id, string $inbox)
	{
		DBA::delete('post-delivery', ['uri-id' => $uri_id, 'inbox-id' => ItemURI::getIdByURI($inbox)]);
	}

	/**
	 * Remove failed posts for an inbox
	 *
	 * @param string  $inbox
	 */
	public static function removeFailed(string $inbox)
	{
		DBA::delete('post-delivery', ["`inbox-id` = ? AND `failed` >= ?", ItemURI::getIdByURI($inbox), DI::config()->get('system', 'worker_defer_limit')]);
	}

	/**
	 * Increment "failed" counter for the given inbox and post
	 *
	 * @param integer $uri_id
	 * @param string  $inbox
	 */
	public static function incrementFailed(int $uri_id, string $inbox)
	{
		return DBA::update('post-delivery', ["`failed` = `failed` + 1"], ['uri-id' => $uri_id, 'inbox-id' => ItemURI::getIdByURI($inbox)]);
	}

	public static function selectForInbox(string $inbox)
	{
		$rows       = DBA::select('post-delivery', [], ["`inbox-id` = ? AND `failed` < ?", ItemURI::getIdByURI($inbox), DI::config()->get('system', 'worker_defer_limit')], ['order' => ['created']]);
		$deliveries = [];
		while ($row = DBA::fetch($rows)) {
			if (!empty($row['receivers'])) {
				$row['receivers'] = json_decode((string) $row['receivers'], true);
			} else {
				$row['receivers'] = [];
			}
			$deliveries[] = $row;
		}
		DBA::close($rows);

		return $deliveries;
	}
}
