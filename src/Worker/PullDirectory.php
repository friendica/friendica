<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

class PullDirectory
{
	/**
	 * Pull contacts from a directory server
	 */
	public static function execute()
	{
		if (!DI::config()->get('system', 'synchronize_directory')) {
			DI::logger()->info('Synchronization deactivated');
			return;
		}

		$directory = Search::getGlobalDirectory();
		if (empty($directory)) {
			DI::logger()->info('No directory configured');
			return;
		}

		$now = (int)(DI::keyValue()->get('last-directory-sync') ?? 0);

		DI::logger()->info('Synchronization started.', ['now' => $now, 'directory' => $directory]);

		$result = DI::httpClient()->fetch($directory . '/sync/pull/since/' . $now, HttpClientAccept::JSON, 0, '', HttpClientRequest::CONTACTDISCOVER);
		if (empty($result)) {
			DI::logger()->info('Directory server return empty result.', ['directory' => $directory]);
			return;
		}

		$contacts = json_decode($result, true);
		if (empty($contacts['results'])) {
			DI::logger()->info('No results fetched.', ['directory' => $directory]);
			return;
		}

		$result = Contact::addByUrls($contacts['results']);

		$now = $contacts['now'] ?? 0;
		DI::keyValue()->set('last-directory-sync', $now);

		DI::logger()->info('Synchronization ended', ['now' => $now, 'count' => $result['count'], 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'directory' => $directory]);
	}
}
