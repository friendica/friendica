<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Vote on a poll
 */
class Vote extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
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

	protected function post(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			throw new HTTPException\ForbiddenException($this->t('Access denied.'));
		}

		$uid  = $this->session->getLocalUserId();
		$item = Post::selectFirstForUser($uid, ['uri-id'], ['uid' => [0, $uid], 'id' => $this->parameters['id'] ?? 0]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		Post\QuestionVote::vote($item['uri-id'], $uid, (array) ($request['choices'] ?? []), $this->l10n);

		$this->earlyJsonExit(['status' => 'ok']);
	}
}
