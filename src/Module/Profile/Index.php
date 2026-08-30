<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Content\Conversation\StatusEditor;
use Friendica\Content\GroupManager;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Module\Response;
use Friendica\Profile\ProfileField\Repository\ProfileField;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Profile index router
 *
 * The default profile path (https://domain.tld/profile/username) has to serve the profile data when queried as an
 * ActivityPub endpoint, but it should show statuses to web users.
 *
 * Both these view have dedicated sub-paths,
 * respectively https://domain.tld/profile/username/profile and https://domain.tld/profile/username/conversations
 */
class Index extends BaseModule
{
	public function __construct(
		private readonly Mode $mode,
		private readonly IManagePersonalConfigValues $pConfig,
		private readonly ConversationRenderer $conversationRenderer,
		private readonly StatusEditor $statusEditor,
		private readonly DateTimeFormat $dateTimeFormat,
		private readonly ProfileField $profileField,
		private readonly Page $page,
		private readonly IManageConfigValues $config,
		private readonly IHandleUserSessions $session,
		private readonly AppHelper $appHelper,
		private readonly Database $database,
		private readonly EventDispatcherInterface $eventDispatcher,
		private readonly GroupManager $groupManager,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		(new Profile($this->profileField, $this->page, $this->config, $this->session, $this->appHelper, $this->database, $this->eventDispatcher, $this->groupManager, $this->l10n, $this->baseUrl, $this->args, $this->logger, $this->profiler, $this->response, $this->server, $this->parameters))->rawContent();
	}

	protected function content(array $request = []): string
	{
		/** @var Response $response */
		$response = $this->response;
		return (new Conversations($this->mode, $this->pConfig, $this->conversationRenderer, $this->statusEditor, $this->session, $this->config, $this->dateTimeFormat, $this->page, $this->appHelper, $this->l10n, $this->baseUrl, $this->args, $this->logger, $this->profiler, $response, $this->server, $this->parameters))->content();
	}
}
