<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Protocol\Relay;

/**
 * Node subscription preferences for social relay systems
 * @see https://git.feneas.org/jaywink/social-relay/blob/master/docs/relays.md
 */
class XSocialRelay extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		$scope = $config->get('system', 'relay_scope');

		$relay = [
			'subscribe' => ($scope != Relay::SCOPE_NONE),
			'scope'     => $scope,
			'tags'      => ($scope == Relay::SCOPE_TAGS) ? Relay::getSubscribedTags() : [],
			'protocols' => [
				'activitypub' => [
					'actor'   => DI::baseUrl() . '/friendica',
					'receive' => DI::baseUrl() . '/inbox',
				],
				'dfrn' => [
					'receive' => DI::baseUrl() . '/dfrn_notify',
				],
			],
		];

		if (DI::config()->get("system", "diaspora_enabled")) {
			$relay['protocols']['diaspora'] = ['receive' => DI::baseUrl() . '/receive/public'];
		}

		$this->jsonExit($relay);
	}
}
