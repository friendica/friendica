<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Model\Contact;

class AddContact
{
	/**
	 * Add contact data via probe
	 * @param int    $uid User ID
	 * @param string $url Contact link
	 */
	public static function execute(int $uid, string $url)
	{
		if ($uid == 0) {
			// Adding public contact
			$result = Contact::getIdForURL($url);
			Logger::info('Added public contact', ['url' => $url, 'result' => $result]);
			return;
		}

		$result = Contact::createFromProbeForUser($uid, $url);
		Logger::info('Added contact', ['uid' => $uid, 'url' => $url, 'result' => $result]);
	}
}
