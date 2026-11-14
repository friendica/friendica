<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Calendar;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Event;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * POST-Controller that remembers the calendar view the user has switched to
 */
class View extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, IManagePersonalConfigValues $pConfig, array $server, EventDispatcherInterface $eventDispatcher, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);

		$this->session = $session;
		$this->pConfig = $pConfig;
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\UnauthorizedException($this->t('Permission denied.'));
		}

		self::checkFormSecurityTokenForbiddenOnError('calendar-view');

		$view = $request['view'] ?? '';
		if (!is_string($view) || !array_key_exists($view, Event::getCalendarViews())) {
			throw new HTTPException\BadRequestException($this->t('Invalid Request'));
		}

		$this->pConfig->set($uid, 'calendar', 'default_view', $view);

		$this->response->setStatus(204);
		$this->response->setType(Response::TYPE_BLANK);
	}
}
