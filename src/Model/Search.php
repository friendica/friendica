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

use Friendica\Database\DBA;

/**
 * Model for DB specific logic for the search entity
 */
class Search
{
	/**
	 * Returns the list of user defined tags (e.g. #Friendica)
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getUserTags(): array
	{
		$termsStmt = DBA::p("SELECT DISTINCT(`term`) FROM `search`");

		$tags = [];

		while ($term = DBA::fetch($termsStmt)) {
			$tags[] = trim(mb_strtolower($term['term']), '#');
		}
		DBA::close($termsStmt);
		return $tags;
	}
}
