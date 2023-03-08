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

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;

class SearchDirectory
{
	// <search pattern>: Searches for "search pattern" in the directory.
	public static function execute($search)
	{
		if (!DI::config()->get('system', 'poco_local_search')) {
			Logger::info('Local search is not enabled');
			return;
		}

		$data = DI::cache()->get('SearchDirectory:' . $search);
		if (!is_null($data)) {
			// Only search for the same item every 24 hours
			if (time() < $data + (60 * 60 * 24)) {
				Logger::info('Already searched this in the last 24 hours', ['search' => $search]);
				return;
			}
		}

		$x = DI::httpClient()->fetch(Search::getGlobalDirectory() . '/lsearch?p=1&n=500&search=' . urlencode($search), HttpClientAccept::JSON);
		$j = json_decode($x);

		if (!empty($j->results)) {
			foreach ($j->results as $jj) {
				Contact::getByURL($jj->url);
			}
		}
		DI::cache()->set('SearchDirectory:' . $search, time(), Duration::DAY);
	}
}
