<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class OAuth extends BaseSettings
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if (!isset($request['delete'])) {
			return;
		}

		BaseSettings::checkFormSecurityTokenRedirectOnError('/settings/oauth', 'settings_oauth');

		$this->database->delete('application-token', ['application-id' => $request['delete'], 'uid' => $this->session->getLocalUserId()]);
		$this->baseUrl->redirect('settings/oauth', true);
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		$applications = $this->database->selectToArray('application-view', ['id', 'uid', 'name', 'website', 'scopes', 'created_at'], ['uid' => $this->session->getLocalUserId()]);
		foreach ($applications as $key => $row) {
			$applications[$key]['created_at'] = $this->l10n->fullDateTime($row['created_at']);
		}

		$tpl = Renderer::getMarkupTemplate('settings/oauth.tpl');
		return Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseSettings::getFormSecurityToken('settings_oauth'),
			'$title'               => $this->t('Connected Apps'),
			'$name'                => $this->t('Name'),
			'$website'             => $this->t('Home Page'),
			'$created_at'          => $this->t('Created'),
			'$delete'              => $this->t('Remove authorization'),
			'$no_connected_apps'   => $this->t('You have no connected apps.'),
			'$apps'                => $applications,
		]);
	}
}
