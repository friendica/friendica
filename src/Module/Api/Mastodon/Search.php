<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\SearchIndex;
use Friendica\Model\Tag;
use Friendica\Module\BaseApi;
use Friendica\Util\Network;

/**
 * @see https://docs.joinmastodon.org/methods/search/
 */
class Search extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'account_id'         => 0,     // If provided, statuses returned will be authored only by this account
			'max_id'             => 0,     // Return results older than this id
			'min_id'             => 0,     // Return results immediately newer than this id
			'type'               => '',    // Enum(accounts, hashtags, statuses)
			'exclude_unreviewed' => false, // Filter out unreviewed tags? Defaults to false. Use true when trying to find trending tags.
			'q'                  => '',    // The search query
			'resolve'            => false, // Attempt WebFinger lookup. Defaults to false.
			'limit'              => 20,    // Maximum number of results to load, per type. Defaults to 20. Max 40.
			'offset'             => 0,     // Offset in search results. Used for pagination. Defaults to 0.
			'following'          => false, // Only include accounts that the user is following. Defaults to false.
		], $request);

		if (empty($request['q'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$limit = min($request['limit'], 40);

		$result = ['accounts' => [], 'statuses' => [], 'hashtags' => []];

		if (empty($request['type']) || ($request['type'] == 'accounts')) {
			$result['accounts'] = $this->searchAccounts($uid, $request['q'], $request['resolve'], $limit, $request['offset'], $request['following']);

			if (!is_array($result['accounts'])) {
				// Curbing the search if we got an exact result
				$request['type']    = 'accounts';
				$result['accounts'] = [$result['accounts']];
			}
		}

		if (empty($request['type']) || ($request['type'] == 'statuses')) {
			$result['statuses'] = $this->searchStatuses($uid, $request['q'], $request['account_id'], $request['max_id'], $request['min_id'], $limit, $request['offset']);

			if (!is_array($result['statuses'])) {
				// Curbing the search if we got an exact result
				$request['type']    = 'statuses';
				$result['statuses'] = [$result['statuses']];
			}
		}

		if ((empty($request['type']) || ($request['type'] == 'hashtags')) && (strpos($request['q'], '@') == false)) {
			$result['hashtags'] = $this->searchHashtags($request['q'], $request['exclude_unreviewed'], $limit, $request['offset'], $this->parameters['version']);
		}

		$this->jsonExit($result);
	}

	/**
	 * @param int    $uid
	 * @param string $q
	 * @param bool   $resolve
	 * @param int    $limit
	 * @param int    $offset
	 * @param bool   $following
	 * @return array|\Friendica\Object\Api\Mastodon\Account Object if result is absolute (exact account match), list if not
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	private function searchAccounts(int $uid, string $q, bool $resolve, int $limit, int $offset, bool $following)
	{
		if (($offset == 0) && (strrpos($q, '@') > 0 || Network::isValidHttpUrl($q))
			&& $id = Contact::getIdForURL($q, 0, $resolve ? null : false)
		) {
			return DI::mstdnAccount()->createFromContactId($id, $uid);
		}

		$accounts = [];
		foreach (Contact::searchByName($q, '', false, $following ? $uid : 0, $limit, $offset) as $contact) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $uid);
		}

		return $accounts;
	}

	/**
	 * @param int    $uid
	 * @param string $q
	 * @param string $account_id
	 * @param int    $max_id
	 * @param int    $min_id
	 * @param int    $limit
	 * @param int    $offset
	 * @return array|\Friendica\Object\Api\Mastodon\Status Object is result is absolute (exact post match), list if not
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	private function searchStatuses(int $uid, string $q, string $account_id, int $max_id, int $min_id, int $limit, int $offset)
	{
		if (Network::isValidHttpUrl($q)) {
			// Unique post search, any offset greater than 0 should return empty result
			if ($offset > 0) {
				return [];
			}
			$q = Network::convertToIdn($q);
			// If the user-specific search failed, we search and probe a public post
			$item_id = Item::fetchByLink($q, $uid) ?: Item::fetchByLink($q);
			if ($item_id && $item = Post::selectFirst(['uri-id'], ['id' => $item_id])) {
				return DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, self::appSupportsQuotes());
			}
		}

		$params = ['order' => ['uri-id' => true], 'limit' => [$offset, $limit]];

		if (substr($q, 0, 1) == '#') {
			$condition = ["`name` = ? AND (`uid` = ? OR (`uid` = ? AND NOT `global`))
				AND (`network` IN (?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
				substr($q, 1), 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, $uid, 0];
			$table = 'tag-search-view';
		} else {
			$q         = Post\Engagement::escapeKeywords($q);
			$condition = ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE) AND (NOT `restricted` OR `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `uid` = ?))", $q, $uid];
			$table     = SearchIndex::getSearchTable();
		}

		if (!empty($account_id)) {
			$condition = DBA::mergeConditions($condition, ["`author-id` = ?", $account_id]);
		}

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $max_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $min_id]);

			$params['order'] = ['uri-id'];
		}

		$items = DBA::select($table, ['uri-id'], $condition, $params);

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			self::setBoundaries($item['uri-id']);
			try {
				$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'exception' => $exception]);
			}
		}
		DBA::close($items);

		if (!empty($min_id)) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		return $statuses;
	}

	private function searchHashtags(string $q, bool $exclude_unreviewed, int $limit, int $offset, int $version): array
	{
		$q = ltrim($q, '#');

		$params = ['order' => ['name'], 'limit' => [$offset, $limit]];

		$condition = ["`id` IN (SELECT `tid` FROM `post-tag` WHERE `type` = ?) AND `name` LIKE ?", Tag::HASHTAG, $q . '%'];

		$tags = DBA::select('tag', ['name'], $condition, $params);

		$hashtags = [];
		foreach ($tags as $tag) {
			if ($version == 1) {
				$hashtags[] = $tag['name'];
			} else {
				$hashtags[] = new \Friendica\Object\Api\Mastodon\Tag(DI::baseUrl(), $tag);
			}
		}

		return $hashtags;
	}
}
