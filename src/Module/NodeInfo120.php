<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Model\Nodeinfo;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Version 2.0 of Nodeinfo, a standardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo120 extends BaseModule
{
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
	}

	protected function rawContent(array $request = [])
	{
		$nodeinfo = [
			'version'  => '2.0',
			'software' => [
				'name'    => 'friendica',
				'version' => App::version() . '-' . DB_UPDATE_VERSION,
			],
			'protocols'         => ['dfrn', 'activitypub'],
			'services'          => Nodeinfo::getServices(),
			'openRegistrations' => Register::getPolicy() !== Register::CLOSED,
			'usage'             => Nodeinfo::getUsage(),
			'metadata'          => [
				'nodeName'        => $this->config->get('config', 'sitename'),
				'nodeDescription' => $this->config->get('config', 'info'),
			],
		];

		if (!empty($this->config->get('system', 'diaspora_enabled'))) {
			$nodeinfo['protocols'][] = 'diaspora';
		}

		$nodeinfo['services']['inbound'][]  = 'atom1.0';
		$nodeinfo['services']['inbound'][]  = 'rss2.0';
		$nodeinfo['services']['outbound'][] = 'atom1.0';

		if (function_exists('imap_open') && !$this->config->get('system', 'imap_disabled')) {
			$nodeinfo['services']['inbound'][] = 'imap';
		}

		$nodeinfo['metadata']['explicitContent'] = $this->config->get('system', 'explicit_content', false) == true;

		$this->response->setType(ICanCreateResponses::TYPE_JSON, 'application/json; charset=utf-8');
		$this->response->addContent(json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}
