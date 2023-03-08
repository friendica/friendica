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

namespace Friendica\Module\Api\Twitter;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * API endpoint: /api/saved_searches
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-list
 */
class SavedSearches extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$terms = DBA::select('search', ['id', 'term'], ['uid' => $uid]);

		$result = [];
		while ($term = DBA::fetch($terms)) {
			$result[] = new \Friendica\Object\Api\Twitter\SavedSearch($term);
		}

		DBA::close($terms);

		$this->response->exit('terms', ['terms' => $result], $this->parameters['extension'] ?? null);
	}
}
