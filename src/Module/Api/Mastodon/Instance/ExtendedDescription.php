<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Instance;

use DateTime;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/instance/#extended_description
 */
class ExtendedDescription extends BaseApi
{
	public function __construct(\Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, private readonly IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$account = User::getSystemAccount();

		$this->jsonExit(new Mastodon\ExtendedDescription(new DateTime($account['updated']), $this->config->get('config', 'info')));
	}
}
