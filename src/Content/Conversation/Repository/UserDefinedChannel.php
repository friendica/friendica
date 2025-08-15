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
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DisposableFullTextSearch;
use Friendica\Model\Contact;
use Friendica\Model\Post\Engagement;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class UserDefinedChannel extends BaseRepository
{
	protected static $table_name = 'channel';

	/** @var UserDefinedChannelFactory */
	protected $factory;

	private IManageConfigValues $config;

	public function __construct(Database $database, LoggerInterface $logger, UserDefinedChannelFactory $factory, IManageConfigValues $config)
	{
		parent::__construct($database, $logger, $factory);

		$this->config = $config;
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
		if (empty($users)) {
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
		if (empty($users)) {
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

	private function getUserCondition(): array
	{
		$condition = ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `user`.`uid` > ?", 0];

		$abandon_days = intval($this->config->get('system', 'account_abandon_days'));
		if (!empty($abandon_days)) {
			$condition = DBA::mergeConditions($condition, ["`last-activity` > ?", DateTimeFormat::utc('now - ' . $abandon_days . ' days')]);
		}
		return $condition;
	}
}
