<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Return the language of a given item uri-id, or, without an id, detect the
 * language of a text that is still being composed.
 */
class Language extends BaseModule
{
	public function __construct(private readonly ContentItem $item, private readonly IHandleUserSessions $session, EventDispatcherInterface $eventDispatcher, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			throw new HttpException\ForbiddenException($this->l10n->t('Access denied.'));
		}

		if (empty($this->parameters['id'])) {
			$languages = $this->item->getLanguageArray((string) ($request['body'] ?? ''), 1);
			$lang      = array_key_first($languages);
			$this->earlyJsonExit(['lang' => $lang]);
		}

		$item = Post::selectFirstForUser($this->session->getLocalUserId(), ['language'], ['uid' => [0, $this->session->getLocalUserId()], 'uri-id' => $this->parameters['id']]);
		if (empty($item)) {
			throw new HTTPException\NotFoundException();
		}

		$this->earlyHttpExit($this->item->getLanguageMessage($item));
	}
}
