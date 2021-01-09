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

namespace Friendica\Model;

use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Register;
use Friendica\Network\CurlResult;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;

/**
 * This class handles GServer related functions
 */
class GServer
{
	// Directory types
	const DT_NONE = 0;
	const DT_POCO = 1;
	const DT_MASTODON = 2;

	// Methods to detect server types

	// Non endpoint specific methods
	const DETECT_MANUAL = 0;
	const DETECT_HEADER = 1;
	const DETECT_BODY = 2;

	// Implementation specific endpoints
	const DETECT_FRIENDIKA = 10;
	const DETECT_FRIENDICA = 11;
	const DETECT_STATUSNET = 12;
	const DETECT_GNUSOCIAL = 13;
	const DETECT_CONFIG_JSON = 14; // Statusnet, GNU Social, Older Hubzilla/Redmatrix
	const DETECT_SITEINFO_JSON = 15; // Newer Hubzilla
	const DETECT_MASTODON_API = 16;
	const DETECT_STATUS_PHP = 17; // Nextcloud
	const DETECT_V1_CONFIG = 18;

	// Standardized endpoints
	const DETECT_STATISTICS_JSON = 100;
	const DETECT_NODEINFO_1 = 101;
	const DETECT_NODEINFO_2 = 102;

	/**
	 * Check for the existance of a server and adds it in the background if not existant
	 *
	 * @param string $url
	 * @param boolean $only_nodeinfo
	 * @return void
	 */
	public static function add(string $url, bool $only_nodeinfo = false)
	{
		if (self::getID($url, false)) {
			return;
		}

		Worker::add(PRIORITY_LOW, 'UpdateGServer', $url, $only_nodeinfo);
	}

	/**
	 * Get the ID for the given server URL
	 *
	 * @param string $url
	 * @param boolean $no_check Don't check if the server hadn't been found
	 * @return int gserver id
	 */
	public static function getID(string $url, bool $no_check = false)
	{
		if (empty($url)) {
			return null;
		}

		$url = self::cleanURL($url);

		$gserver = DBA::selectFirst('gserver', ['id'], ['nurl' => Strings::normaliseLink($url)]);
		if (DBA::isResult($gserver)) {
			Logger::info('Got ID for URL', ['id' => $gserver['id'], 'url' => $url, 'callstack' => System::callstack(20)]);
			return $gserver['id'];
		}

		if ($no_check || !self::check($url)) {
			return null;
		}
	
		return self::getID($url, true);
	}

	/**
	 * Checks if the given server is reachable
	 *
	 * @param string  $profile URL of the given profile
	 * @param string  $server  URL of the given server (If empty, taken from profile)
	 * @param string  $network Network value that is used, when detection failed
	 * @param boolean $force   Force an update.
	 *
	 * @return boolean 'true' if server seems vital
	 */
	public static function reachable(string $profile, string $server = '', string $network = '', bool $force = false)
	{
		if ($server == '') {
			$contact = Contact::getByURL($profile, null, ['baseurl']);
			if (!empty($contact['baseurl'])) {
				$server = $contact['baseurl'];
			}
		}

		if ($server == '') {
			return true;
		}

		return self::check($server, $network, $force);
	}

	public static function getNextUpdateDate(bool $success, string $created = '', string $last_contact = '')
	{
		// On successful contact process check again next week
		if ($success) {
			return DateTimeFormat::utc('now +7 day');
		}

		$now = strtotime(DateTimeFormat::utcNow());

		if ($created > $last_contact) {
			$contact_time = strtotime($created);
		} else {
			$contact_time = strtotime($last_contact);
		}

		// If the last contact was less than 6 hours before then try again in 6 hours
		if (($now - $contact_time) < (60 * 60 * 6)) {
			return DateTimeFormat::utc('now +6 hour');
		}

		// If the last contact was less than 12 hours before then try again in 12 hours
		if (($now - $contact_time) < (60 * 60 * 12)) {
			return DateTimeFormat::utc('now +12 hour');
		}

		// If the last contact was less than 24 hours before then try tomorrow again
		if (($now - $contact_time) < (60 * 60 * 24)) {
			return DateTimeFormat::utc('now +1 day');
		}
		
		// If the last contact was less than a week before then try again in a week
		if (($now - $contact_time) < (60 * 60 * 24 * 7)) {
			return DateTimeFormat::utc('now +1 week');
		}

		// If the last contact was less than two weeks before then try again in two week
		if (($now - $contact_time) < (60 * 60 * 24 * 14)) {
			return DateTimeFormat::utc('now +2 week');
		}

		// If the last contact was less than a month before then try again in a month
		if (($now - $contact_time) < (60 * 60 * 24 * 30)) {
			return DateTimeFormat::utc('now +1 month');
		}

		// The system hadn't been successul contacted for more than a month, so try again in three months
		return DateTimeFormat::utc('now +3 month');
	}

	/**
	 * Checks the state of the given server.
	 *
	 * @param string  $server_url    URL of the given server
	 * @param string  $network       Network value that is used, when detection failed
	 * @param boolean $force         Force an update.
	 * @param boolean $only_nodeinfo Only use nodeinfo for server detection
	 *
	 * @return boolean 'true' if server seems vital
	 */
	public static function check(string $server_url, string $network = '', bool $force = false, bool $only_nodeinfo = false)
	{
		$server_url = self::cleanURL($server_url);
		if ($server_url == '') {
			return false;
		}

		$gserver = DBA::selectFirst('gserver', [], ['nurl' => Strings::normaliseLink($server_url)]);
		if (DBA::isResult($gserver)) {
			if ($gserver['created'] <= DBA::NULL_DATETIME) {
				$fields = ['created' => DateTimeFormat::utcNow()];
				$condition = ['nurl' => Strings::normaliseLink($server_url)];
				DBA::update('gserver', $fields, $condition);
			}

			if (!$force && (strtotime($gserver['next_contact']) > time())) {
				Logger::info('No update needed', ['server' => $server_url]);
				return (!$gserver['failed']);
			}
			Logger::info('Server is outdated. Start discovery.', ['Server' => $server_url, 'Force' => $force]);
		} else {
			Logger::info('Server is unknown. Start discovery.', ['Server' => $server_url]);
		}

		return self::detect($server_url, $network, $only_nodeinfo);
	}

