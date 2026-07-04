<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Media\Attachment;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
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
 * Only used as the target action of the AjaxUpload JavaScript library
 */
class Upload extends \Friendica\BaseModule
{
	/** @var bool */
	private $isJson;

	public function __construct(
		private readonly SystemMessages $systemMessages,
		private readonly IManageConfigValues $config,
		private readonly IHandleUserSessions $userSession,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->isJson = !empty($request['response']) && $request['response'] == 'json';

		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());
		if (!$owner) {
			$this->logger->warning('Owner not found.', ['uid' => $this->userSession->getLocalUserId()]);
			$this->return(401, $this->t('Invalid request.'));
		}

		if (empty($_FILES['userfile'])) {
			$this->logger->warning('No file uploaded (empty userfile)');
			$this->return(401, $this->t('Invalid request.'), true);
		}

		$tempFileName = $_FILES['userfile']['tmp_name'];
		$fileName     = basename((string) $_FILES['userfile']['name']);
		$fileSize     = intval($_FILES['userfile']['size']);
		$maxFileSize  = Strings::getBytesFromShorthand($this->config->get('system', 'maxfilesize'));

		/*
		 * Found html code written in text field of form, when trying to upload a
		 * file with filesize greater than upload_max_filesize. Cause is unknown.
		 * Then Filesize gets <= 0.
		 */
		if ($fileSize <= 0) {
			@unlink($tempFileName);
			$msg = $this->t('Sorry, maybe your upload is bigger than the PHP configuration allows') . '<br />' . $this->t('Or - did you try to upload an empty file?');
			$this->logger->warning($msg, ['fileSize' => $fileSize]);
			$this->return(401, $msg, true);
		}

		if ($maxFileSize && $fileSize > $maxFileSize) {
			@unlink($tempFileName);
			$msg = $this->t('File exceeds size limit of %s', Strings::formatBytes($maxFileSize));
			$this->logger->warning($msg, ['fileSize' => $fileSize]);
			$this->return(401, $msg);
		}

		$newid = Attach::storeFile($tempFileName, $owner['uid'], $fileName, $_FILES['userfile']['type'] ?? '', '<' . $owner['id'] . '>');

		@unlink($tempFileName);

		if ($newid === false) {
			$msg = $this->t('File upload failed.');
			$this->logger->warning($msg);
			$this->return(500, $msg);
		}

		if ($this->isJson) {
			$content = $newid;
		} else {
			$content = "\n\n" . '[attachment]' . $newid . '[/attachment]' . "\n";
		}

		$this->return(200, $content);
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
			$message = $httpCode >= 400 ? ['error' => $message] : ['ok' => true, 'id' => $message];
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
