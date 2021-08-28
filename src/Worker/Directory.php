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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Sends updated profile data to the directory
 */
class Directory
{
	public static function execute($url = '')
	{
		$dir = DI::config()->get('system', 'directory');

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

		Logger::log('Updating directory: ' . $arr['url'], Logger::DEBUG);
		if (strlen($arr['url'])) {
			DI::httpClient()->fetch($dir . '?url=' . bin2hex($arr['url']));
		}

		return;
	}

	private static function updateAll() {
		$users = DBA::select('owner-view', ['url'], ['net-publish' => true, 'account_expired' => false, 'verified' => true]);
		while ($user = DBA::fetch($users)) {
			Worker::add(PRIORITY_LOW, 'Directory', $user['url']);
		}
		DBA::close($users);
	}
}
