<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Folders;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Renames a whole folder, moving every post filed under it to the new name
 */
class Rename extends BaseModule
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

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError('folders', 'folder_rename');

		$oldname = trim($request['oldname'] ?? '');
		$newname = trim($request['newname'] ?? '');

		$this->logger->info('Rename folder', ['uid' => $uid, 'old' => $oldname, 'new' => $newname]);

		if ($oldname === '' || $newname === '' || $oldname === $newname || !Post\Category::renameFolder($uid, $oldname, $newname)) {
			$this->systemMessages->addNotice($this->t('Folder was not renamed'));
		}

		$this->baseUrl->redirect('folders');
	}

	protected function content(array $request = []): string
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		Nav::setSelected('network');
		DI::page()['aside'] .= Widget\SidebarMenu::getHTML($uid, Widget\SidebarMenu::FOLDERS);

		$file = trim($request['file'] ?? '');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('folder_rename.tpl'), [
			'$title'               => $this->t('Rename this folder'),
			'$form_security_token' => self::getFormSecurityToken('folder_rename'),
			'$oldname'             => $file,
			'$newname'             => ['newname', $this->t('New folder name:'), $file],
			'$submit'              => $this->t('Rename'),
			'$cancel'              => $this->t('Cancel'),
		]);
	}
}
