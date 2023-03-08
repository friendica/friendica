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

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;

class AddContact
{
	/**
	 * Add contact data via probe
	 * @param int    $uid User ID
	 * @param string $url Contact link
	 */
	public static function execute(int $uid, string $url)
	{
		try {
			if ($uid == 0) {
				// Adding public contact
				$result = Contact::getIdForURL($url);
				DI::logger()->info('Added public contact', ['url' => $url, 'result' => $result]);
				return;
			}

			$result = Contact::createFromProbeForUser($uid, $url);
			DI::logger()->info('Added contact for user', ['uid' => $uid, 'url' => $url, 'result' => $result]);
		} catch (InternalServerErrorException $e) {
			DI::logger()->warning('Internal server error.', ['exception' => $e, 'uid' => $uid, 'url' => $url]);
		} catch (NotFoundException $e) {
			DI::logger()->notice('uid not found.', ['exception' => $e, 'uid' => $uid, 'url' => $url]);
		} catch (\ImagickException $e) {
			DI::logger()->notice('Imagick not found.', ['exception' => $e, 'uid' => $uid, 'url' => $url]);
		}
	}
}
