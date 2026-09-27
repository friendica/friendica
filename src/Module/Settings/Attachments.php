<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Util\Strings;

class Attachments extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->isAuthenticated()) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/attachments', 'settings_attachments');

		$uid = DI::userSession()->getLocalUserId();

		if (!empty($request['delete'])) {
			Attach::delete(['id' => $request['delete'], 'uid' => $uid]);
			DI::baseUrl()->redirect('settings/attachments');
		}

		if (empty($_FILES['userfile'])) {
			return;
		}

		$src      = $_FILES['userfile']['tmp_name'];
		$filename = basename((string) $_FILES['userfile']['name']);
		$filesize = intval($_FILES['userfile']['size']);
		$filetype = $_FILES['userfile']['type'];

		if ($filesize <= 0) {
			DI::sysmsg()->addNotice(DI::l10n()->t('File is empty.'));
			@unlink($src);
			return;
		}

		$maxfilesize = Strings::getBytesFromShorthand(DI::config()->get('system', 'maxfilesize'));
		if ($maxfilesize && $filesize > $maxfilesize) {
			DI::sysmsg()->addNotice(DI::l10n()->t('File exceeds size limit of %s', Strings::formatBytes($maxfilesize)));
			@unlink($src);
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$newid = Attach::storeFile($src, $uid, $filename, $filetype, '<' . $owner['id'] . '>');

		@unlink($src);

		if ($newid === false) {
			DI::sysmsg()->addNotice(DI::l10n()->t('File upload failed.'));
		}

		DI::baseUrl()->redirect('settings/attachments');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$uid = DI::userSession()->getLocalUserId();

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$total = DBA::count('attach', ['uid' => $uid]);

		$attachments = Attach::selectToArray(
			['id', 'filename', 'filetype', 'filesize', 'created'],
			['uid'   => $uid],
			['order' => ['created' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]],
		);

		foreach ($attachments as $key => $attachment) {
			$attachments[$key]['filesize'] = Strings::formatBytes((int) $attachment['filesize']);
			$attachments[$key]['created']  = $this->l10n->fullDateTime($attachment['created']);
		}

		$usage = '';
		if (!empty($request['usage'])) {
			$attachment = Attach::selectFirst(['id', 'filename'], ['id' => $request['usage'], 'uid' => $uid]);
			if (!empty($attachment)) {
				$posts = [];
				foreach (Post\Media::getPostsByAttachment($attachment['id'], $uid) as $post) {
					$posts[] = [
						'url'     => 'display/' . $post['guid'],
						'created' => $this->l10n->fullDateTime($post['created']),
						'text'    => $post['title'] ?: Plaintext::shorten(BBCode::toPlaintext($post['body'], false), 100),
					];
				}

				$usage = Renderer::replaceMacros(Renderer::getMarkupTemplate('media_usage.tpl'), [
					'$title'    => $this->t('Posts using %s', $attachment['filename']),
					'$no_posts' => $this->t('This file isn\'t used in any of your posts.'),
					'$posts'    => $posts,
				]);
			}
		}

		$tpl = Renderer::getMarkupTemplate('settings/attachments.tpl');
		return Renderer::replaceMacros($tpl, [
			'$form_security_token' => self::getFormSecurityToken('settings_attachments'),
			'$title'               => $this->t('Files'),
			'$name'                => $this->t('Filename'),
			'$size'                => $this->t('Size'),
			'$created'             => $this->t('Uploaded'),
			'$delete'              => $this->t('Delete'),
			'$show_usage'          => $this->t('Used in posts'),
			'$no_attachments'      => $this->t('You have no uploaded files.'),
			'$upload'              => $this->t('Upload'),
			'$attachments'         => $attachments,
			'$usage'               => $usage,
			'$paginate'            => $pager->renderFull($total),
		]);
	}
}
