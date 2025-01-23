<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Content\Text\BBCode;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Strings;

/**
 * Class Tag
 *
 * This Model class handles tag table interactions.
 * This tables stores relevant tags related to posts, like hashtags and mentions.
 */
class Tag
{
	const UNKNOWN = 0;
	const HASHTAG = 1;
	const MENTION = 2;
	/**
	 * An implicit mention is a mention in a comment body that is redundant with the threading information.
	 */
	const IMPLICIT_MENTION = 8;
	/**
	 * An exclusive mention transmits the post only to the target account without transmitting it to the followers, usually a group.
	 */
	const EXCLUSIVE_MENTION = 9;

	const TO         = 10;
	const CC         = 11;
	const BTO        = 12;
	const BCC        = 13;
	const AUDIENCE   = 14;
	const ATTRIBUTED = 15;

	const CAN_ANNOUNCE = 20;
	const CAN_LIKE     = 21;
	const CAN_REPLY    = 22;

	const ACCOUNT             = 1;
	const GENERAL_COLLECTION  = 2;
	const FOLLOWER_COLLECTION = 3;
	const PUBLIC_COLLECTION   = 4;

	const TAG_CHARACTER = [
		self::HASHTAG           => '#',
		self::MENTION           => '@',
		self::EXCLUSIVE_MENTION => '!',
		self::IMPLICIT_MENTION  => '%',
	];

	/**
	 * Store tag/mention elements
	 *
	 * @param integer $uriId
	 * @param integer $type Tag type
	 * @param string  $name Tag name
	 * @param string  $url Contact URL (optional)
	 * @param integer $target Target (default: null)
	 * @return void
	 */
	public static function store(int $uriId, int $type, string $name, string $url = '', int $target = null)
	{
		if ($type == self::HASHTAG) {
			// Trim Unicode non-word characters
			$name = preg_replace('/(^\W+)|(\W+$)/us', '', $name);

			$tags = explode(self::TAG_CHARACTER[self::HASHTAG], $name);
			if (count($tags) > 1) {
				foreach ($tags as $tag) {
					self::store($uriId, $type, $tag, $url);
				}
				return;
			}
		}

		if (empty($name)) {
			return;
		}

		$cid   = 0;
		$tagid = 0;

		if (in_array($type, [self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION, self::TO, self::CC, self::BTO, self::BCC, self::AUDIENCE, self::ATTRIBUTED])) {
			if (empty($url)) {
				// No mention without a contact url
				return;
			}

			if ((substr($url, 0, 7) == 'https//') || (substr($url, 0, 6) == 'http//')) {
				DI::logger()->notice('Wrong scheme in url', ['url' => $url]);
			}

			$cid = Contact::getIdForURL($url, 0, false);
			DI::logger()->debug('Got id for contact', ['cid' => $cid, 'url' => $url]);

			if (empty($cid)) {
				$tag = DBA::selectFirst('tag', ['name', 'type'], ['url' => $url]);
				if (!empty($tag)) {
					if ($tag['name'] != substr($name, 0, 96)) {
						DBA::update('tag', ['name' => substr($name, 0, 96)], ['url' => $url, 'type' => $tag['type']]);
					}
					if (!empty($target) && ($tag['type'] != $target)) {
						DBA::update('tag', ['type' => $target], ['url' => $url]);
					}
				}
			}
		}

		if (empty($cid)) {
			if (!in_array($type, [self::TO, self::CC, self::BTO, self::BCC, self::AUDIENCE, self::ATTRIBUTED])) {
				if (($type != self::HASHTAG) && !empty($url) && ($url != $name)) {
					$url = strtolower($url);
				} else {
					$url = '';
				}
			}

			$tagid = self::getID($name, $url, $target);
			if (empty($tagid)) {
				return;
			}
		}

		$fields = ['uri-id' => $uriId, 'type' => $type, 'tid' => $tagid, 'cid' => $cid];

		if (in_array($type, [self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION])) {
			$condition         = $fields;
			$condition['type'] = [self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION];
			if (DBA::exists('post-tag', $condition)) {
				DI::logger()->info('Tag already exists', $fields);
				return;
			}
		}

		DBA::insert('post-tag', $fields, Database::INSERT_IGNORE);

		DI::logger()->debug('Stored tag/mention', ['uri-id' => $uriId, 'tag-id' => $tagid, 'contact-id' => $cid, 'name' => $name, 'type' => $type]);
	}

