<?php
/**
 * @file src/Worker/SearchDirectory.php
 */
namespace Friendica\Worker;

use Friendica\Core\Cache\Cache;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Network\Probe;
use Friendica\Registry\Core;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class SearchDirectory
{
	// <search pattern>: Searches for "search pattern" in the directory.
	public static function execute($search)
	{
		if (!Config::get('system', 'poco_local_search')) {
			Logger::info('Local search is not enabled');
			return;
		}

		$data = Core::cache()->get('SearchDirectory:' . $search);
		if (!is_null($data)) {
			// Only search for the same item every 24 hours
			if (time() < $data + (60 * 60 * 24)) {
				Logger::info('Already searched this in the last 24 hours', ['search' => $search]);
				return;
			}
		}

		$x = Network::fetchUrl(Search::getGlobalDirectory() . '/lsearch?p=1&n=500&search=' . urlencode($search));
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
		Core::cache()->set('SearchDirectory:' . $search, time(), Cache::DAY);
	}
}
