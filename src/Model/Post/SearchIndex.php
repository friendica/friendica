<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Util\DateTimeFormat;

class SearchIndex
{
	/**
	 * Insert a post-searchindex entry
	 *
	 * @param int $uri_id
	 * @param string $created
	 * @param bool $refresh
	 */
	public static function insert(int $uri_id, string $created, bool $refresh = false)
	{
		$limit = self::searchAgeDateLimit();
		if (!empty($limit) && (strtotime($created) < strtotime($limit))) {
			return;
		}

		$item = Post::selectFirstPost(['created', 'owner-id', 'private', 'language', 'network', 'title', 'content-warning', 'body', 'quote-uri-id'], ['uri-id' => $uri_id]);
		if (empty($item)) {
			return;
		}

		$search = [
			'uri-id'     => $uri_id,
			'owner-id'   => $item['owner-id'],
			'media-type' => Engagement::getMediaType($uri_id, $item['quote-uri-id']),
			'language'   => substr(!empty($item['language']) ? (array_key_first(json_decode($item['language'], true)) ?? L10n::UNDETERMINED_LANGUAGE) : L10n::UNDETERMINED_LANGUAGE, 0, 2),
			'searchtext' => Post\Engagement::getSearchTextForUriId($uri_id, $refresh),
			'size'       => Engagement::getContentSize($item),
			'created'    => $item['created'],
			'restricted' => !in_array($item['network'], Protocol::FEDERATED) || ($item['private'] != Item::PUBLIC),
		];
		return DBA::insert('post-searchindex', $search, Database::INSERT_UPDATE);
	}

	/**
	 * update a post-searchindex entry
	 *
	 * @param int $uri_id
	 */
	public static function update(int $uri_id)
	{
		$searchtext = Post\Engagement::getSearchTextForUriId($uri_id);
		return DBA::update('post-searchindex', ['searchtext' => $searchtext], ['uri-id' => $uri_id]);
	}

	/**
	 * Expire old searchindex entries
	 *
	 * @return void
	 */
	public static function expire()
	{
		$limit = self::searchAgeDateLimit();
		if (empty($limit)) {
			return;
		}
		DBA::delete('post-searchindex', ["`created` < ?", $limit]);
		DI::logger()->notice('Cleared expired searchindex entries', ['limit' => $limit, 'rows' => DBA::affectedRows()]);
	}

	public static function searchAgeDateLimit(): string
	{
		$days = DI::config()->get('system', 'search_age_days');
		if (empty($days)) {
			return '';
		}
		return DateTimeFormat::utc('now - ' . $days . ' day');
	}

	public static function getSearchTable(): string
	{
		return DI::config()->get('system', 'limited_search_scope') ? 'post-engagement' : 'post-searchindex';
	}

	public static function getSearchView(): string
	{
		return DI::config()->get('system', 'limited_search_scope') ? 'post-engagement-user-view' : 'post-searchindex-user-view';
	}
}
