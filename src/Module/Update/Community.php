<?php

/* Copyright (C) 2010-2026, the Friendica project
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See update_profile.php for documentation
 */

namespace Friendica\Module\Update;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Content\Conversation\Factory\Activity as ActivityFactory;
use Friendica\Content\Conversation\Factory\Community as CommunityFactory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Conversation\StatusEditor;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Database\Database;
use Friendica\Module\Conversation\Community as CommunityModule;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous update module for the community page
 *
 * @package Friendica\Module\Update
 */
class Community extends CommunityModule
{
	public function __construct(
		UserDefinedChannel $channel,
		CommunityFactory $community,
		ConversationRenderer $conversationRenderer,
		StatusEditor $statusEditor,
		Page $page,
		SystemMessages $systemMessages,
		Mode $mode,
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
		array $parameters = [],
	) {
		parent::__construct($channel, $community, $conversationRenderer, $statusEditor, $page, $systemMessages, $mode, $session, $database, $pConfig, $config, $cache, $ActivityFactory, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$this->parseRequest($request);

		$o = '';
		if ($this->update || $this->force) {
			$o = $this->conversationRenderer->renderThreaded($this->getCommunityItems(), ConversationRenderer::MODE_COMMUNITY, true, ConversationRenderer::ORDER_COMMENTED, DI::userSession()->getLocalUserId(), $request);
		}

		System::htmlUpdateExit($o);
	}
}