	/**
	 * Set failed server status
	 *
	 * @param string $url
	 */
	public static function setFailure(string $url)
	{
		$gserver = DBA::selectFirst('gserver', [], ['nurl' => Strings::normaliseLink($url)]);
		if (DBA::isResult($gserver)) {
			$next_update = self::getNextUpdateDate(false, $gserver['created'], $gserver['last_contact']);
			DBA::update('gserver', ['failed' => true, 'last_failure' => DateTimeFormat::utcNow(),
			'next_contact' => $next_update, 'detection-method' => null],
			['nurl' => Strings::normaliseLink($url)]);
			Logger::info('Set failed status for existing server', ['url' => $url]);
			return;
		}
		DBA::insert('gserver', ['url' => $url, 'nurl' => Strings::normaliseLink($url),
			'network' => Protocol::PHANTOM, 'created' => DateTimeFormat::utcNow(),
			'failed' => true, 'last_failure' => DateTimeFormat::utcNow()]);
		Logger::info('Set failed status for new server', ['url' => $url]);
	}

	/**
	 * Remove unwanted content from the given URL
	 *
	 * @param string $url
	 * @return string cleaned URL
	 */
	public static function cleanURL(string $url)
	{
		$url = trim($url, '/');
		$url = str_replace('/index.php', '', $url);

		$urlparts = parse_url($url);
		unset($urlparts['user']);
		unset($urlparts['pass']);
		unset($urlparts['query']);
		unset($urlparts['fragment']);
		return Network::unparseURL($urlparts);
	}

	/**
	 * Return the base URL
	 *
	 * @param string $url
	 * @return string base URL
	 */
	private static function getBaseURL(string $url)
	{
		$urlparts = parse_url(self::cleanURL($url));
		unset($urlparts['path']);
		return Network::unparseURL($urlparts);
	}

