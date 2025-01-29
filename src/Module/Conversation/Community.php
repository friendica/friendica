<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Conversation;

use Friendica\App;
use Friendica\App\Mode;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation;
use Friendica\Content\Conversation\Entity\Community as CommunityEntity;
use Friendica\Content\Conversation\Factory\Community as CommunityFactory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget;
use Friendica\Content\Widget\TrendingTags;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Network\HTTPException;
use Friendica\Database\Database;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Community extends Timeline
{
	/**
	 * Type of the community page
	 * @{
	 */
	const DISABLED         = -2;
	const DISABLED_VISITOR = -1;
	const LOCAL            = 0;
	const GLOBAL           = 1;
	const LOCAL_AND_GLOBAL = 2;

	protected $pageStyle;

	/** @var CommunityFactory */
	protected $community;
	/** @var Conversation */
	protected $conversation;
	/** @var App\Page */
	protected $page;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(UserDefinedChannel $channel, CommunityFactory $community, Conversation $conversation, App\Page $page, SystemMessages $systemMessages, Mode $mode, IHandleUserSessions $session, Database $database, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, ICanCache $cache, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($channel, $mode, $session, $database, $pConfig, $config, $cache, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->community      = $community;
		$this->conversation   = $conversation;
		$this->page           = $page;
		$this->systemMessages = $systemMessages;
	}

	protected function content(array $request = []): string
	{
		$this->parseRequest($request);

		$t = Renderer::getMarkupTemplate("community.tpl");
		$o = Renderer::replaceMacros($t, [
			'$content'                    => '',
			'$header'                     => '',
			'$show_global_community_hint' => ($this->selectedTab == CommunityEntity::GLOBAL) && $this->config->get('system', 'show_global_community_hint'),
			'$global_community_hint'      => $this->l10n->t("This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.")
		]);

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o .= Renderer::replaceMacros($tpl, ['$reload_uri' => $this->args->getQueryString()]);
		}

		if (!$this->raw) {
			$tabs    = $this->getTabArray($this->community->getTimelines($this->session->isAuthenticated()), 'community');
			$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
			$o .= Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs, '$more' => $this->l10n->t('More')]);

			Nav::setSelected('community');

			$this->page['aside'] .= Widget::accountTypes('community/' . $this->selectedTab, $this->accountTypeString);

			if ($this->session->getLocalUserId()) {
				$this->page['aside'] .= $this->getNoSharerWidget('community');
			}

			if (Feature::isEnabled($this->session->getLocalUserId(), Feature::TRENDING_TAGS)) {
				$this->page['aside'] .= TrendingTags::getHTML($this->selectedTab);
			}

			// We need the editor here to be able to reshare an item.
			if ($this->session->isAuthenticated()) {
				$o .= $this->conversation->statusEditor([], 0, true);
			}
		}

		$items = $this->getCommunityItems();

		if (!$this->database->isResult($items)) {
			$this->systemMessages->addNotice($this->l10n->t('No results.'));
			return $o;
		}

		$o .= $this->conversation->render($items, Conversation::MODE_COMMUNITY, false, false, 'received', $this->session->getLocalUserId());

		$pager = new BoundariesPager(
			$this->l10n,
			$this->args->getQueryString(),
			$items[array_key_first($items)]['received'],
			$items[array_key_last($items)]['received'],
			$this->itemsPerPage
		);

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Computes module parameters from the request and local configuration
	 *
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 */
	protected function parseRequest($request)
	{
		parent::parseRequest($request);

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Public access denied.'));
		}

		$this->pageStyle = $this->config->get('system', 'community_page_style');

		if ($this->pageStyle == self::DISABLED) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Access denied.'));
		}

		if (!$this->selectedTab) {
			if (!empty($this->config->get('system', 'singleuser'))) {
				// On single user systems only the global page does make sense
				$this->selectedTab = CommunityEntity::GLOBAL;
			} else {
				// When only the global community is allowed, we use this as default
				$this->selectedTab = $this->pageStyle == self::GLOBAL ? CommunityEntity::GLOBAL : CommunityEntity::LOCAL;
			}
		}

		if (!$this->community->isTimeline($this->selectedTab)) {
			throw new HTTPException\BadRequestException($this->l10n->t('Community option not available.'));
		}

		// Check if we are allowed to display the content to visitors
		if (!$this->session->isAuthenticated()) {
			$available = $this->pageStyle == self::LOCAL_AND_GLOBAL;

			if (!$available) {
				$available = ($this->pageStyle == self::LOCAL) && ($this->selectedTab == CommunityEntity::LOCAL);
			}

			if (!$available) {
				$available = ($this->pageStyle == self::GLOBAL) && ($this->selectedTab == CommunityEntity::GLOBAL);
			}

			if (!$available) {
				throw new HTTPException\ForbiddenException($this->l10n->t('Not available.'));
			}
		}

		$this->maxId = $request['last_received']  ?? $this->maxId;
		$this->minId = $request['first_received'] ?? $this->minId;
	}
}
