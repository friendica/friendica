<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Ping;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact as ModelContact;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Number of threads on the conversations page of a contact that are newer than the ones shown on the page
 */
class Contact extends BaseModule
{
	public function __construct(private readonly IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$uid  = $this->session->getLocalUserId();
		$pcid = $uid ? ModelContact::getPublicContactId((int) $this->parameters['id'], $uid) : 0;

		// Only the first page is updated
		if (!$pcid || empty($request['first_created']) || !empty($request['page'])) {
			$this->earlyHttpExit('');
		}

		$count = ModelContact::countNewThreadsFromId($pcid, $uid, $request['first_created']);

		$this->earlyHttpExit($count < 100 ? (string) $count : '99+');
	}
}
