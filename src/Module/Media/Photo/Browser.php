<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Media\Photo;

use Friendica\Model\Photo;
use Friendica\Module\Media\BaseBrowser;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Util\Images;
use Friendica\Util\Proxy;

/**
 * Browser for Photos
 */
class Browser extends BaseBrowser
{
	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new UnauthorizedException($this->t('You need to be logged in to access this page.'));
		}

		$this->setBrowserTheme($request);

		$album = $this->parameters['album'] ?? null;

		$photos = Photo::getBrowsablePhotosForUser($this->session->getLocalUserId(), $album);
		$albums = $album ? false : Photo::getBrowsableAlbumsForUser($this->session->getLocalUserId());

		$path = ['' => $this->t('Photos')];
		if (!empty($album)) {
			$path[$album] = $album;
		}

		$output = $this->renderBrowser(
			'photo',
			$path,
			$albums,
			array_map($this->mapFiles(...), $photos),
		);

		if (empty($request['mode'])) {
			$this->earlyHttpExit($output);
		}

		return $output;
	}

	/**
	 * @inheritDoc
	 */
	protected function mapFiles(array $record): array
	{
		$ext = Images::getExtensionByMimeType($record['type']);

		// Take the largest picture that is smaller or equal 640 pixels
		$photo = Photo::selectFirst(
			['scale'],
			[
				"`resource-id` = ? AND `height` <= ? AND `width` <= ?",
				$record['resource-id'],
				Proxy::PIXEL_MEDIUM,
				Proxy::PIXEL_MEDIUM,
			],
			['order' => ['scale']],
		);
		$scale = $photo['scale'] ?? $record['loq'];

		return [
			sprintf('%s/photos/%s/image/%s', $this->baseUrl, $this->session->getLocalUserNickname(), $record['resource-id']),
			(string) $record['filename'],
			sprintf('%s/photo/%s-%s%s', $this->baseUrl, $record['resource-id'], $scale, $ext),
			(string) ($record['desc'] ?? ''),
		];
	}
}
