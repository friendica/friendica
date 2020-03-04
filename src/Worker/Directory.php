<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Network\HTTPRequest;
use Friendica\Util\Network;

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
			HTTPRequest::fetchUrl($dir . '?url=' . bin2hex($arr['url']));
		}

		return;
	}

	private static function updateAll() {
		$r = q("SELECT `url` FROM `contact`
			INNER JOIN `profile` ON `profile`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`self` AND `profile`.`net-publish` AND
					NOT `user`.`account_expired` AND `user`.`verified`");

		if (DBA::isResult($r)) {
			foreach ($r AS $user) {
				Worker::add(PRIORITY_LOW, 'Directory', $user['url']);
			}
		}
	}
}
