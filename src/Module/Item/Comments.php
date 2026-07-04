<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Return rendered comments for a thread root uri-id.
 */
class Comments extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly IManageConfigValues $config,
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
		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$uriId = (int) ($this->parameters['id'] ?? 0);
		if ($uriId <= 0) {
			throw new HTTPException\BadRequestException($this->t('Parameter id is missing.'));
		}

		$viewerUid = $this->session->getLocalUserId();
		$existing  = !empty($request['existing']) ? array_map(intval(...), explode(',', $request['existing'])) : [];
		$this->httpExit($this->htmlRenderer->renderCommentsByUriId($uriId, $viewerUid, $existing));
	}
}
