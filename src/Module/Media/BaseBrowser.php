<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Media;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Shared base for the photo and the attachment browser.
 *
 * Both browsers feed the same template, so they have to agree on the shape of a file
 * entry. That agreement used to live only in the template, where a missing element
 * silently rendered as an empty string - which is how the attachment browser ended up
 * shipping empty alt texts. It is now declared here and enforced by PHPStan.
 */
abstract class BaseBrowser extends BaseModule
{
	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, protected IHandleUserSessions $session, protected AppHelper $appHelper, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * Turns one database record into a file entry for media/browser.tpl.
	 *
	 * The template accesses the elements by position, so every browser must return all
	 * four of them - use an empty string where a browser has nothing to offer:
	 *
	 *   0 => link to the item's own page
	 *   1 => file name, printed as the visible caption below the thumbnail
	 *   2 => image source for the thumbnail
	 *   3 => description, used as alt text; empty string marks the thumbnail decorative
	 *
	 * @param array $record
	 *
	 * @return array{0: string, 1: string, 2: string, 3: string}
	 */
	abstract protected function mapFiles(array $record): array;

	/**
	 * Applies the theme requested for this browser instance.
	 *
	 * Needed to match the correct template in a module that uses a different theme than
	 * the user/site/default.
	 */
	protected function setBrowserTheme(array $request): void
	{
		$theme = Strings::sanitizeFilePathItem($request['theme'] ?? '');

		if ($theme && is_file("view/theme/$theme/config.php")) {
			$this->appHelper->setCurrentTheme($theme);
		}
	}

	/**
	 * Renders media/browser.tpl. Keeps the label set identical for both browsers.
	 *
	 * @param string                   $type    'photo' or 'attachment'
	 * @param array<string, string>    $path    Breadcrumb, folder => label
	 * @param array<int, string>|false $folders Album list, or false when there is none
	 * @param array<int, array>        $files   Entries as returned by mapFiles()
	 */
	protected function renderBrowser(string $type, array $path, array|false $folders, array $files): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('media/browser.tpl'), [
			'$type'     => $type,
			'$path'     => $path,
			'$folders'  => $folders,
			'$files'    => $files,
			'$cancel'   => $this->t('Cancel'),
			'$nickname' => $this->session->getLocalUserNickname(),
			'$upload'   => $this->t('Upload'),
			// Label of the upload spinner in view/templates/media/browser.tpl; without it
			// the spinner renders alt="" title="".
			'$wait'                 => $this->t('Please wait'),
			'$photos_text'          => $this->t('Photos'),
			'$files_text'           => $this->t('Files'),
			'$aria_close'           => $this->t('Close'),
			'$aria_breadcrumb'      => $this->t('Breadcrumb'),
			'$aria_mode_switch'     => $this->t('Switch between photo and attachment mode'),
			'$aria_album_nav'       => $this->t('Album navigation'),
			'$aria_browser_content' => $this->t('Browser content'),
		]);
	}
}
