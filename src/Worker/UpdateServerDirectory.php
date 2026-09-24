<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

class UpdateServerDirectory
{
	// Platforms that provide a list of their groups
	public const GROUP_PLATFORMS = ['lemmy', 'piefed', 'nodebb'];

	/**
	 * Query the given server for their users
	 *
	 * @param array $gserver Server record
	 */
	public static function execute(array $gserver)
	{
		// Groups are only queried on demand, so this doesn't depend on the discovery setting
		if (in_array($gserver['platform'] ?? '', self::GROUP_PLATFORMS)) {
			self::discoverGroups($gserver);
			return;
		}

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

	private static function discoverGroups(array $gserver)
	{
		DI::logger()->info('Group discovery started', ['url' => $gserver['url'], 'platform' => $gserver['platform']]);

		if ($gserver['platform'] === 'lemmy') {
			$urls = self::getLemmyGroups($gserver['url'] . '/api/v3/community/list');
		} elseif ($gserver['platform'] === 'piefed') {
			$urls = self::getLemmyGroups($gserver['url'] . '/api/alpha/community/list');
		} else {
			$urls = self::getNodeBBGroups($gserver['url']);
		}

		foreach ($urls as $url) {
			Worker::add(Worker::PRIORITY_LOW, 'DiscoverGroup', $url);
		}

		DI::logger()->info('Group discovery ended', ['count' => count($urls), 'url' => $gserver['url']]);
	}

	/**
	 * Fetches the most active local groups from the Lemmy compatible API
	 *
	 * @param string $url API endpoint
	 *
	 * @return array Profile URLs of the groups
	 */
	private static function getLemmyGroups(string $url): array
	{
		$result = DI::httpClient()->fetch($url . '?type_=Local&sort=Active&limit=50', HttpClientAccept::JSON, 0, '', HttpClientRequest::SERVERDISCOVER);

		$urls = [];
		foreach (json_decode($result, true)['communities'] ?? [] as $entry) {
			$community = $entry['community'] ?? [];
			if (empty($community['actor_id']) || !empty($community['deleted']) || !empty($community['removed'])) {
				continue;
			}

			// Local only communities aren't federated
			if (str_starts_with($community['visibility'] ?? '', 'LocalOnly')) {
				continue;
			}

			$urls[] = $community['actor_id'];
		}

		return $urls;
	}

	/**
	 * Fetches the categories of a NodeBB server
	 *
	 * @param string $url Server URL
	 *
	 * @return array Profile URLs of the categories
	 */
	private static function getNodeBBGroups(string $url): array
	{
		$result = DI::httpClient()->fetch($url . '/api/categories', HttpClientAccept::JSON, 0, '', HttpClientRequest::SERVERDISCOVER);

		return self::getNodeBBCategories(json_decode($result, true)['categories'] ?? [], $url);
	}

	private static function getNodeBBCategories(array $categories, string $url): array
	{
		$urls = [];
		foreach ($categories as $category) {
			// Link categories only point to external pages, sections only structure the list
			if (!empty($category['cid']) && empty($category['link']) && empty($category['isSection'])) {
				$urls[] = $url . '/category/' . $category['cid'];
			}

			$urls = array_merge($urls, self::getNodeBBCategories($category['children'] ?? [], $url));
		}

		return $urls;
	}
}
