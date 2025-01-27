<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Conversation;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\Conversation\Entity\Channel;
use Friendica\Content\Conversation\Entity\Network as NetworkEntity;
use Friendica\Content\Conversation\Factory\Timeline as TimelineFactory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Conversation\Factory\Channel as ChannelFactory;
use Friendica\Content\Conversation\Factory\UserDefinedChannel as UserDefinedChannelFactory;
use Friendica\Content\Conversation\Factory\Community as CommunityFactory;
use Friendica\Content\Conversation\Factory\Network as NetworkFactory;
use Friendica\Content\Feature;
use Friendica\Content\GroupManager;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\ACL;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Network extends Timeline
{
	/** @var int */
	protected $circleId;
	/** @var string */
	protected $dateFrom;
	/** @var string */
	protected $dateTo;
	/** @var int */
	protected $star;
	/** @var int */
	protected $mention;

	/** @var AppHelper */
	protected $appHelper;
	/** @var ICanCache */
	protected $cache;
	/** @var IManageConfigValues The config */
	protected $config;
	/** @var SystemMessages */
	protected $systemMessages;
	/** @var Page */
	protected $page;
	/** @var Conversation */
	protected $conversation;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var Database */
	protected $database;
	/** @var TimelineFactory */
	protected $timeline;
	/** @var ChannelFactory */
	protected $channel;
	/** @var UserDefinedChannelFactory */
	protected $userDefinedChannel;
	/** @var CommunityFactory */
	protected $community;
	/** @var NetworkFactory */
	protected $networkFactory;

	public function __construct(UserDefinedChannelFactory $userDefinedChannel, NetworkFactory $network, CommunityFactory $community, ChannelFactory $channelFactory, UserDefinedChannel $channel, AppHelper $appHelper, TimelineFactory $timeline, SystemMessages $systemMessages, Mode $mode, Conversation $conversation, Page $page, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($channel, $mode, $session, $database, $pConfig, $config, $cache, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->appHelper          = $appHelper;
		$this->timeline           = $timeline;
		$this->systemMessages     = $systemMessages;
		$this->conversation       = $conversation;
		$this->page               = $page;
		$this->channel            = $channelFactory;
		$this->community          = $community;
		$this->networkFactory     = $network;
		$this->userDefinedChannel = $userDefinedChannel;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form();
		}

		$this->parseRequest($request);

		$module = 'network';

		$arr = ['query' => $this->args->getQueryString()];
		Hook::callAll('network_content_init', $arr);

		$o = '';

		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::CIRCLES)) {
			$this->page['aside'] .= Circle::sidebarWidget($module, $module . '/circle', 'standard', $this->circleId);
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::GROUPS)) {
			$this->page['aside'] .= GroupManager::widget($this->session->getLocalUserId());
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::ARCHIVE)) {
			$this->page['aside'] .= Widget::postedByYear($module . '/archive', $this->session->getLocalUserId(), false);
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::NETWORKS)) {
			$this->page['aside'] .= Widget::networks($module, $this->network);
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::ACCOUNTS)) {
			$this->page['aside'] .= Widget::accountTypes($module, $this->accountTypeString);
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::CHANNELS)) {
			$this->page['aside'] .= Widget::channels($module, $this->selectedTab, $this->session->getLocalUserId());
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::SEARCHES)) {
			$this->page['aside'] .= Widget\SavedSearches::getHTML($this->args->getQueryString());
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::FOLDERS)) {
			$this->page['aside'] .= Widget::fileAs('filed', '');
		}
		if (($this->channel->isTimeline($this->selectedTab) || $this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId())) &&
			!in_array($this->selectedTab, [Channel::FOLLOWERS, Channel::FORYOU, Channel::DISCOVER]) && Feature::isEnabled($this->session->getLocalUserId(), Feature::NOSHARER)) {
			$this->page['aside'] .= $this->getNoSharerWidget('network');
		}
		if (Feature::isEnabled($this->session->getLocalUserId(), Feature::TRENDING_TAGS)) {
			$this->page['aside'] .= TrendingTags::getHTML($this->selectedTab);
		}

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll') && ($_GET['mode'] ?? '') != 'minimal') {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => $this->args->getQueryString()]);
		}

		if (!$this->raw) {
			$o .= $this->getTabsHTML();

			Nav::setSelected($this->args->get(0));

			$default_permissions = [];
			if ($this->circleId) {
				$default_permissions['allow_gid'] = [$this->circleId];
			}

			$allowedCids = [];
			if ($this->network) {
				$condition = [
					'uid'     => $this->session->getLocalUserId(),
					'network' => $this->network,
					'self'    => false,
					'blocked' => false,
					'pending' => false,
					'archive' => false,
					'rel'     => [Contact::SHARING, Contact::FRIEND],
				];
				$contactStmt = $this->database->select('contact', ['id'], $condition);
				while ($contact = $this->database->fetch($contactStmt)) {
					$allowedCids[] = (int) $contact['id'];
				}
				$this->database->close($contactStmt);
			}

			if (count($allowedCids)) {
				$default_permissions['allow_cid'] = $allowedCids;
			}

			$x = [
				'lockstate' => $this->circleId || $this->network || ACL::getLockstateForUserId($this->session->getLocalUserId()) ? 'lock' : 'unlock',
				'acl'       => ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), true, $default_permissions),
				'bang'      => (($this->circleId || $this->network) ? '!' : ''),
				'content'   => '',
			];

			$o .= $this->conversation->statusEditor($x);

			if ($this->circleId) {
				$circle = $this->database->selectFirst('group', ['name'], ['id' => $this->circleId, 'uid' => $this->session->getLocalUserId()]);
				if (!$this->database->isResult($circle)) {
					$this->systemMessages->addNotice($this->l10n->t('No such circle'));
				}

				$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
					'$title' => $this->l10n->t('Circle: %s', $circle['name'])
				]) . $o;
			} elseif (Profile::shouldDisplayEventList($this->session->getLocalUserId(), $this->mode)) {
				$o .= Profile::getBirthdays($this->session->getLocalUserId());
				$o .= Profile::getEventsReminderHTML($this->session->getLocalUserId(), $this->session->getPublicContactId());
			}
		}

		try {
			if ($this->channel->isTimeline($this->selectedTab) || $this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId())) {
				$items = $this->getChannelItems($request, $this->session->getLocalUserId());
			} elseif ($this->community->isTimeline($this->selectedTab)) {
				$items = $this->getCommunityItems();
			} else {
				$items = $this->getItems();
			}

			$o .= $this->conversation->render($items, Conversation::MODE_NETWORK, false, false, $this->getOrder(), $this->session->getLocalUserId());
		} catch (\Exception $e) {
			$this->logger->error('Exception when fetching items', ['code' => $e->getCode(), 'message' => $e->getMessage()]);
			$o .= $this->l10n->t('Error %d (%s) while fetching the timeline.', $e->getCode(), $e->getMessage());
			$items = [];
		}

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$pager = new BoundariesPager(
				$this->l10n,
				$this->args->getQueryString(),
				$items[0][$this->order]                 ?? null,
				$items[count($items) - 1][$this->order] ?? null,
				$this->itemsPerPage
			);

			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	protected function getOrder(): string
	{
		if ($this->order === 'received') {
			return '`received`';
		} elseif ($this->order === 'created') {
			return '`created`';
		} else {
			return '`commented`';
		}
	}

	/**
	 * Get the network tabs menu
	 *
	 * @return string Html of the network tabs
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getTabsHTML()
	{
		$tabs = $this->getTabArray($this->networkFactory->getTimelines($this->args->getCommand()), 'network');

		$network_timelines = $this->pConfig->get($this->session->getLocalUserId(), 'system', 'network_timelines', []);
		if (!empty($network_timelines)) {
			$tabs = array_merge($tabs, $this->getTabArray($this->channel->getTimelines($this->session->getLocalUserId()), 'network', 'channel'));
			$tabs = array_merge($tabs, $this->getTabArray($this->channelRepository->selectByUid($this->session->getLocalUserId()), 'network', 'channel'));
			$tabs = array_merge($tabs, $this->getTabArray($this->community->getTimelines(true), 'network', 'channel'));
		}

		$arr = ['tabs' => $tabs];
		Hook::callAll('network_tabs', $arr);

		if (!empty($network_timelines)) {
			$tabs = [];

			foreach ($arr['tabs'] as $tab) {
				if (in_array($tab['code'], $network_timelines)) {
					$tabs[] = $tab;
				}
			}
		} else {
			$tabs = $arr['tabs'];
		}

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		return Renderer::replaceMacros($tpl, ['$tabs' => $tabs, '$more' => $this->l10n->t('More')]);
	}

	protected function parseRequest(array $request)
	{
		parent::parseRequest($request);

		$this->circleId = (int)($this->parameters['circle_id'] ?? 0);

		if (!$this->selectedTab) {
			$this->selectedTab = $this->getTimelineOrderBySession();
		} elseif (!$this->networkFactory->isTimeline($this->selectedTab) && !$this->channel->isTimeline($this->selectedTab) && !$this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId()) && !$this->community->isTimeline($this->selectedTab)) {
			throw new HTTPException\BadRequestException($this->l10n->t('Network feed not available.'));
		}

		if (($this->network || $this->circleId) && ($this->channel->isTimeline($this->selectedTab) || $this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId()) || $this->community->isTimeline($this->selectedTab))) {
			$this->selectedTab = NetworkEntity::RECEIVED;
		}

		if (!empty($request['star'])) {
			$this->selectedTab = NetworkEntity::STAR;
			$this->star        = true;
		} else {
			$this->star = $this->selectedTab == NetworkEntity::STAR;
		}

		if (!empty($request['mention'])) {
			$this->selectedTab = NetworkEntity::MENTION;
			$this->mention     = true;
		} else {
			$this->mention = $this->selectedTab == NetworkEntity::MENTION;
		}

		if (!empty($request['order'])) {
			$this->selectedTab = $request['order'];
			$this->order       = $request['order'];
			$this->star        = false;
			$this->mention     = false;
		} elseif (in_array($this->selectedTab, [NetworkEntity::RECEIVED, NetworkEntity::STAR]) || $this->community->isTimeline($this->selectedTab)) {
			$this->order = 'received';
		} elseif (($this->selectedTab == NetworkEntity::CREATED) || $this->channel->isTimeline($this->selectedTab) || $this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId())) {
			$this->order = 'created';
		} else {
			$this->order = 'commented';
		}

		$this->selectedTab = $this->selectedTab ?? $this->order;

		// Upon updates in the background and order by last comment we order by received date,
		// since otherwise the feed will optically jump, when some already visible thread has been updated.
		if ($this->update && ($this->selectedTab == NetworkEntity::COMMENTED)) {
			$this->order = 'received';

			$request['last_received']  = $request['last_commented']  ?? null;
			$request['first_received'] = $request['first_commented'] ?? null;
		}

		// Prohibit combined usage of "star" and "mention"
		if ($this->selectedTab == NetworkEntity::STAR) {
			$this->mention = false;
		} elseif ($this->selectedTab == NetworkEntity::MENTION) {
			$this->star = false;
		}

		$this->session->set('network-tab', $this->selectedTab);
		$this->pConfig->set($this->session->getLocalUserId(), 'network.view', 'selected_tab', $this->selectedTab);

		$this->network = $request['nets'] ?? '';

		$this->dateFrom = $this->parameters['from'] ?? '';
		$this->dateTo   = $this->parameters['to']   ?? '';

		$this->setMaxMinByOrder($request);

		if (is_null($this->maxId) && !is_null($this->minId)) {
			$this->session->set('network-request', $request);
			$this->pConfig->set($this->session->getLocalUserId(), 'network.view', 'request', $request);
		}
	}

	protected function setPing(bool $ping)
	{
		$this->ping = $ping;
	}

	protected function getItems()
	{
		$conditionFields  = ['uid' => $this->session->getLocalUserId()];
		$conditionStrings = [];

		if (!is_null($this->accountType)) {
			$conditionFields['contact-type'] = $this->accountType;
		}

		if ($this->star) {
			$conditionFields['starred'] = true;
		}
		if ($this->mention) {
			$conditionFields['mention'] = true;
		}
		if ($this->network) {
			$conditionFields['network'] = $this->network;
		}

		if ($this->dateFrom) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` <= ? ", DateTimeFormat::convert($this->dateFrom, 'UTC', $this->appHelper->getTimeZone())]);
		}
		if ($this->dateTo) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` >= ? ", DateTimeFormat::convert($this->dateTo, 'UTC', $this->appHelper->getTimeZone())]);
		}

		if ($this->circleId) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ["`contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)", $this->circleId]);
		}

		// Currently only the order modes "received" and "commented" are in use
		if (!empty($this->itemUriId)) {
			$conditionStrings = DBA::mergeConditions($conditionStrings, ['uri-id' => $this->itemUriId]);
		} else {
			if (isset($this->maxId)) {
				switch ($this->order) {
					case 'received':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` < ?", $this->maxId]);
						break;
					case 'commented':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` < ?", $this->maxId]);
						break;
					case 'created':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` < ?", $this->maxId]);
						break;
					case 'uriid':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` < ?", $this->maxId]);
						break;
				}
			}

			if (isset($this->minId)) {
				switch ($this->order) {
					case 'received':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`received` > ?", $this->minId]);
						break;
					case 'commented':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`commented` > ?", $this->minId]);
						break;
					case 'created':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`created` > ?", $this->minId]);
						break;
					case 'uriid':
						$conditionStrings = DBA::mergeConditions($conditionStrings, ["`uri-id` > ?", $this->minId]);
						break;
				}
			}
		}

		$params = ['limit' => $this->itemsPerPage];

		if (isset($this->minId) && !isset($this->maxId)) {
			// min_id quirk: querying in reverse order with min_id gets the most recent rows, regardless of how close
			// they are to min_id. We change the query ordering to get the expected data, and we need to reverse the
			// order of the results.
			$params['order'] = [$this->order => false];
		} else {
			$params['order'] = [$this->order => true];
		}

		$items = $this->database->selectToArray($this->circleId ? 'network-thread-circle-view' : 'network-thread-view', [], DBA::mergeConditions($conditionFields, $conditionStrings), $params);

		// min_id quirk, continued
		if (isset($this->minId) && !isset($this->maxId)) {
			$items = array_reverse($items);
		}

		if ($this->ping || !$this->database->isResult($items)) {
			return $items;
		}

		$this->setItemsSeenByCondition(['unseen' => true, 'uid' => $this->session->getLocalUserId(), 'parent-uri-id' => array_column($items, 'uri-id')]);

		$posts = Post::selectToArray(['uri-id'], ['unseen' => true, 'uid' => $this->session->getLocalUserId()], ['limit' => 100]);
		if (!empty($posts)) {
			$this->setItemsSeenByCondition(['unseen' => true, 'uid' => $this->session->getLocalUserId(), 'uri-id' => array_column($posts, 'uri-id')]);
		}

		if (count($posts) == 100) {
			Worker::add(Worker::PRIORITY_MEDIUM, 'SetSeen', $this->session->getLocalUserId());
		}
		return $items;
	}

	/**
	 * Returns the selected network tab of the currently logged-in user
	 *
	 * @return string
	 */
	private function getTimelineOrderBySession(): string
	{
		return $this->session->get('network-tab')
			?? $this->pConfig->get($this->session->getLocalUserId(), 'network.view', 'selected_tab')
			?? '';
	}

	/**
	 * Returns the lst request parameters of the currently logged-in user
	 *
	 * @return array
	 */
	protected function getTimelineRequestBySession(): array
	{
		return $this->session->get('network-request')
			?? $this->pConfig->get($this->session->getLocalUserId(), 'network.view', 'request')
			?? [];
	}
}
