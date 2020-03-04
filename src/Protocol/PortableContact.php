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

namespace Friendica\Protocol;

use Exception;
use Friendica\Content\Text\HTML;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Network\HTTPRequest;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 *
 * @todo Move GNU Social URL schemata (http://server.tld/user/number) to http://server.tld/username
 * @todo Fetch profile data from profile page for Redmatrix users
 * @todo Detect if it is a forum
 */
class PortableContact
{
	const DISABLED = 0;
	const USERS = 1;
	const USERS_GCONTACTS = 2;
	const USERS_GCONTACTS_FALLBACK = 3;

	/**
	 * Fetch POCO data
	 *
	 * @param integer $cid  Contact ID
	 * @param integer $uid  User ID
	 * @param integer $zcid Global Contact ID
	 * @param integer $url  POCO address that should be polled
	 *
	 * Given a contact-id (minimum), load the PortableContacts friend list for that contact,
	 * and add the entries to the gcontact (Global Contact) table, or update existing entries
	 * if anything (name or photo) has changed.
	 * We use normalised urls for comparison which ignore http vs https and www.domain vs domain
	 *
	 * Once the global contact is stored add (if necessary) the contact linkage which associates
	 * the given uid, cid to the global contact entry. There can be many uid/cid combinations
	 * pointing to the same global contact id.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function loadWorker($cid, $uid = 0, $zcid = 0, $url = null)
	{
		// Call the function "load" via the worker
		Worker::add(PRIORITY_LOW, 'FetchPoCo', (int)$cid, (int)$uid, (int)$zcid, $url);
	}

	/**
	 * Fetch POCO data from the worker
	 *
	 * @param integer $cid  Contact ID
	 * @param integer $uid  User ID
	 * @param integer $zcid Global Contact ID
	 * @param integer $url  POCO address that should be polled
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function load($cid, $uid, $zcid, $url)
	{
		if ($cid) {
			if (!$url || !$uid) {
				$contact = DBA::selectFirst('contact', ['poco', 'uid'], ['id' => $cid]);
				if (DBA::isResult($contact)) {
					$url = $contact['poco'];
					$uid = $contact['uid'];
				}
			}
			if (!$uid) {
				return;
			}
		}

		if (!$url) {
			return;
		}

		$url = $url . (($uid) ? '/@me/@all?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,contactType,generation' : '?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,contactType,generation');

		Logger::log('load: ' . $url, Logger::DEBUG);

		$fetchresult = HTTPRequest::fetchUrlFull($url);
		$s = $fetchresult->getBody();

		Logger::log('load: returns ' . $s, Logger::DATA);

		Logger::log('load: return code: ' . $fetchresult->getReturnCode(), Logger::DEBUG);

		if (($fetchresult->getReturnCode() > 299) || (! $s)) {
			return;
		}

		$j = json_decode($s, true);

		Logger::log('load: json: ' . print_r($j, true), Logger::DATA);

		if (!isset($j['entry'])) {
			return;
		}

		$total = 0;
		foreach ($j['entry'] as $entry) {
			$total ++;
			$profile_url = '';
			$profile_photo = '';
			$connect_url = '';
			$name = '';
			$network = '';
			$updated = DBA::NULL_DATETIME;
			$location = '';
			$about = '';
			$keywords = '';
			$contact_type = -1;
			$generation = 0;

			if (!empty($entry['displayName'])) {
				$name = $entry['displayName'];
			}

			if (isset($entry['urls'])) {
				foreach ($entry['urls'] as $url) {
					if ($url['type'] == 'profile') {
						$profile_url = $url['value'];
						continue;
					}
					if ($url['type'] == 'webfinger') {
						$connect_url = str_replace('acct:', '', $url['value']);
						continue;
					}
				}
			}
			if (isset($entry['photos'])) {
				foreach ($entry['photos'] as $photo) {
					if ($photo['type'] == 'profile') {
						$profile_photo = $photo['value'];
						continue;
					}
				}
			}

			if (isset($entry['updated'])) {
				$updated = date(DateTimeFormat::MYSQL, strtotime($entry['updated']));
			}

			if (isset($entry['network'])) {
				$network = $entry['network'];
			}

			if (isset($entry['currentLocation'])) {
				$location = $entry['currentLocation'];
			}

			if (isset($entry['aboutMe'])) {
				$about = HTML::toBBCode($entry['aboutMe']);
			}

			if (isset($entry['generation']) && ($entry['generation'] > 0)) {
				$generation = ++$entry['generation'];
			}

			if (isset($entry['tags'])) {
				foreach ($entry['tags'] as $tag) {
					$keywords = implode(", ", $tag);
				}
			}

			if (isset($entry['contactType']) && ($entry['contactType'] >= 0)) {
				$contact_type = $entry['contactType'];
			}

			$gcontact = ["url" => $profile_url,
					"name" => $name,
					"network" => $network,
					"photo" => $profile_photo,
					"about" => $about,
					"location" => $location,
					"keywords" => $keywords,
					"connect" => $connect_url,
					"updated" => $updated,
					"contact-type" => $contact_type,
					"generation" => $generation];

			try {
				$gcontact = GContact::sanitize($gcontact);
				$gcid = GContact::update($gcontact);

				GContact::link($gcid, $uid, $cid, $zcid);
			} catch (Exception $e) {
				Logger::log($e->getMessage(), Logger::DEBUG);
			}
		}
		Logger::log("load: loaded $total entries", Logger::DEBUG);

		$condition = ["`cid` = ? AND `uid` = ? AND `zcid` = ? AND `updated` < UTC_TIMESTAMP - INTERVAL 2 DAY", $cid, $uid, $zcid];
		DBA::delete('glink', $condition);
	}

	/**
	 * Returns a list of all known servers
	 * @return array List of server urls
	 * @throws Exception
	 */
	public static function serverlist()
	{
		$r = q(
			"SELECT `url`, `site_name` AS `displayName`, `network`, `platform`, `version` FROM `gserver`
			WHERE `network` IN ('%s', '%s', '%s') AND `last_contact` > `last_failure`
			ORDER BY `last_contact`
			LIMIT 1000",
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::OSTATUS)
		);

		if (!DBA::isResult($r)) {
			return false;
		}

		return $r;
	}

	/**
	 * Fetch server list from remote servers and adds them when they are new.
	 *
	 * @param string $poco URL to the POCO endpoint
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function fetchServerlist($poco)
	{
		$curlResult = DI::httpRequest()->curl($poco . "/@server");

		if (!$curlResult->isSuccess()) {
			return;
		}

		$serverlist = json_decode($curlResult->getBody(), true);

		if (!is_array($serverlist)) {
			return;
		}

		foreach ($serverlist as $server) {
			$server_url = str_replace("/index.php", "", $server['url']);

			$r = q("SELECT `nurl` FROM `gserver` WHERE `nurl` = '%s'", DBA::escape(Strings::normaliseLink($server_url)));

			if (!DBA::isResult($r)) {
				Logger::log("Call server check for server ".$server_url, Logger::DEBUG);
				Worker::add(PRIORITY_LOW, 'UpdateGServer', $server_url);
			}
		}
	}

	public static function discoverSingleServer($id)
	{
		$server = DBA::selectFirst('gserver', ['poco', 'nurl', 'url', 'network'], ['id' => $id]);

		if (!DBA::isResult($server)) {
			return false;
		}

		// Discover new servers out there (Works from Friendica version 3.5.2)
		self::fetchServerlist($server["poco"]);

		// Fetch all users from the other server
		$url = $server["poco"] . "/?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,contactType,generation";

		Logger::info("Fetch all users from the server " . $server["url"]);

		$curlResult = DI::httpRequest()->curl($url);

		if ($curlResult->isSuccess() && !empty($curlResult->getBody())) {
			$data = json_decode($curlResult->getBody(), true);

			if (!empty($data)) {
				self::discoverServer($data, 2);
			}

			if (DI::config()->get('system', 'poco_discovery') >= self::USERS_GCONTACTS) {
				$timeframe = DI::config()->get('system', 'poco_discovery_since');

				if ($timeframe == 0) {
					$timeframe = 30;
				}

				$updatedSince = date(DateTimeFormat::MYSQL, time() - $timeframe * 86400);

				// Fetch all global contacts from the other server (Not working with Redmatrix and Friendica versions before 3.3)
				$url = $server["poco"]."/@global?updatedSince=".$updatedSince."&fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,contactType,generation";

				$success = false;

				$curlResult = DI::httpRequest()->curl($url);

				if ($curlResult->isSuccess() && !empty($curlResult->getBody())) {
					Logger::info("Fetch all global contacts from the server " . $server["nurl"]);
					$data = json_decode($curlResult->getBody(), true);

					if (!empty($data)) {
						$success = self::discoverServer($data);
					}
				}

				if (!$success && !empty($data) && DI::config()->get('system', 'poco_discovery') >= self::USERS_GCONTACTS_FALLBACK) {
					Logger::info("Fetch contacts from users of the server " . $server["nurl"]);
					self::discoverServerUsers($data, $server);
				}
			}

			$fields = ['last_poco_query' => DateTimeFormat::utcNow()];
			DBA::update('gserver', $fields, ['nurl' => $server["nurl"]]);

			return true;
		} else {
			// If the server hadn't replied correctly, then force a sanity check
			GServer::check($server["url"], $server["network"], true);

			// If we couldn't reach the server, we will try it some time later
			$fields = ['last_poco_query' => DateTimeFormat::utcNow()];
			DBA::update('gserver', $fields, ['nurl' => $server["nurl"]]);

			return false;
		}
	}

	private static function discoverServerUsers(array $data, array $server)
	{
		if (!isset($data['entry'])) {
			return;
		}

		foreach ($data['entry'] as $entry) {
			$username = '';

			if (isset($entry['urls'])) {
				foreach ($entry['urls'] as $url) {
					if ($url['type'] == 'profile') {
						$profile_url = $url['value'];
						$path_array = explode('/', parse_url($profile_url, PHP_URL_PATH));
						$username = end($path_array);
					}
				}
			}

			if ($username != '') {
				Logger::log('Fetch contacts for the user ' . $username . ' from the server ' . $server['nurl'], Logger::DEBUG);

				// Fetch all contacts from a given user from the other server
				$url = $server['poco'] . '/' . $username . '/?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,contactType,generation';

				$curlResult = DI::httpRequest()->curl($url);

				if ($curlResult->isSuccess()) {
					$data = json_decode($curlResult->getBody(), true);

					if (!empty($data)) {
						self::discoverServer($data, 3);
					}
				}
			}
		}
	}

	private static function discoverServer(array $data, $default_generation = 0)
	{
		if (empty($data['entry'])) {
			return false;
		}

		$success = false;

		foreach ($data['entry'] as $entry) {
			$profile_url = '';
			$profile_photo = '';
			$connect_url = '';
			$name = '';
			$network = '';
			$updated = DBA::NULL_DATETIME;
			$location = '';
			$about = '';
			$keywords = '';
			$contact_type = -1;
			$generation = $default_generation;

			if (!empty($entry['displayName'])) {
				$name = $entry['displayName'];
			}

			if (isset($entry['urls'])) {
				foreach ($entry['urls'] as $url) {
					if ($url['type'] == 'profile') {
						$profile_url = $url['value'];
						continue;
					}
					if ($url['type'] == 'webfinger') {
						$connect_url = str_replace('acct:' , '', $url['value']);
						continue;
					}
				}
			}

			if (isset($entry['photos'])) {
				foreach ($entry['photos'] as $photo) {
					if ($photo['type'] == 'profile') {
						$profile_photo = $photo['value'];
						continue;
					}
				}
			}

			if (isset($entry['updated'])) {
				$updated = date(DateTimeFormat::MYSQL, strtotime($entry['updated']));
			}

			if (isset($entry['network'])) {
				$network = $entry['network'];
			}

			if (isset($entry['currentLocation'])) {
				$location = $entry['currentLocation'];
			}

			if (isset($entry['aboutMe'])) {
				$about = HTML::toBBCode($entry['aboutMe']);
			}

			if (isset($entry['generation']) && ($entry['generation'] > 0)) {
				$generation = ++$entry['generation'];
			}

			if (isset($entry['contactType']) && ($entry['contactType'] >= 0)) {
				$contact_type = $entry['contactType'];
			}

			if (isset($entry['tags'])) {
				foreach ($entry['tags'] as $tag) {
					$keywords = implode(", ", $tag);
				}
			}

			if ($generation > 0) {
				$success = true;

				Logger::log("Store profile ".$profile_url, Logger::DEBUG);

				$gcontact = ["url" => $profile_url,
						"name" => $name,
						"network" => $network,
						"photo" => $profile_photo,
						"about" => $about,
						"location" => $location,
						"keywords" => $keywords,
						"connect" => $connect_url,
						"updated" => $updated,
						"contact-type" => $contact_type,
						"generation" => $generation];

				try {
					$gcontact = GContact::sanitize($gcontact);
					GContact::update($gcontact);
				} catch (Exception $e) {
					Logger::log($e->getMessage(), Logger::DEBUG);
				}

				Logger::log("Done for profile ".$profile_url, Logger::DEBUG);
			}
		}
		return $success;
	}
}
