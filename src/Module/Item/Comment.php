<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Return a freshly posted reply rendered together with its immediate parent's
 * subtree, so it can be spliced into an already loaded thread.
 */
class Comment extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly ConversationRenderer $htmlRenderer,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = []): void
	{
		if (!$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$uriId = (int) ($this->parameters['id'] ?? 0);
		if ($uriId <= 0) {
			throw new HTTPException\BadRequestException($this->t('Parameter id is missing.'));
		}

		$viewerUid = $this->session->getLocalUserId();

		$html = $this->htmlRenderer->renderCommentByUriId($uriId, $viewerUid);
		$this->logger->debug('Rendered comment for targeted insert', ['uri-id' => $uriId, 'viewer' => $viewerUid, 'length' => strlen($html)]);

		$this->earlyHttpExit($html);
	}
}
