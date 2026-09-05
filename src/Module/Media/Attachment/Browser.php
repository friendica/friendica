<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Media\Attachment;

use Friendica\Model\Attach;
use Friendica\Module\Media\BaseBrowser;
use Friendica\Network\HTTPException\UnauthorizedException;

/**
 * Browser for Attachments
 */
class Browser extends BaseBrowser
{
	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new UnauthorizedException($this->t('You need to be logged in to access this page.'));
		}

		$this->setBrowserTheme($request);

		$files = Attach::selectToArray(['id', 'filename', 'filetype'], ['uid' => $this->session->getLocalUserId()]);

		$output = $this->renderBrowser(
			'attachment',
			['' => $this->t('Files')],
			false,
			array_map($this->mapFiles(...), $files),
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
		[$m1]     = explode('/', (string) $record['filetype']);
		$filetype = file_exists(sprintf('images/icons/%s.png', $m1)) ? $m1 : 'text';

		return [
			sprintf('%s/attach/%s', $this->baseUrl, $record['id']),
			(string) $record['filename'],
			sprintf('%s/images/icons/%s.png', $this->baseUrl, $filetype),
			// Attachments carry no description, so the thumbnail stays decorative.
			'',
		];
	}
}
