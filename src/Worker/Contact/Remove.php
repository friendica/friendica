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

namespace Friendica\Worker\Contact;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Model\Contact;

/**
 * Removes a contact and all its related content
 */
class Remove extends RemoveContent
{
	public static function execute(int $id): bool
	{
		// Only delete if the contact is to be deleted
		$contact = DBA::selectFirst('contact', ['id', 'uid', 'url', 'nick', 'name'], ['deleted' => true, 'id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		if (!parent::execute($id)) {
			return false;
		}

		$ret = Contact::deleteById($id);
		Logger::info('Deleted contact', ['id' => $id, 'result' => $ret]);

		return true;
	}
}
