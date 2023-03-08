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

namespace Friendica\Model;

use Friendica\Database\Database;
use Friendica\Database\DBA;

class Verb
{
	/**
	 * Insert a verb record and return its id
	 *
	 * @param string $verb
	 *
	 * @return integer verb id
	 * @throws \Exception
	 */
	public static function getID(string $verb): int
	{
		if (empty($verb)) {
			return 0;
		}

		$verb_record = DBA::selectFirst('verb', ['id'], ['name' => $verb]);
		if (DBA::isResult($verb_record)) {
			return $verb_record['id'];
		}

		DBA::insert('verb', ['name' => $verb], Database::INSERT_IGNORE);

		return DBA::lastInsertId();
	}

	/**
	 * Return verb name for the given ID
	 *
	 * @param integer $id
	 * @return string verb
	 */
	public static function getByID(int $id): string
	{
		if (empty($id)) {
			return '';
		}

		$verb_record = DBA::selectFirst('verb', ['name'], ['id' => $id]);
		if (!DBA::isResult($verb_record)) {
			return '';
		}

		return $verb_record['name'];
	}
}
