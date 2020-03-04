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

use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Network\HTTPRequest;
use Friendica\Network\Probe;
use Friendica\Util\Network;
use Friendica\Util\Strings;

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

		$x = DI::httpRequest()->fetchUrl(Search::getGlobalDirectory() . '/lsearch?p=1&n=500&search=' . urlencode($search));
		$j = json_decode($x);

		if (!empty($j->results)) {
			foreach ($j->results as $jj) {
				// Check if the contact already exists
				$gcontact = DBA::selectFirst('gcontact', ['id', 'last_contact', 'last_failure', 'updated'], ['nurl' => Strings::normaliseLink($jj->url)]);
				if (DBA::isResult($gcontact)) {
					Logger::info('Profile already exists', ['profile' => $jj->url, 'search' => $search]);

					if (($gcontact['last_contact'] < $gcontact['last_failure']) &&
						($gcontact['updated'] < $gcontact['last_failure'])) {
						continue;
					}

					// Update the contact
					GContact::updateFromProbe($jj->url);
					continue;
				}

				$server_url = GContact::getBasepath($jj->url, true);
				if ($server_url != '') {
					if (!GServer::check($server_url)) {
						Logger::info("Friendica server doesn't answer.", ['server' => $server_url]);
						continue;
					}
					Logger::info('Friendica server seems to be okay.', ['server' => $server_url]);
				}

				$data = Probe::uri($jj->url);
				if ($data['network'] == Protocol::DFRN) {
					Logger::info('Add profile to local directory', ['profile' => $jj->url]);

					if ($jj->tags != '') {
						$data['keywords'] = $jj->tags;
					}

					$data['server_url'] = $data['baseurl'];

					GContact::update($data);
				} else {
					Logger::info('Profile is not responding or no Friendica contact', ['profile' => $jj->url, 'network' => $data['network']]);
				}
			}
		}
		DI::cache()->set('SearchDirectory:' . $search, time(), Duration::DAY);
	}
}
