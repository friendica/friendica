<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Ping;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Content\Conversation;
use Friendica\Content\Conversation\Factory\Timeline as TimelineFactory;
use Friendica\Content\Conversation\Factory\Activity as ActivityFactory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Conversation\Factory\Channel as ChannelFactory;
use Friendica\Content\Conversation\Factory\UserDefinedChannel as UserDefinedChannelFactory;
use Friendica\Content\Conversation\Factory\Community as CommunityFactory;
use Friendica\Content\Conversation\Factory\Network as NetworkFactory;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Module\Conversation\Network as NetworkModule;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Network extends NetworkModule
{
	/**
	 * @var ICanLock
	 */
	private $lock;

	public function __construct(
		ICanLock $lock,
		UserDefinedChannelFactory $userDefinedChannel,
		NetworkFactory $network,
		CommunityFactory $community,
		ChannelFactory $channelFactory,
		UserDefinedChannel $channel,
		AppHelper $appHelper,
		EventDispatcherInterface $eventDispatcher,
		TimelineFactory $timeline,
		SystemMessages $systemMessages,
		Mode $mode,
		Conversation $conversation,
		Page $page,
		IHandleUserSessions $session,
		Database $database,
		IManagePersonalConfigValues $pConfig,
		IManageConfigValues $config,
		ICanCache $cache,
		ActivityFactory $ActivityFactory,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct(
			$userDefinedChannel,
			$network,
			$community,
			$channelFactory,
			$channel,
			$appHelper,
			$eventDispatcher,
			$timeline,
			$systemMessages,
			$mode,
			$conversation,
			$page,
			$session,
			$database,
			$pConfig,
			$config,
			$cache,
			$ActivityFactory,
			$l10n,
			$baseUrl,
			$args,
			$logger,
			$profiler,
			$response,
			$server,
			$parameters,
		);

		$this->lock = $lock;
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			System::exit();
		}

		if (!empty($request['ping'])) {
			$request = $this->getTimelineRequestBySession();
		}

		if (!isset($request['p']) || !isset($request['item'])) {
			System::exit();
		}

		$this->parseRequest($request);

		if ($this->force || !is_null($this->maxId)) {
			System::httpExit('');
		}

		$lockkey = 'network-ping-' . $this->session->getLocalUserId();
		if (!$this->lock->acquire($lockkey, 0)) {
			$this->logger->debug('Ping-1-lock', ['uid' => $this->session->getLocalUserId()]);
			System::httpExit('');
		}

		$this->setPing(true);
		$this->itemsPerPage = 100;

		if ($this->channel->isTimeline($this->selectedTab) || $this->userDefinedChannel->isTimeline($this->selectedTab, $this->session->getLocalUserId())) {
			$items = $this->getChannelItems($request, $this->session->getLocalUserId());
		} elseif ($this->community->isTimeline($this->selectedTab)) {
			$items = $this->getCommunityItems();
		} else {
			$items = $this->getItems();
		}
		$this->lock->release($lockkey);
		$count = count($items);
		System::httpExit(($count < 100) ? $count : '99+');
	}
}
