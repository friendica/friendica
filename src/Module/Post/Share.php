<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Post;

use Friendica\App;
use Friendica\Content;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Generates a share BBCode block for the provided item.
 *
 * Only used in Ajax calls
 */
class Share extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var Content\Item */
	private $contentItem;

	public function __construct(Content\Item $contentItem, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->contentItem = $contentItem;
	}

	protected function rawContent(array $request = [])
	{
		$post_id = $this->parameters['post_id'];
		if (!$post_id || !$this->session->getLocalUserId()) {
			$this->httpError(403);
		}

		$item = Post::selectFirst(['private', 'body', 'uri', 'plink', 'network'], ['id' => $post_id]);
		if (!$item || in_array($item['private'], [Item::PRIVATE, Item::SERVER_ONLY])) {
			$this->httpError(404);
		}

		$shared = $this->contentItem->getSharedPost($item, ['uri']);
		if ($shared && empty($shared['comment'])) {
			$content = '[share]' . $shared['post']['uri'] . '[/share]';
		} elseif (!empty($item['plink']) && !in_array($item['network'], Protocol::FEDERATED)) {
			$content = '[attachment]' . $item['plink'] . '[/attachment]';
		} else {
			$content = '[share]' . $item['uri'] . '[/share]';
		}

		$this->httpExit($content);
	}
}
