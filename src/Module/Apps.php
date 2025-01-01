<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Shows the App menu
 */
class Apps extends BaseModule
{
	/** @var Nav */
	protected $nav;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(SystemMessages $systemMessages, Nav $nav, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->nav            = $nav;
		$this->systemMessages = $systemMessages;

		$privateaddons = $config->get('config', 'private_addons');
		if ($privateaddons === "1" && !$session->getLocalUserId()) {
			$baseUrl->redirect();
		}
	}

	protected function content(array $request = []): string
	{
		$apps = $this->nav->getAppMenu();
		if (count($apps) == 0) {
			$this->systemMessages->addNotice($this->t('No installed applications.'));
		}

		$tpl = Renderer::getMarkupTemplate('apps.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title' => $this->t('Applications'),
			'$apps'  => $apps,
		]);
	}
}
