<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\GNUSocial\GNUSocial;

use Friendica\App;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Register;

/**
 * API endpoint: /api/gnusocial/version, /api/statusnet/version
 */
class Config extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$config = [
			'site' => [
				'name'         => DI::config()->get('config', 'sitename'),
				'server'       => DI::baseUrl()->getHost(),
				'theme'        => DI::config()->get('system', 'theme'),
				'path'         => DI::baseUrl()->getPath(),
				'logo'         => DI::baseUrl() . '/images/friendica-64.png',
				'fancy'        => true,
				'language'     => DI::config()->get('system', 'language'),
				'email'        => implode(',', User::getAdminEmailList()),
				'broughtby'    => '',
				'broughtbyurl' => '',
				'timezone'     => DI::config()->get('system', 'default_timezone'),
				'closed'       => Register::getPolicy() === Register::CLOSED,
				'inviteonly'   => (bool)DI::config()->get('system', 'invitation_only'),
				'private'      => (bool)DI::config()->get('system', 'block_public'),
				'textlimit'    => (string) DI::config()->get('config', 'api_import_size', DI::config()->get('config', 'max_import_size')),
				'sslserver'    => null,
				'ssl'          => DI::baseUrl()->getScheme() === 'https' ? 'always' : '0',
				'friendica'    => [
					'FRIENDICA_PLATFORM' => App::PLATFORM,
					'FRIENDICA_VERSION'  => App::VERSION,
					'DB_UPDATE_VERSION'  => DB_UPDATE_VERSION,
				]
			],
		];

		$this->response->addFormattedContent('config', ['config' => $config], $this->parameters['extension'] ?? null);
	}
}