	/**
	 * Fetch the target type for the given url
	 *
	 * @param string $url
	 * @param bool   $fetch Fetch information via network operations
	 * @return null|int
	 */
	public static function getTargetType(string $url, bool $fetch = true)
	{
		$target = null;

		if (empty($url)) {
			return $target;
		}

		$tag = DBA::selectFirst('tag', ['url', 'type'], ['url' => $url]);
		if (!empty($tag['type'])) {
			$target = $tag['type'];
			if ($target != self::GENERAL_COLLECTION) {
				DI::logger()->debug('Found existing type', ['type' => $tag['type'], 'url' => $url]);
				return $target;
			}
		}

		if ($url == ActivityPub::PUBLIC_COLLECTION) {
			$target = self::PUBLIC_COLLECTION;
			DI::logger()->debug('Public collection', ['url' => $url]);
		} else {
			if (DBA::exists('apcontact', ['followers' => $url])) {
				$target = self::FOLLOWER_COLLECTION;
				DI::logger()->debug('Found collection via existing apcontact', ['url' => $url]);
			} elseif (Contact::getIdForURL($url, 0, $fetch ? null : false)) {
				$target = self::ACCOUNT;
				DI::logger()->debug('URL is an account', ['url' => $url]);
			} elseif ($fetch && ($target != self::GENERAL_COLLECTION)) {
				$content = HTTPSignature::fetch($url);
				if (!empty($content['type']) && ($content['type'] == 'OrderedCollection')) {
					$target = self::GENERAL_COLLECTION;
					DI::logger()->debug('URL is an ordered collection', ['url' => $url]);
				}
			}
		}

		if (!empty($target) && !empty($tag['url']) && ($tag['type'] != $target)) {
			DBA::update('tag', ['type' => $target], ['url' => $url]);
		}

		if (empty($target)) {
			DI::logger()->debug('No type could be detected', ['url' => $url]);
		}

		return $target;
	}

	/**
	 * Get a tag id for a given tag name and URL
	 *
	 * @param string $name Name of tag
	 * @param string $url
	 * @param int    $type Type of tag
	 * @return int Tag id
	 */
	public static function getID(string $name, string $url = '', int $type = null): int
	{
		$fields = ['name' => substr($name, 0, 96), 'url' => $url];

		$tag = DBA::selectFirst('tag', ['id', 'type'], $fields);
		if (DBA::isResult($tag)) {
			if (empty($tag['type']) && !empty($type)) {
				DBA::update('tag', ['type' => $type], $fields);
			}
			return $tag['id'];
		}

		if (!empty($type)) {
			$fields['type'] = $type;
		}

		DBA::insert('tag', $fields, Database::INSERT_IGNORE);
		$tid = DBA::lastInsertId();
		if (!empty($tid)) {
			return $tid;
		}

		// Also log type
		$fields['type'] = $type;

		DI::logger()->error('No tag id created', $fields);
		return 0;
	}

	/**
	 * Store tag/mention elements
	 *
	 * @param integer $uriId
	 * @param string $hash
	 * @param string $name
	 * @param string $url
	 * @return void
	 */
	public static function storeByHash(int $uriId, string $hash, string $name, string $url = '')
	{
		$type = self::getTypeForHash($hash);
		if ($type == self::UNKNOWN) {
			return;
		}

		self::store($uriId, $type, $name, $url);
	}

	/**
	 * Get tags and mentions from the body
	 *
	 * @param string  $body    Body of the post
	 * @param string  $tags    Accepted tags
	 *
	 * @return array Tag list
	 */
	public static function getFromBody(string $body, string $tags = null): array
	{
		if (is_null($tags)) {
			$tags = self::TAG_CHARACTER[self::HASHTAG] . self::TAG_CHARACTER[self::MENTION] . self::TAG_CHARACTER[self::EXCLUSIVE_MENTION];
		}

		if (!preg_match_all("/([" . $tags . "])\[url\=([^\[\]]*)\]([^\[\]]*)\[\/url\]/ism", $body, $result, PREG_SET_ORDER)) {
			return [];
		}

		return $result;
	}