	/**
	 * Detect server data (type, protocol, version number, ...)
	 * The detected data is then updated or inserted in the gserver table.
	 *
	 * @param string  $url           URL of the given server
	 * @param string  $network       Network value that is used, when detection failed
	 * @param boolean $only_nodeinfo Only use nodeinfo for server detection
	 *
	 * @return boolean 'true' if server could be detected
	 */
	public static function detect(string $url, string $network = '', bool $only_nodeinfo = false)
	{
		Logger::info('Detect server type', ['server' => $url]);
		$serverdata = ['detection-method' => self::DETECT_MANUAL];

		$original_url = $url;

		// Remove URL content that is not supposed to exist for a server url
		$url = self::cleanURL($url);

		// Get base URL
		$baseurl = self::getBaseURL($url);

		// If the URL missmatches, then we mark the old entry as failure
		if ($url != $original_url) {
			/// @todo What to do with "next_contact" here?
			DBA::update('gserver', ['failed' => true, 'last_failure' => DateTimeFormat::utcNow()],
				['nurl' => Strings::normaliseLink($original_url)]);
		}

		// When a nodeinfo is present, we don't need to dig further
		$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
		$curlResult = DI::httpRequest()->get($url . '/.well-known/nodeinfo', ['timeout' => $xrd_timeout]);
		if ($curlResult->isTimeout()) {
			self::setFailure($url);
			return false;
		}

		// On a redirect follow the new host but mark the old one as failure
		if ($curlResult->isSuccess() && (parse_url($url, PHP_URL_HOST) != parse_url($curlResult->getRedirectUrl(), PHP_URL_HOST))) {
			$curlResult = DI::httpRequest()->get($url, ['timeout' => $xrd_timeout]);
			if (parse_url($url, PHP_URL_HOST) != parse_url($curlResult->getRedirectUrl(), PHP_URL_HOST)) {
				Logger::info('Found redirect. Mark old entry as failure', ['old' => $url, 'new' => $curlResult->getRedirectUrl()]);
				self::setFailure($url);
				self::detect($curlResult->getRedirectUrl(), $network, $only_nodeinfo);
				return false;
			}
		}

		$nodeinfo = self::fetchNodeinfo($url, $curlResult);
		if ($only_nodeinfo && empty($nodeinfo)) {
			Logger::info('Invalid nodeinfo in nodeinfo-mode, server is marked as failure', ['url' => $url]);
			self::setFailure($url);
			return false;
		}

		// When nodeinfo isn't present, we use the older 'statistics.json' endpoint
		if (empty($nodeinfo)) {
			$nodeinfo = self::fetchStatistics($url);
		}

		// If that didn't work out well, we use some protocol specific endpoints
		// For Friendica and Zot based networks we have to dive deeper to reveal more details
		if (empty($nodeinfo['network']) || in_array($nodeinfo['network'], [Protocol::DFRN, Protocol::ZOT])) {
			if (!empty($nodeinfo['detection-method'])) {
				$serverdata['detection-method'] = $nodeinfo['detection-method'];
			}

			// Fetch the landing page, possibly it reveals some data
			if (empty($nodeinfo['network'])) {
				if ($baseurl == $url) {
					$basedata = $serverdata;
				} else {
					$basedata = ['detection-method' => self::DETECT_MANUAL];
				}

				$curlResult = DI::httpRequest()->get($baseurl, ['timeout' => $xrd_timeout]);
				if ($curlResult->isSuccess()) {
					if ((parse_url($baseurl, PHP_URL_HOST) != parse_url($curlResult->getRedirectUrl(), PHP_URL_HOST))) {
						Logger::info('Found redirect. Mark old entry as failure', ['old' => $url, 'new' => $curlResult->getRedirectUrl()]);
						self::setFailure($url);
						self::detect($curlResult->getRedirectUrl(), $network, $only_nodeinfo);
						return false;
					}

					$basedata = self::analyseRootHeader($curlResult, $basedata);
					$basedata = self::analyseRootBody($curlResult, $basedata, $baseurl);
				}

				if (!$curlResult->isSuccess() || empty($curlResult->getBody()) || self::invalidBody($curlResult->getBody())) {
					self::setFailure($url);
					return false;
				}

				if ($baseurl == $url) {
					$serverdata = $basedata;
				} else {
					// When the base path doesn't seem to contain a social network we try the complete path.
					// Most detectable system have to be installed in the root directory.
					// We checked the base to avoid false positives.
					$curlResult = DI::httpRequest()->get($url, ['timeout' => $xrd_timeout]);
					if ($curlResult->isSuccess()) {
						$urldata = self::analyseRootHeader($curlResult, $serverdata);
						$urldata = self::analyseRootBody($curlResult, $urldata, $url);

						$comparebase = $basedata;
						unset($comparebase['info']);
						unset($comparebase['site_name']);
						$compareurl = $urldata;
						unset($compareurl['info']);
						unset($compareurl['site_name']);

						// We assume that no one will install the identical system in the root and a subfolder
						if (!empty(array_diff($comparebase, $compareurl))) {
							$serverdata = $urldata;
						}
					}
				}
			}

			if (empty($serverdata['network']) || ($serverdata['network'] == Protocol::ACTIVITYPUB)) {
				$serverdata = self::detectMastodonAlikes($url, $serverdata);
			}

			// All following checks are done for systems that always have got a "host-meta" endpoint.
			// With this check we don't have to waste time and ressources for dead systems.
			// Also this hopefully prevents us from receiving abuse messages.
			if (empty($serverdata['network']) && !self::validHostMeta($url)) {
				self::setFailure($url);
				return false;
			}

			if (empty($serverdata['network']) || in_array($serverdata['network'], [Protocol::DFRN, Protocol::ACTIVITYPUB])) {
				$serverdata = self::detectFriendica($url, $serverdata);
			}

			// the 'siteinfo.json' is some specific endpoint of Hubzilla and Red
			if (empty($serverdata['network']) || ($serverdata['network'] == Protocol::ZOT)) {
				$serverdata = self::fetchSiteinfo($url, $serverdata);
			}

			// The 'siteinfo.json' doesn't seem to be present on older Hubzilla installations
			if (empty($serverdata['network'])) {
				$serverdata = self::detectHubzilla($url, $serverdata);
			}

			if (empty($serverdata['network']) || in_array($serverdata['detection-method'], [self::DETECT_MANUAL, self::DETECT_BODY])) {
				$serverdata = self::detectPeertube($url, $serverdata);
			}

			if (empty($serverdata['network'])) {
				$serverdata = self::detectNextcloud($url, $serverdata);
			}

			if (empty($serverdata['network'])) {
				$serverdata = self::detectGNUSocial($url, $serverdata);
			}

			$serverdata = array_merge($nodeinfo, $serverdata);
		} else {
			$serverdata = $nodeinfo;
		}

		// Detect the directory type
		$serverdata['directory-type'] = self::DT_NONE;
		$serverdata = self::checkPoCo($url, $serverdata);
		$serverdata = self::checkMastodonDirectory($url, $serverdata);

		// We can't detect the network type. Possibly it is some system that we don't know yet
		if (empty($serverdata['network'])) {
			$serverdata['network'] = Protocol::PHANTOM;
		}

		// When we hadn't been able to detect the network type, we use the hint from the parameter
		if (($serverdata['network'] == Protocol::PHANTOM) && !empty($network)) {
			$serverdata['network'] = $network;
		}

		$serverdata['url'] = $url;
		$serverdata['nurl'] = Strings::normaliseLink($url);

		// We take the highest number that we do find
		$registeredUsers = $serverdata['registered-users'] ?? 0;

		// On an active server there has to be at least a single user
		if (($serverdata['network'] != Protocol::PHANTOM) && ($registeredUsers == 0)) {
			$registeredUsers = 1;
		}

		if ($serverdata['network'] == Protocol::PHANTOM) {
			$serverdata['registered-users'] = max($registeredUsers, 1);
			$serverdata = self::detectNetworkViaContacts($url, $serverdata);
		}

		$serverdata['next_contact'] = self::getNextUpdateDate(true);

		$serverdata['last_contact'] = DateTimeFormat::utcNow();
		$serverdata['failed'] = false;

		$gserver = DBA::selectFirst('gserver', ['network'], ['nurl' => Strings::normaliseLink($url)]);
		if (!DBA::isResult($gserver)) {
			$serverdata['created'] = DateTimeFormat::utcNow();
			$ret = DBA::insert('gserver', $serverdata);
			$id = DBA::lastInsertId();
		} else {
			// Don't override the network with 'unknown' when there had been a valid entry before
			if (($serverdata['network'] == Protocol::PHANTOM) && !empty($gserver['network'])) {
				unset($serverdata['network']);
			}

			$ret = DBA::update('gserver', $serverdata, ['nurl' => $serverdata['nurl']]);
			$gserver = DBA::selectFirst('gserver', ['id'], ['nurl' => $serverdata['nurl']]);
			if (DBA::isResult($gserver)) {
				$id = $gserver['id'];
			}
		}

		if (!empty($serverdata['network']) && !empty($id) && ($serverdata['network'] != Protocol::PHANTOM)) {
			$apcontacts = DBA::count('apcontact', ['gsid' => $id]);
			$contacts = DBA::count('contact', ['uid' => 0, 'gsid' => $id]);
			$max_users = max($apcontacts, $contacts, $registeredUsers, 1);
			if ($max_users > $registeredUsers) {
				Logger::info('Update registered users', ['id' => $id, 'url' => $serverdata['nurl'], 'registered-users' => $max_users]);
				DBA::update('gserver', ['registered-users' => $max_users], ['id' => $id]);
			}
		}

		if (!empty($serverdata['network']) && in_array($serverdata['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
			self::discoverRelay($url);
		}

		return $ret;
	}

	/**
	 * Fetch relay data from a given server url
	 *
	 * @param string $server_url address of the server
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function discoverRelay(string $server_url)
	{
		Logger::info('Discover relay data', ['server' => $server_url]);

		$curlResult = DI::httpRequest()->get($server_url . '/.well-known/x-social-relay');
		if (!$curlResult->isSuccess()) {
			return;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (!is_array($data)) {
			return;
		}

		// Sanitize incoming data, see https://github.com/friendica/friendica/issues/8565
		$data['subscribe'] = (bool)$data['subscribe'] ?? false;

		if (!$data['subscribe'] || empty($data['scope']) || !in_array(strtolower($data['scope']), ['all', 'tags'])) {
			$data['scope'] = '';
			$data['subscribe'] = false;
			$data['tags'] = [];
		}

		$gserver = DBA::selectFirst('gserver', ['id', 'url', 'network', 'relay-subscribe', 'relay-scope'], ['nurl' => Strings::normaliseLink($server_url)]);
		if (!DBA::isResult($gserver)) {
			return;
		}

		if (($gserver['relay-subscribe'] != $data['subscribe']) || ($gserver['relay-scope'] != $data['scope'])) {
			$fields = ['relay-subscribe' => $data['subscribe'], 'relay-scope' => $data['scope']];
			DBA::update('gserver', $fields, ['id' => $gserver['id']]);
		}

		DBA::delete('gserver-tag', ['gserver-id' => $gserver['id']]);

		if ($data['scope'] == 'tags') {
			// Avoid duplicates
			$tags = [];
			foreach ($data['tags'] as $tag) {
				$tag = mb_strtolower($tag);
				if (strlen($tag) < 100) {
					$tags[$tag] = $tag;
				}
			}

			foreach ($tags as $tag) {
				DBA::insert('gserver-tag', ['gserver-id' => $gserver['id'], 'tag' => $tag], Database::INSERT_IGNORE);
			}
		}

		// Create or update the relay contact
		$fields = [];
		if (isset($data['protocols'])) {
			if (isset($data['protocols']['diaspora'])) {
				$fields['network'] = Protocol::DIASPORA;

				if (isset($data['protocols']['diaspora']['receive'])) {
					$fields['batch'] = $data['protocols']['diaspora']['receive'];
				} elseif (is_string($data['protocols']['diaspora'])) {
					$fields['batch'] = $data['protocols']['diaspora'];
				}
			}

			if (isset($data['protocols']['dfrn'])) {
				$fields['network'] = Protocol::DFRN;

				if (isset($data['protocols']['dfrn']['receive'])) {
					$fields['batch'] = $data['protocols']['dfrn']['receive'];
				} elseif (is_string($data['protocols']['dfrn'])) {
					$fields['batch'] = $data['protocols']['dfrn'];
				}
			}

			if (isset($data['protocols']['activitypub'])) {
				$fields['network'] = Protocol::ACTIVITYPUB;

				if (!empty($data['protocols']['activitypub']['actor'])) {
					$fields['url'] = $data['protocols']['activitypub']['actor'];
				}
				if (!empty($data['protocols']['activitypub']['receive'])) {
					$fields['batch'] = $data['protocols']['activitypub']['receive'];
				}
			}
		}

		Logger::info('Discovery ended', ['server' => $server_url, 'data' => $fields]);

		Relay::updateContact($gserver, $fields);
	}

	/**
	 * Fetch server data from '/statistics.json' on the given server
	 *
	 * @param string $url URL of the given server
	 *
	 * @return array server data
	 */
	private static function fetchStatistics(string $url)
	{
		$curlResult = DI::httpRequest()->get($url . '/statistics.json');
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return [];
		}

		$serverdata = ['detection-method' => self::DETECT_STATISTICS_JSON];

		if (!empty($data['version'])) {
			$serverdata['version'] = $data['version'];
			// Version numbers on statistics.json are presented with additional info, e.g.:
			// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
			$serverdata['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $serverdata['version']);
		}

		if (!empty($data['name'])) {
			$serverdata['site_name'] = $data['name'];
		}

		if (!empty($data['network'])) {
			$serverdata['platform'] = strtolower($data['network']);

			if ($serverdata['platform'] == 'diaspora') {
				$serverdata['network'] = Protocol::DIASPORA;
			} elseif ($serverdata['platform'] == 'friendica') {
				$serverdata['network'] = Protocol::DFRN;
			} elseif ($serverdata['platform'] == 'hubzilla') {
				$serverdata['network'] = Protocol::ZOT;
			} elseif ($serverdata['platform'] == 'redmatrix') {
				$serverdata['network'] = Protocol::ZOT;
			}
		}


		if (!empty($data['registrations_open'])) {
			$serverdata['register_policy'] = Register::OPEN;
		} else {
			$serverdata['register_policy'] = Register::CLOSED;
		}

		return $serverdata;
	}

	/**
	 * Detect server type by using the nodeinfo data
	 *
	 * @param string     $url        address of the server
	 * @param CurlResult $curlResult
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function fetchNodeinfo(string $url, CurlResult $curlResult)
	{
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo) || empty($nodeinfo['links'])) {
			return [];
		}

		$nodeinfo1_url = '';
		$nodeinfo2_url = '';

		foreach ($nodeinfo['links'] as $link) {
			if (!is_array($link) || empty($link['rel']) || empty($link['href'])) {
				Logger::info('Invalid nodeinfo format', ['url' => $url]);
				continue;
			}
			if ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/1.0') {
				$nodeinfo1_url = $link['href'];
			} elseif ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/2.0') {
				$nodeinfo2_url = $link['href'];
			}
		}

		if ($nodeinfo1_url . $nodeinfo2_url == '') {
			return [];
		}

		$server = [];

		// When the nodeinfo url isn't on the same host, then there is obviously something wrong
		if (!empty($nodeinfo2_url) && (parse_url($url, PHP_URL_HOST) == parse_url($nodeinfo2_url, PHP_URL_HOST))) {
			$server = self::parseNodeinfo2($nodeinfo2_url);
		}

		// When the nodeinfo url isn't on the same host, then there is obviously something wrong
		if (empty($server) && !empty($nodeinfo1_url) && (parse_url($url, PHP_URL_HOST) == parse_url($nodeinfo1_url, PHP_URL_HOST))) {
			$server = self::parseNodeinfo1($nodeinfo1_url);
		}

		return $server;
	}

	/**
	 * Parses Nodeinfo 1
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo1(string $nodeinfo_url)
	{
		$curlResult = DI::httpRequest()->get($nodeinfo_url);

		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo)) {
			return [];
		}

		$server = ['detection-method' => self::DETECT_NODEINFO_1,
			'register_policy' => Register::CLOSED];

		if (!empty($nodeinfo['openRegistrations'])) {
			$server['register_policy'] = Register::OPEN;
		}

		if (is_array($nodeinfo['software'])) {
			if (!empty($nodeinfo['software']['name'])) {
				$server['platform'] = strtolower($nodeinfo['software']['name']);
			}

			if (!empty($nodeinfo['software']['version'])) {
				$server['version'] = $nodeinfo['software']['version'];
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $server['version']);
			}
		}

		if (!empty($nodeinfo['metadata']['nodeName'])) {
			$server['site_name'] = $nodeinfo['metadata']['nodeName'];
		}

		if (!empty($nodeinfo['usage']['users']['total'])) {
			$server['registered-users'] = max($nodeinfo['usage']['users']['total'], 1);
		}

		if (!empty($nodeinfo['protocols']['inbound']) && is_array($nodeinfo['protocols']['inbound'])) {
			$protocols = [];
			foreach ($nodeinfo['protocols']['inbound'] as $protocol) {
				$protocols[$protocol] = true;
			}

			if (!empty($protocols['friendica'])) {
				$server['network'] = Protocol::DFRN;
			} elseif (!empty($protocols['activitypub'])) {
				$server['network'] = Protocol::ACTIVITYPUB;
			} elseif (!empty($protocols['diaspora'])) {
				$server['network'] = Protocol::DIASPORA;
			} elseif (!empty($protocols['ostatus'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['gnusocial'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['zot'])) {
				$server['network'] = Protocol::ZOT;
			}
		}

		if (empty($server)) {
			return [];
		}

		return $server;
	}

	/**
	 * Parses Nodeinfo 2
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 * @return array Server data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo2(string $nodeinfo_url)
	{
		$curlResult = DI::httpRequest()->get($nodeinfo_url);
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBody(), true);

		if (!is_array($nodeinfo)) {
			return [];
		}

		$server = ['detection-method' => self::DETECT_NODEINFO_2,
			'register_policy' => Register::CLOSED];

		if (!empty($nodeinfo['openRegistrations'])) {
			$server['register_policy'] = Register::OPEN;
		}

		if (is_array($nodeinfo['software'])) {
			if (!empty($nodeinfo['software']['name'])) {
				$server['platform'] = strtolower($nodeinfo['software']['name']);
			}

			if (!empty($nodeinfo['software']['version'])) {
				$server['version'] = $nodeinfo['software']['version'];
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $server['version']);
			}
		}

		if (!empty($nodeinfo['metadata']['nodeName'])) {
			$server['site_name'] = $nodeinfo['metadata']['nodeName'];
		}

		if (!empty($nodeinfo['usage']['users']['total'])) {
			$server['registered-users'] = max($nodeinfo['usage']['users']['total'], 1);
		}

		if (!empty($nodeinfo['protocols'])) {
			$protocols = [];
			foreach ($nodeinfo['protocols'] as $protocol) {
				$protocols[$protocol] = true;
			}

			if (!empty($protocols['dfrn'])) {
				$server['network'] = Protocol::DFRN;
			} elseif (!empty($protocols['activitypub'])) {
				$server['network'] = Protocol::ACTIVITYPUB;
			} elseif (!empty($protocols['diaspora'])) {
				$server['network'] = Protocol::DIASPORA;
			} elseif (!empty($protocols['ostatus'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['gnusocial'])) {
				$server['network'] = Protocol::OSTATUS;
			} elseif (!empty($protocols['zot'])) {
				$server['network'] = Protocol::ZOT;
			}
		}

		if (empty($server)) {
			return [];
		}

		return $server;
	}

	/**
	 * Fetch server information from a 'siteinfo.json' file on the given server
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function fetchSiteinfo(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/siteinfo.json');
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
			$serverdata['detection-method'] = self::DETECT_SITEINFO_JSON;
		}

		if (!empty($data['url'])) {
			$serverdata['platform'] = strtolower($data['platform']);
			$serverdata['version'] = $data['version'];
		}

		if (!empty($data['plugins'])) {
			if (in_array('pubcrawl', $data['plugins'])) {
				$serverdata['network'] = Protocol::ACTIVITYPUB;
			} elseif (in_array('diaspora', $data['plugins'])) {
				$serverdata['network'] = Protocol::DIASPORA;
			} elseif (in_array('gnusoc', $data['plugins'])) {
				$serverdata['network'] = Protocol::OSTATUS;
			} else {
				$serverdata['network'] = Protocol::ZOT;
			}
		}

		if (!empty($data['site_name'])) {
			$serverdata['site_name'] = $data['site_name'];
		}

		if (!empty($data['channels_total'])) {
			$serverdata['registered-users'] = max($data['channels_total'], 1);
		}

		if (!empty($data['register_policy'])) {
			switch ($data['register_policy']) {
				case 'REGISTER_OPEN':
					$serverdata['register_policy'] = Register::OPEN;
					break;

				case 'REGISTER_APPROVE':
					$serverdata['register_policy'] = Register::APPROVE;
					break;

				case 'REGISTER_CLOSED':
				default:
					$serverdata['register_policy'] = Register::CLOSED;
					break;
			}
		}

		return $serverdata;
	}

	/**
	 * Checks if the server contains a valid host meta file
	 *
	 * @param string $url URL of the given server
	 *
	 * @return boolean 'true' if the server seems to be vital
	 */
	private static function validHostMeta(string $url)
	{
		$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
		$curlResult = DI::httpRequest()->get($url . '/.well-known/host-meta', ['timeout' => $xrd_timeout]);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		$xrd = XML::parseString($curlResult->getBody());
		if (!is_object($xrd)) {
			return false;
		}

		$elements = XML::elementToArray($xrd);
		if (empty($elements) || empty($elements['xrd']) || empty($elements['xrd']['link'])) {
			return false;
		}

		$valid = false;
		foreach ($elements['xrd']['link'] as $link) {
			// When there is more than a single "link" element, the array looks slightly different
			if (!empty($link['@attributes'])) {
				$link = $link['@attributes'];
			}

			if (empty($link['rel']) || empty($link['template'])) {
				continue;
			}

			if ($link['rel'] == 'lrdd') {
				// When the webfinger host is the same like the system host, it should be ok.
				$valid = (parse_url($url, PHP_URL_HOST) == parse_url($link['template'], PHP_URL_HOST));
			}
		}

		return $valid;
	}

	/**
	 * Detect the network of the given server via their known contacts
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectNetworkViaContacts(string $url, array $serverdata)
	{
		$contacts = [];

		$apcontacts = DBA::select('apcontact', ['url'], ['baseurl' => [$url, $serverdata['nurl']]]);
		while ($apcontact = DBA::fetch($apcontacts)) {
			$contacts[Strings::normaliseLink($apcontact['url'])] = $apcontact['url'];
		}
		DBA::close($apcontacts);

		$pcontacts = DBA::select('contact', ['url', 'nurl'], ['uid' => 0, 'baseurl' => [$url, $serverdata['nurl']]]);
		while ($pcontact = DBA::fetch($pcontacts)) {
			$contacts[$pcontact['nurl']] = $pcontact['url'];
		}
		DBA::close($pcontacts);

		if (empty($contacts)) {
			return $serverdata;
		}

		foreach ($contacts as $contact) {
			$probed = Contact::getByURL($contact);
			if (!empty($probed) && in_array($probed['network'], Protocol::FEDERATED)) {
				$serverdata['network'] = $probed['network'];
				break;
			}
		}

		$serverdata['registered-users'] = max($serverdata['registered-users'], count($contacts), 1);

		return $serverdata;
	}

	/**
	 * Checks if the given server does have a '/poco' endpoint.
	 * This is used for the 'PortableContact' functionality,
	 * which is used by both Friendica and Hubzilla.
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function checkPoCo(string $url, array $serverdata)
	{
		$serverdata['poco'] = '';

		$curlResult = DI::httpRequest()->get($url . '/poco');
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['totalResults'])) {
			$registeredUsers = $serverdata['registered-users'] ?? 0;
			$serverdata['registered-users'] = max($data['totalResults'], $registeredUsers, 1);
			$serverdata['directory-type'] = self::DT_POCO;
			$serverdata['poco'] = $url . '/poco';
		}

		return $serverdata;
	}

	/**
	 * Checks if the given server does have a Mastodon style directory endpoint.
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	public static function checkMastodonDirectory(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/api/v1/directory?limit=1');
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (count($data) == 1) {
			$serverdata['directory-type'] = self::DT_MASTODON;
		}

		return $serverdata;
	}

	/**
	 * Detects Peertube via their known endpoint
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectPeertube(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/api/v1/config');

		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['instance']) && !empty($data['serverVersion'])) {
			$serverdata['platform'] = 'peertube';
			$serverdata['version'] = $data['serverVersion'];
			$serverdata['network'] = Protocol::ACTIVITYPUB;

			if (!empty($data['instance']['name'])) {
				$serverdata['site_name'] = $data['instance']['name'];
			}

			if (!empty($data['instance']['shortDescription'])) {
				$serverdata['info'] = $data['instance']['shortDescription'];
			}

			if (!empty($data['signup'])) {
				if (!empty($data['signup']['allowed'])) {
					$serverdata['register_policy'] = Register::OPEN;
				}
			}

			if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
				$serverdata['detection-method'] = self::DETECT_V1_CONFIG;
			}
		}

		return $serverdata;
	}

	/**
	 * Detects the version number of a given server when it was a NextCloud installation
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectNextcloud(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/status.php');

		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['version'])) {
			$serverdata['platform'] = 'nextcloud';
			$serverdata['version'] = $data['version'];
			$serverdata['network'] = Protocol::ACTIVITYPUB;

			if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
				$serverdata['detection-method'] = self::DETECT_STATUS_PHP;
			}
		}

		return $serverdata;
	}

	/**
	 * Detects data from a given server url if it was a mastodon alike system
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectMastodonAlikes(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/api/v1/instance');

		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
			$serverdata['detection-method'] = self::DETECT_MASTODON_API;
		}

		if (!empty($data['version'])) {
			$serverdata['platform'] = 'mastodon';
			$serverdata['version'] = $data['version'] ?? '';
			$serverdata['network'] = Protocol::ACTIVITYPUB;
		}

		if (!empty($data['title'])) {
			$serverdata['site_name'] = $data['title'];
		}

		if (!empty($data['title']) && empty($serverdata['platform']) && empty($serverdata['network'])) {
			$serverdata['platform'] = 'mastodon';
			$serverdata['network'] = Protocol::ACTIVITYPUB;
		}

		if (!empty($data['description'])) {
			$serverdata['info'] = trim($data['description']);
		}

		if (!empty($data['stats']['user_count'])) {
			$serverdata['registered-users'] = max($data['stats']['user_count'], 1);
		}

		if (!empty($serverdata['version']) && preg_match('/.*?\(compatible;\s(.*)\s(.*)\)/ism', $serverdata['version'], $matches)) {
			$serverdata['platform'] = strtolower($matches[1]);
			$serverdata['version'] = $matches[2];
		}

		if (!empty($serverdata['version']) && strstr(strtolower($serverdata['version']), 'pleroma')) {
			$serverdata['platform'] = 'pleroma';
			$serverdata['version'] = trim(str_ireplace('pleroma', '', $serverdata['version']));
		}

		if (!empty($serverdata['platform']) && strstr($serverdata['platform'], 'pleroma')) {
			$serverdata['version'] = trim(str_ireplace('pleroma', '', $serverdata['platform']));
			$serverdata['platform'] = 'pleroma';
		}

		return $serverdata;
	}

	/**
	 * Detects data from typical Hubzilla endpoints
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectHubzilla(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/api/statusnet/config.json');
		if (!$curlResult->isSuccess() || ($curlResult->getBody() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data) || empty($data['site'])) {
			return $serverdata;
		}

		if (!empty($data['site']['name'])) {
			$serverdata['site_name'] = $data['site']['name'];
		}

		if (!empty($data['site']['platform'])) {
			$serverdata['platform'] = strtolower($data['site']['platform']['PLATFORM_NAME']);
			$serverdata['version'] = $data['site']['platform']['STD_VERSION'];
			$serverdata['network'] = Protocol::ZOT;
		}

		if (!empty($data['site']['hubzilla'])) {
			$serverdata['platform'] = strtolower($data['site']['hubzilla']['PLATFORM_NAME']);
			$serverdata['version'] = $data['site']['hubzilla']['RED_VERSION'];
			$serverdata['network'] = Protocol::ZOT;
		}

		if (!empty($data['site']['redmatrix'])) {
			if (!empty($data['site']['redmatrix']['PLATFORM_NAME'])) {
				$serverdata['platform'] = strtolower($data['site']['redmatrix']['PLATFORM_NAME']);
			} elseif (!empty($data['site']['redmatrix']['RED_PLATFORM'])) {
				$serverdata['platform'] = strtolower($data['site']['redmatrix']['RED_PLATFORM']);
			}

			$serverdata['version'] = $data['site']['redmatrix']['RED_VERSION'];
			$serverdata['network'] = Protocol::ZOT;
		}

		$private = false;
		$inviteonly = false;
		$closed = false;

		if (!empty($data['site']['closed'])) {
			$closed = self::toBoolean($data['site']['closed']);
		}

		if (!empty($data['site']['private'])) {
			$private = self::toBoolean($data['site']['private']);
		}

		if (!empty($data['site']['inviteonly'])) {
			$inviteonly = self::toBoolean($data['site']['inviteonly']);
		}

		if (!$closed && !$private and $inviteonly) {
			$serverdata['register_policy'] = Register::APPROVE;
		} elseif (!$closed && !$private) {
			$serverdata['register_policy'] = Register::OPEN;
		} else {
			$serverdata['register_policy'] = Register::CLOSED;
		}

		if (!empty($serverdata['network']) && in_array($serverdata['detection-method'],
			[self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
			$serverdata['detection-method'] = self::DETECT_CONFIG_JSON;
		}

		return $serverdata;
	}

	/**
	 * Converts input value to a boolean value
	 *
	 * @param string|integer $val
	 *
	 * @return boolean
	 */
	private static function toBoolean($val)
	{
		if (($val == 'true') || ($val == 1)) {
			return true;
		} elseif (($val == 'false') || ($val == 0)) {
			return false;
		}

		return $val;
        }

	/**
	 * Detect if the URL belongs to a GNU Social server
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectGNUSocial(string $url, array $serverdata)
	{
		// Test for GNU Social
		$curlResult = DI::httpRequest()->get($url . '/api/gnusocial/version.json');
		if ($curlResult->isSuccess() && ($curlResult->getBody() != '{"error":"not implemented"}') &&
			($curlResult->getBody() != '') && (strlen($curlResult->getBody()) < 30)) {
			$serverdata['platform'] = 'gnusocial';
			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239) . chr(187) . chr(191), '', $curlResult->getBody());
			$serverdata['version'] = str_replace(["\r", "\n", "\t"], '', $serverdata['version']);
			$serverdata['version'] = trim($serverdata['version'], '"');
			$serverdata['network'] = Protocol::OSTATUS;

			if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
				$serverdata['detection-method'] = self::DETECT_GNUSOCIAL;
			}
	
			return $serverdata;
		}

		// Test for Statusnet
		$curlResult = DI::httpRequest()->get($url . '/api/statusnet/version.json');
		if ($curlResult->isSuccess() && ($curlResult->getBody() != '{"error":"not implemented"}') &&
			($curlResult->getBody() != '') && (strlen($curlResult->getBody()) < 30)) {

			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239).chr(187).chr(191), '', $curlResult->getBody());
			$serverdata['version'] = str_replace(["\r", "\n", "\t"], '', $serverdata['version']);
			$serverdata['version'] = trim($serverdata['version'], '"');

			if (!empty($serverdata['version']) && strtolower(substr($serverdata['version'], 0, 7)) == 'pleroma') {
				$serverdata['platform'] = 'pleroma';
				$serverdata['version'] = trim(str_ireplace('pleroma', '', $serverdata['version']));
				$serverdata['network'] = Protocol::ACTIVITYPUB;
			} else {
				$serverdata['platform'] = 'statusnet';
				$serverdata['network'] = Protocol::OSTATUS;
			}

			if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {
				$serverdata['detection-method'] = self::DETECT_STATUSNET;
			}
		}

		return $serverdata;
	}

	/**
	 * Detect if the URL belongs to a Friendica server
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function detectFriendica(string $url, array $serverdata)
	{
		$curlResult = DI::httpRequest()->get($url . '/friendica/json');
		if (!$curlResult->isSuccess()) {
			$curlResult = DI::httpRequest()->get($url . '/friendika/json');
			$friendika = true;
			$platform = 'Friendika';
		} else {
			$friendika = false;
			$platform = 'Friendica';
		}

		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBody(), true);
		if (empty($data) || empty($data['version'])) {
			return $serverdata;
		}

		if (in_array($serverdata['detection-method'], [self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_MANUAL])) {			
			$serverdata['detection-method'] = $friendika ? self::DETECT_FRIENDIKA : self::DETECT_FRIENDICA;
		}

		$serverdata['network'] = Protocol::DFRN;
		$serverdata['version'] = $data['version'];

		if (!empty($data['no_scrape_url'])) {
			$serverdata['noscrape'] = $data['no_scrape_url'];
		}

		if (!empty($data['site_name'])) {
			$serverdata['site_name'] = $data['site_name'];
		}

		if (!empty($data['info'])) {
			$serverdata['info'] = trim($data['info']);
		}

		$register_policy = ($data['register_policy'] ?? '') ?: 'REGISTER_CLOSED';
		switch ($register_policy) {
			case 'REGISTER_OPEN':
				$serverdata['register_policy'] = Register::OPEN;
				break;

			case 'REGISTER_APPROVE':
				$serverdata['register_policy'] = Register::APPROVE;
				break;

			case 'REGISTER_CLOSED':
			case 'REGISTER_INVITATION':
				$serverdata['register_policy'] = Register::CLOSED;
				break;
			default:
				Logger::info('Register policy is invalid', ['policy' => $register_policy, 'server' => $url]);
 				$serverdata['register_policy'] = Register::CLOSED;
				break;
		}

		$serverdata['platform'] = strtolower($data['platform'] ?? $platform);

		return $serverdata;
	}

	/**
	 * Analyses the landing page of a given server for hints about type and system of that server
	 *
	 * @param object $curlResult result of curl execution
	 * @param array  $serverdata array with server data
	 * @param string $url        Server URL
	 *
	 * @return array server data
	 */
	private static function analyseRootBody($curlResult, array $serverdata, string $url)
	{
		$doc = new DOMDocument();
		@$doc->loadHTML($curlResult->getBody());
		$xpath = new DOMXPath($doc);

		$title = trim(XML::getFirstNodeValue($xpath, '//head/title/text()'));
		if (!empty($title)) {
			$serverdata['site_name'] = $title;
		}

		$list = $xpath->query('//meta[@name]');

		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$value = trim($attribute->value);
					if (empty($value)) {
						continue;
					}

					$attr[$attribute->name] = $value;
				}

				if (empty($attr['name']) || empty($attr['content'])) {
					continue;
				}
			}

			if ($attr['name'] == 'description') {
				$serverdata['info'] = $attr['content'];
			}

			if (in_array($attr['name'], ['application-name', 'al:android:app_name', 'al:ios:app_name',
				'twitter:app:name:googleplay', 'twitter:app:name:iphone', 'twitter:app:name:ipad'])) {
				$serverdata['platform'] = strtolower($attr['content']);
 				if (in_array($attr['content'], ['Misskey', 'Write.as'])) {
					$serverdata['network'] = Protocol::ACTIVITYPUB;
				}
			}
			if (($attr['name'] == 'generator') && (empty($serverdata['platform']) || (substr(strtolower($attr['content']), 0, 9) == 'wordpress'))) {
				$serverdata['platform'] = strtolower($attr['content']);
				$version_part = explode(' ', $attr['content']);

				if (count($version_part) == 2) {
					if (in_array($version_part[0], ['WordPress'])) {
						$serverdata['platform'] = 'wordpress';
						$serverdata['version'] = $version_part[1];

						// We still do need a reliable test if some AP plugin is activated
						if (DBA::exists('apcontact', ['baseurl' => $url])) {
							$serverdata['network'] = Protocol::ACTIVITYPUB;
						} else {
							$serverdata['network'] = Protocol::FEED;
						}

						if ($serverdata['detection-method'] == self::DETECT_MANUAL) {
							$serverdata['detection-method'] = self::DETECT_BODY;
						}
					}
					if (in_array($version_part[0], ['Friendika', 'Friendica'])) {
						$serverdata['platform'] = strtolower($version_part[0]);
						$serverdata['version'] = $version_part[1];
						$serverdata['network'] = Protocol::DFRN;
					}
				}
			}
		}

