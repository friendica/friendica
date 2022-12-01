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

namespace Friendica\Module\Media\Attachment;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Attach;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous attachment upload module
 *
 * Only used as the target action of the AjaxUpload Javascript library
 */
class Upload extends \Friendica\BaseModule
{
	/** @var Database */
	private $database;

	/** @var IHandleUserSessions */
	private $userSession;

	/** @var IManageConfigValues */
	private $config;

	/** @var SystemMessages */
	private $systemMessages;

	/** @var bool */
	private $isJson;

	public function __construct(SystemMessages $systemMessages, IManageConfigValues $config, IHandleUserSessions $userSession, Database $database, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database       = $database;
		$this->userSession    = $userSession;
		$this->config         = $config;
		$this->systemMessages = $systemMessages;
	}

	protected function post(array $request = [])
	{
		if ($this->isJson = !empty($request['response']) && $request['response'] == 'json') {
			$this->response->setType(Response::TYPE_JSON, 'application/json');
		}

		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());
		if (!$owner) {
			$this->logger->warning('Owner not found.', ['uid' => $this->userSession->getLocalUserId()]);
			return $this->return(401, $this->t('Invalid request.'));
		}

		if (empty($_FILES['userfile'])) {
			$this->logger->warning('No file uploaded (empty userfile)');
			return $this->return(401, $this->t('Invalid request.'), true);
		}

		$tempFileName = $_FILES['userfile']['tmp_name'];
		$fileName     = basename($_FILES['userfile']['name']);
		$fileSize     = intval($_FILES['userfile']['size']);
		$maxFileSize  = $this->config->get('system', 'maxfilesize');

		/*
		 * Found html code written in text field of form, when trying to upload a
		 * file with filesize greater than upload_max_filesize. Cause is unknown.
		 * Then Filesize gets <= 0.
		 */
		if ($fileSize <= 0) {
			@unlink($tempFileName);
			$msg = $this->t('Sorry, maybe your upload is bigger than the PHP configuration allows') . '<br />' . $this->t('Or - did you try to upload an empty file?');
			$this->logger->warning($msg, ['fileSize' => $fileSize]);
			return $this->return(401, $msg, true);
		}

		if ($maxFileSize && $fileSize > $maxFileSize) {
			@unlink($tempFileName);
			$msg = $this->t('File exceeds size limit of %s', Strings::formatBytes($maxFileSize));
			$this->logger->warning($msg, ['fileSize' => $fileSize]);
			return $this->return(401, $msg);
		}

		$newid = Attach::storeFile($tempFileName, $owner['uid'], $fileName, '<' . $owner['id'] . '>');

		@unlink($tempFileName);

		if ($newid === false) {
			$msg = $this->t('File upload failed.');
			$this->logger->warning($msg);
			return $this->return(500, $msg);
		}

		if ($this->isJson) {
			$content = json_encode(['ok' => true, 'id' => $newid], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		} else {
			$content = "\n\n" . '[attachment]' . $newid . '[/attachment]' . "\n";
		}

		return $this->response->addContent($content);
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
		$this->response->setStatus($httpCode, $message);

		if ($this->isJson) {
			$this->response->addContent(json_encode(['error' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		} else {
			if ($systemMessage) {
				$this->systemMessages->addNotice($message);
			}

			$this->response->addContent($message);
		}
	}
}