	/**
	 * Store tags and mentions from the body
	 *
	 * @param integer $uriId   URI-Id
	 * @param string  $body    Body of the post
	 * @param string  $tags    Accepted tags
	 * @return void
	 */
	public static function storeFromBody(int $uriId, string $body, string $tags = null)
	{
		$item = ['uri-id' => $uriId, 'body' => $body, 'quote-uri-id' => null];
		self::storeFromArray($item, $tags);
	}

	/**
	 * Store tags and mentions from the item array
	 *
	 * @param array   $item    Item array
	 * @param string  $tags    Accepted tags
	 * @return void
	 */
	public static function storeFromArray(array $item, string $tags = null)
	{
		DI::logger()->info('Check for tags', ['uri-id' => $item['uri-id'], 'hash' => $tags]);

		if (is_null($tags)) {
			$tags = self::TAG_CHARACTER[self::HASHTAG] . self::TAG_CHARACTER[self::MENTION] . self::TAG_CHARACTER[self::EXCLUSIVE_MENTION];
		}

		foreach (self::getFromBody($item['body'], $tags) as $tag) {
			self::storeByHash($item['uri-id'], $tag[1], $tag[3], $tag[2]);
		}

		$shared = DI::contentItem()->getSharedPost($item, ['uri-id']);

		// Search for hashtags in the shared body (but only if hashtags are wanted)
		if (!empty($shared) && (strpos($tags, self::TAG_CHARACTER[self::HASHTAG]) !== false)) {
			foreach (self::getByURIId($shared['post']['uri-id'], [self::HASHTAG]) as $tag) {
				self::store($item['uri-id'], $tag['type'], $tag['name'], $tag['url']);
			}
		}
	}

	/**
	 * Store raw tags (not encapsulated in links) from the body
	 * This function is needed in the intermediate phase.
	 * Later we can call Item::setHashtags in advance to have all tags converted.
	 *
	 * @param integer $uriId URI-Id
	 * @param string  $body   Body of the post
	 * @return void
	 */
	public static function storeRawTagsFromBody(int $uriId, string $body)
	{
		DI::logger()->info('Check for tags', ['uri-id' => $uriId]);

		$result = BBCode::getTags($body);
		if (empty($result)) {
			return;
		}

		DI::logger()->info('Found tags', ['uri-id' => $uriId, 'result' => $result]);

		foreach ($result as $tag) {
			if (substr($tag, 0, 1) != self::TAG_CHARACTER[self::HASHTAG]) {
				continue;
			}
			self::storeByHash($uriId, substr($tag, 0, 1), substr($tag, 1));
		}
	}

	/**
	 * Checks for stored hashtags and mentions for the given post
	 *
	 * @param integer $uriId
	 * @return bool
	 */
	public static function existsForPost(int $uriId): bool
	{
		return DBA::exists('post-tag', ['uri-id' => $uriId, 'type' => [self::HASHTAG, self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION]]);
	}

	/**
	 * Check for a given hashtag on a given post
	 *
	 * @param integer $uriId
	 * @param string $tag
	 * @return boolean
	 */
	public static function existsTagForPost(int $uriId, string $tag): bool
	{
		return DBA::exists('tag-view', ['uri-id' => $uriId, 'type' => self::HASHTAG, 'name' => $tag]);
	}

	/**
	 * Remove tag/mention
	 *
	 * @param integer $uriId
	 * @param integer $type Type
	 * @param string $name Name
	 * @param string $url URL
	 * @return void
	 */
	public static function remove(int $uriId, int $type, string $name, string $url = '')
	{
		$condition = ['uri-id' => $uriId, 'type' => $type, 'url' => $url];
		if ($type == self::HASHTAG) {
			$condition['name'] = $name;
		}

		$tag = DBA::selectFirst('tag-view', ['tid', 'cid'], $condition);
		if (!DBA::isResult($tag)) {
			return;
		}

		DI::logger()->debug('Removing tag/mention', ['uri-id' => $uriId, 'tid' => $tag['tid'], 'name' => $name, 'url' => $url]);
		DBA::delete('post-tag', ['uri-id' => $uriId, 'type' => $type, 'tid' => $tag['tid'], 'cid' => $tag['cid']]);
	}

	/**
	 * Remove tag/mention
	 *
	 * @param integer $uriId
	 * @param string $hash
	 * @param string $name
	 * @param string $url
	 * @return void
	 */
	public static function removeByHash(int $uriId, string $hash, string $name, string $url = '')
	{
		$type = self::getTypeForHash($hash);
		if ($type == self::UNKNOWN) {
			return;
		}

		self::remove($uriId, $type, $name, $url);
	}

