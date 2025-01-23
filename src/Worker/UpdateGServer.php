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
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class UpdateGServer
{
	/**
	 * Update the given server
	 *
	 * @param string  $server_url    Server URL
	 * @param boolean $only_nodeinfo Only use nodeinfo for server detection
	 * @return void
	 * @throws \Exception
	 */
	public static function execute(string $server_url, bool $only_nodeinfo)
	{
		if (empty($server_url)) {
			return;
		}

		$filtered = filter_var($server_url, FILTER_SANITIZE_URL);
		if (substr(Strings::normaliseLink($filtered), 0, 7) != 'http://') {
			GServer::setFailureByUrl($server_url);
			return;
		}

		// Silently dropping the worker task if the server domain is blocked
		if (Network::isUrlBlocked($filtered)) {
			GServer::setBlockedByUrl($filtered);
			return;
		}

		// Silently dropping the worker task if the server domain is blocked
		if (Network::isUrlBlocked($filtered)) {
			return;
		}

		if (($filtered != $server_url) && DBA::exists('gserver', ['nurl' => Strings::normaliseLink($server_url)])) {
			GServer::setFailureByUrl($server_url);
			return;
		}

		$cleaned = GServer::cleanURL($server_url);
		if (($cleaned != $server_url) && DBA::exists('gserver', ['nurl' => Strings::normaliseLink($server_url)])) {
			GServer::setFailureByUrl($server_url);
			return;
		}

		$ret = GServer::check($filtered, '', true, $only_nodeinfo);
		DI::logger()->info('Updated gserver', ['url' => $filtered, 'result' => $ret]);
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param string    $serverUrl
	 * @param bool      $onlyNodeInfo   Only use NodeInfo for server detection
	 * @return int
	 * @throws InternalServerErrorException
	 */
	public static function add($run_parameters, string $serverUrl, bool $onlyNodeInfo = false): int
	{
		// Dropping the worker task if the server domain is blocked
		if (Network::isUrlBlocked($serverUrl)) {
			GServer::setBlockedByUrl($serverUrl);
			return 0;
		}

		// We have to convert the Uri back to string because worker parameters are saved in JSON format which
		// doesn't allow for structured objects.
		return Worker::add($run_parameters, 'UpdateGServer', $serverUrl, $onlyNodeInfo);
	}
}
