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
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;

/**
 * Expires old item entries
 */
class Expire
{
	public static function execute($param = '', $hook_function = '')
	{
		$a = DI::app();

		Hook::loadHooks();

		if (intval($param) > 0) {
			$user = DBA::selectFirst('user', ['uid', 'username', 'expire'], ['uid' => $param]);
			if (DBA::isResult($user)) {
				Logger::info('Expire items', ['user' => $user['uid'], 'username' => $user['username'], 'interval' => $user['expire']]);
				Item::expire($user['uid'], $user['expire']);
				Logger::info('Expire items done', ['user' => $user['uid'], 'username' => $user['username'], 'interval' => $user['expire']]);
			}
			return;
		} elseif ($param == 'hook' && !empty($hook_function)) {
			foreach (Hook::getByName('expire') as $hook) {
				if ($hook[1] == $hook_function) {
					Logger::info('Calling expire hook', ['hook' => $hook[1]]);
					Hook::callSingle('expire', $hook, $data);
				}
			}
			return;
		}

		Logger::notice('start expiry');

		$r = DBA::select('user', ['uid', 'username'], ["`expire` != ?", 0]);
		while ($row = DBA::fetch($r)) {
			Logger::info('Calling expiry', ['user' => $row['uid'], 'username' => $row['username']]);
			Worker::add(['priority' => $a->getQueueValue('priority'), 'created' => $a->getQueueValue('created'), 'dont_fork' => true],
				'Expire', (int)$row['uid']);
		}
		DBA::close($r);

		Logger::notice('calling hooks');
		foreach (Hook::getByName('expire') as $hook) {
			Logger::info('Calling expire', ['hook' => $hook[1]]);
			Worker::add(['priority' => $a->getQueueValue('priority'), 'created' => $a->getQueueValue('created'), 'dont_fork' => true],
				'Expire', 'hook', $hook[1]);
		}

		Logger::notice('calling hooks done');

		return;
	}
}
