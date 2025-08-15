<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Conversation;
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
 * The default profile path (https://domain.tld/profile/nickname) has to serve the profile data when queried as an
 * ActivityPub endpoint, but it should show statuses to web users.
 *
 * Both these view have dedicated sub-paths,
 * respectively https://domain.tld/profile/nickname/profile and https://domain.tld/profile/nickname/conversations
 */
class Index extends BaseModule
{
	/** @var Database */
	private $database;
	/** @var AppHelper */
	private $appHelper;
	/** @var IHandleUserSessions */
	private $session;
	/** @var IManageConfigValues */
	private $config;
	/** @var Page */
	private $page;
	/** @var ProfileField */
	private $profileField;
	/** @var DateTimeFormat */
	private $dateTimeFormat;
	/** @var Conversation */
	private $conversation;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var Mode */
	private $mode;
	private EventDispatcherInterface $eventDispatcher;

	public function __construct(
		Mode $mode,
		IManagePersonalConfigValues $pConfig,
		Conversation $conversation,
		DateTimeFormat $dateTimeFormat,
		ProfileField $profileField,
		Page $page,
		IManageConfigValues $config,
		IHandleUserSessions $session,
		AppHelper $appHelper,
		Database $database,
		EventDispatcherInterface $eventDispatcher,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database        = $database;
		$this->appHelper       = $appHelper;
		$this->session         = $session;
		$this->config          = $config;
		$this->page            = $page;
		$this->profileField    = $profileField;
		$this->dateTimeFormat  = $dateTimeFormat;
		$this->conversation    = $conversation;
		$this->pConfig         = $pConfig;
		$this->mode            = $mode;
		$this->eventDispatcher = $eventDispatcher;
	}

	protected function rawContent(array $request = [])
	{
		(new Profile($this->profileField, $this->page, $this->config, $this->session, $this->appHelper, $this->database, $this->eventDispatcher, $this->l10n, $this->baseUrl, $this->args, $this->logger, $this->profiler, $this->response, $this->server, $this->parameters))->rawContent();
	}

	protected function content(array $request = []): string
	{
		return (new Conversations($this->mode, $this->pConfig, $this->conversation, $this->session, $this->config, $this->dateTimeFormat, $this->page, $this->appHelper, $this->l10n, $this->baseUrl, $this->args, $this->logger, $this->profiler, $this->response, $this->server, $this->parameters))->content();
	}
}
