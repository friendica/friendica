<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Search;
use Friendica\Core\Worker;
use Friendica\Event\GlobalDirUpdateEvent;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

/**
 * Sends updated profile data to the directory
 */
class Directory
{
	public static function execute(string $url = '')
	{
		$dir = Search::getGlobalDirectory();

		if (!strlen($dir)) {
			return;
		}

		if ($url == '') {
			self::updateAll();
			return;
		}

		$dir .= "/submit";

		$url = DI::eventDispatcher()->dispatch(
			new GlobalDirUpdateEvent($url),
		)->getUrl();

		DI::logger()->info('Updating directory: ' . $url);
		if (strlen((string) $url)) {
			DI::httpClient()->fetch($dir . '?url=' . bin2hex((string) $url), HttpClientAccept::HTML, 0, '', HttpClientRequest::CONTACTDISCOVER);
		}

		return;
	}

	private static function updateAll()
	{
		$users = DBA::select('owner-view', ['url'], ['net-publish' => true, 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false]);
		while ($user = DBA::fetch($users)) {
			Worker::add(Worker::PRIORITY_LOW, 'Directory', $user['url']);
		}
		DBA::close($users);
	}
}
