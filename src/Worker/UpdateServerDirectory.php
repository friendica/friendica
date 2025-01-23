<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

class UpdateServerDirectory
{
	/**
	 * Query the given server for their users
	 *
	 * @param array $gserver Server record
	 */
	public static function execute(array $gserver)
	{
		if (!DI::config()->get('system', 'poco_discovery')) {
			return;
		}

		if ($gserver['directory-type'] == GServer::DT_MASTODON) {
			self::discoverMastodonDirectory($gserver);
		} elseif (!empty($gserver['poco'])) {
			self::discoverPoCo($gserver);
		}
	}

	private static function discoverPoCo(array $gserver)
	{
		$result = DI::httpClient()->fetch($gserver['poco'] . '?fields=urls', HttpClientAccept::JSON, 0, '', HttpClientRequest::SERVERDISCOVER);
		if (empty($result)) {
			DI::logger()->info('Empty result', ['url' => $gserver['url']]);
			return;
		}

		$contacts = json_decode($result, true);
		if (empty($contacts['entry'])) {
			DI::logger()->info('No contacts', ['url' => $gserver['url']]);
			return;
		}

		DI::logger()->info('PoCo discovery started', ['poco' => $gserver['poco']]);

		$urls = [];
		foreach (array_column($contacts['entry'], 'urls') as $url_entries) {
			foreach ($url_entries as $url_entry) {
				if (empty($url_entry['type']) || empty($url_entry['value'])) {
					continue;
				}
				if ($url_entry['type'] == 'profile') {
					$urls[] = $url_entry['value'];
				}
			}
		}

		$result = Contact::addByUrls($urls);

		DI::logger()->info('PoCo discovery ended', ['count' => $result['count'], 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'poco' => $gserver['poco']]);
	}

	private static function discoverMastodonDirectory(array $gserver)
	{
		$result = DI::httpClient()->fetch($gserver['url'] . '/api/v1/directory?order=new&local=true&limit=200&offset=0', HttpClientAccept::JSON, 0, '', HttpClientRequest::SERVERDISCOVER);
		if (empty($result)) {
			DI::logger()->info('Empty result', ['url' => $gserver['url']]);
			return;
		}

		$accounts = json_decode($result, true);
		if (!is_array($accounts)) {
			DI::logger()->info('No contacts', ['url' => $gserver['url']]);
			return;
		}

		DI::logger()->info('Account discovery started', ['url' => $gserver['url']]);

		$urls = [];
		foreach ($accounts as $account) {
			if (!empty($account['url'])) {
				$urls[] = $account['url'];
			}
		}

		$result = Contact::addByUrls($urls);

		DI::logger()->info('Account discovery ended', ['count' => $result['count'], 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'url' => $gserver['url']]);
	}
}
