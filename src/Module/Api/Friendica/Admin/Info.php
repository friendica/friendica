<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Admin;

use Friendica\App;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Returns information about the current node for administration purposes
 * Like for alerting systems
 *
 * API endpoint: api/friendica/admin/info
 */
class Info extends BaseApi
{
	/** @var UserSession */
	private $userSession;

	public function __construct(UserSession $userSession, \Friendica\Factory\Api\Mastodon\Error $errorFactory, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userSession = $userSession;
	}

	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);

		if (!$this->userSession->isSiteAdmin()) {
			$this->logAndJsonError(401, $this->errorFactory->Unauthorized());
		}

		$adminInfo = [
			'php' => [
				'version'             => phpversion(),
				'upload_max_filesize' => ini_get('upload_max_filesize'),
				'post_max_size'       => ini_get('post_max_size'),
				'memory_limit'        => ini_get('memory_limit')
			],
			'mysql' => [
				'max_allowed_packet' => DBA::getVariable('max_allowed_packet'),
			],
			'worker' => [
				'last_call'   => Worker::getLastCall(),
				'deffered'    => Worker::getDeferredMessagesCount(),
				'workerqueue' => Worker::getWorkerQueueCount(),
			],
			'update' => [
				'dbupdate_status' => DBStructure::getUpdateStatus(),
				'update_status'   => Update::getStatus(),
				'has_update'      => Update::needsUpdate(),
				'git_version'     => Update::getGitVersion(),
			]
		];

		$this->response->setType(ICanCreateResponses::TYPE_JSON, 'application/json; charset=utf-8');
		$this->response->addContent(json_encode($adminInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}
