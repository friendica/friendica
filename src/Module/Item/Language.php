<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Item as ContentItem;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Return the language of a given item uri-id
 */
class Language extends BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var ContentItem */
	private $item;

	public function __construct(ContentItem $item, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
		$this->item    = $item;
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			throw new HttpException\ForbiddenException($this->l10n->t('Access denied.'));
		}

		if (empty($this->parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$item = Post::selectFirstForUser($this->session->getLocalUserId(), ['language'], ['uid' => [0, $this->session->getLocalUserId()], 'uri-id' => $this->parameters['id']]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		$this->httpExit($this->item->getLanguageMessage($item));
	}
}
