<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Folders;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Content\Post\Repository\PostCategory;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Renames a whole folder, moving every post filed under it to the new name
 */
class Rename extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly PostCategory $postCategory,
		private readonly SystemMessages $systemMessages,
		private readonly Widget\SidebarMenu $sidebarMenu,
		private Page $page,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters,
		EventDispatcherInterface $eventDispatcher,
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
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

		$oldTagId = $this->postCategory->findFolderId($oldname);
		if ($oldTagId !== null && $oldTagId === $this->postCategory->findFolderId($newname)) {
			// Folder names are tags and these are compared case insensitive
			$this->systemMessages->addNotice($this->t('Folder names that only differ in upper and lower case are the same folder.'));
		} elseif ($oldname === '' || $newname === '' || !$this->postCategory->renameFolder($uid, $oldname, $newname)) {
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
		$this->page['aside'] .= $this->sidebarMenu->getHTML($uid, Widget\SidebarMenu::FOLDERS);

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
