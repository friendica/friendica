<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Factory\Api\Mastodon\Account as AccountFactory;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\Instance as InstanceEntity;
use Friendica\Object\Api\Mastodon\InstanceV2 as InstanceV2Entity;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/instance/
 */
class Instance extends BaseApi
{
	public function __construct(private readonly AccountFactory $accountFactory, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, private readonly Database $database, private readonly IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @param array $request
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	protected function rawContent(array $request = [])
	{
		$administrator = User::getFirstAdmin(['nickname']);
		if ($administrator) {
			$adminContact    = $this->database->selectFirst('contact', ['uri-id'], ['nick' => $administrator['nickname'], 'self' => true]);
			$contact_account = $this->accountFactory->createFromUriId($adminContact['uri-id']);
		}

		$this->jsonExit(new InstanceEntity($this->config, $this->baseUrl, $this->database, $this->buildConfigurationInfo(), $contact_account ?? null, System::getRules()));
	}

	private function buildConfigurationInfo(): InstanceV2Entity\Configuration
	{
		$statuses_config = new InstanceV2Entity\StatusesConfig((int) $this->config->get(
			'config',
			'api_import_size',
			$this->config->get('config', 'max_import_size'),
		), 99, 23);

		$image_size_limit = Strings::getBytesFromShorthand($this->config->get('system', 'maximagesize') ?? 0);
		$max_image_length = $this->config->get('system', 'max_image_length');
		if ($max_image_length > 0) {
			$image_matrix_limit = pow($max_image_length, 2);
		} else {
			$image_matrix_limit = 33177600; // 5760^2
		}

		$media_size_limit = Strings::getBytesFromShorthand($this->config->get('system', 'maxfilesize') ?? 0);
		if (empty($media_size_limit)) {
			$media_size_limit = Strings::getBytesFromShorthand(ini_get('upload_max_filesize'));
		}

		return new InstanceV2Entity\Configuration(
			$statuses_config,
			new InstanceV2Entity\MediaAttachmentsConfig(Images::supportedMimeTypes(), $image_size_limit, $image_matrix_limit, $media_size_limit),
			new InstanceV2Entity\Polls(),
			new InstanceV2Entity\Accounts(),
		);
	}
}
