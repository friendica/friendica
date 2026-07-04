<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Return the search text of a given item id
 */
class Searchtext extends BaseModule
{
	public function __construct(private readonly IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			throw new HttpException\ForbiddenException($this->l10n->t('Access denied.'));
		}

		if (empty($this->parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$item = Post::selectFirstForUser($this->session->getLocalUserId(), ['uri-id'], ['uid' => [0, $this->session->getLocalUserId()], 'uri-id' => $this->parameters['id']]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		$search = DBA::selectFirst('post-searchindex', [], ['uri-id' => $item['uri-id']]);
		if (empty($search)) {
			$this->httpExit($this->l10n->t('No search text found for this item. This can happen when the author has set their account to be non-discoverable or their posts to be non-indexable for searches.'));
		}

		$this->httpExit(Post\Engagement::unescapeKeywords($search['searchtext']));
	}
}
