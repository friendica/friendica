<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Repository;

use Friendica\BaseRepository;
use Friendica\Content\Conversation\Collection\UserDefinedChannels;
use Friendica\Content\Conversation\Entity\UserDefinedChannel as UserDefinedChannelEntity;
use Friendica\Content\Conversation\Factory\UserDefinedChannel as UserDefinedChannelFactory;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DisposableFullTextSearch;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Engagement;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * Repository for user-defined channels.
 *
 * Encapsulates selection, matching and statistics needed to evaluate
 * user-defined channels for posts.
 *
 * @package Friendica\Content\Conversation\Repository
 */
class UserDefinedChannel extends BaseRepository
{
	protected static $table_name = 'channel';

	/** @var UserDefinedChannelFactory */
	protected $factory;

	private ICanCache $cache;
	private IManageConfigValues $config;

	/**
	 * UserDefinedChannel repository constructor.
	 *
	 * @param Database $database Database access layer.
	 * @param LoggerInterface $logger Logger instance.
	 * @param UserDefinedChannelFactory $factory Entity factory.
	 * @param IManageConfigValues $config Configuration manager.
	 * @param ICanCache $cache Cache capability.
	 */
	public function __construct(Database $database, LoggerInterface $logger, UserDefinedChannelFactory $factory, IManageConfigValues $config, ICanCache $cache)
	{
		parent::__construct($database, $logger, $factory);

		$this->config = $config;
		$this->cache  = $cache;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return UserDefinedChannels
	 * @throws \Exception
	 */
	protected function _select(array $condition, array $params = []): UserDefinedChannels
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new UserDefinedChannels();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields);
		}

		return $Entities;
	}

	/**
	 * Select user defined channels matching a condition.
	 *
	 * @param array $condition Query condition.
	 * @param array $params Additional parameters.
	 * @return UserDefinedChannels
	 * @throws \Exception
	 */
	public function select(array $condition, array $params = []): UserDefinedChannels
	{
		return $this->_select($condition, $params);
	}

	/**
	 * Fetch a single user channel
	 *
	 * @param int $id  The id of the user defined channel
	 * @param int $uid The user that this channel belongs to. (Not part of the primary key)
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectById(int $id, int $uid): UserDefinedChannelEntity
	{
		$fields = $this->_selectFirstRowAsArray(['id' => $id, 'uid' => $uid]);

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * Checks if the provided channel id exists for this user
	 *
	 * @param integer $id
	 * @param integer $uid
	 * @return boolean
	 */
	public function existsById(int $id, int $uid): bool
	{
		return $this->exists(['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Delete the given channel
	 *
	 * @param integer $id
	 * @param integer $uid
	 * @return boolean
	 */
	public function deleteById(int $id, int $uid): bool
	{
		return $this->db->delete(self::$table_name, ['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Fetch all user channels
	 *
	 * @param integer $uid
	 * @return UserDefinedChannels
	 * @throws \Exception
	 */
	public function selectByUid(int $uid): UserDefinedChannels
	{
		return $this->_select(['uid' => $uid]);
	}

	/**
	 * Persist a user defined channel.
	 *
	 * Inserts or updates the channel and returns the stored entity.
	 *
	 * @param UserDefinedChannelEntity $Channel Channel entity to save.
	 * @return UserDefinedChannelEntity
	 */
	public function save(UserDefinedChannelEntity $Channel): UserDefinedChannelEntity
	{
		$fields = [
			'label'            => $Channel->label,
			'description'      => $Channel->description,
			'access-key'       => $Channel->accessKey,
			'uid'              => $Channel->uid,
			'circle'           => $Channel->circle,
			'include-tags'     => $Channel->includeTags,
			'exclude-tags'     => $Channel->excludeTags,
			'min-size'         => $Channel->minSize,
			'max-size'         => $Channel->maxSize,
			'full-text-search' => $Channel->fullTextSearch,
			'media-type'       => $Channel->mediaType,
			'languages'        => !empty($Channel->languages) ? serialize($Channel->languages) : null,
			'publish'          => $Channel->publish,
			'valid'            => $this->isValid($Channel->fullTextSearch),
		];

		if ($Channel->code) {
			$this->db->update(self::$table_name, $fields, ['uid' => $Channel->uid, 'id' => $Channel->code]);
		} else {
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$newChannelId = $this->db->lastInsertId();

			$Channel = $this->selectById($newChannelId, $Channel->uid);
		}

		return $Channel;
	}

	/**
	 * Validate full-text search syntax by running a quick MATCH query.
	 *
	 * @param string $searchtext Search expression.
	 * @return bool True when valid.
	 */
	private function isValid(string $searchtext): bool
	{
		if ($searchtext == '') {
			return true;
		}

		return $this->db->select('check-full-text-search', [], ["`pid` = ? AND MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", getmypid(), Engagement::escapeKeywords($searchtext)]) !== false;
	}

	/**
	 * Checks if one of the user-defined channels matches the given language or item text via full-text search
	 *
	 * @param string $haystack
	 * @param string $language
	 * @return boolean
	 * @throws \Exception
	 */
	public function match(string $haystack, string $language): bool
	{
		$users = $this->db->selectToArray('user', ['uid'], $this->getUserCondition());
		if (count($users) === 0) {
			return false;
		}

		$uids = array_column($users, 'uid');

		$usercondition = ['uid' => $uids];
		$condition     = DBA::mergeConditions($usercondition, ["`languages` != ? AND `include-tags` = ? AND `full-text-search` = ? AND `circle` = ?", '', '', '', 0]);
		foreach ($this->select($condition) as $channel) {
			if (!empty($channel->languages) && in_array($language, $channel->languages)) {
				return true;
			}
		}

		$search    = '';
		$condition = DBA::mergeConditions($usercondition, ["`full-text-search` != ? AND `circle` = ? AND `valid`", '', 0]);
		foreach ($this->select($condition) as $channel) {
			$search .= '(' . $channel->fullTextSearch . ') ';
		}

		return (new DisposableFullTextSearch($this->db, $haystack))->match(Engagement::escapeKeywords($search));
	}

	/**
	 * List the IDs of the relay/group users that have matching user-defined channels based on an item details
	 *
	 * @param string $searchtext
	 * @param string $language
	 * @param array  $tags
	 * @param int    $media_type
	 * @param int    $owner_id
	 * @param int    $reshare_id
	 * @return array
	 * @throws \Exception
	 */
	public function getMatchingChannelUsers(string $searchtext, string $language, array $tags, int $media_type, int $owner_id, int $reshare_id): array
	{
		$condition = $this->getUserCondition();
		$condition = DBA::mergeConditions($condition, ["`account-type` IN (?, ?) AND `uid` != ?", User::ACCOUNT_TYPE_RELAY, User::ACCOUNT_TYPE_COMMUNITY, 0]);
		$users     = $this->db->selectToArray('user', ['uid'], $condition);
		if (count($users) === 0) {
			return [];
		}

		if (!in_array($language, User::getLanguages())) {
			$this->logger->debug('Unwanted language found. No matched channel found.', ['language' => $language, 'searchtext' => $searchtext]);
			return [];
		}

		$disposableFullTextSearch = new DisposableFullTextSearch($this->db, $searchtext);

		$filteredChannels = $this->select(['uid' => array_column($users, 'uid'), 'publish' => true, 'valid' => true])->filter(
			function (UserDefinedChannelEntity $channel) use ($owner_id, $reshare_id, $language, $tags, $media_type, $disposableFullTextSearch, $searchtext) {
				static $uids = [];

				// Filter out channels from already picked users
				if (in_array($channel->uid, $uids)) {
					return false;
				}

				if (
					($channel->circle ?? 0)
					&& !$this->inCircle($channel->circle, $channel->uid, $owner_id)
					&& !$this->inCircle($channel->circle, $channel->uid, $reshare_id)
				) {
					return false;
				}

				if (!in_array($language, $channel->languages ?: User::getWantedLanguages($channel->uid))) {
					return false;
				}

				if ($channel->includeTags && !$this->inTaglist($channel->includeTags, $tags)) {
					return false;
				}

				if ($channel->excludeTags && $this->inTaglist($channel->excludeTags, $tags)) {
					return false;
				}

				if ($channel->mediaType && !($channel->mediaType & $media_type)) {
					return false;
				}

				if ($channel->fullTextSearch && !$disposableFullTextSearch->match(Engagement::escapeKeywords($channel->fullTextSearch))) {
					return false;
				}

				$uids[] = $channel->uid;
				$this->logger->debug('Matching channel found.', ['uid' => $channel->uid, 'label' => $channel->label, 'language' => $language, 'tags' => $tags, 'media_type' => $media_type, 'searchtext' => $searchtext]);

				return true;
			}
		);

		return $filteredChannels->column('uid');
	}

	/**
	 * Get matching channels for a given post
	 *
	 * @param string $searchtext Search text for matching.
	 * @param string $language Language of the post.
	 * @param array  $tags Tags of the post.
	 * @param int    $media_type Media type of the post.
	 * @param int    $owner_id Owner contact id.
	 * @param int    $reshare_id Reshare contact id.
	 * @param array  $uids User IDs to filter channels.
	 * @param array  $circles circle IDs to filter channels.
	 * @return UserDefinedChannels|null Collection of matching channels or null.
	 */
	public function getMatchingChannels(string $searchtext, string $language, array $tags, int $media_type, int $owner_id, int $reshare_id, array $uids, array $circles): ?UserDefinedChannels
	{
		if (!in_array($language, User::getLanguages())) {
			$this->logger->debug('Unwanted language found. No matched channel found.', ['language' => $language, 'searchtext' => $searchtext]);
			return null;
		}

		$disposableFullTextSearch = new DisposableFullTextSearch($this->db, $searchtext);

		$condition = ['uid' => $uids, 'valid' => true];
		if ($circles) {
			$condition = DBA::mergeConditions($condition, ['circle' => $circles]);
		}

		$filteredChannels = $this->select($condition)->filter(
			function (UserDefinedChannelEntity $channel) use ($owner_id, $reshare_id, $language, $tags, $media_type, $disposableFullTextSearch, $searchtext) {
				if (
					($channel->circle > 0)
					&& !$this->inCircle($channel->circle, $channel->uid, $owner_id)
					&& !$this->inCircle($channel->circle, $channel->uid, $reshare_id)
				) {
					return false;
				}

				if (!in_array($language, $channel->languages ?: User::getWantedLanguages($channel->uid))) {
					return false;
				}

				if ($channel->includeTags && !$this->inTaglist($channel->includeTags, $tags)) {
					return false;
				}

				if ($channel->excludeTags && $this->inTaglist($channel->excludeTags, $tags)) {
					return false;
				}

				if ($channel->mediaType && !($channel->mediaType & $media_type)) {
					return false;
				}

				if ($channel->fullTextSearch && !$disposableFullTextSearch->match(Engagement::escapeKeywords($channel->fullTextSearch))) {
					return false;
				}

				$this->logger->debug('Matching channel found.', ['id' => $channel->code, 'label' => $channel->label, 'language' => $language, 'tags' => $tags, 'media_type' => $media_type, 'searchtext' => $searchtext]);

				return true;
			}
		);

		/** @var UserDefinedChannels $filteredChannels */
		return $filteredChannels;
	}

	/**
	 * Check whether a contact id belongs to a given circle for a user.
	 *
	 * @param int $circleId Circle id (group id).
	 * @param int $uid Owner user id.
	 * @param int $cid Contact public id (pid).
	 * @return bool True when contact is member of the circle.
	 */
	private function inCircle(int $circleId, int $uid, int $cid): bool
	{
		if ($cid == 0) {
			return false;
		}

		$account = Contact::selectFirstAccountUser(['id'], ['pid' => $cid, 'uid' => $uid]);
		if (empty($account['id'])) {
			return false;
		}
		return $this->db->exists('group_member', ['gid' => $circleId, 'contact-id' => $account['id']]);
	}

	/**
	 * Check if any of the provided tags exist in a comma-separated tag list.
	 *
	 * @param string $tagList Comma-separated tags.
	 * @param array $tags Tags to check (lowercased).
	 * @return bool True when at least one tag matches.
	 */
	private function inTaglist(string $tagList, array $tags): bool
	{
		if (empty($tags)) {
			return false;
		}
		array_walk($tags, function (&$value) {
			$value = mb_strtolower($value);
		});
		foreach (explode(',', $tagList) as $tag) {
			if (in_array($tag, $tags)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get users for a given post based on network and privacy settings
	 *
	 * @param int    $uri_id  The URI ID of the post
	 * @param int    $uid     The owner user ID
	 * @param string $network The network protocol of the post
	 * @param int    $private The privacy level of the post
	 * @return array Array of user IDs
	 */
	public function getUsersForPost(int $uri_id, int $uid, string $network, int $private): array
	{
		if (in_array($network, Protocol::FEDERATED)) {
			if ($private === Item::PRIVATE) {
				$posts = Post::selectToArray(['uid'], ["`uri-id` = ? AND `uid` != ?", $uri_id, 0]);
				if (!$posts) {
					return [];
				}
				$uids = array_column($posts, 'uid');
			} else {
				$condition = $this->getUserCondition();
				$condition = DBA::mergeConditions($condition, ["NOT `account-type` IN (?, ?)", User::ACCOUNT_TYPE_RELAY, User::ACCOUNT_TYPE_COMMUNITY]);
				$users     = $this->db->selectToArray('user', ['uid'], $condition);
				if (!$users) {
					return [];
				}
				$uids = array_column($users, 'uid');
			}
		} else {
			$uids = [$uid];
		}
		return $uids;
	}

	/**
	 * Build a base condition for selecting valid users.
	 *
	 * Respects account verification, block and expiry flags and optional
	 * abandonment days configured in the system.
	 *
	 * @return array Condition usable with the database layer.
	 */
	public function getUserCondition(): array
	{
		$condition = ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `user`.`uid` > ?", 0];

		$abandon_days = intval($this->config->get('system', 'account_abandon_days'));
		if (!empty($abandon_days)) {
			$condition = DBA::mergeConditions($condition, ["`last-activity` > ?", DateTimeFormat::utc('now - ' . $abandon_days . ' days')]);
		}
		return $condition;
	}

	/**
	 * Build a condition to find posts matching a user-defined channel.
	 *
	 * The returned condition can be passed to selects on the
	 * `post-engagement`/`post` tables.
	 *
	 * @param UserDefinedChannelEntity $channel Channel definition.
	 * @param int $uid User id context.
	 * @return array Condition and parameters.
	 */
	public function getCondition(UserDefinedChannelEntity $channel, int $uid): array
	{
		$condition = [];

		if (!empty($channel->circle)) {
			if ($channel->circle == UserDefinedChannelEntity::CIRCLE_FOLLOWING) {
				$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?))", $uid, Contact::SHARING, Contact::FRIEND];
			} elseif ($channel->circle == UserDefinedChannelEntity::CIRCLE_FOLLOWERS) {
				$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", $uid, Contact::FOLLOWER];
			} elseif ($channel->circle > 0) {
				$condition = DBA::mergeConditions($condition, ["`owner-id` IN (SELECT `pid` FROM `group_member` INNER JOIN `account-user-view` ON `group_member`.`contact-id` = `account-user-view`.`id` WHERE `gid` = ? AND `account-user-view`.`uid` = ?)", $channel->circle, $uid]);
			}
		}

		if (!empty($channel->fullTextSearch)) {
			if (!empty($channel->includeTags)) {
				$additional = $this->addIncludeTags($channel->includeTags);
			} else {
				$additional = '';
			}

			if (!empty($channel->excludeTags)) {
				foreach (explode(',', mb_strtolower($channel->excludeTags)) as $tag) {
					$additional .= ' -tag:' . $tag;
				}
			}

			if (!empty($channel->mediaType)) {
				$additional .= $this->addMediaTerms($channel->mediaType);
			}

			$additional .= $this->addLanguageSearchTerms($uid, $channel->languages);

			if ($additional) {
				$searchterms = '+(' . trim($channel->fullTextSearch) . ')' . $additional;
			} else {
				$searchterms = $channel->fullTextSearch;
			}

			$condition = DBA::mergeConditions($condition, ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", Engagement::escapeKeywords($searchterms)]);
		} else {
			if (!empty($channel->includeTags)) {
				$search       = explode(',', mb_strtolower($channel->includeTags));
				$placeholders = substr(str_repeat("?, ", count($search)), 0, -2);
				$condition    = DBA::mergeConditions($condition, array_merge(["`uri-id` IN (SELECT `uri-id` FROM `post-tag` INNER JOIN `tag` ON `tag`.`id` = `post-tag`.`tid` WHERE `post-tag`.`type` = 1 AND `name` IN (" . $placeholders . "))"], $search));
			}

			if (!empty($channel->excludeTags)) {
				$search       = explode(',', mb_strtolower($channel->excludeTags));
				$placeholders = substr(str_repeat("?, ", count($search)), 0, -2);
				$condition    = DBA::mergeConditions($condition, array_merge(["NOT `uri-id` IN (SELECT `uri-id` FROM `post-tag` INNER JOIN `tag` ON `tag`.`id` = `post-tag`.`tid` WHERE `post-tag`.`type` = 1 AND `name` IN (" . $placeholders . "))"], $search));
			}

			if (!empty($channel->mediaType)) {
				$condition = DBA::mergeConditions($condition, ["`media-type` & ?", $channel->mediaType]);
			}

			// For "addLanguageCondition" to work, the condition must not be empty
			$condition = $this->addLanguageCondition($uid, $condition ?: ["true"], $channel->languages);
		}

		if (!is_null($channel->minSize)) {
			$condition = DBA::mergeConditions($condition, ["`size` >= ?", $channel->minSize]);
		}

		if (!is_null($channel->maxSize)) {
			$condition = DBA::mergeConditions($condition, ["`size` <= ?", $channel->maxSize]);
		}

		if (in_array($channel->circle, [UserDefinedChannelEntity::CIRCLE_CREATION, UserDefinedChannelEntity::CIRCLE_POSTS, UserDefinedChannelEntity::CIRCLE_ACTIVITY])) {
			$condition = DBA::mergeConditions($condition, ['uid' => $uid]);
		}

		return $condition;
	}

	/**
	 * Convert include tag list into full-text search tag terms.
	 *
	 * @param string $includeTags Comma-separated include tags.
	 * @return string Full-text search fragment or empty string.
	 */
	private function addIncludeTags(string $includeTags): string
	{
		$tagterms = '';
		foreach (explode(',', mb_strtolower($includeTags)) as $tag) {
			$tagterms .= ' tag:' . $tag;
		}

		if ($tagterms) {
			return ' +(' . trim($tagterms) . ')';
		} else {
			return '';
		}
	}

	/**
	 * Convert media type flags into full-text search media terms.
	 *
	 * @param int $mediaType Media type bitmask.
	 * @return string Full-text search fragment or empty string.
	 */
	private function addMediaTerms(int $mediaType): string
	{
		$mediaterms = '';
		if ($mediaType & 1) {
			$mediaterms .= ' media:image';
		}

		if ($mediaType & 2) {
			$mediaterms .= ' media:video';
		}

		if ($mediaType & 4) {
			$mediaterms .= ' media:audio';
		}

		if ($mediaterms) {
			return ' +(' . trim($mediaterms) . ')';
		} else {
			return '';
		}
	}

	/**
	 * Build language search terms for full-text queries.
	 *
	 * @param int $uid User id for default wanted languages.
	 * @param array|null $languages Optional explicit languages.
	 * @return string Full-text language fragment or empty string.
	 */
	private function addLanguageSearchTerms(int $uid, $languages = null): string
	{
		$langterms = '';
		foreach ($languages ?: User::getWantedLanguages($uid) as $language) {
			$langterms .= ' language:' . $language;
		}

		if ($langterms) {
			return ' +(' . trim($langterms) . ')';
		} else {
			return '';
		}
	}

	/**
	 * Add language constraints to an existing condition array.
	 *
	 * @param int $uid User id for default wanted languages.
	 * @param array $condition Existing condition array to extend.
	 * @param array|null $languages Optional explicit languages.
	 * @return array Modified condition array.
	 */
	public function addLanguageCondition(int $uid, array $condition, $languages = null): array
	{
		$conditions = [];
		foreach ($languages ?: User::getWantedLanguages($uid) as $language) {
			$conditions[] = "`language` = ?";
			$condition[]  = $language;
		}

		if (!empty($conditions)) {
			$condition[0] .= " AND (" . implode(' OR ', $conditions) . ")";
		}
		return $condition;
	}

	/**
	 * Compute a median-like post score for a contact id.
	 *
	 * Uses caching to avoid repeated heavy queries.
	 *
	 * @param int $cid Contact id.
	 * @param int $divider Divider to compute the median index.
	 * @return int Median post score.
	 */
	public function getMedianPostScore(int $cid, int $divider): int
	{
		$cache_key = 'Channel:getPostScore:' . $cid . ':' . $divider;
		$score     = $this->cache->get($cache_key);
		if (!empty($score)) {
			return $score;
		}

		$condition = ["`relation-cid` = ? AND `post-score` > ?", $cid, 0];

		$limit    = $this->db->count('contact-relation', $condition) / $divider;
		$relation = $this->db->selectToArray('contact-relation', ['post-score'], $condition, ['order' => ['post-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['post-score'] ?? 0;
		if ($score === 0) {
			return 0;
		}

		$this->cache->set($cache_key, $score, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median score', ['cid' => $cid, 'divider' => $divider, 'median' => $score]);
		return $score;
	}

	/**
	 * Compute median comments for a user's wanted languages.
	 *
	 * @param int $uid User id.
	 * @param int $divider Divider used to determine median index.
	 * @param string $network Optional network filter.
	 * @return int Median comments value.
	 */
	public function getMedianComments(int $uid, int $divider, string $network = ''): int
	{
		$languages = User::getWantedLanguages($uid);
		$cache_key = 'Channel:getMedianComments:' . $divider . ':' . implode(':', $languages) . $network;
		$comments  = $this->cache->get($cache_key);
		if (!empty($comments)) {
			return $comments;
		}

		$condition = ["`contact-type` != ? AND `comments` > ? AND NOT `restricted`", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		if ($network) {
			$condition = DBA::mergeConditions($condition, ["`network` = ?", $network]);
		}

		$limit    = $this->db->count('post-engagement', $condition) / $divider;
		$post     = $this->db->selectToArray('post-engagement', ['comments'], $condition, ['order' => ['comments' => true], 'limit' => [$limit, 1]]);
		$comments = $post[0]['comments'] ?? 0;
		if (empty($comments)) {
			return 0;
		}

		$this->cache->set($cache_key, $comments, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median comments', ['divider' => $divider, 'languages' => $languages, 'network' => $network, 'median' => $comments]);
		return $comments;
	}

	/**
	 * Compute median activities for a user's wanted languages.
	 *
	 * @param int $uid User id.
	 * @param int $divider Divider used to determine median index.
	 * @param string $network Optional network filter.
	 * @return int Median activities value.
	 */
	public function getMedianActivities(int $uid, int $divider, string $network = ''): int
	{
		$languages  = User::getWantedLanguages($uid);
		$cache_key  = 'Channel:getMedianActivities:' . $divider . ':' . implode(':', $languages) . $network;
		$activities = $this->cache->get($cache_key);
		if (!empty($activities)) {
			return $activities;
		}

		$condition = ["`contact-type` != ? AND `activities` > ? AND NOT `restricted`", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		if ($network) {
			$condition = DBA::mergeConditions($condition, ["`network` = ?", $network]);
		}

		$limit      = $this->db->count('post-engagement', $condition) / $divider;
		$post       = $this->db->selectToArray('post-engagement', ['activities'], $condition, ['order' => ['activities' => true], 'limit' => [$limit, 1]]);
		$activities = $post[0]['activities'] ?? 0;
		if (empty($activities)) {
			return 0;
		}

		$this->cache->set($cache_key, $activities, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median activities', ['divider' => $divider, 'languages' => $languages, 'network' => $network, 'median' => $activities]);
		return $activities;
	}

	/**
	 * Compute median views for a user's wanted languages.
	 *
	 * @param int $uid User id.
	 * @param int $divider Divider used to determine median index.
	 * @param string $network Optional network filter.
	 * @return int Median views value.
	 */
	public function getMedianViews(int $uid, int $divider, string $network = ''): int
	{
		$languages = User::getWantedLanguages($uid);
		$cache_key = 'Channel:getMedianViews:' . $divider . ':' . implode(':', $languages) . $network;
		$views     = $this->cache->get($cache_key);
		if (!empty($views)) {
			return $views;
		}

		$condition = ["`contact-type` != ? AND `views` > ? AND NOT `restricted`", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		if ($network) {
			$condition = DBA::mergeConditions($condition, ["`network` = ?", $network]);
		}

		$limit = $this->db->count('post-engagement', $condition) / $divider;
		$post  = $this->db->selectToArray('post-engagement', ['views'], $condition, ['order' => ['views' => true], 'limit' => [$limit, 1]]);
		$views = $post[0]['views'] ?? 0;
		if (empty($views)) {
			return 0;
		}

		$this->cache->set($cache_key, $views, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median views', ['divider' => $divider, 'languages' => $languages, 'network' => $network, 'median' => $views]);
		return $views;
	}

	/**
	 * Compute median relation thread score for a contact id.
	 *
	 * @param int $cid Contact id.
	 * @param int $divider Divider to compute the median index.
	 * @return int Median relation thread score.
	 */
	public function getMedianRelationThreadScore(int $cid, int $divider): int
	{
		$cache_key = 'Channel:getThreadScore:' . $cid . ':' . $divider;
		$score     = $this->cache->get($cache_key);
		if (!empty($score)) {
			return $score;
		}

		$condition = ["`relation-cid` = ? AND `relation-thread-score` > ?", $cid, 0];

		$limit    = $this->db->count('contact-relation', $condition) / $divider;
		$relation = $this->db->selectToArray('contact-relation', ['relation-thread-score'], $condition, ['order' => ['relation-thread-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['relation-thread-score'] ?? 0;
		if ($score === 0) {
			return 0;
		}

		$this->cache->set($cache_key, $score, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median score', ['cid' => $cid, 'divider' => $divider, 'median' => $score]);
		return $score;
	}
}
