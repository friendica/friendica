<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Conversation;

use Friendica\App;
use Friendica\App\Mode;
use Friendica\BaseModule;
use Friendica\Content\Conversation\Collection\Timelines;
use Friendica\Content\Conversation\Entity\Channel as ChannelEntity;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Engagement;
use Friendica\Module\Response;
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
	protected $raw;

	/** @var App\Mode $mode */
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

	public function __construct(UserDefinedChannel $channel, Mode $mode, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
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
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
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
				$this->config->get('system', 'itemspage_network_mobile')
			);
		} else {
			$this->itemsPerPage = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_network',
				$this->config->get('system', 'itemspage_network')
			);
		}

		if (!empty($request['item'])) {
			$item            = Post::selectFirst(['parent', 'parent-uri-id'], ['id' => $request['item']]);
			$this->itemUriId = $item['parent-uri-id'] ?? 0;
		} else {
			$this->itemUriId = 0;
		}

		$this->minId = $request['min_id'] ?? null;
		$this->maxId = $request['max_id'] ?? null;

		$this->noSharer = !empty($request['no_sharer']);
		$this->force    = !empty($request['force']) && !empty($request['item']);
		$this->update   = !empty($request['force']) && !empty($request['first_received']) && !empty($request['first_created']) && !empty($request['first_uriid']) && !empty($request['first_commented']);
		$this->raw      = !empty($request['mode']) && ($request['mode'] == 'raw');
	}

	protected function getNoSharerWidget(string $base): string
	{
		$path = $this->selectedTab;
		if (!empty($this->accountTypeString)) {
			$path .= '/' . $this->accountTypeString;
		}
		$query_parameters = [];

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

	/**
	 * Database query for the channel page
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getChannelItems()
	{
		$items = $this->getRawChannelItems();

		$contacts = $this->database->selectToArray('user-contact', ['cid'], ['channel-frequency' => Contact\User::FREQUENCY_REDUCED, 'cid' => array_column($items, 'owner-id')]);
		$reduced  = array_column($contacts, 'cid');

		$maxpostperauthor = $this->config->get('channel', 'max_posts_per_author');

		if ($maxpostperauthor != 0) {
			$count          = 1;
			$owner_posts    = [];
			$selected_items = [];

			while (count($selected_items) < $this->itemsPerPage && ++$count < 50 && count($items) > 0) {
				$maxposts = round((count($items) / $this->itemsPerPage) * $maxpostperauthor);
				$minId = $items[array_key_first($items)]['created'];
				$maxId = $items[array_key_last($items)]['created'];

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

				if (count($selected_items) < $this->itemsPerPage) {
					$items = $this->getRawChannelItems();
				}
			}
		} else {
			$selected_items = $items;
		}

		$condition = ['unseen' => true, 'uid' => $this->session->getLocalUserId(), 'parent-uri-id' => array_column($selected_items, 'uri-id')];
		$this->setItemsSeenByCondition($condition);

		return $selected_items;
	}

	/**
	 * Database query for the channel page
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getRawChannelItems()
	{
		$uid = $this->session->getLocalUserId();

		if ($this->selectedTab == ChannelEntity::WHATSHOT) {
			if (!is_null($this->accountType)) {
				$condition = ["(`comments` > ? OR `activities` > ?) AND `contact-type` = ?", $this->getMedianComments($uid, 4), $this->getMedianActivities($uid, 4), $this->accountType];
			} else {
				$condition = ["(`comments` > ? OR `activities` > ?) AND `contact-type` != ?", $this->getMedianComments($uid, 4), $this->getMedianActivities($uid, 4), Contact::TYPE_COMMUNITY];
			}
		} elseif ($this->selectedTab == ChannelEntity::FORYOU) {
			$cid = Contact::getPublicIdByUserId($uid);

			$condition = [
				"(`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `relation-thread-score` > ?) OR
				((`comments` >= ? OR `activities` >= ?) AND `owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ?)) OR
				(`owner-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`notify_new_posts` OR `channel-frequency` = ?))))",
				$cid, $this->getMedianRelationThreadScore($cid, 4), $this->getMedianComments($uid, 4), $this->getMedianActivities($uid, 4), $cid,
				$uid, Contact\User::FREQUENCY_ALWAYS
			];
		} elseif ($this->selectedTab == ChannelEntity::FOLLOWERS) {
			$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", $uid, Contact::FOLLOWER];
		} elseif ($this->selectedTab == ChannelEntity::SHARERSOFSHARERS) {
			$cid = Contact::getPublicIdByUserId($uid);

			// @todo Suggest posts from contacts that are followed most by our followers
			$condition = [
				"`owner-id` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `last-interaction` > ?
				AND `relation-cid` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ? AND `relation-thread-score` >= ?)
				AND NOT `cid` IN (SELECT `cid` FROM `contact-relation` WHERE `follows` AND `relation-cid` = ?))",
				DateTimeFormat::utc('now - ' . $this->config->get('channel', 'sharer_interaction_days') . ' day'), $cid, $this->getMedianRelationThreadScore($cid, 4), $cid
			];
		} elseif ($this->selectedTab == ChannelEntity::IMAGE) {
			$condition = ["`media-type` & ?", 1];
		} elseif ($this->selectedTab == ChannelEntity::VIDEO) {
			$condition = ["`media-type` & ?", 2];
		} elseif ($this->selectedTab == ChannelEntity::AUDIO) {
			$condition = ["`media-type` & ?", 4];
		} elseif ($this->selectedTab == ChannelEntity::LANGUAGE) {
			$condition = ["JSON_EXTRACT(JSON_KEYS(language), '$[0]') = ?", User::getLanguageCode($uid)];
		} elseif (is_numeric($this->selectedTab)) {
			$condition = $this->getUserChannelConditions($this->selectedTab, $this->session->getLocalUserId());
		}

		if ($this->selectedTab != ChannelEntity::LANGUAGE) {
			$condition = $this->addLanguageCondition($uid, $condition);
		}

		$condition = DBA::mergeConditions($condition, ["(NOT `restricted` OR EXISTS(SELECT `id` FROM `post-user` WHERE `uid` = ? AND `uri-id` = `post-engagement`.`uri-id`))", $uid]);

		$condition = DBA::mergeConditions($condition, ["NOT EXISTS(SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND `cid` = `post-engagement`.`owner-id` AND (`ignored` OR `blocked` OR `collapsed` OR `is-blocked` OR `channel-frequency` = ?))", $uid, Contact\User::FREQUENCY_NEVER]);

		if (($this->selectedTab != ChannelEntity::WHATSHOT) && !is_null($this->accountType)) {
			$condition = DBA::mergeConditions($condition, ['contact-type' => $this->accountType]);
		}

		$params = ['order' => ['created' => true], 'limit' => $this->itemsPerPage];

		if (!empty($this->itemUriId)) {
			$condition = DBA::mergeConditions($condition, ['uri-id' => $this->itemUriId]);
		} else {
			if ($this->noSharer) {
				$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uid` = ? AND `post-user`.`uri-id` = `post-engagement`.`uri-id`)", $this->session->getLocalUserId()]);
			}

			if (isset($this->maxId)) {
				$condition = DBA::mergeConditions($condition, ["`created` < ?", $this->maxId]);
			}

			if (isset($this->minId)) {
				$condition = DBA::mergeConditions($condition, ["`created` > ?", $this->minId]);

				// Previous page case: we want the items closest to min_id but for that we need to reverse the query order
				if (!isset($this->maxId)) {
					$params['order']['created'] = false;
				}
			}
		}

		$items = [];
		$result = $this->database->select('post-engagement', ['uri-id', 'created', 'owner-id', 'comments', 'activities'], $condition, $params);
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

		$condition = ['unseen' => true, 'uid' => $uid, 'parent-uri-id' => array_column($items, 'uri-id')];
		$this->setItemsSeenByCondition($condition);

		return $items;
	}

	private function getUserChannelConditions(int $id, int $uid): array
	{
		$channel = $this->channelRepository->selectById($id, $uid);
		if (empty($channel)) {
			return [];
		}

		$condition = [];

		if (!empty($channel->circle)) {
			if ($channel->circle == -1) {
				$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` IN (?, ?))", $uid, Contact::SHARING, Contact::FRIEND];
			} elseif ($channel->circle == -2) {
				$condition = ["`owner-id` IN (SELECT `pid` FROM `account-user-view` WHERE `uid` = ? AND `rel` = ?)", $uid, Contact::FOLLOWER];
			} elseif ($channel->circle == -3) {
				$condition = ["EXISTS(SELECT `uri-id` FROM `post-thread-user` WHERE `uid` = ? AND `post-thread-user`.`uri-id` = `post-engagement`.`uri-id`)", $uid];
			} elseif ($channel->circle > 0) {
				$condition = DBA::mergeConditions($condition, ["`owner-id` IN (SELECT `pid` FROM `group_member` INNER JOIN `account-user-view` ON `group_member`.`contact-id` = `account-user-view`.`id` WHERE `gid` = ? AND `account-user-view`.`uid` = ?)", $channel->circle, $uid]);
			}
		}

		if (!empty($channel->fullTextSearch)) {
			$search = $channel->fullTextSearch;
			foreach (Engagement::KEYWORDS as $keyword) {
				$search = preg_replace('~(' . $keyword . ':.[\w@\.-]+)~', '"$1"', $search);
			}
			$condition = DBA::mergeConditions($condition, ["MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", $search]);
		}

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
		return $condition ?: ["true"];
	}

	private function addLanguageCondition(int $uid, array $condition): array
	{
		$conditions = [];
		$languages  = User::getWantedLanguages($uid);
		foreach ($languages as $language) {
			$conditions[] = "JSON_EXTRACT(JSON_KEYS(language), '$[0]') = ?";
			$condition[]  = $language;
		}
		if (!empty($conditions)) {
			$condition[0] .= " AND (`language` IS NULL OR " . implode(' OR ', $conditions) . ")";
		}
		return $condition;
	}

	private function getMedianComments(int $uid, int $divider): int
	{
		$languages = User::getWantedLanguages($uid);
		$cache_key = 'Channel:getMedianComments:' . $divider . ':' . implode(':', $languages);
		$comments  = $this->cache->get($cache_key);
		if (!empty($comments)) {
			return $comments;
		}

		$condition = ["`contact-type` != ? AND `comments` > ? AND NOT `restricted`", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		$limit    = $this->database->count('post-engagement', $condition) / $divider;
		$post     = $this->database->selectToArray('post-engagement', ['comments'], $condition, ['order' => ['comments' => true], 'limit' => [$limit, 1]]);
		$comments = $post[0]['comments'] ?? 0;
		if (empty($comments)) {
			return 0;
		}

		$this->cache->set($cache_key, $comments, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median comments', ['divider' => $divider, 'languages' => $languages, 'median' => $comments]);
		return $comments;
	}

	private function getMedianActivities(int $uid, int $divider): int
	{
		$languages  = User::getWantedLanguages($uid);
		$cache_key  = 'Channel:getMedianActivities:' . $divider . ':' . implode(':', $languages);
		$activities = $this->cache->get($cache_key);
		if (!empty($activities)) {
			return $activities;
		}

		$condition = ["`contact-type` != ? AND `activities` > ? AND NOT `restricted`", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->addLanguageCondition($uid, $condition);

		$limit      = $this->database->count('post-engagement', $condition) / $divider;
		$post       = $this->database->selectToArray('post-engagement', ['activities'], $condition, ['order' => ['activities' => true], 'limit' => [$limit, 1]]);
		$activities = $post[0]['activities'] ?? 0;
		if (empty($activities)) {
			return 0;
		}

		$this->cache->set($cache_key, $activities, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median activities', ['divider' => $divider, 'languages' => $languages, 'median' => $activities]);
		return $activities;
	}

	private function getMedianRelationThreadScore(int $cid, int $divider): int
	{
		$cache_key = 'Channel:getThreadScore:' . $cid . ':' . $divider;
		$score     = $this->cache->get($cache_key);
		if (!empty($score)) {
			return $score;
		}

		$condition = ["`relation-cid` = ? AND `relation-thread-score` > ?", $cid, 0];

		$limit    = $this->database->count('contact-relation', $condition) / $divider;
		$relation = $this->database->selectToArray('contact-relation', ['relation-thread-score'], $condition, ['order' => ['relation-thread-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['relation-thread-score'] ?? 0;
		if (empty($score)) {
			return 0;
		}

		$this->cache->set($cache_key, $score, Duration::HALF_HOUR);
		$this->logger->debug('Calculated median score', ['cid' => $cid, 'divider' => $divider, 'median' => $score]);
		return $score;
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

		if ($this->selectedTab == 'local') {
			$maxpostperauthor = (int)$this->config->get('system', 'max_author_posts_community_page');
			$key = 'author-id';
		} elseif ($this->selectedTab == 'global') {
			$maxpostperauthor = (int)$this->config->get('system', 'max_server_posts_community_page');
			$key = 'author-gsid';
		} else {
			$maxpostperauthor = 0;
		}
		if ($maxpostperauthor != 0) {
			$count          = 1;
			$author_posts   = [];
			$selected_items = [];

			while (count($selected_items) < $this->itemsPerPage && ++$count < 50 && count($items) > 0) {
				$maxposts = round((count($items) / $this->itemsPerPage) * $maxpostperauthor);
				$minId = $items[array_key_first($items)]['received'];
				$maxId = $items[array_key_last($items)]['received'];
				$this->logger->debug('Blubb', ['tab' => $this->selectedTab, 'count' => $count, 'min' => $minId, 'max' => $maxId]);

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
		} else {
			$selected_items = $items;
		}

		$condition = ['unseen' => true, 'uid' => $this->session->getLocalUserId(), 'parent-uri-id' => array_column($selected_items, 'uri-id')];
		$this->setItemsSeenByCondition($condition);

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
		if ($this->selectedTab == 'local') {
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
		$result = Post::selectThreadForUser($this->session->getLocalUserId() ?: 0, ['uri-id', 'received', 'author-id', 'author-gsid'], $condition, $params);

		while ($item = $this->database->fetch($result)) {
			$items[$item['uri-id']] = $item;
		}
		$this->database->close($result);

		if (empty($items)) {
			return [];
		}

		// Previous page case: once we get the relevant items closest to min_id, we need to restore the expected display order
		if (empty($this->itemUriId) && isset($this->minId) && !isset($this->maxId)) {
			$items = array_reverse($items);
		}

		return $items;
	}

	/**
	 * Sets items as seen
	 *
	 * @param array $condition The array with the SQL condition
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function setItemsSeenByCondition(array $condition)
	{
		if (empty($condition)) {
			return;
		}

		$unseen = Post::exists($condition);

		if ($unseen) {
			/// @todo handle huge "unseen" updates in the background to avoid timeout errors
			Item::update(['unseen' => false], $condition);
		}
	}
}
