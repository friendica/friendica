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

namespace Friendica\Module\Media\Photo;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Photo;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Browser for Photos
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

		$album = $this->parameters['album'] ?? null;

		$photos = Photo::getBrowsablePhotosForUser($this->session->getLocalUserId(), $album);
		$albums = $album ? false : Photo::getBrowsableAlbumsForUser($this->session->getLocalUserId());

		$path = [
			'' => $this->t('Photos'),
		];
		if (!empty($album)) {
			$path[$album] = $album;
		}

		$photosArray = array_map([$this, 'map_files'], $photos);

		$tpl    = Renderer::getMarkupTemplate('media/browser.tpl');
		$output = Renderer::replaceMacros($tpl, [
			'$type'     => 'photo',
			'$path'     => $path,
			'$folders'  => $albums,
			'$files'    => $photosArray,
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
		$types      = Images::supportedTypes();
		$ext        = $types[$record['type']];
		$filename_e = $record['filename'];

		// Take the largest picture that is smaller or equal 640 pixels
		$photo = Photo::selectFirst(
			['scale'],
			[
				"`resource-id` = ? AND `height` <= ? AND `width` <= ?",
				$record['resource-id'],
				640,
				640
			],
			['order' => ['scale']]);
		$scale = $photo['scale'] ?? $record['loq'];

		return [
			sprintf('%s/photos/%s/image/%s', $this->baseUrl, $this->app->getLoggedInUserNickname(), $record['resource-id']),
			$filename_e,
			sprintf('%s/photo/%s-%s.%s', $this->baseUrl, $record['resource-id'], $scale, $ext),
			$record['desc'],
		];
	}
}
