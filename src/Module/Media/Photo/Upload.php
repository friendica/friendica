<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous photo upload module
 *
 * Only used as the target action of the AjaxUpload JavaScript library
 */
class Upload extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $userSession;

	/** @var SystemMessages */
	private $systemMessages;

	/** @var IManageConfigValues */
	private $config;

	/** @var bool */
	private $isJson = false;

	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, IManageConfigValues $config, SystemMessages $systemMessages, IHandleUserSessions $userSession, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userSession    = $userSession;
		$this->systemMessages = $systemMessages;
		$this->config         = $config;
		$this->page           = $page;
	}

	protected function post(array $request = [])
	{
		$this->isJson = !empty($request['response']) && $request['response'] == 'json';

		$album = trim($request['album'] ?? '');

		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());

		if (!$owner) {
			$this->logger->warning('Owner not found.', ['uid' => $this->userSession->getLocalUserId()]);
			$this->return(401, $this->t('Invalid request.'));
		}

		if (empty($_FILES['userfile']) && empty($_FILES['media'])) {
			$this->logger->warning('Empty "userfile" and "media" field');
			$this->return(401, $this->t('Invalid request.'));
		}

		$src      = '';
		$filename = '';
		$filesize = 0;
		$filetype = '';

		if (!empty($_FILES['userfile'])) {
			$src      = $_FILES['userfile']['tmp_name'];
			$filename = basename($_FILES['userfile']['name']);
			$filesize = intval($_FILES['userfile']['size']);
			$filetype = $_FILES['userfile']['type'];
		} elseif (!empty($_FILES['media'])) {
			if (!empty($_FILES['media']['tmp_name'])) {
				if (is_array($_FILES['media']['tmp_name'])) {
					$src = $_FILES['media']['tmp_name'][0];
				} else {
					$src = $_FILES['media']['tmp_name'];
				}
			}

			if (!empty($_FILES['media']['name'])) {
				if (is_array($_FILES['media']['name'])) {
					$filename = basename($_FILES['media']['name'][0]);
				} else {
					$filename = basename($_FILES['media']['name']);
				}
			}

			if (!empty($_FILES['media']['size'])) {
				if (is_array($_FILES['media']['size'])) {
					$filesize = intval($_FILES['media']['size'][0]);
				} else {
					$filesize = intval($_FILES['media']['size']);
				}
			}

			if (!empty($_FILES['media']['type'])) {
				if (is_array($_FILES['media']['type'])) {
					$filetype = $_FILES['media']['type'][0];
				} else {
					$filetype = $_FILES['media']['type'];
				}
			}
		}

		if ($src == '') {
			$this->logger->warning('File source (temporary file) cannot be determined', ['$_FILES' => $_FILES]);
			$this->return(401, $this->t('Invalid request.'), true);
		}

		$this->logger->info('File upload:', [
			'src'      => $src,
			'filename' => $filename,
			'filesize' => $filesize,
			'filetype' => $filetype,
		]);

		$imagedata = @file_get_contents($src);
		$image     = new Image($imagedata, $filetype, $filename);

		if (!$image->isValid()) {
			@unlink($src);
			$this->logger->warning($this->t('Unable to process image.'), ['imagedata[]' => gettype($imagedata), 'filetype' => $filetype]);
			$this->return(401, $this->t('Unable to process image.'));
		}

		$image->orient($src);
		@unlink($src);

		$max_length = $this->config->get('system', 'max_image_length');
		if ($max_length > 0) {
			$image->scaleDown($max_length);
			$filesize = strlen($image->asString());
			$this->logger->info('File upload: Scaling picture to new size', ['max_length' => $max_length]);
		}

		$resource_id = Photo::newResource();

		// If we don't have an album name use the Wall Photos album
		if (!strlen($album)) {
			$album = $this->t('Wall Photos');
		}

		$allow_cid = '<' . $owner['id'] . '>';

		$preview = Photo::storeWithPreview($image, $owner['uid'], $resource_id, $filename, $filesize, $album, '', $allow_cid, '', '', '');
		if ($preview < 0) {
			$this->logger->warning('Photo::store() failed');
			$this->return(401, $this->t('Image upload failed.'));
		}

		$this->logger->info('upload done');
		$this->return(200, "\n\n" . Images::getBBCodeByResource($resource_id, $owner['nickname'], $preview, $image->getExt()) . "\n\n");
	}

	/**
	 * @param int    $httpCode
	 * @param string $message
	 * @param bool   $systemMessage
	 * @return void
	 * @throws InternalServerErrorException
	 */
	private function return(int $httpCode, string $message, bool $systemMessage = false): void
	{
		if ($this->isJson) {
			$message = $httpCode >= 400 ? ['error' => $message] : ['ok' => true];
			$this->response->setType(Response::TYPE_JSON, 'application/json');
			$this->response->addContent(json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		} else {
			if ($systemMessage) {
				$this->systemMessages->addNotice($message);
			}

			if ($httpCode >= 400) {
				$this->response->setStatus($httpCode, $message);
			}

			$this->response->addContent($message);
		}

		System::echoResponse($this->response->generate());
		System::exit();
	}
}
