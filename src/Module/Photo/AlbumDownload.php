<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Photo;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Util\ZipDownload;
use Psr\Log\LoggerInterface;

class AlbumDownload extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
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

	protected function rawContent(array $request = []): never
	{
		$nickname = $this->parameters['nickname'] ?? '';
		$hexalbum = $this->parameters['album']    ?? '';

		$user = User::getByNickname($nickname);
		if (!$user) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($this->session->getLocalUserId() != $user['uid']) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		if (!Strings::isHex($hexalbum)) {
			throw new HTTPException\BadRequestException();
		}
		$album = hex2bin($hexalbum);

		if (!class_exists('ZipArchive')) {
			throw new HTTPException\InternalServerErrorException($this->t('This server is missing PHP\'s Zip extension, which is needed to download a photo album.'));
		}

		$photos = Photo::selectToArray([], ['uid' => $user['uid'], 'album' => $album, 'scale' => 0]);
		if (empty($photos)) {
			throw new HTTPException\NotFoundException($this->t('Album not found.'));
		}

		$zip = new ZipDownload(System::getTempPath());

		foreach ($photos as $photo) {
			$data = Photo::getImageDataForPhoto($photo);
			if (empty($data)) {
				$this->logger->notice('Photo without data skipped in album download', ['id' => $photo['id']]);
				continue;
			}

			$name = trim((string) $photo['filename']);
			if ($name === '') {
				$name = $photo['resource-id'] . Images::getExtensionByMimeType($photo['type']);
			}

			$zip->add($name, $data);
		}

		$zipfile = $zip->close();

		$filename = $album !== '' ? $album : $nickname;
		$ascii    = trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', (string) $filename), '-');
		if ($ascii === '') {
			$ascii = 'album';
		}

		header('Content-Type: application/zip');
		header(sprintf('Content-Disposition: attachment; filename="%s.zip"; filename*=UTF-8\'\'%s', $ascii, rawurlencode($filename . '.zip')));
		header('Content-Length: ' . filesize($zipfile));

		readfile($zipfile);
		unlink($zipfile);
		exit;
	}
}
