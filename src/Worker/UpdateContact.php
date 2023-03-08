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
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Network;

class UpdateContact
{
	/**
	 * Update contact data via probe
	 *
	 * @param int $contact_id Contact ID
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(int $contact_id)
	{
		// Silently dropping the task if the contact is blocked
		if (Contact::isBlocked($contact_id)) {
			return;
		}

		$success = Contact::updateFromProbe($contact_id);

		Logger::info('Updated from probe', ['id' => $contact_id, 'success' => $success]);
	}

	/**
	 * @param array|int $run_parameters Priority constant or array of options described in Worker::add
	 * @param int       $contact_id
	 * @return int
	 * @throws InternalServerErrorException
	 */
	public static function add($run_parameters, int $contact_id): int
	{
		if (!$contact_id) {
			throw new \InvalidArgumentException('Invalid value provided for contact_id');
		}

		// Dropping the task if the contact is blocked
		if (Contact::isBlocked($contact_id)) {
			return 0;
		}

		return Worker::add($run_parameters, 'UpdateContact', $contact_id);
	}
}