	/**
	 * Get the type for the given hash
	 *
	 * @param string $hash
	 * @return integer Tag type
	 */
	private static function getTypeForHash(string $hash): int
	{
		if ($hash == self::TAG_CHARACTER[self::MENTION]) {
			return self::MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::EXCLUSIVE_MENTION]) {
			return self::EXCLUSIVE_MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::IMPLICIT_MENTION]) {
			return self::IMPLICIT_MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::HASHTAG]) {
			return self::HASHTAG;
		} else {
			return self::UNKNOWN;
		}
	}

	/**
	 * Create implicit mentions for a given post
	 *
	 * @param integer $uriId
	 * @param integer $parentUriId
	 * @return void
	 */
	public static function createImplicitMentions(int $uriId, int $parentUriId)
	{
		// Always mention the direct parent author
		$parent = Post::selectFirst(['author-link', 'author-name'], ['uri-id' => $parentUriId]);
		self::store($uriId, self::IMPLICIT_MENTION, $parent['author-name'], $parent['author-link']);

		if (DI::config()->get('system', 'disable_implicit_mentions')) {
			return;
		}

		$tags = DBA::select('tag-view', ['name', 'url'], ['uri-id' => $parentUriId, 'type' => [self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION]]);
		while ($tag = DBA::fetch($tags)) {
			self::store($uriId, self::IMPLICIT_MENTION, $tag['name'], $tag['url']);
		}
		DBA::close($tags);
	}

	/**
	 * Retrieves the terms from the provided type(s) associated with the provided item ID.
	 *
	 * @param int   $uriId
	 * @param array $type Tag type(s)
	 * @return array|bool Array on success, false on error
	 * @throws \Exception
	 */
	public static function getByURIId(int $uriId, array $type = [self::HASHTAG, self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION])
	{
		$condition = ['uri-id' => $uriId, 'type' => $type];
		return DBA::selectToArray('tag-view', ['type', 'name', 'url', 'tag-type'], $condition);
	}

	/**
	 * Checks if the given url is mentioned in the post
	 *
	 * @param integer $uriId
	 * @param string $url
	 * @param array $type
	 *
	 * @return boolean
	 */
	public static function isMentioned(int $uriId, string $url, array $type = [self::MENTION, self::EXCLUSIVE_MENTION, self::AUDIENCE]): bool
	{
		$tags = self::getByURIId($uriId, $type);
		foreach ($tags as $tag) {
			if (Strings::compareLink($url, $tag['url'])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return a string with all tags and mentions
	 *
	 * @param integer $uriId
	 * @param array   $type Tag type(s)
	 * @return string tags and mentions
	 * @throws \Exception
	 */
	public static function getCSVByURIId(int $uriId, array $type = [self::HASHTAG, self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION]): string
	{
		$tag_list = [];
		foreach (self::getByURIId($uriId, $type) as $tag) {
			$tag_list[] = self::TAG_CHARACTER[$tag['type']] . '[url=' . $tag['url'] . ']' . $tag['name'] . '[/url]';
		}

		return implode(',', $tag_list);
	}

	/**
	 * Sorts an item's tags into mentions, hashtags and other tags. Generate personalized URLs by user and modify the
	 * provided item's body with them.
	 *
	 * @param array $item
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function populateFromItem(array &$item): array
	{
		$return = [
			'tags'              => [],
			'hashtags'          => [],
			'mentions'          => [],
			'implicit_mentions' => [],
		];

		$searchpath = DI::baseUrl() . '/search?tag=';

		$taglist = DBA::select(
			'tag-view',
			['type', 'name', 'url', 'cid'],
			['uri-id' => $item['uri-id'], 'type' => [self::HASHTAG, self::MENTION, self::EXCLUSIVE_MENTION, self::IMPLICIT_MENTION]]
		);
		while ($tag = DBA::fetch($taglist)) {
			if ($tag['url'] == '') {
				$tag['url'] = $searchpath . urlencode($tag['name']);
			}

			$orig_tag = $tag['url'];

			$prefix = self::TAG_CHARACTER[$tag['type']];
			switch ($tag['type']) {
				case self::HASHTAG:
					if ($orig_tag != $tag['url']) {
						$item['body'] = str_replace($orig_tag, $tag['url'], $item['body']);
					}

					$return['hashtags'][] = '<bdi>' . $prefix . '<a href="' . $tag['url'] . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($tag['name']) . '</a></bdi>';
					$return['tags'][]     = '<bdi>' . $prefix . '<a href="' . $tag['url'] . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($tag['name']) . '</a></bdi>';
					break;

				case self::MENTION:
				case self::EXCLUSIVE_MENTION:
					if (!empty($tag['cid'])) {
						$tag['url'] = Contact::magicLinkById($tag['cid']);
					} else {
						$tag['url'] = Contact::magicLink($tag['url']);
					}
					$return['mentions'][] = '<bdi>' . $prefix . '<a href="' . $tag['url'] . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($tag['name']) . '</a></bdi>';
					$return['tags'][]     = '<bdi>' . $prefix . '<a href="' . $tag['url'] . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($tag['name']) . '</a></bdi>';
					break;

				case self::IMPLICIT_MENTION:
					$return['implicit_mentions'][] = $prefix . $tag['name'];
					break;

				default:
					DI::logger()->warning('Unknown tag type found', $tag);
			}
		}
		DBA::close($taglist);

		return $return;
	}

	/**
	 * Counts posts for given tag
	 *
	 * @param string $search
	 * @param integer $uid
	 * @return integer number of posts
	 */
	public static function countByTag(string $search, int $uid = 0): int
	{
		$condition = ["`name` = ? AND (`uid` = ? OR (`uid` = ? AND NOT `global`))
			AND (`network` IN (?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
			$search, 0, $uid,
			Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, $uid, 0,
		];

		return DBA::count('tag-search-view', $condition);
	}

	/**
	 * Search posts for given tag
	 *
	 * @param string $search Tag to search for
	 * @param integer $uid User Id
	 * @param integer $start Starting record
	 * @param integer $limit Maximum count of records
	 * @param integer $last_uriid
	 * @return array with URI-ID
	 */
	public static function getURIIdListByTag(string $search, int $uid = 0, int $start = 0, int $limit = 100, int $last_uriid = 0): array
	{
		$condition = ["`name` = ? AND (`uid` = ? OR (`uid` = ? AND NOT `global`))
			AND (`network` IN (?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
			$search, 0, $uid,
			Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, $uid, 0,
		];

		if (!empty($last_uriid)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $last_uriid]);
		}

		$params = [
			'order' => ['uri-id' => true],
			'limit' => [$start, $limit]
		];

		$tags = DBA::select('tag-search-view', ['uri-id'], $condition, $params);

		$uriIds = [];
		while ($tag = DBA::fetch($tags)) {
			$uriIds[] = $tag['uri-id'];
		}
		DBA::close($tags);

		return $uriIds;
	}

	/**
	 * Returns a list of the most frequent global hashtags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @param int $limit  Number of returned tags
	 * @param int $offset  Page offset in results
	 * @return array
	 * @throws \Exception
	 */
	public static function getGlobalTrendingHashtags(int $period, int $limit = 10, int $offset = 0): array
	{
		$tags = DI::cache()->get("global_trending_tags-$period");
		if (empty($tags)) {
			$tags = self::setGlobalTrendingHashtags($period, 1000);
		}
		return array_slice($tags, $offset, $limit);
	}

	/**
	 * Fetch the blocked tags as SQL
	 *
	 * @return string SQL for blocked tag names or empty string
	 */
	private static function getBlockedSQL(): string
	{
		$blocked = Strings::getTagArrayByString(DI::config()->get('system', 'blocked_tags'));
		if (empty($blocked)) {
			return '';
		}

		array_walk($blocked, function (&$value) {
			$value = "'" . DBA::escape(trim($value)) . "'";
		});
		return ' AND NOT `name` IN (' . implode(',', $blocked) . ')';
	}

	/**
	 * Creates a list of the most frequent global hashtags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @param int $limit  Number of returned tags
	 * @return array
	 * @throws \Exception
	 */
	public static function setGlobalTrendingHashtags(int $period, int $limit = 10): array
	{
		/*
		* Get a uri-id that is at least X hours old.
		* We use the uri-id in the query for the hash tags since this is much faster
		*/
		$post = Post::selectFirstThread(
			['uri-id'],
			["`uid` = ? AND `received` < ?", 0, DateTimeFormat::utc('now - ' . $period . ' hour')],
			['order' => ['received' => true]]
		);

		if (empty($post['uri-id'])) {
			return [];
		}

		$block_sql = self::getBlockedSQL();

		$tagsStmt = DBA::p(
			"SELECT `name` AS `term`, COUNT(*) AS `score`, COUNT(DISTINCT(`author-id`)) as `authors`
			FROM `tag-search-view`
			WHERE `private` = ? AND `uid` = ? AND `uri-id` > ? $block_sql
			GROUP BY `term` ORDER BY `authors` DESC, `score` DESC LIMIT ?",
			Item::PUBLIC,
			0,
			$post['uri-id'],
			$limit
		);

		if (DBA::isResult($tagsStmt)) {
			$tags = DBA::toArray($tagsStmt);
			DI::cache()->set("global_trending_tags-$period", $tags, Duration::HOUR);
			return $tags;
		}

		return [];
	}

	/**
	 * Returns a list of the most frequent local hashtags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @param int $limit  Number of returned tags
	 * @param int $offset  Page offset in results
	 * @return array
	 * @throws \Exception
	 */
	public static function getLocalTrendingHashtags(int $period, $limit = 10, int $offset = 0): array
	{
		$tags = DI::cache()->get("local_trending_tags-$period");
		if (empty($tags)) {
			$tags = self::setLocalTrendingHashtags($period, 1000);
		}
		return array_slice($tags, $offset, $limit);
	}

	/**
	 * Returns a list of the most frequent local hashtags over the given period
	 *
	 * @param int $period Period in hours to consider posts
	 * @param int $limit  Number of returned tags
	 * @return array
	 * @throws \Exception
	 */
	public static function setLocalTrendingHashtags(int $period, int $limit = 10): array
	{
		// Get a uri-id that is at least X hours old.
		// We use the uri-id in the query for the hash tags since this is much faster
		$post = Post::selectFirstThread(
			['uri-id'],
			["`uid` = ? AND `received` < ?", 0, DateTimeFormat::utc('now - ' . $period . ' hour')],
			['order' => ['received' => true]]
		);
		if (empty($post['uri-id'])) {
			return [];
		}

		$block_sql = self::getBlockedSQL();

		$tagsStmt = DBA::p(
			"SELECT `name` AS `term`, COUNT(*) AS `score`, COUNT(DISTINCT(`author-id`)) as `authors`
			FROM `tag-search-view`
			WHERE `private` = ? AND `wall` AND `origin` AND `uri-id` > ? $block_sql
			GROUP BY `term` ORDER BY `authors` DESC, `score` DESC LIMIT ?",
			Item::PUBLIC,
			$post['uri-id'],
			$limit
		);

		if (DBA::isResult($tagsStmt)) {
			$tags = DBA::toArray($tagsStmt);
			DI::cache()->set("local_trending_tags-$period", $tags, Duration::HOUR);
			return $tags;
		}

		return [];
	}

	/**
	 * Check if the provided tag is of one of the provided term types.
	 *
	 * @param string $tag Tag name
	 * @param int    ...$types
	 * @return bool
	 */
	public static function isType(string $tag, ...$types): bool
	{
		$tag_chars = [];
		foreach ($types as $type) {
			if (array_key_exists($type, self::TAG_CHARACTER)) {
				$tag_chars[] = self::TAG_CHARACTER[$type];
			}
		}

		return Strings::startsWithChars($tag, $tag_chars);
	}

	/**
	 * Fetch user who subscribed to the given tag
	 *
	 * @param string $tag
	 * @return array User list
	 */
	private static function getUIDListByTag(string $tag): array
	{
		$uids     = [];
		$searches = DBA::select('search', ['uid'], ['term' => $tag]);
		while ($search = DBA::fetch($searches)) {
			$uids[] = $search['uid'];
		}
		DBA::close($searches);

		return $uids;
	}

	/**
	 * Fetch user who subscribed to the tags of the given item
	 *
	 * @param integer $uriId
	 * @return array User list
	 */
	public static function getUIDListByURIId(int $uriId): array
	{
		$uids = [];

		foreach (self::getByURIId($uriId, [self::HASHTAG]) as $tag) {
			foreach (self::getUIDListByTag(self::TAG_CHARACTER[self::HASHTAG] . $tag['name']) as $uid) {
				$uids[$uid][] = $tag['name'];
			}
		}

		return $uids;
	}
}
