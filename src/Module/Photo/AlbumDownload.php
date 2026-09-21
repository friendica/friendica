<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
use Psr\Log\LoggerInterface;
use ZipArchive;

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

	protected function rawContent(array $request = [])
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

		$tempfile = tempnam(System::getTempPath(), 'photo-album-');

		$zip = new ZipArchive();
		$zip->open($tempfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$names = [];
		foreach ($photos as $photo) {
			$data = Photo::getImageDataForPhoto($photo);
			if (empty($data)) {
				continue;
			}

			$name = trim((string) $photo['filename']);
			if ($name === '') {
				$name = $photo['resource-id'] . Images::getExtensionByMimeType($photo['type']);
			}

			// Avoid overwriting entries when several photos share the same original file name
			if (isset($names[$name])) {
				$names[$name]++;
				$extension = strrpos($name, '.') !== false ? substr($name, strrpos($name, '.')) : '';
				$basename  = strrpos($name, '.') !== false ? substr($name, 0, strrpos($name, '.')) : $name;
				$name      = $basename . '-' . $names[$name] . $extension;
			} else {
				$names[$name] = 0;
			}

			$zip->addFromString($name, $data);
		}

		$zip->close();

		$content = file_get_contents($tempfile);
		unlink($tempfile);

		$filename = preg_replace('/[^a-z0-9_-]+/i', '-', (string) $album !== '' ? $album : $nickname);
		$filename = trim((string) $filename, '-');
		if ($filename === '') {
			$filename = 'album';
		}

		$this->response->setHeader(sprintf('Content-Disposition: attachment; filename="%s.zip"', $filename));
		$this->response->setType(Response::TYPE_BLANK, 'application/zip');
		$this->response->addContent($content);
	}
}
