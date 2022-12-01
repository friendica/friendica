<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Database\Database;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous photo upload module
 *
 * Only used as the target action of the AjaxUpload Javascript library
 */
class Upload extends \Friendica\BaseModule
{
	/** @var Database */
	private $database;

	/** @var IHandleUserSessions */
	private $userSession;

	/** @var SystemMessages */
	private $systemMessages;

	/** @var IManageConfigValues */
	private $config;

	/** @var bool */
	private $isJson = false;

	public function __construct(IManageConfigValues $config, SystemMessages $systemMessages, IHandleUserSessions $userSession, Database $database, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database       = $database;
		$this->userSession    = $userSession;
		$this->systemMessages = $systemMessages;
		$this->config         = $config;
	}

	protected function post(array $request = [])
	{
		$this->isJson = !empty($request['response']) && $request['response'] == 'json';

		$album = trim($request['album'] ?? '');

		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());

		if (!$owner) {
			$this->logger->warning('Owner not found.', ['uid' => $this->userSession->getLocalUserId()]);
			return $this->return(401, $this->t('Invalid request.'));
		}

		if (empty($_FILES['userfile']) && empty($_FILES['media'])) {
			$this->logger->warning('Empty "userfile" and "media" field');
			return $this->return(401, $this->t('Invalid request.'));
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
			return $this->return(401, $this->t('Invalid request.'), true);
		}

		$filetype = Images::getMimeTypeBySource($src, $filename, $filetype);

		$this->logger->info('File upload:', [
			'src'      => $src,
			'filename' => $filename,
			'filesize' => $filesize,
			'filetype' => $filetype,
		]);

		$imagedata = @file_get_contents($src);
		$image     = new Image($imagedata, $filetype);

		if (!$image->isValid()) {
			@unlink($src);
			$this->logger->warning($this->t('Unable to process image.'), ['imagedata[]' => gettype($imagedata), 'filetype' => $filetype]);
			return $this->return(401, $this->t('Unable to process image.'));
		}

		$image->orient($src);
		@unlink($src);

		$max_length = $this->config->get('system', 'max_image_length');
		if ($max_length > 0) {
			$image->scaleDown($max_length);
			$filesize = strlen($image->asString());
			$this->logger->info('File upload: Scaling picture to new size', ['max_length' => $max_length]);
		}

		$width  = $image->getWidth();
		$height = $image->getHeight();

		$maximagesize = Strings::getBytesFromShorthand($this->config->get('system', 'maximagesize'));

		if ($maximagesize && $filesize > $maximagesize) {
			// Scale down to multiples of 640 until the maximum size isn't exceeded anymore
			foreach ([5120, 2560, 1280, 640] as $pixels) {
				if ($filesize > $maximagesize && max($width, $height) > $pixels) {
					$this->logger->info('Resize', ['size' => $filesize, 'width' => $width, 'height' => $height, 'max' => $maximagesize, 'pixels' => $pixels]);
					$image->scaleDown($pixels);
					$filesize = strlen($image->asString());
					$width    = $image->getWidth();
					$height   = $image->getHeight();
				}
			}

			if ($filesize > $maximagesize) {
				@unlink($src);
				$this->logger->notice('Image size is too big', ['size' => $filesize, 'max' => $maximagesize]);
				return $this->return(401, $this->t('Image exceeds size limit of %s', Strings::formatBytes($maximagesize)));
			}
		}

		$resource_id = Photo::newResource();

		$smallest = 0;

		// If we don't have an album name use the Wall Photos album
		if (!strlen($album)) {
			$album = $this->t('Wall Photos');
		}

		$allow_cid = '<' . $owner['id'] . '>';

		$result = Photo::store($image, $owner['uid'], 0, $resource_id, $filename, $album, 0, Photo::DEFAULT, $allow_cid);
		if (!$result) {
			$this->logger->warning('Photo::store() failed', ['result' => $result]);
			return $this->return(401, $this->t('Image upload failed.'));
		}

		if ($width > 640 || $height > 640) {
			$image->scaleDown(640);
			$result = Photo::store($image, $owner['uid'], 0, $resource_id, $filename, $album, 1, Photo::DEFAULT, $allow_cid);
			if ($result) {
				$smallest = 1;
			}
		}

		if ($width > 320 || $height > 320) {
			$image->scaleDown(320);
			$result = Photo::store($image, $owner['uid'], 0, $resource_id, $filename, $album, 2, Photo::DEFAULT, $allow_cid);
			if ($result && ($smallest == 0)) {
				$smallest = 2;
			}
		}

		$this->logger->info('upload done');
		return $this->return(200, "\n\n" . '[url=' . $this->baseUrl . '/photos/' . $owner['nickname'] . '/image/' . $resource_id . '][img]' . $this->baseUrl . "/photo/$resource_id-$smallest." . $image->getExt() . "[/img][/url]\n\n");
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
	}
}
