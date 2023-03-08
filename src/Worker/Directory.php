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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Search;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;

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

		Logger::info('Updating directory: ' . $arr['url']);
		if (strlen($arr['url'])) {
			DI::httpClient()->fetch($dir . '?url=' . bin2hex($arr['url']), HttpClientAccept::HTML);
		}

		return;
	}

	private static function updateAll() {
		$users = DBA::select('owner-view', ['url'], ['net-publish' => true, 'account_expired' => false, 'verified' => true]);
		while ($user = DBA::fetch($users)) {
			Worker::add(Worker::PRIORITY_LOW, 'Directory', $user['url']);
		}
		DBA::close($users);
	}
}