		$list = $xpath->query('//meta[@property]');

		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$value = trim($attribute->value);
					if (empty($value)) {
						continue;
					}

					$attr[$attribute->name] = $value;
				}

				if (empty($attr['property']) || empty($attr['content'])) {
					continue;
				}
			}

			if ($attr['property'] == 'og:site_name') {
				$serverdata['site_name'] = $attr['content'];
			}

			if ($attr['property'] == 'og:description') {
				$serverdata['info'] = $attr['content'];
			}

			if ($attr['property'] == 'og:platform') {
				$serverdata['platform'] = strtolower($attr['content']);

				if (in_array($attr['content'], ['PeerTube'])) {
					$serverdata['network'] = Protocol::ACTIVITYPUB;
				}
			}

			if ($attr['property'] == 'generator') {
				$serverdata['platform'] = strtolower($attr['content']);

				if (in_array($attr['content'], ['hubzilla'])) {
					// We later check which compatible protocol modules are loaded.
					$serverdata['network'] = Protocol::ZOT;
				}
			}
		}

		if (!empty($serverdata['network']) && ($serverdata['detection-method'] == self::DETECT_MANUAL)) {
			$serverdata['detection-method'] = self::DETECT_BODY;
		}

		return $serverdata;
	}

	/**
	 * Analyses the header data of a given server for hints about type and system of that server
	 *
	 * @param object $curlResult result of curl execution
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function analyseRootHeader($curlResult, array $serverdata)
	{
		if ($curlResult->getHeader('server') == 'Mastodon') {
			$serverdata['platform'] = 'mastodon';
			$serverdata['network'] = Protocol::ACTIVITYPUB;
		} elseif ($curlResult->inHeader('x-diaspora-version')) {
			$serverdata['platform'] = 'diaspora';
			$serverdata['network'] = Protocol::DIASPORA;
			$serverdata['version'] = $curlResult->getHeader('x-diaspora-version');
		} elseif ($curlResult->inHeader('x-friendica-version')) {
			$serverdata['platform'] = 'friendica';
			$serverdata['network'] = Protocol::DFRN;
			$serverdata['version'] = $curlResult->getHeader('x-friendica-version');
		} else {
			return $serverdata;
		}

		if ($serverdata['detection-method'] == self::DETECT_MANUAL) {
			$serverdata['detection-method'] = self::DETECT_HEADER;
		}

		return $serverdata;
	}

	/**
	 * Test if the body contains valid content
	 *
	 * @param string $body
	 * @return boolean
	 */
	private static function invalidBody(string $body)
	{
		// Currently we only test for a HTML element.
		// Possibly we enhance this in the future.
		return !strpos($body, '>');
	}

	/**
	 * Update GServer entries
	 */
	public static function discover()
	{
		// Update the server list
		self::discoverFederation();

		$no_of_queries = 5;

		$requery_days = intval(DI::config()->get('system', 'poco_requery_days'));

		if ($requery_days == 0) {
			$requery_days = 7;
		}

		$last_update = date('c', time() - (60 * 60 * 24 * $requery_days));

		$gservers = DBA::p("SELECT `id`, `url`, `nurl`, `network`, `poco`, `directory-type`
			FROM `gserver`
			WHERE NOT `failed`
			AND `directory-type` != ?
			AND `last_poco_query` < ?
			ORDER BY RAND()", self::DT_NONE, $last_update
		);

		while ($gserver = DBA::fetch($gservers)) {
			Logger::info('Update peer list', ['server' => $gserver['url'], 'id' => $gserver['id']]);
			Worker::add(PRIORITY_LOW, 'UpdateServerPeers', $gserver['url']);

			Logger::info('Update directory', ['server' => $gserver['url'], 'id' => $gserver['id']]);
			Worker::add(PRIORITY_LOW, 'UpdateServerDirectory', $gserver);

			$fields = ['last_poco_query' => DateTimeFormat::utcNow()];
			DBA::update('gserver', $fields, ['nurl' => $gserver['nurl']]);
	
			if (--$no_of_queries == 0) {
				break;
			}
		}

		DBA::close($gservers);
	}

	/**
	 * Discover federated servers
	 */
	private static function discoverFederation()
	{
		$last = DI::config()->get('poco', 'last_federation_discovery');

		if ($last) {
			$next = $last + (24 * 60 * 60);

			if ($next > time()) {
				return;
			}
		}

		// Discover federated servers
		$protocols = ['activitypub', 'diaspora', 'dfrn', 'ostatus'];
		foreach ($protocols as $protocol) {
			$query = '{nodes(protocol:"' . $protocol . '"){host}}';
			$curlResult = DI::httpRequest()->fetch('https://the-federation.info/graphql?query=' . urlencode($query));
			if (!empty($curlResult)) {
				$data = json_decode($curlResult, true);
				if (!empty($data['data']['nodes'])) {
					foreach ($data['data']['nodes'] as $server) {
						// Using "only_nodeinfo" since servers that are listed on that page should always have it.
						self::add('https://' . $server['host'], true);
					}
				}
			}
		}

		// Disvover Mastodon servers
		$accesstoken = DI::config()->get('system', 'instances_social_key');

		if (!empty($accesstoken)) {
			$api = 'https://instances.social/api/1.0/instances/list?count=0';
			$header = ['Authorization: Bearer '.$accesstoken];
			$curlResult = DI::httpRequest()->get($api, ['header' => $header]);

			if ($curlResult->isSuccess()) {
				$servers = json_decode($curlResult->getBody(), true);

				foreach ($servers['instances'] as $server) {
					$url = (is_null($server['https_score']) ? 'http' : 'https') . '://' . $server['name'];
					self::add($url);
				}
			}
		}

		DI::config()->set('poco', 'last_federation_discovery', time());
	}

	/**
	 * Set the protocol for the given server
	 *
	 * @param int $gsid     Server id
	 * @param int $protocol Protocol id
	 * @return void 
	 * @throws Exception 
	 */
	static function setProtocol(int $gsid, int $protocol)
	{
		if (empty($gsid)) {
			return;
		}

		$gserver = DBA::selectFirst('gserver', ['protocol', 'url'], ['id' => $gsid]);
		if (!DBA::isResult($gserver)) {
			return;
		}

		$old = $gserver['protocol'];

		if (!is_null($old)) {
			/*
			The priority for the protocols is:
				1. ActivityPub
				2. DFRN via Diaspora
				3. Legacy DFRN
				4. Diaspora
				5. OStatus
			*/

			// We don't need to change it when nothing is to be changed
			if ($old == $protocol) {
				return;
			}

			// We don't want to mark a server as OStatus when it had been marked with any other protocol before
			if ($protocol == Post\DeliveryData::OSTATUS) {
				return;
			}

			// If the server is marked as ActivityPub then we won't change it to anything different
			if ($old == Post\DeliveryData::ACTIVITYPUB) {
				return;
			}

			// Don't change it to anything lower than DFRN if the new one wasn't ActivityPub
			if (($old == Post\DeliveryData::DFRN) && ($protocol != Post\DeliveryData::ACTIVITYPUB)) {
				return;
			}

			// Don't change it to Diaspora when it is a legacy DFRN server
			if (($old == Post\DeliveryData::LEGACY_DFRN) && ($protocol == Post\DeliveryData::DIASPORA)) {
				return;
			}
		}

		Logger::info('Protocol for server', ['protocol' => $protocol, 'old' => $old, 'id' => $gsid, 'url' => $gserver['url']]);
		DBA::update('gserver', ['protocol' => $protocol], ['id' => $gsid]);
	}
}
