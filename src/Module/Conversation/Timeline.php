<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Conversation;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\BaseModule;
use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Conversation\Entity\Channel as ChannelEntity;
use Friendica\Content\Conversation\Entity\Community;
use Friendica\Content\Conversation\Entity\UserDefinedChannel as EntityUserDefinedChannel;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\SearchIndex;
use Friendica\Model\Verb;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Timeline extends BaseModule
{
	/** @var string */
	protected $selectedTab;
	/** @var mixed */
	protected $minId;
	/** @var mixed */
	protected $maxId;
	/** @var string */
	protected $accountTypeString;
	/** @var int */
	protected $accountType;
	/** @var int */
	protected $itemUriId;
	/** @var int */
	protected $itemsPerPage;
	/** @var bool */
	protected $noSharer;
	/** @var bool */
	protected $force;
	/** @var bool */
	protected $update;
	/** @var bool */
	protected $ping;
	/** @var bool */
	protected $raw;
	/** @var string */
	protected $order;
	/** @var string */
	protected $network;

	/** @var Mode $mode */
	protected $mode;
	/** @var IHandleUserSessions */
	protected $session;
	/** @var Database */
	protected $database;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var IManageConfigValues The config */
	protected $config;
	/** @var ICanCache */
	protected $cache;
	/** @var UserDefinedChannel */
	protected $channelRepository;

	public function __construct(UserDefinedChannel $channel, Mode $mode, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server = [], array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->channelRepository = $channel;
		$this->mode              = $mode;
		$this->session           = $session;
		$this->database          = $database;
		$this->pConfig           = $pConfig;
		$this->config            = $config;
		$this->cache             = $cache;
	}

	/**
	 * Computes module parameters from the request and local configuration
	 *
	 * @throws BadRequestException
	 * @throws ForbiddenException
	 */
	protected function parseRequest(array $request)
	{
		$this->logger->debug('Got request', $request);
		$this->selectedTab = $this->parameters['content'] ?? $request['channel'] ?? '';

		$this->accountTypeString = $request['accounttype'] ?? $this->parameters['accounttype'] ?? '';
		$this->accountType       = User::getAccountTypeByString($this->accountTypeString);

		if ($this->mode->isMobile()) {
			$this->itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				$this->config->get('system', 'itemspage_network_mobile'),
			);
		} else {
			$this->itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_network',
				$this->config->get('system', 'itemspage_network'),
			);
		}

		if (!empty($request['item'])) {
			$item            = Post::selectFirst(['parent', 'parent-uri-id'], ['id' => $request['item']]);
			$this->itemUriId = $item['parent-uri-id'] ?? 0;
		} else {
			$this->itemUriId = 0;
		}

		$this->order = 'created';

		$this->minId = $request['min_id'] ?? null;
		$this->maxId = $request['max_id'] ?? null;

		$this->noSharer = !empty($request['no_sharer']);
		$this->force    = !empty($request['force']) && !empty($request['item']);
		$this->update   = !empty($request['force']) && !empty($request['first_received']) && !empty($request['first_created']) && !empty($request['first_uriid']) && !empty($request['first_commented']);
		$this->raw      = !empty($request['mode']) && ($request['mode'] == 'raw');
	}

	protected function setMaxMinByOrder(array $request)
	{
		switch ($this->order) {
			case 'received':
				$this->maxId = $request['last_received']  ?? $this->maxId;
				$this->minId = $request['first_received'] ?? $this->minId;
				break;
			case 'created':
				$this->maxId = $request['last_created']  ?? $this->maxId;
				$this->minId = $request['first_created'] ?? $this->minId;
				break;
			case 'uri-id':
				$this->maxId = $request['last_uriid']  ?? $this->maxId;
				$this->minId = $request['first_uriid'] ?? $this->minId;
				break;
			default:
				$this->order = 'commented';
				$this->maxId = $request['last_commented']  ?? $this->maxId;
				$this->minId = $request['first_commented'] ?? $this->minId;
		}
	}

	protected function getNoSharerWidget(string $base): string
	{
		$path = $this->selectedTab;

		$query_parameters = [];
		if (!empty($this->accountTypeString)) {
			$query_parameters['accounttype'] = $this->accountTypeString;
		}
		if (!empty($this->minId)) {
			$query_parameters['min_id'] = $this->minId;
		}
		if (!empty($this->maxId)) {
			$query_parameters['max_id'] = $this->maxId;
		}

		$path_all       = $path . (!empty($query_parameters) ? '?' . http_build_query($query_parameters) : '');
		$path_no_sharer = $path . '?' . http_build_query(array_merge($query_parameters, ['no_sharer' => true]));
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/community_sharer.tpl'), [
			'$title'           => $this->l10n->t('Own Contacts'),
			'$path_all'        => $path_all,
			'$path_no_sharer'  => $path_no_sharer,
			'$no_sharer'       => $this->noSharer,
			'$all'             => $this->l10n->t('Include'),
			'$no_sharer_label' => $this->l10n->t('Hide'),
			'$base'            => $base,
		]);
	}

	protected function getTabArray(Timelines $timelines, string $prefix, string $parameter = ''): array
	{
		$tabs = [];

		foreach ($timelines as $tab) {
			if (is_null($tab->path) && !empty($parameter)) {
				$path = $prefix . '?' . http_build_query([$parameter => $tab->code]);
			} else {
				$path = $tab->path ?? $prefix . '/' . $tab->code;
			}
			$tabs[$tab->code] = [
				'code'      => $tab->code,
				'label'     => $tab->label,
				'url'       => $path,
				'sel'       => $this->selectedTab == $tab->code ? 'active' : '',
				'title'     => $tab->description,
				'id'        => $prefix . '-' . $tab->code . '-tab',
				'accesskey' => $tab->accessKey,
			];
		}
		return $tabs;
	}

	public function getChannelItemsForAPI(string $channel, int $uid, int $limit, int $min = null, int $max = null): array
	{
		$this->itemsPerPage = $limit;
		$this->itemUriId    = 0;
		$this->maxId        = $max;
		$this->minId        = $min;
		$this->noSharer     = false;
		$this->order        = 'uri-id';
		$this->ping         = false;
		$this->selectedTab  = $channel;

		return $this->getChannelItems([], $uid);
	}

	/**
	 * Database query for the channel page
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getChannelItems(array $request, int $uid): array
	{
		$items = $this->getRawChannelItems($request, $uid);
		$total = min(count($items), $this->itemsPerPage);

		$contacts = $this->database->selectToArray('user-contact', ['cid'], ['channel-frequency' => Contact\User::FREQUENCY_REDUCED, 'cid' => array_column($items, 'owner-id')]);
		$reduced  = array_column($contacts, 'cid');

		$maxpostperauthor = $this->config->get('channel', 'max_posts_per_author');

		if ($maxpostperauthor != 0) {
			$count          = 1;
			$owner_posts    = [];
			$selected_items = [];

			while (count($selected_items) < $total && ++$count < 50 && count($items) > 0) {
				$maxposts = round((count($items) / $total) * $maxpostperauthor);
				$minId    = $items[array_key_first($items)][$this->order];
				$maxId    = $items[array_key_last($items)][$this->order];

				foreach ($items as $item) {
					if (!in_array($item['owner-id'], $reduced)) {
						continue;
					}
					$owner_posts[$item['owner-id']][$item['uri-id']] = (($item['comments'] * 100) + $item['activities']);
				}
				foreach ($owner_posts as $posts) {
					if (count($posts) <= $maxposts) {
						continue;
					}
					asort($posts);
					while (count($posts) > $maxposts) {
						$uri_id = array_key_first($posts);
						unset($posts[$uri_id]);
						unset($items[$uri_id]);
					}
				}
				$selected_items = array_merge($selected_items, $items);

				// If we're looking at a "previous page", the lookup continues forward in time because the list is
				// sorted in chronologically decreasing order
				if (!empty($this->minId)) {
					$this->minId = $minId;
				} else {
					// In any other case, the lookup continues backwards in time
					$this->maxId = $maxId;
				}

				if (count($selected_items) < $total) {
					$items = $this->getRawChannelItems($request, $uid);
				}
			}
		} else {
			$selected_items = $items;
		}

		$this->setItemsSeenForUser($uid);

		return $selected_items;
	}

	/**
	 * Database query for the channel page
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getRawChannelItems(array $request, int $uid): array
	{
		$cache = $this->config->get('system', 'system_channel_cache');

		if ($cache) {
			$table     = 'system-channel-post-view';
			$condition = ["`channel` = ? AND `uid` = ?", $this->selectedTab, $uid];
		} else {
			$table     = 'post-engagement';
			$condition = [];
		}

		if ($this->selectedTab == ChannelEntity::WHATSHOT) {
			if (!$cache) {
				if (!is_null($this->accountType)) {
					$condition = ["(`comments` > ? OR `activities` > ? OR `views` > ?) AND `contact-type` = ?", $this->channelRepository->getMedianComments($uid, 4), $this->channelRepository->getMedianActivities($uid, 4), $this->channelRepository->getMedianViews($uid, 4), $this->accountType];
				} else {
					$condition = ["(`comments` > ? OR `activities` > ? OR `views` > ?) AND `contact-type` != ?", $this->channelRepository->getMedianComments($uid, 4), $this->channelRepository->getMedianActivities($uid, 4), $this->channelRepository->getMedianViews($uid, 4), Contact::TYPE_COMMUNITY];
				}
			}
		} elseif ($this->selectedTab == ChannelEntity::FORYOU) {
			if (!$cache) {
				$cid = Contact::getPublicIdByUserId($uid);

				$condition = [
					"(`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `relation-thread-score` > ?) OR
					((`comments` >= ? OR `activities` >= ? OR `views` >= ?) AND `owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ?)) OR
					(`owner-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`notify_new_posts` OR `channel-frequency` = ?))))",
					$cid, $this->channelRepository->getMedianRelationThreadScore($cid, 4), $this->channelRepository->getMedianComments($uid, 4), $this->channelRepository->getMedianActivities($uid, 4), $this->channelRepository->getMedianViews($uid, 4), $cid,
					$uid, Contact\User::FREQUENCY_ALWAYS,
				];
			}
		} elseif ($this->selectedTab == ChannelEntity::DISCOVER) {
			if (!$cache) {
				$cid = Contact::getPublicIdByUserId($uid);

				$condition = [
					"`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND NOT `follows`) AND
					(`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND NOT `follows` AND `relation-thread-score` > ?) OR
					`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `cid` = ? AND `relation-thread-score` > ?) OR
					((`comments` >= ? OR `activities` >= ? OR `views` >= ?) AND
					(`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `cid` = ? AND `relation-thread-score` > ?)) OR
					(`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `relation-thread-score` > ?))))",
					$cid, $cid, $this->channelRepository->getMedianRelationThreadScore($cid, 4), $cid, $this->channelRepository->getMedianRelationThreadScore($cid, 4),
					$this->channelRepository->getMedianComments($uid, 4), $this->channelRepository->getMedianActivities($uid, 4), $this->channelRepository->getMedianViews($uid, 4), $cid, 0, $cid, 0,
				];
			}
		} elseif ($this->selectedTab == ChannelEntity::FOLLOWERS) {
			if (!$cache) {
				$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", $uid, Contact::FOLLOWER];
			}
		} elseif ($this->selectedTab == ChannelEntity::SHARERSOFSHARERS) {
			if (!$cache) {
				$cid = Contact::getPublicIdByUserId($uid);

				// @todo Suggest posts from contacts that are followed most by our followers
				$condition = [
					"`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `last-interaction` > ?
					AND `relation-cid` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ? AND `relation-thread-score` >= ?)
					AND NOT `cid` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ?))",
					DateTimeFormat::utc('now - ' . $this->config->get('channel', 'sharer_interaction_days') . ' day'), $cid, $this->channelRepository->getMedianRelationThreadScore($cid, 4), $cid,
				];
			}
		} elseif ($this->selectedTab == ChannelEntity::QUIETSHARERS) {
			if (!$cache) {
				$cid = Contact::getPublicIdByUserId($uid);

				$condition = [
					"`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ? AND `post-score` <= ?)",
					$cid, $this->channelRepository->getMedianPostScore($cid, 2),
				];
			}
		} elseif ($this->selectedTab == ChannelEntity::IMAGE) {
			if (!$cache) {
				$condition = ["`media-type` & ?", 1];
			}
		} elseif ($this->selectedTab == ChannelEntity::VIDEO) {
			if (!$cache) {
				$condition = ["`media-type` & ?", 2];
			}
		} elseif ($this->selectedTab == ChannelEntity::AUDIO) {
			if (!$cache) {
				$condition = ["`media-type` & ?", 4];
			}
		} elseif ($this->selectedTab == ChannelEntity::LANGUAGE) {
			if (!$cache) {
				$condition = ["`language` = ?", User::getLanguageCode($uid)];
			}
		} elseif (is_numeric($this->selectedTab) && !empty($channel = $this->channelRepository->selectById($this->selectedTab, $uid))) {
			if (!$this->config->get('system', 'channel_cache')) {
				$condition = $this->channelRepository->getCondition($channel, $uid);
				if (in_array($channel->circle, [EntityUserDefinedChannel::CIRCLE_CREATION, EntityUserDefinedChannel::CIRCLE_POSTS, EntityUserDefinedChannel::CIRCLE_ACTIVITY])) {
					$table     = SearchIndex::getSearchView();
					$condition = DBA::mergeConditions($condition, ['uid' => $uid]);
				}
			} else {
				$condition = ['channel' => $this->selectedTab];
				$table     = 'channel-post-view';
			}
			if (in_array($channel->circle, [EntityUserDefinedChannel::CIRCLE_CREATION, EntityUserDefinedChannel::CIRCLE_POSTS, EntityUserDefinedChannel::CIRCLE_ACTIVITY])) {
				$orders = [
					EntityUserDefinedChannel::CIRCLE_CREATION => 'created',
					EntityUserDefinedChannel::CIRCLE_POSTS    => 'received',
					EntityUserDefinedChannel::CIRCLE_ACTIVITY => 'commented',
				];
				$this->order = $orders[(int) $channel->circle];
			}
		}

		$this->setMaxMinByOrder($request);

		if (!empty($this->network)) {
			$condition = DBA::mergeConditions($condition, ['network' => $this->network]);
		}

		if (($this->selectedTab != ChannelEntity::LANGUAGE) && !is_numeric($this->selectedTab)) {
			$condition = $this->channelRepository->addLanguageCondition($uid, $condition);
		}

		$condition = DBA::mergeConditions($condition, ["(NOT `restricted` OR EXISTS(SELECT `id` FROM `post-user` WHERE `uid` = ? AND `uri-id` = `$table`.`uri-id`))", $uid]);

		$condition = DBA::mergeConditions($condition, ["NOT EXISTS(SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `$table`.`owner-id` AND (`ignored` OR `blocked` OR `collapsed` OR `is-blocked` OR `channel-frequency` = ?))", $uid, Contact\User::FREQUENCY_NEVER]);

		if (($this->selectedTab != ChannelEntity::WHATSHOT) && !is_null($this->accountType)) {
			$condition = DBA::mergeConditions($condition, ['contact-type' => $this->accountType]);
		}

		$params = ['order' => [$this->order => true], 'limit' => $this->itemsPerPage];

		if (!empty($this->itemUriId)) {
			$condition = DBA::mergeConditions($condition, ['uri-id' => $this->itemUriId]);
		} else {
			if ($this->noSharer) {
				$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `$table`.`uri-id`)", $this->session->getLocalUserId()]);
			}

			if (isset($this->maxId)) {
				$condition = DBA::mergeConditions($condition, ["`$this->order` < ?", $this->maxId]);
			}

			if (isset($this->minId)) {
				$condition = DBA::mergeConditions($condition, ["`$this->order` > ?", $this->minId]);

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset($this->maxId)) {
					$params['order'][$this->order] = false;
				}
			}
		}

		$items    = [];
		$fields   = ['uri-id', 'owner-id', 'comments', 'activities'];
		$fields[] = $this->order;
		$result   = $this->database->select($table, $fields, $condition, $params);
		if ($this->database->errorNo()) {
			throw new \Exception($this->database->errorMessage(), $this->database->errorNo());
		}

		while ($item = $this->database->fetch($result)) {
			$items[$item['uri-id']] = $item;
		}
		$this->database->close($result);

		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty($this->itemUriId) && isset($this->minId) && !isset($this->maxId)) {
			$items = array_reverse($items, true);
		}

		$this->setItemsSeenForUser($uid);

		return $items;
	}

	/**
	 * Computes the displayed items.
	 *
	 * Community pages have a restriction on how many successive posts by the same author can show on any given page,
	 * so we may have to retrieve more content beyond the first query
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getCommunityItems()
	{
		$items = $this->selectItems();
		$key   = '';

		$maxpostperauthor = 0;
		if ($this->selectedTab == Community::LOCAL) {
			$maxpostperauthor = (int) $this->config->get('system', 'max_author_posts_community_page');
			$key              = 'author-id';
		} elseif ($this->selectedTab == Community::GLOBAL) {
			$maxpostperauthor = (int) $this->config->get('system', 'max_server_posts_community_page');
			$key              = 'author-gsid';
		}

		if ($maxpostperauthor === 0) {
			return $items;
		}

		$count          = 1;
		$author_posts   = [];
		$selected_items = [];

		while (count($selected_items) < $this->itemsPerPage && ++$count < 50 && count($items) > 0) {
			$maxposts = round((count($items) / $this->itemsPerPage) * $maxpostperauthor);
			$minId    = $items[array_key_first($items)]['received'];
			$maxId    = $items[array_key_last($items)]['received'];

			foreach ($items as $item) {
				$author_posts[$item[$key]][$item['uri-id']] = $item['received'];
			}
			foreach ($author_posts as $posts) {
				if (count($posts) <= $maxposts) {
					continue;
				}
				asort($posts);
				while (count($posts) > $maxposts) {
					$uri_id = array_key_first($posts);
					unset($posts[$uri_id]);
					unset($items[$uri_id]);
				}
			}
			$selected_items = array_merge($selected_items, $items);

			// If we're looking at a "previous page", the lookup continues forward in time because the list is
			// sorted in chronologically decreasing order
			if (!empty($this->minId)) {
				$this->minId = $minId;
			} else {
				// In any other case, the lookup continues backwards in time
				$this->maxId = $maxId;
			}

			if (count($selected_items) < $this->itemsPerPage) {
				$items = $this->selectItems();
			}
		}

		return $selected_items;
	}

	/**
	 * Database query for the community page
	 *
	 * @return array
	 * @throws \Exception
	 * @TODO Move to repository/factory
	 */
	private function selectItems()
	{
		$this->order = 'received';

		if ($this->selectedTab == Community::LOCAL) {
			$condition = ["`wall` AND `origin` AND `private` = ?", Item::PUBLIC];
		} elseif ($this->selectedTab == 'global') {
			$condition = ["`uid` = ? AND `private` = ?", 0, Item::PUBLIC];
		} else {
			return [];
		}

		if (!is_null($this->accountType)) {
			$condition = DBA::mergeConditions($condition, ['owner-contact-type' => $this->accountType]);
		}

		$params = ['order' => ['received' => true], 'limit' => $this->itemsPerPage];

		if (!empty($this->itemUriId)) {
			$condition = DBA::mergeConditions($condition, ['uri-id' => $this->itemUriId]);
		} else {
			if ($this->session->getLocalUserId() && $this->noSharer) {
				$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-thread-user-view`.`uri-id`)", $this->session->getLocalUserId()]);
			}

			if (isset($this->maxId)) {
				$condition = DBA::mergeConditions($condition, ["`received` < ?", $this->maxId]);
			}

			if (isset($this->minId)) {
				$condition = DBA::mergeConditions($condition, ["`received` > ?", $this->minId]);

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset($this->maxId)) {
					$params['order']['received'] = false;
				}
			}
		}

		$items = [];
		if ($this->selectedTab == Community::LOCAL) {
			$result = Post::selectOriginThread(['uri-id', 'received', 'author-id', 'author-gsid'], $condition, $params);
		} else {
			$result = Post::selectThreadForUser($this->session->getLocalUserId() ?: 0, ['uri-id', 'received', 'author-id', 'author-gsid'], $condition, $params);
		}

		while ($item = $this->database->fetch($result)) {
			$item['comments'] = 0;

			$items[$item['uri-id']] = $item;
		}
		$this->database->close($result);

		if (empty($items)) {
			return [];
		}

		$uriids = array_keys($items);

		foreach (Post\Counts::get(['parent-uri-id' => $uriids, 'vid' => Verb::getID(Activity::POST)]) as $count) {
			$items[$count['parent-uri-id']]['comments'] += $count['count'];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty($this->itemUriId) && isset($this->minId) && !isset($this->maxId)) {
			$items = array_reverse($items);
		}

		return $items;
	}

	/**
	 * Sets all unseen items for a user as seen
	 * @param int $uid User ID
	 */
	protected function setItemsSeenForUser(int $uid)
	{
		$posts = Post::getUnseenPosts($uid);
		if (!empty($posts)) {
			Item::update(['unseen' => false], ['unseen' => true, 'uid' => $uid, 'uri-id' => $posts]);
		}

		if (count($posts) == 100) {
			Worker::add(Worker::PRIORITY_MEDIUM, 'SetSeen', $uid);
		}
	}
}
