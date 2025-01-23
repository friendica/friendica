<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GServer;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class UpdateServerPeers
{
	/**
	 * Query the given server for their known peers
	 *
	 * @param string $url Server URL
	 * @return void
	 */
	public static function execute(string $url)
	{
		if (!DI::config()->get('system', 'poco_discovery')) {
			return;
		}

		try {
			$ret = DI::httpClient()->get($url . '/api/v1/instance/peers', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERDISCOVER]);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return;
		}
		if (!$ret->isSuccess() || empty($ret->getBodyString())) {
			DI::logger()->info('Server is not reachable or does not offer the "peers" endpoint', ['url' => $url]);
			return;
		}

		$peers = json_decode($ret->getBodyString());
		if (empty($peers) || !is_array($peers)) {
			DI::logger()->info('Server does not have any peers listed', ['url' => $url]);
			return;
		}

		DI::logger()->info('Server peer update start', ['url' => $url]);

		$total = 0;
		$added = 0;
		foreach ($peers as $peer) {
			if (Network::isUrlBlocked('https://' . $peer)) {
				// Ignore blocked systems as soon as possible in the loop to avoid being slowed down by tar pits
				continue;
			}

			++$total;
			if (DBA::exists('gserver', ['nurl' => Strings::normaliseLink('http://' . $peer)])) {
				// We already know this server
				continue;
			}
			// This endpoint doesn't offer the schema. So we assume that it is HTTPS.
			GServer::add('https://' . $peer);
			++$added;
			Worker::coolDown();
		}
		DI::logger()->info('Server peer update ended', ['total' => $total, 'added' => $added, 'url' => $url]);
	}
}
