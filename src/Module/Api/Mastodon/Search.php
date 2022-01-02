<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Protocol;
use Friendica\Core\Search as CoreSearch;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Module\BaseApi;
use Friendica\Object\Search\ContactResult;

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
		self::checkAllowedScope(self::SCOPE_READ);
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
			DI::mstdnError()->UnprocessableEntity();
		}

		$limit = min($request['limit'], 40);

		$result = ['accounts' => [], 'statuses' => [], 'hashtags' => []];

		if (empty($request['type']) || ($request['type'] == 'accounts')) {
			$result['accounts'] = self::searchAccounts($uid, $request['q'], $request['resolve'], $limit, $request['offset'], $request['following']);
		}
		if ((empty($request['type']) || ($request['type'] == 'statuses')) && (strpos($request['q'], '@') == false)) {
			$result['statuses'] = self::searchStatuses($uid, $request['q'], $request['account_id'], $request['max_id'], $request['min_id'], $limit, $request['offset']);
		}
		if ((empty($request['type']) || ($request['type'] == 'hashtags')) && (strpos($request['q'], '@') == false)) {
			$result['hashtags'] = self::searchHashtags($request['q'], $request['exclude_unreviewed'], $limit, $request['offset'], $this->parameters['version']);
		}

		System::jsonExit($result);
	}

	private static function searchAccounts(int $uid, string $q, bool $resolve, int $limit, int $offset, bool $following)
	{
		$accounts = [];

		if (!$following) {
			if ((strrpos($q, '@') > 0) && $resolve) {
				$results = CoreSearch::getContactsFromProbe($q);
			}

			if (empty($results)) {
				if (DI::config()->get('system', 'poco_local_search')) {
					$results = CoreSearch::getContactsFromLocalDirectory($q, CoreSearch::TYPE_ALL, 0, $limit);
				} elseif (!empty(DI::config()->get('system', 'directory'))) {
					$results = CoreSearch::getContactsFromGlobalDirectory($q, CoreSearch::TYPE_ALL, 1);
				}
			}
			if (!empty($results)) {
				$counter = 0;
				foreach ($results->getResults() as $result) {
					if (++$counter > $limit) {
						continue;
					}
					if ($result instanceof ContactResult) {
						$id = Contact::getIdForURL($result->getUrl(), 0, false);

						$accounts[] = DI::mstdnAccount()->createFromContactId($id, $uid);
					}
				}
			}
		} else {
			$contacts = Contact::searchByName($q, '', $uid);

			$counter = 0;
			foreach ($contacts as $contact) {
				if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
					continue;
				}
				if (++$counter > $limit) {
					continue;
				}
				$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $uid);
			}
			DBA::close($contacts);
		}

		return $accounts;
	}

	private static function searchStatuses(int $uid, string $q, string $account_id, int $max_id, int $min_id, int $limit, int $offset)
	{
		$params = ['order' => ['uri-id' => true], 'limit' => [$offset, $limit]];

		if (substr($q, 0, 1) == '#') {
			$condition = ["`name` = ? AND (`uid` = ? OR (`uid` = ? AND NOT `global`))
				AND (`network` IN (?, ?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
				substr($q, 1), 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, $uid, 0];
			$table = 'tag-search-view';
		} else {
			$condition = ["`uri-id` IN (SELECT `uri-id` FROM `post-content` WHERE MATCH (`title`, `content-warning`, `body`) AGAINST (? IN BOOLEAN MODE))
				AND (`uid` = ? OR (`uid` = ? AND NOT `global`)) AND (`network` IN (?, ?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
				str_replace('@', ' ', $q), 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, $uid, 0];
			$table = 'post-user-view';
		}

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $min_id]);

			$params['order'] = ['uri-id'];
		}

		$items = DBA::select($table, ['uri-id'], $condition, $params);

		$statuses = [];
		while ($item = Post::fetch($items)) {
			self::setBoundaries($item['uri-id']);
			$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid);
		}
		DBA::close($items);

		if (!empty($min_id)) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		return $statuses;
	}

	private static function searchHashtags(string $q, bool $exclude_unreviewed, int $limit, int $offset, int $version)
	{
		$q = ltrim($q, '#');

		$params = ['order' => ['name'], 'limit' => [$offset, $limit]];

		$condition = ["EXISTS(SELECT `tid` FROM `post-tag` WHERE `type` = ? AND `tid` = `id`) AND `name` LIKE ?", Tag::HASHTAG, $q . '%'];

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
