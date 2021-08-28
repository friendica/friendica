<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;

class UpdateServerDirectory
{
	/**
	 * Query the given server for their users
	 * 
	 * @param array $gserver Server record
	 */
	public static function execute(array $gserver)
	{
		if ($gserver['directory-type'] == GServer::DT_MASTODON) {
			self::discoverMastodonDirectory($gserver);
		} elseif (!empty($gserver['poco'])) {
			self::discoverPoCo($gserver);
		}
	}

	private static function discoverPoCo(array $gserver)
	{
		$result = DI::httpClient()->fetch($gserver['poco'] . '?fields=urls');
		if (empty($result)) {
			Logger::info('Empty result', ['url' => $gserver['url']]);
			return;
		}

		$contacts = json_decode($result, true);
		if (empty($contacts['entry'])) {
			Logger::info('No contacts', ['url' => $gserver['url']]);
			return;
		}

		Logger::info('PoCo discovery started', ['poco' => $gserver['poco']]);

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

		Logger::info('PoCo discovery ended', ['count' => $result['count'], 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'poco' => $gserver['poco']]);
	}

	private static function discoverMastodonDirectory(array $gserver)
	{		
		$result = DI::httpClient()->fetch($gserver['url'] . '/api/v1/directory?order=new&local=true&limit=200&offset=0');
		if (empty($result)) {
			Logger::info('Empty result', ['url' => $gserver['url']]);
			return;
		}

		$accounts = json_decode($result, true);
		if (empty($accounts)) {
			Logger::info('No contacts', ['url' => $gserver['url']]);
			return;
		}

		Logger::info('Account discovery started', ['url' => $gserver['url']]);

		$urls = [];
		foreach ($accounts as $account) {
			if (!empty($account['url'])) {
				$urls[] = $account['url'];
			}
		}

		$result = Contact::addByUrls($urls);

		Logger::info('Account discovery ended', ['count' => $result['count'], 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'url' => $gserver['url']]);
	}
}
