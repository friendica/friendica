<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Folders;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Removes a whole folder, with every post filed under it
 */
class Remove extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly SystemMessages $systemMessages,
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

	protected function rawContent(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('folders', 'folder_remove', 't');

		$file = trim($request['file'] ?? '');

		$this->logger->info('Remove folder', ['uid' => $uid, 'file' => $file]);

		if ($file === '' || !Post\Category::deleteFolder($uid, $file)) {
			$this->systemMessages->addNotice($this->t('Folder was not removed'));
		}

		$this->baseUrl->redirect('folders');
	}
}
