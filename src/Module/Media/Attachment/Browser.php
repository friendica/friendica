<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Media\Attachment;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
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
	/** @var App */
	protected $app;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, App $app, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
		$this->app     = $app;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new UnauthorizedException($this->t('You need to be logged in to access this page.'));
		}

		// Needed to match the correct template in a module that uses a different theme than the user/site/default
		$theme = Strings::sanitizeFilePathItem($request['theme'] ?? '');
		if ($theme && is_file("view/theme/$theme/config.php")) {
			$this->app->setCurrentTheme($theme);
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
			'$nickname' => $this->app->getLoggedInUserNickname(),
			'$upload'   => $this->t('Upload'),
		]);

		if (empty($request['mode'])) {
			System::httpExit($output);
		}

		return $output;
	}

	protected function map_files(array $record): array
	{
		list($m1, $m2) = explode('/', $record['filetype']);
		$filetype      = file_exists(sprintf('images/icons/%s.png', $m1) ? $m1 : 'text');

		return [
			sprintf('%s/attach/%s', $this->baseUrl, $record['id']),
			$record['filename'],
			sprintf('%s/images/icon/16/%s.png', $this->baseUrl, $filetype),
		];
	}
}
