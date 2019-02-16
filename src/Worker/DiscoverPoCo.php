<?php
/**
 * @file src/Worker/DiscoverPoCo.php
 */
namespace Friendica\Worker;

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\GContact;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class DiscoverPoCo extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @todo Clean up this mess of a parameter hell and split it in several classes
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		/*
		This function can be called in these ways:
		- dirsearch <search pattern>: Searches for "search pattern" in the directory. "search pattern" is url encoded.
		- checkcontact: Updates gcontact entries
		- suggestions: Discover other servers for their contacts.
		- server <poco url>: Searches for the poco server list. "poco url" is base64 encoded.
		- update_server: Frequently check the first 250 servers for vitality.
		- update_server_directory: Discover the given server id for their contacts
		- PortableContact::load: Load POCO data from a given POCO address
		- check_profile: Update remote profile data
		*/

		$command = isset($parameters[0]) ? $parameters[0] : '';
		$param1  = isset($parameters[1]) ? $parameters[1] : '';
		$param2  = isset($parameters[2]) ? $parameters[2] : '';
		$param3  = isset($parameters[3]) ? $parameters[3] : '';
		$param4  = isset($parameters[4]) ? $parameters[4] : '';

		$search = "";
		$mode = 0;
		if ($command == "dirsearch") {
			$search = urldecode($param1);
			$mode = 1;
		} elseif ($command == "checkcontact") {
			$mode = 2;
		} elseif ($command == "suggestions") {
			$mode = 3;
		} elseif ($command == "server") {
			$mode = 4;
		} elseif ($command == "update_server") {
			$mode = 5;
		} elseif ($command == "update_server_directory") {
			$mode = 6;
		} elseif ($command == "load") {
			$mode = 7;
		} elseif ($command == "check_profile") {
			$mode = 8;
		} elseif ($command !== "") {
			$this->logger->notice('Unknown or missing parameter.', ['cmd' => $command]);
			return;
		}

		$this->logger->info('start ' . $search);

		if ($mode == 8) {
			if ($param1 != "") {
				PortableContact::lastUpdated($param1, true);
			}
		} elseif ($mode == 7) {
			if (!empty($param4)) {
				$url = $param4;
			} else {
				$url = '';
			}
			PortableContact::load(intval($param1), intval($param2), intval($param3), $url);
		} elseif ($mode == 6) {
			PortableContact::discoverSingleServer(intval($param1));
		} elseif ($mode == 5) {
			$this->updateServer();
		} elseif ($mode == 4) {
			$server_url = $param1;
			if ($server_url == "") {
				return;
			}
			$server_url = filter_var($server_url, FILTER_SANITIZE_URL);
			if (substr(Strings::normaliseLink($server_url), 0, 7) != "http://") {
				return;
			}
			$result = "Checking server ".$server_url." - ";
			$ret = PortableContact::checkServer($server_url);
			if ($ret) {
				$result .= "success";
			} else {
				$result .= "failed";
			}
			$this->logger->info($result);
		} elseif ($mode == 3) {
			GContact::updateSuggestions();
		} elseif (($mode == 2) && Config::get('system', 'poco_completion')) {
			$this->discoverUsers();
		} elseif (($mode == 1) && ($search != "") && Config::get('system', 'poco_local_search')) {
			$this->discoverDirectory($search);
			$this->gsSearchUser($search);
		} elseif (($mode == 0) && ($search == "") && (Config::get('system', 'poco_discovery') != PortableContact::DISABLED)) {
			// Query Friendica and Hubzilla servers for their users
			PortableContact::discover();

			// Query GNU Social servers for their users ("statistics" addon has to be enabled on the GS server)
			if (!Config::get('system', 'ostatus_disabled')) {
				GContact::discoverGsUsers();
			}
		}

		$this->logger->info('end '.$search);

		return;
	}

	/**
	 * @brief Updates the first 250 servers
	 *
	 */
	private function updateServer() {
		$r = q("SELECT `url`, `created`, `last_failure`, `last_contact` FROM `gserver` ORDER BY rand()");

		if (!DBA::isResult($r)) {
			return;
		}

		$updated = 0;

		foreach ($r AS $server) {
			if (!PortableContact::updateNeeded($server["created"], "", $server["last_failure"], $server["last_contact"])) {
				continue;
			}
			$this->logger->info('Update server status for server '.$server["url"]);

			Worker::add(PRIORITY_LOW, "DiscoverPoCo", "server", $server["url"]);

			if (++$updated > 250) {
				return;
			}
		}
	}

	private function discoverUsers() {
		$this->logger->info("Discover users");

		$starttime = time();

		$users = q("SELECT `url`, `created`, `updated`, `last_failure`, `last_contact`, `server_url`, `network` FROM `gcontact`
				WHERE `last_contact` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
					`last_failure` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
					`network` IN ('%s', '%s', '%s', '%s', '') ORDER BY rand()",
				DBA::escape(Protocol::DFRN), DBA::escape(Protocol::DIASPORA),
				DBA::escape(Protocol::OSTATUS), DBA::escape(Protocol::FEED));

		if (!$users) {
			return;
		}
		$checked = 0;

		foreach ($users AS $user) {

			$urlparts = parse_url($user["url"]);
			if (!isset($urlparts["scheme"])) {
				DBA::update('gcontact', ['network' => Protocol::PHANTOM],
					['nurl' => Strings::normaliseLink($user["url"])]);
				continue;
			 }

			if (in_array($urlparts["host"], ["twitter.com", "identi.ca"])) {
				$networks = ["twitter.com" => Protocol::TWITTER, "identi.ca" => Protocol::PUMPIO];

				DBA::update('gcontact', ['network' => $networks[$urlparts["host"]]],
					['nurl' => Strings::normaliseLink($user["url"])]);
				continue;
			}

			$server_url = PortableContact::detectServer($user["url"]);
			$force_update = false;

			if ($user["server_url"] != "") {

				$force_update = (Strings::normaliseLink($user["server_url"]) != Strings::normaliseLink($server_url));

				$server_url = $user["server_url"];
			}

			if ((($server_url == "") && ($user["network"] == Protocol::FEED)) || $force_update || PortableContact::checkServer($server_url, $user["network"])) {
				$this->logger->info('Check profile.', ['url' => $user["url"]]);
				Worker::add(PRIORITY_LOW, "DiscoverPoCo", "check_profile", $user["url"]);

				if (++$checked > 100) {
					return;
				}
			} else {
				DBA::update('gcontact', ['last_failure' => DateTimeFormat::utcNow()],
					['nurl' => Strings::normaliseLink($user["url"])]);
			}

			// Quit the loop after 3 minutes
			if (time() > ($starttime + 180)) {
				return;
			}
		}
	}

	private function discoverDirectory($search) {

		$data = Cache::get("dirsearch:".$search);
		if (!is_null($data)) {
			// Only search for the same item every 24 hours
			if (time() < $data + (60 * 60 * 24)) {
				$this->logger->info("Already searched for ".$search." in the last 24 hours");
				return;
			}
		}

		$x = Network::fetchUrl(get_server()."/lsearch?p=1&n=500&search=".urlencode($search));
		$j = json_decode($x);

		if (!empty($j->results)) {
			foreach ($j->results as $jj) {
				// Check if the contact already exists
				$exists = q("SELECT `id`, `last_contact`, `last_failure`, `updated` FROM `gcontact` WHERE `nurl` = '%s'", Strings::normaliseLink($jj->url));
				if (DBA::isResult($exists)) {
					$this->logger->info("Profile ".$jj->url." already exists (".$search.")");

					if (($exists[0]["last_contact"] < $exists[0]["last_failure"]) &&
						($exists[0]["updated"] < $exists[0]["last_failure"])) {
						continue;
					}
					// Update the contact
					PortableContact::lastUpdated($jj->url);
					continue;
				}

				$server_url = PortableContact::detectServer($jj->url);
				if ($server_url != '') {
					if (!PortableContact::checkServer($server_url)) {
						$this->logger->info("Friendica server ".$server_url." doesn't answer.");
						continue;
					}
					$this->logger->info("Friendica server ".$server_url." seems to be okay.");
				}

				$data = Probe::uri($jj->url);
				if ($data["network"] == Protocol::DFRN) {
					$this->logger->info("Profile ".$jj->url." is reachable (".$search.")");
					$this->logger->info("Add profile ".$jj->url." to local directory (".$search.")");

					if ($jj->tags != "") {
						$data["keywords"] = $jj->tags;
					}

					$data["server_url"] = $data["baseurl"];

					GContact::update($data);
				} else {
					$this->logger->info("Profile ".$jj->url." is not responding or no Friendica contact - but network ".$data["network"]);
				}
			}
		}
		Cache::set("dirsearch:".$search, time(), Cache::DAY);
	}

	/**
	 * @brief Search for GNU Social user with gstools.org
	 *
	 * @param string $search User name
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function gsSearchUser($search) {

		// Currently disabled, since the service isn't available anymore.
		// It is not removed since I hope that there will be a successor.
		return false;

		$url = "http://gstools.org/api/users_search/".urlencode($search);

		$curlResult = Network::curl($url);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		$contacts = json_decode($curlResult->getBody());

		if ($contacts->status == 'ERROR') {
			return false;
		}

		/// @TODO AS is considered as a notation for constants (as they usually being written all upper-case)
		/// @TODO find all those and convert to all lower-case which is a keyword then
		foreach ($contacts->data AS $user) {
			$contact = Probe::uri($user->site_address."/".$user->name);
			if ($contact["network"] != Protocol::PHANTOM) {
				$contact["about"] = $user->description;
				GContact::update($contact);
			}
		}
	}
}
