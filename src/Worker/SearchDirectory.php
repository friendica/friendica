<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

class SearchDirectory
{
	// <search pattern>: Searches for "search pattern" in the directory.
	public static function execute($search)
	{
		if (!DI::config()->get('system', 'poco_local_search')) {
			DI::logger()->info('Local search is not enabled');
			return;
		}

		$data = DI::cache()->get('SearchDirectory:' . $search);
		if (!is_null($data)) {
			// Only search for the same item every 24 hours
			if (time() < $data + (60 * 60 * 24)) {
				DI::logger()->info('Already searched this in the last 24 hours', ['search' => $search]);
				return;
			}
		}

		$x = DI::httpClient()->fetch(Search::getGlobalDirectory() . '/lsearch?p=1&n=500&search=' . urlencode($search), HttpClientAccept::JSON, 0, '', HttpClientRequest::CONTACTDISCOVER);
		$j = json_decode($x);

		if (!empty($j->results)) {
			foreach ($j->results as $jj) {
				Contact::getByURL($jj->url);
			}
		}
		DI::cache()->set('SearchDirectory:' . $search, time(), Duration::DAY);
	}
}
