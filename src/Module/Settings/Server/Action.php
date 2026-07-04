<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\Server;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Federation\Repository\GServer;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\User\Settings\Repository\UserGServer;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Action extends \Friendica\BaseModule
{
	public function __construct(private readonly GServer $gserverRepo, private readonly UserGServer $repository, private readonly IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	public function content(array $request = []): string
	{
		$GServer = $this->gserverRepo->selectOneById($this->parameters['gsid']);

		switch ($this->parameters['action']) {
			case 'ignore':
				$action = $this->t('Do you want to ignore this server?');
				$desc   = $this->t("You won't see any content from this server including reshares in your Network page, the community pages and individual conversations.");
				break;
			case 'unignore':
				$action = $this->t('Do you want to unignore this server?');
				$desc   = '';
				break;
			default:
				throw new BadRequestException('Unknown user server action ' . $this->parameters['action']);
		}

		$tpl = Renderer::getMarkupTemplate('settings/server/action.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'    => $this->t('Remote server settings'),
				'action'   => $action,
				'siteName' => $this->t('Server Name'),
				'siteUrl'  => $this->t('Server URL'),
				'desc'     => $desc,
				'submit'   => $this->t('Submit'),
			],

			'$action' => $this->args->getQueryString(),

			'$GServer' => $GServer,

			'$form_security_token' => self::getFormSecurityToken('settings-server'),
		]);
	}

	public function post(array $request = [])
	{
		if (!empty($request['redirect_url'])) {
			self::checkFormSecurityTokenRedirectOnError($this->args->getQueryString(), 'settings-server');
		}

		$userGServer = $this->repository->getOneByUserAndServer($this->session->getLocalUserId(), $this->parameters['gsid']);

		match ($this->parameters['action']) {
			'ignore'   => $userGServer->ignore(),
			'unignore' => $userGServer->unignore(),
			default    => throw new BadRequestException('Unknown user server action ' . $this->parameters['action']),
		};

		$this->repository->save($userGServer);

		if (!empty($request['redirect_url'])) {
			$this->baseUrl->redirect($request['redirect_url']);
		}

		System::exit();
	}
}
