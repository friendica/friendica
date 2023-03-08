<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;

class PullDirectory
{
	/**
	 * Pull contacts from a directory server
	 */
	public static function execute()
	{
		if (!DI::config()->get('system', 'synchronize_directory')) {
			Logger::info('Synchronization deactivated');
			return;
		}

		$directory = Search::getGlobalDirectory();
		if (empty($directory)) {
			Logger::info('No directory configured');
			return;
		}

		$now = (int)(DI::keyValue()->get('last-directory-sync') ?? 0);

		Logger::info('Synchronization started.', ['now' => $now, 'directory' => $directory]);

		$result = DI::httpClient()->fetch($directory . '/sync/pull/since/' . $now, HttpClientAccept::JSON);
		if (empty($result)) {
			Logger::info('Directory server return empty result.', ['directory' => $directory]);
			return;
		}

		$contacts = json_decode($result, true);
		if (empty($contacts['results'])) {
			Logger::info('No results fetched.', ['directory' => $directory]);
			return;
		}

		$result = Contact::addByUrls($contacts['results']);

		$now = $contacts['now'] ?? 0;
		DI::keyValue()->set('last-directory-sync', $now);

		Logger::info('Synchronization ended', ['now' => $now, 'count' => $result['count'], 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged'], 'directory' => $directory]);
	}
}
