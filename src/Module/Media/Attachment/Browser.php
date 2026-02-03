<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Media\Attachment;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Attach;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Browser for Attachments
 */
class Browser extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var AppHelper */
	protected $appHelper;

	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, AppHelper $appHelper, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session   = $session;
		$this->appHelper = $appHelper;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new UnauthorizedException($this->t('You need to be logged in to access this page.'));
		}

		// Needed to match the correct template in a module that uses a different theme than the user/site/default
		$theme = Strings::sanitizeFilePathItem($request['theme'] ?? '');
		if ($theme && is_file("view/theme/$theme/config.php")) {
			$this->appHelper->setCurrentTheme($theme);
		}

		$files = Attach::selectToArray(['id', 'filename', 'filetype'], ['uid' => $this->session->getLocalUserId()]);

		$fileArray = array_map([$this, 'map_files'], $files);

		$tpl    = Renderer::getMarkupTemplate('media/browser.tpl');
		$output = Renderer::replaceMacros($tpl, [
			'$type'     => 'attachment',
			'$path'     => ['' => $this->t('Files')],
			'$folders'  => false,
			'$files'    => $fileArray,
			'$cancel'   => $this->t('Cancel'),
			'$nickname' => $this->session->getLocalUserNickname(),
			'$upload'   => $this->t('Upload'),
		]);

		if (empty($request['mode'])) {
			$this->httpExit($output);
		}

		return $output;
	}

	protected function map_files(array $record): array
	{
		list($m1, $m2) = explode('/', $record['filetype']);
		$filetype      = file_exists(sprintf('images/icons/%s.png', $m1)) ? $m1 : 'text';

		return [
			sprintf('%s/attach/%s', $this->baseUrl, $record['id']),
			$record['filename'],
			sprintf('%s/images/icons/%s.png', $this->baseUrl, $filetype),
		];
	}
}
