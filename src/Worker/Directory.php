<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Hook;
use Friendica\Core\Search;
use Friendica\Core\Worker;
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

		$arr = ['url' => $url];

		Hook::callAll('globaldir_update', $arr);

		DI::logger()->info('Updating directory: ' . $arr['url']);
		if (strlen($arr['url'])) {
			DI::httpClient()->fetch($dir . '?url=' . bin2hex($arr['url']), HttpClientAccept::HTML, 0, '', HttpClientRequest::CONTACTDISCOVER);
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
