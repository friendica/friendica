<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Statistics extends BaseModule
{
	/** @var IManageConfigValues */
	protected $config;
	/** @var IManageKeyValuePairs */
	protected $keyValue;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, IManageConfigValues $config, IManageKeyValuePairs $keyValue, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config   = $config;
		$this->keyValue = $keyValue;
		if (!$this->config->get("system", "nodeinfo")) {
			throw new NotFoundException();
		}
	}

	protected function rawContent(array $request = [])
	{
		$registration_open = Register::getPolicy() !== Register::CLOSED
			&& !$this->config->get('config', 'invitation_only');

		/// @todo mark the "service" addons and load them dynamically here
		$services = [
			'appnet'      => Addon::isEnabled('appnet'),
			'bluesky'     => Addon::isEnabled('bluesky'),
			'dreamwidth'  => Addon::isEnabled('dreamwidth'),
			'gnusocial'   => Addon::isEnabled('gnusocial'),
			'libertree'   => Addon::isEnabled('libertree'),
			'livejournal' => Addon::isEnabled('livejournal'),
			'pumpio'      => Addon::isEnabled('pumpio'),
			'twitter'     => Addon::isEnabled('twitter'),
			'tumblr'      => Addon::isEnabled('tumblr'),
			'wordpress'   => Addon::isEnabled('wordpress'),
		];

		$statistics = array_merge([
			'name'                  => $this->config->get('config', 'sitename'),
			'network'               => App::PLATFORM,
			'version'               => App::VERSION . '-' . DB_UPDATE_VERSION,
			'registrations_open'    => $registration_open,
			'total_users'           => $this->keyValue->get('nodeinfo_total_users'),
			'active_users_halfyear' => $this->keyValue->get('nodeinfo_active_users_halfyear'),
			'active_users_monthly'  => $this->keyValue->get('nodeinfo_active_users_monthly'),
			'local_posts'           => $this->keyValue->get('nodeinfo_local_posts'),
			'services'              => $services,
		], $services);

		$this->logger->debug("statistics.", ['statistics' => $statistics]);
		$this->jsonExit($statistics);
	}
}
