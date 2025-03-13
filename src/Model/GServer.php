<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Register;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\Probe;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\JsonLD;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use Friendica\Network\HTTPException;
use Friendica\Worker\UpdateGServer;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * This class handles GServer related functions
 */
class GServer
{
	// Directory types
	const DT_NONE     = 0;
	const DT_POCO     = 1;
	const DT_MASTODON = 2;

	// Methods to detect server types

	// Non endpoint specific methods
	const DETECT_MANUAL        = 0;
	const DETECT_HEADER        = 1;
	const DETECT_BODY          = 2;
	const DETECT_HOST_META     = 3;
	const DETECT_CONTACTS      = 4;
	const DETECT_AP_ACTOR      = 5;
	const DETECT_AP_COLLECTION = 6;

	const DETECT_UNSPECIFIC = [self::DETECT_MANUAL, self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_HOST_META, self::DETECT_CONTACTS, self::DETECT_AP_ACTOR];

	// Implementation specific endpoints
	// @todo Possibly add Lemmy detection via the endpoint /api/v3/site
	const DETECT_FRIENDIKA     = 10;
	const DETECT_FRIENDICA     = 11;
	const DETECT_STATUSNET     = 12;
	const DETECT_GNUSOCIAL     = 13;
	const DETECT_CONFIG_JSON   = 14; // Statusnet, GNU Social, Older Hubzilla/Redmatrix
	const DETECT_SITEINFO_JSON = 15; // Newer Hubzilla
	const DETECT_MASTODON_API  = 16;
	const DETECT_STATUS_PHP    = 17; // Nextcloud
	const DETECT_V1_CONFIG     = 18;
	const DETECT_SYSTEM_ACTOR  = 20; // Mistpark, Osada, Roadhouse, Zap
	const DETECT_THREADS       = 21;

	// Standardized endpoints
	const DETECT_STATISTICS_JSON = 100;
	const DETECT_NODEINFO_10     = 101; // Nodeinfo Version 1.0
	const DETECT_NODEINFO_20     = 102; // Nodeinfo Version 2.0
	const DETECT_NODEINFO2_10    = 103; // Nodeinfo2 Version 1.0
	const DETECT_NODEINFO_21     = 104; // Nodeinfo Version 2.1
	const DETECT_NODEINFO_22     = 105; // Nodeinfo Version 2.2

	/**
	 * Check for the existence of a server and adds it in the background if not existant
	 *
	 * @param string $url
	 * @param boolean $only_nodeinfo
	 *
	 * @return void
	 */
	public static function add(string $url, bool $only_nodeinfo = false)
	{
		if (self::getID($url)) {
			return;
		}

		UpdateGServer::add(Worker::PRIORITY_LOW, $url, $only_nodeinfo);
	}

	/**
	 * Get the ID for the given server URL
	 *
	 * @param string $url
	 * @param boolean $no_check Don't check if the server hadn't been found
	 *
	 * @return int|null gserver id or NULL on empty URL or failed check
	 */
	public static function getID(string $url, bool $no_check = false): ?int
	{
		$url = self::cleanURL($url);

		if (empty($url)) {
			return null;
		}

		$gserver = DBA::selectFirst('gserver', ['id'], ['nurl' => Strings::normaliseLink($url)]);
		if (DBA::isResult($gserver)) {
			DI::logger()->debug('Got ID for URL', ['id' => $gserver['id'], 'url' => $url]);

			if (Network::isUrlBlocked($url)) {
				self::setBlockedById($gserver['id']);
			} else {
				self::setUnblockedById($gserver['id']);
			}

			return $gserver['id'];
		}

		if ($no_check || !self::check($url)) {
			return null;
		}

		return self::getID($url, true);
	}


	/**
	 * Get the real ID for the given server URL, follows redirects.
	 *
	 * @param string $url
	 * @param boolean $no_check Don't check if the server hadn't been found
	 *
	 * @return int|null gserver id or NULL on empty URL or failed check
	 */
	public static function getRealID(string $url, bool $no_check = false): ?int
	{
		$gsid = self::getID($url, $no_check);
		if (empty($gsid)) {
			return $gsid;
		}

		$gserver = DBA::selectFirst('gserver', ['redirect-gsid'], ['id' => $gsid]);
		if (!empty($gserver['redirect-gsid'])) {
			$redirect = DBA::selectFirst('gserver', ['failed'], ['id' => $gserver['redirect-gsid']]);
			if (isset($redirect['failed']) && !$redirect['failed']) {
				return $gserver['redirect-gsid'];
			}
		}
		return $gsid;
	}

	/**
	 * Retrieves all the servers which base domain are matching the provided domain pattern
	 *
	 * The pattern is a simple fnmatch() pattern with ? for single wildcard and * for multiple wildcard
	 *
	 * @param string $pattern
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public static function listByDomainPattern(string $pattern): array
	{
		$likePattern = 'http://' . strtr($pattern, ['_' => '\_', '%' => '\%', '?' => '_', '*' => '%']);

		// The SUBSTRING_INDEX returns everything before the eventual third /, which effectively trims an
		// eventual server path and keep only the server domain which we're matching against the pattern.
		$sql = "SELECT `gserver`.*, COUNT(*) AS `contacts`
			FROM `gserver`
			LEFT JOIN `contact` ON `gserver`.`id` = `contact`.`gsid`
			WHERE SUBSTRING_INDEX(`gserver`.`nurl`, '/', 3) LIKE ?
			AND NOT `gserver`.`failed`
			GROUP BY `gserver`.`id`";

		$stmt = DI::dba()->p($sql, $likePattern);

		return DI::dba()->toArray($stmt);
	}

	/**
	 * Checks if the given server array is unreachable for a long time now
	 */
	private static function isDefunct(array $gserver): bool
	{
		return ($gserver['failed'] || in_array($gserver['network'], Protocol::FEDERATED)) &&
			($gserver['last_contact'] >= $gserver['created']) &&
			($gserver['last_contact'] < $gserver['last_failure']) &&
			($gserver['last_contact'] < DateTimeFormat::utc('now - 90 days'));
	}

	/**
	 * Checks if the given server id is unreachable for a long time now
	 *
	 * @param integer $gsid
	 * @return boolean
	 */
	public static function isDefunctById(int $gsid): bool
	{
		$gserver = DBA::selectFirst('gserver', ['url', 'next_contact', 'last_contact', 'last_failure', 'created', 'failed', 'network'], ['id' => $gsid]);
		if (empty($gserver)) {
			return false;
		} else {
			if (strtotime($gserver['next_contact']) < time()) {
				UpdateGServer::add(Worker::PRIORITY_LOW, $gserver['url']);
			}

			return self::isDefunct($gserver);
		}
	}

	/**
	 * Checks if the given server id is reachable
	 *
	 * @param integer $gsid
	 * @return boolean
	 */
	public static function isReachableById(int $gsid): bool
	{
		$gserver = DBA::selectFirst('gserver', ['url', 'next_contact', 'failed', 'network'], ['id' => $gsid]);
		if (empty($gserver)) {
			return true;
		} else {
			if (strtotime($gserver['next_contact']) < time()) {
				UpdateGServer::add(Worker::PRIORITY_LOW, $gserver['url']);
			}

			return !$gserver['failed'] && in_array($gserver['network'], Protocol::FEDERATED);
		}
	}

	/**
	 * Checks if the given server is reachable
	 *
	 * @param array $contact Contact that should be checked
	 *
	 * @return boolean 'true' if server seems vital
	 */
	public static function reachable(array $contact): bool
	{
		$server = '';

		if (!empty($contact['gsid'])) {
			$gsid = $contact['gsid'];
		} elseif (!empty($contact['baseurl'])) {
			$server = $contact['baseurl'];
		} elseif ($contact['network'] == Protocol::DIASPORA) {
			$parts = (array)parse_url($contact['url']);
			unset($parts['path']);
			$server = (string)Uri::fromParts($parts);
		} else {
			return true;
		}

		if (!empty($gsid)) {
			$condition = ['id' => $gsid];
		} else {
			$condition = ['nurl' => Strings::normaliseLink($server)];
		}

		$gserver = DBA::selectFirst('gserver', ['url', 'next_contact', 'failed', 'network'], $condition);
		if (empty($gserver)) {
			$reachable = true;
		} else {
			$reachable = !$gserver['failed'] && in_array($gserver['network'], Protocol::FEDERATED);
			$server    = $gserver['url'];
		}

		if (!empty($server) && (empty($gserver) || strtotime($gserver['next_contact']) < time())) {
			UpdateGServer::add(Worker::PRIORITY_LOW, $server);
		}

		return $reachable;
	}

	/**
	 * Calculate the next update day
	 *
	 * @param bool $success
	 * @param string $created
	 * @param string $last_contact
	 * @param bool $undetected
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public static function getNextUpdateDate(bool $success, string $created = '', string $last_contact = '', bool $undetected = false): string
	{
		// On successful contact process check again next week when it is a detected system.
		// When we haven't detected the system, it could be a static website or a really old system.
		if ($success) {
			return DateTimeFormat::utc($undetected ? 'now +1 month' : 'now +7 day');
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

		// The system hadn't been successful contacted for more than a month, so try again in three months
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
	public static function check(string $server_url, string $network = '', bool $force = false, bool $only_nodeinfo = false): bool
	{
		$server_url = self::cleanURL($server_url);
		if ($server_url == '') {
			return false;
		}

		if (Network::isUrlBlocked($server_url)) {
			DI::logger()->info('Server is blocked', ['url' => $server_url]);
			self::setBlockedByUrl($server_url);
			return false;
		}

		$gserver = DBA::selectFirst('gserver', [], ['nurl' => Strings::normaliseLink($server_url)]);
		if (DBA::isResult($gserver)) {
			if ($gserver['created'] <= DBA::NULL_DATETIME) {
				$fields    = ['created' => DateTimeFormat::utcNow()];
				$condition = ['nurl' => Strings::normaliseLink($server_url)];
				self::update($fields, $condition);
			}

			if (!$force && (strtotime($gserver['next_contact']) > time())) {
				DI::logger()->info('No update needed', ['server' => $server_url]);
				return (!$gserver['failed']);
			}
			DI::logger()->info('Server is outdated. Start discovery.', ['Server' => $server_url, 'Force' => $force]);
		} else {
			DI::logger()->info('Server is unknown. Start discovery.', ['Server' => $server_url]);
		}

		return self::detect($server_url, $network, $only_nodeinfo);
	}

	/**
	 * Reset failed server status by gserver id
	 *
	 * @param int    $gsid
	 * @param string $network
	 */
	public static function setReachableById(int $gsid, string $network)
	{
		$gserver = DBA::selectFirst('gserver', ['url', 'failed', 'next_contact', 'network'], ['id' => $gsid]);
		if (!DBA::isResult($gserver)) {
			return;
		}

		$blocked = Network::isUrlBlocked($gserver['url']);
		if ($gserver['failed']) {
			$fields = ['failed' => false, 'blocked' => $blocked, 'last_contact' => DateTimeFormat::utcNow()];
			if (!empty($network) && !in_array($gserver['network'], Protocol::FEDERATED)) {
				$fields['network'] = $network;
			}
			self::update($fields, ['id' => $gsid]);
			DI::logger()->info('Reset failed status for server', ['url' => $gserver['url']]);

			if (strtotime($gserver['next_contact']) < time()) {
				UpdateGServer::add(Worker::PRIORITY_LOW, $gserver['url']);
			}
		} elseif ($blocked) {
			self::setBlockedById($gsid);
		} else {
			self::setUnblockedById($gsid);
		}
	}

	/**
	 * Set failed server status by gserver id
	 *
	 * @param int $gsid
	 */
	public static function setFailureById(int $gsid)
	{
		$gserver = DBA::selectFirst('gserver', ['url', 'failed', 'next_contact'], ['id' => $gsid]);
		if (DBA::isResult($gserver) && !$gserver['failed']) {
			self::update(['failed' => true, 'blocked' => Network::isUrlBlocked($gserver['url']), 'last_failure' => DateTimeFormat::utcNow()], ['id' => $gsid]);
			DI::logger()->info('Set failed status for server', ['url' => $gserver['url']]);

			if (strtotime($gserver['next_contact']) < time()) {
				UpdateGServer::add(Worker::PRIORITY_LOW, $gserver['url']);
			}
		}
	}

	public static function setUnblockedById(int $gsid)
	{
		$gserver = DBA::selectFirst('gserver', ['url'], ["(`blocked` OR `blocked` IS NULL) AND `id` = ?", $gsid]);
		if (DBA::isResult($gserver)) {
			self::update(['blocked' => false], ['id' => $gsid]);
			DI::logger()->info('Set unblocked status for server', ['url' => $gserver['url']]);
		}
	}

	public static function setBlockedById(int $gsid)
	{
		$gserver = DBA::selectFirst('gserver', ['url'], ["(NOT `blocked` OR `blocked` IS NULL) AND `id` = ?", $gsid]);
		if (DBA::isResult($gserver)) {
			self::update(['blocked' => true, 'failed' => true], ['id' => $gsid]);
			DI::logger()->info('Set blocked status for server', ['url' => $gserver['url']]);
		}
	}

	public static function setBlockedByUrl(string $url)
	{
		$gserver = DBA::selectFirst('gserver', ['url', 'id'], ["(NOT `blocked` OR `blocked` IS NULL) AND `nurl` = ?", Strings::normaliseLink($url)]);
		if (DBA::isResult($gserver)) {
			self::update(['blocked' => true, 'failed' => true], ['id' => $gserver['id']]);
			DI::logger()->info('Set blocked status for server', ['url' => $gserver['url']]);
		}
	}

	/**
	 * Set failed server status
	 *
	 * @param string $url
	 * @return void
	 */
	public static function setFailureByUrl(string $url)
	{
		$nurl = Strings::normaliseLink($url);

		$gserver = DBA::selectFirst('gserver', [], ['nurl' => $nurl]);
		if (DBA::isResult($gserver)) {
			$next_update = self::getNextUpdateDate(false, $gserver['created'], $gserver['last_contact']);
			self::update(
				['url'          => $url, 'failed' => true, 'blocked' => Network::isUrlBlocked($url), 'last_failure' => DateTimeFormat::utcNow(),
					'next_contact' => $next_update, 'network' => Protocol::PHANTOM, 'detection-method' => null],
				['nurl' => $nurl]
			);
			DI::logger()->info('Set failed status for existing server', ['url' => $url]);
			if (self::isDefunct($gserver)) {
				self::archiveContacts($gserver['id']);
			}
			return;
		}

		self::insert(['url' => $url, 'nurl' => $nurl,
			'network'          => Protocol::PHANTOM, 'created' => DateTimeFormat::utcNow(),
			'failed'           => true, 'last_failure' => DateTimeFormat::utcNow()]);
		DI::logger()->info('Set failed status for new server', ['url' => $url]);
	}

	/**
	 * Archive server related contacts and inboxes
	 *
	 * @param integer $gsid
	 * @return void
	 */
	private static function archiveContacts(int $gsid)
	{
		Contact::update(['archive' => true], ['gsid' => $gsid]);
		DBA::update('inbox-status', ['archive' => true], ['gsid' => $gsid]);
	}

	/**
	 * Remove unwanted content from the given URL
	 *
	 * @param string $dirtyUrl
	 *
	 * @return string cleaned URL
	 * @throws Exception
	 * @deprecated since 2023.03 Use cleanUri instead
	 */
	public static function cleanURL(string $dirtyUrl): string
	{
		try {
			return (string)self::cleanUri(new Uri($dirtyUrl));
		} catch (\Throwable $e) {
			DI::logger()->warning('Invalid URL', ['dirtyUrl' => $dirtyUrl]);
			return '';
		}
	}

	/**
	 * Remove unwanted content from the given URI
	 *
	 * @param UriInterface $dirtyUri
	 *
	 * @return UriInterface cleaned URI
	 * @throws Exception
	 */
	public static function cleanUri(UriInterface $dirtyUri): UriInterface
	{
		return $dirtyUri
			->withUserInfo('')
			->withQuery('')
			->withFragment('')
			->withPath(
				preg_replace(
					'#(?:^|/)index\.php#',
					'',
					rtrim($dirtyUri->getPath(), '/')
				)
			);
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
	private static function detect(string $url, string $network = '', bool $only_nodeinfo = false): bool
	{
		DI::logger()->info('Detect server type', ['server' => $url]);

		$original_url = $url;

		// Remove URL content that is not supposed to exist for a server url
		$url = rtrim(self::cleanURL($url), '/');
		if (empty($url)) {
			DI::logger()->notice('Empty URL.');
			return false;
		}

		// If the URL mismatches, then we mark the old entry as failure
		if (!Strings::compareLink($url, $original_url)) {
			self::setFailureByUrl($original_url);
			if (!self::getID($url, true) && !Network::isUrlBlocked($url)) {
				self::detect($url, $network, $only_nodeinfo);
			}
			return false;
		}

		$valid_url = Network::isUrlValid($url);
		if (!$valid_url) {
			self::setFailureByUrl($url);
			return false;
		} else {
			$valid_url = rtrim($valid_url, '/');
		}

		if (!Strings::compareLink($url, $valid_url)) {
			// We only follow redirects when the path stays the same or the target url has no path.
			// Some systems have got redirects on their landing page to a single account page. This check handles it.
			if (((parse_url($url, PHP_URL_HOST) != parse_url($valid_url, PHP_URL_HOST)) && (parse_url($url, PHP_URL_PATH) == parse_url($valid_url, PHP_URL_PATH))) ||
			(((parse_url($url, PHP_URL_HOST) != parse_url($valid_url, PHP_URL_HOST)) || (parse_url($url, PHP_URL_PATH) != parse_url($valid_url, PHP_URL_PATH))) && empty(parse_url($valid_url, PHP_URL_PATH)))) {
				DI::logger()->debug('Found redirect. Mark old entry as failure', ['old' => $url, 'new' => $valid_url]);
				self::setFailureByUrl($url);
				$target_id = self::getID($valid_url, true);
				if (!$target_id && !Network::isUrlBlocked($valid_url)) {
					self::detect($valid_url, $network, $only_nodeinfo);
					$target_id = self::getID($valid_url, true);
				}
				if ($target_id) {
					self::update(['redirect-gsid' => $target_id], ['nurl' => Strings::normaliseLink($url)]);
				}
				return false;
			}

			if ((parse_url($url, PHP_URL_HOST) != parse_url($valid_url, PHP_URL_HOST)) && (parse_url($url, PHP_URL_PATH) != parse_url($valid_url, PHP_URL_PATH)) &&
			(parse_url($url, PHP_URL_PATH) == '')) {
				DI::logger()->debug('Found redirect. Mark old entry as failure and redirect to the basepath.', ['old' => $url, 'new' => $valid_url]);
				$parts = (array)parse_url($valid_url);
				unset($parts['path']);
				$valid_url = (string)Uri::fromParts($parts);

				self::setFailureByUrl($url);
				if (!self::getID($valid_url, true) && !Network::isUrlBlocked($valid_url)) {
					self::detect($valid_url, $network, $only_nodeinfo);
				}
				return false;
			}
			DI::logger()->debug('Found redirect, but ignore it.', ['old' => $url, 'new' => $valid_url]);
		}

		if ((parse_url($url, PHP_URL_HOST) == parse_url($valid_url, PHP_URL_HOST)) &&
			(parse_url($url, PHP_URL_PATH) == parse_url($valid_url, PHP_URL_PATH)) &&
			(parse_url($url, PHP_URL_SCHEME) != parse_url($valid_url, PHP_URL_SCHEME))) {
			$url = $valid_url;
		}

		$in_webroot = empty(parse_url($url, PHP_URL_PATH));

		// When a nodeinfo is present, we don't need to dig further
		$curlResult = DI::httpClient()->get($url . '/.well-known/nodeinfo', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if ($curlResult->isTimeout()) {
			self::setFailureByUrl($url);
			return false;
		}

		if (!empty($network) && !in_array($network, Protocol::NATIVE_SUPPORT)) {
			$serverdata = ['detection-method' => self::DETECT_MANUAL, 'network' => $network, 'platform' => '', 'version' => '', 'site_name' => '', 'info' => ''];
		} else {
			$serverdata = self::parseNodeinfo($url, $curlResult);

			if (empty($serverdata) || !in_array($serverdata['detection-method'], [self::DETECT_NODEINFO_20, self::DETECT_NODEINFO_21, self::DETECT_NODEINFO_22])) {
				$curlResult = DI::httpClient()->get($url . '/.well-known/x-nodeinfo2', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
				$serverdata = self::parseNodeinfo2($curlResult) ?: $serverdata;
			}
		}

		if ($only_nodeinfo && empty($serverdata)) {
			DI::logger()->info('Invalid nodeinfo in nodeinfo-mode, server is marked as failure', ['url' => $url]);
			self::setFailureByUrl($url);
			return false;
		} elseif (empty($serverdata)) {
			$serverdata = ['detection-method' => self::DETECT_MANUAL, 'network' => Protocol::PHANTOM, 'platform' => '', 'version' => '', 'site_name' => '', 'info' => ''];
		}

		// When there is no Nodeinfo, then use some protocol specific endpoints
		if ($serverdata['network'] == Protocol::PHANTOM) {
			if ($in_webroot) {
				// Fetch the landing page, possibly it reveals some data
				$accept     = 'application/activity+json,application/ld+json,application/json,*/*;q=0.9';
				$curlResult = DI::httpClient()->get($url, $accept, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
				if (!$curlResult->isSuccess() && $curlResult->getReturnCode() == '406') {
					$curlResult   = DI::httpClient()->get($url, HttpClientAccept::HTML, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
					$html_fetched = true;
				} else {
					$html_fetched = false;
				}

				if ($curlResult->isSuccess()) {
					$json = json_decode($curlResult->getBodyString(), true);
					if (!empty($json) && is_array($json)) {
						$data        = self::fetchDataFromSystemActor($json, $serverdata);
						$serverdata  = $data['server'];
						$systemactor = $data['actor'];
						if (!$html_fetched && !in_array($serverdata['detection-method'], [self::DETECT_SYSTEM_ACTOR, self::DETECT_AP_COLLECTION])) {
							$curlResult = DI::httpClient()->get($url, HttpClientAccept::HTML, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
						}
					} elseif (!$html_fetched && (strlen($curlResult->getBodyString()) < 1000)) {
						$curlResult = DI::httpClient()->get($url, HttpClientAccept::HTML, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
					}

					if ($serverdata['detection-method'] != self::DETECT_SYSTEM_ACTOR) {
						$serverdata = self::analyseRootHeader($curlResult, $serverdata);
						$serverdata = self::analyseRootBody($curlResult, $serverdata);
					}
				}

				if (!$curlResult->isSuccess() || empty($curlResult->getBodyString())) {
					self::setFailureByUrl($url);
					return false;
				}

				if (in_array($url, ['https://www.threads.net', 'https://threads.net'])) {
					$serverdata['detection-method'] = self::DETECT_THREADS;
					$serverdata['network']          = Protocol::ACTIVITYPUB;
					$serverdata['platform']         = 'threads';
				}

				if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
					$serverdata = self::detectMastodonAlikes($url, $serverdata);
				}
			}

			// All following checks are done for systems that always have got a "host-meta" endpoint.
			// With this check we don't have to waste time and resources for dead systems.
			// Also this hopefully prevents us from receiving abuse messages.
			if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
				$validHostMeta = self::validHostMeta($url);
			} else {
				$validHostMeta = false;
			}

			if ($validHostMeta) {
				if (in_array($serverdata['detection-method'], [self::DETECT_MANUAL, self::DETECT_HEADER, self::DETECT_BODY])) {
					$serverdata['detection-method'] = self::DETECT_HOST_META;
				}

				if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
					$serverdata = self::detectFriendica($url, $serverdata);
				}

				// The following systems have to be installed in the root directory.
				if ($in_webroot) {
					// the 'siteinfo.json' is some specific endpoint of Hubzilla and Red
					if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
						$serverdata = self::fetchSiteinfo($url, $serverdata);
					}

					// The 'siteinfo.json' doesn't seem to be present on older Hubzilla installations, so we check other endpoints as well
					if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
						$serverdata = self::detectHubzilla($url, $serverdata);
					}

					if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
						$serverdata = self::detectPeertube($url, $serverdata);
					}

					if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
						$serverdata = self::detectGNUSocial($url, $serverdata);
					}
				}
			} elseif (in_array($serverdata['platform'], ['friendica', 'friendika']) && in_array($serverdata['detection-method'], array_merge(self::DETECT_UNSPECIFIC, [self::DETECT_SYSTEM_ACTOR]))) {
				$serverdata = self::detectFriendica($url, $serverdata);
			}

			if (($serverdata['network'] == Protocol::PHANTOM) || in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
				$serverdata = self::detectNextcloud($url, $serverdata, $validHostMeta);
			}

			// When nodeinfo isn't present, we use the older 'statistics.json' endpoint
			// Since this endpoint is only rarely used, we query it at a later time
			if (in_array($serverdata['detection-method'], array_merge(self::DETECT_UNSPECIFIC, [self::DETECT_FRIENDICA, self::DETECT_CONFIG_JSON]))) {
				$serverdata = self::fetchStatistics($url, $serverdata);
			}
		}

		if (in_array($serverdata['platform'] ?? '', ['friendica', 'hubzilla', 'streams', 'osada', 'mistpark', 'roadhouse', 'zap'])) {
			$serverdata = self::getZotData($url, $serverdata);
		}

		// When we hadn't been able to detect the network type, we use the hint from the parameter
		if (($serverdata['network'] == Protocol::PHANTOM) && !empty($network)) {
			$serverdata['network'] = $network;
		}

		// Most servers aren't installed in a subdirectory, so we declare this entry as failed
		if (($serverdata['network'] == Protocol::PHANTOM) && !empty(parse_url($url, PHP_URL_PATH)) && in_array($serverdata['detection-method'], [self::DETECT_MANUAL])) {
			self::setFailureByUrl($url);
			return false;
		}

		$serverdata['url']  = $url;
		$serverdata['nurl'] = Strings::normaliseLink($url);

		// We have to prevent an endless loop here.
		// When a server is new, then there is no gserver entry yet.
		// But in "detectNetworkViaContacts" it could happen that a contact is updated,
		// and this can call this function here as well.
		if (self::getID($url, true) && (in_array($serverdata['network'], [Protocol::PHANTOM, Protocol::FEED]) ||
			in_array($serverdata['detection-method'], [self::DETECT_MANUAL, self::DETECT_HEADER, self::DETECT_BODY, self::DETECT_HOST_META]))) {
			$serverdata = self::detectNetworkViaContacts($url, $serverdata);
		}

		if ($serverdata['platform'] == 'mastodon') {
			$serverdata = self::detectMastodonForks($serverdata);
		}

		if (($serverdata['network'] == Protocol::PHANTOM) && in_array($serverdata['detection-method'], [self::DETECT_MANUAL, self::DETECT_BODY])) {
			self::setFailureByUrl($url);
			return false;
		}

		if (empty($serverdata['version']) && in_array($serverdata['platform'], ['osada']) && in_array($serverdata['detection-method'], [self::DETECT_CONTACTS, self::DETECT_BODY])) {
			$serverdata['version'] = self::getNomadVersion($url);
		}

		// Detect the directory type
		$serverdata['directory-type'] = self::DT_NONE;

		if (in_array($serverdata['network'], Protocol::FEDERATED)) {
			$serverdata = self::checkMastodonDirectory($url, $serverdata);

			if ($serverdata['directory-type'] == self::DT_NONE) {
				$serverdata = self::checkPoCo($url, $serverdata);
			}
		}

		if ($serverdata['network'] == Protocol::ACTIVITYPUB) {
			$serverdata = self::fetchWeeklyUsage($url, $serverdata);
		}

		$serverdata['registered-users'] = $serverdata['registered-users'] ?? 0;

		// Numbers above a reasonable value (10 millions) are ignored
		if ($serverdata['registered-users'] > 10000000) {
			$serverdata['registered-users'] = 0;
		}

		// On an active server there has to be at least a single user
		if (!in_array($serverdata['network'], [Protocol::PHANTOM, Protocol::FEED]) && ($serverdata['registered-users'] <= 0)) {
			$serverdata['registered-users'] = 1;
		} elseif (in_array($serverdata['network'], [Protocol::PHANTOM, Protocol::FEED])) {
			$serverdata['registered-users'] = 0;
		}

		$serverdata['next_contact'] = self::getNextUpdateDate(true, '', '', in_array($serverdata['network'], [Protocol::PHANTOM, Protocol::FEED]));
		$serverdata['last_contact'] = DateTimeFormat::utcNow();
		$serverdata['failed']       = false;
		$serverdata['blocked']      = false;

		$id = 0;

		$gserver = DBA::selectFirst('gserver', ['network'], ['nurl' => Strings::normaliseLink($url)]);
		if (!DBA::isResult($gserver)) {
			$serverdata['created'] = DateTimeFormat::utcNow();

			$ret = self::insert($serverdata);
			$id  = DBA::lastInsertId();
		} else {
			$ret     = self::update($serverdata, ['nurl' => $serverdata['nurl']]);
			$gserver = DBA::selectFirst('gserver', ['id'], ['nurl' => $serverdata['nurl']]);
			if (DBA::isResult($gserver)) {
				$id = $gserver['id'];
			}
		}

		// Count the number of known contacts from this server
		self::countNumberOfKnownContacts((int) $id, $serverdata);

		if (in_array($serverdata['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
			self::discoverRelay($url);
		}

		if (!empty($systemactor)) {
			$contact = Contact::getByURL($systemactor, true, ['gsid', 'baseurl', 'id', 'network', 'url', 'name']);
			DI::logger()->debug('Fetched system actor', ['url' => $url, 'gsid' => $id, 'contact' => $contact]);
		}

		return $ret;
	}

	/**
	 * Count the number of known contacts from this server
	 */
	private static function countNumberOfKnownContacts(int $id, array $serverdata): void
	{
		if (!empty($id) && !in_array($serverdata['network'], [Protocol::PHANTOM, Protocol::FEED])) {
			$apcontacts = DBA::count('apcontact', ['gsid' => $id]);
			$contacts   = DBA::count('contact', ['uid' => 0, 'gsid' => $id, 'failed' => false]);
			$max_users  = max($apcontacts, $contacts);
			if ($max_users > $serverdata['registered-users']) {
				DI::logger()->info('Update registered users', ['id' => $id, 'url' => $serverdata['nurl'], 'registered-users' => $max_users]);
				self::update(['registered-users' => $max_users], ['id' => $id]);
			}

			if (empty($serverdata['active-month-users'])) {
				$contacts = DBA::count('contact', ["`uid` = ? AND `gsid` = ? AND NOT `failed` AND `last-item` > ?", 0, $id, DateTimeFormat::utc('now - 30 days')]);
				if ($contacts > 0) {
					DI::logger()->info('Update monthly users', ['id' => $id, 'url' => $serverdata['nurl'], 'monthly-users' => $contacts]);
					self::update(['active-month-users' => $contacts], ['id' => $id]);
				}
			}

			if (empty($serverdata['active-halfyear-users'])) {
				$contacts = DBA::count('contact', ["`uid` = ? AND `gsid` = ? AND NOT `failed` AND `last-item` > ?", 0, $id, DateTimeFormat::utc('now - 180 days')]);
				if ($contacts > 0) {
					DI::logger()->info('Update halfyear users', ['id' => $id, 'url' => $serverdata['nurl'], 'halfyear-users' => $contacts]);
					self::update(['active-halfyear-users' => $contacts], ['id' => $id]);
				}
			}
		}
	}

	/**
	 * Fetch relay data from a given server url
	 *
	 * @param string $server_url address of the server
	 *
	 * @return void
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function discoverRelay(string $server_url)
	{
		DI::logger()->info('Discover relay data', ['server' => $server_url]);

		$curlResult = DI::httpClient()->get($server_url . '/.well-known/x-social-relay', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (!is_array($data)) {
			return;
		}

		// Sanitize incoming data, see https://github.com/friendica/friendica/issues/8565
		$data['subscribe'] = (bool)($data['subscribe'] ?? false);

		if (!$data['subscribe'] || empty($data['scope']) || !in_array(strtolower($data['scope']), ['all', 'tags'])) {
			$data['scope']     = '';
			$data['subscribe'] = false;
			$data['tags']      = [];
		}

		$gserver = DBA::selectFirst('gserver', ['id', 'url', 'network', 'relay-subscribe', 'relay-scope'], ['nurl' => Strings::normaliseLink($server_url)]);
		if (!DBA::isResult($gserver)) {
			return;
		}

		if (($gserver['relay-subscribe'] != $data['subscribe']) || ($gserver['relay-scope'] != $data['scope'])) {
			$fields = ['relay-subscribe' => $data['subscribe'], 'relay-scope' => $data['scope']];
			self::update($fields, ['id' => $gserver['id']]);
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

		DI::logger()->info('Discovery ended', ['server' => $server_url, 'data' => $fields]);

		Relay::updateContact($gserver, $fields);
	}

	/**
	 * Fetch server data from '/statistics.json' on the given server
	 *
	 * @param string $url URL of the given server
	 *
	 * @return array server data
	 */
	private static function fetchStatistics(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/statistics.json', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		// Some AP enabled systems return activity data that we don't expect here.
		if (strpos($curlResult->getContentType(), 'application/activity+json') !== false) {
			return $serverdata;
		}

		$valid          = false;
		$old_serverdata = $serverdata;

		$serverdata['detection-method'] = self::DETECT_STATISTICS_JSON;

		if (!empty($data['version'])) {
			$valid = true;

			$serverdata['version'] = $data['version'];
			// Version numbers on statistics.json are presented with additional info, e.g.:
			// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
			$serverdata['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $serverdata['version']);
		}

		if (!empty($data['name'])) {
			$valid = true;

			$serverdata['site_name'] = $data['name'];
		}

		if (!empty($data['network'])) {
			$valid = true;

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

		if (!empty($data['total_users'])) {
			$valid = true;

			$serverdata['registered-users'] = max($data['total_users'], 1);
		}

		if (!empty($data['active_users_monthly'])) {
			$valid = true;

			$serverdata['active-month-users'] = max($data['active_users_monthly'], 0);
		}

		if (!empty($data['active_users_halfyear'])) {
			$valid = true;

			$serverdata['active-halfyear-users'] = max($data['active_users_halfyear'], 0);
		}

		if (!empty($data['local_posts'])) {
			$valid = true;

			$serverdata['local-posts'] = max($data['local_posts'], 0);
		}

		if (!empty($data['registrations_open'])) {
			$serverdata['register_policy'] = Register::OPEN;
		} else {
			$serverdata['register_policy'] = Register::CLOSED;
		}

		if (!$valid) {
			return $old_serverdata;
		}

		return $serverdata;
	}

	/**
	 * Parses Nodeinfo
	 *
	 * @see https://github.com/jhass/nodeinfo
	 *
	 * @param string                  $url        address of the server
	 * @param ICanHandleHttpResponses $httpResult
	 *
	 * @return array Server data
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo(string $url, ICanHandleHttpResponses $httpResult): array
	{
		if (!$httpResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($httpResult->getBodyString(), true);

		if (!is_array($nodeinfo) || empty($nodeinfo['links'])) {
			return [];
		}

		$nodeinfo1_url    = '';
		$nodeinfo2_url    = '';
		$detection_method = self::DETECT_MANUAL;

		foreach ($nodeinfo['links'] as $link) {
			if (!is_array($link) || empty($link['rel']) || empty($link['href'])) {
				DI::logger()->info('Invalid nodeinfo format', ['url' => $url]);
				continue;
			}

			if ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/1.0') {
				$nodeinfo1_url = Network::addBasePath($link['href'], $httpResult->getUrl());
			} elseif (($detection_method < self::DETECT_NODEINFO_20) && ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/2.0')) {
				$nodeinfo2_url    = Network::addBasePath($link['href'], $httpResult->getUrl());
				$detection_method = self::DETECT_NODEINFO_20;
			} elseif (($detection_method < self::DETECT_NODEINFO_21) && ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/2.1')) {
				$nodeinfo2_url    = Network::addBasePath($link['href'], $httpResult->getUrl());
				$detection_method = self::DETECT_NODEINFO_21;
			} elseif (($detection_method < self::DETECT_NODEINFO_22) && ($link['rel'] == 'http://nodeinfo.diaspora.software/ns/schema/2.2')) {
				$nodeinfo2_url    = Network::addBasePath($link['href'], $httpResult->getUrl());
				$detection_method = self::DETECT_NODEINFO_22;
			}
		}

		if ($nodeinfo1_url . $nodeinfo2_url == '') {
			return [];
		}

		$server = [];

		if (!empty($nodeinfo2_url)) {
			$server = self::parseNodeinfo_2($nodeinfo2_url, $detection_method);
		}

		if (empty($server) && !empty($nodeinfo1_url)) {
			$server = self::parseNodeinfo_1($nodeinfo1_url);
		}

		return $server;
	}

	/**
	 * Parses Nodeinfo with the version 1.0
	 *
	 * @see https://github.com/jhass/nodeinfo/tree/main/schemas/1.0
	 *
	 * @param string $nodeinfo_url address of the nodeinfo path
	 *
	 * @return array Server data
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo_1(string $nodeinfo_url): array
	{
		$curlResult = DI::httpClient()->get($nodeinfo_url, HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBodyString(), true);

		if (!is_array($nodeinfo)) {
			return [];
		}

		$server = [
			'detection-method' => self::DETECT_NODEINFO_10,
			'register_policy'  => Register::CLOSED
		];

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

		if (!empty($nodeinfo['usage']['users']['activeMonth'])) {
			$server['active-month-users'] = max($nodeinfo['usage']['users']['activeMonth'], 0);
		}

		if (!empty($nodeinfo['usage']['users']['activeHalfyear'])) {
			$server['active-halfyear-users'] = max($nodeinfo['usage']['users']['activeHalfyear'], 0);
		}

		if (!empty($nodeinfo['usage']['localPosts'])) {
			$server['local-posts'] = max($nodeinfo['usage']['localPosts'], 0);
		}

		if (!empty($nodeinfo['usage']['localComments'])) {
			$server['local-comments'] = max($nodeinfo['usage']['localComments'], 0);
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

		if (empty($server['network'])) {
			$server['network'] = Protocol::PHANTOM;
		}

		return $server;
	}

	/**
	 * Parses Nodeinfo with the versions 2.0, 2.1 and 2.2
	 *
	 * @see https://github.com/jhass/nodeinfo/tree/main/schemas/2.0
	 * @see https://github.com/jhass/nodeinfo/tree/main/schemas/2.1
	 * @see https://github.com/jhass/nodeinfo/tree/main/schemas/2.2
	 *
	 * @param string $nodeinfo_url     address of the nodeinfo path
	 * @param int    $detection_method nodeinfo version
	 *
	 * @return array Server data
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo_2(string $nodeinfo_url, int $detection_method): array
	{
		$curlResult = DI::httpClient()->get($nodeinfo_url, HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($curlResult->getBodyString(), true);
		if (!is_array($nodeinfo)) {
			return [];
		}

		$server = [
			'detection-method' => $detection_method,
			'register_policy'  => Register::CLOSED,
			'platform'         => 'unknown',
		];

		if (!empty($nodeinfo['openRegistrations'])) {
			$server['register_policy'] = Register::OPEN;
		}

		if (!empty($nodeinfo['instance'])) {
			if (!empty($nodeinfo['instance']['name'])) {
				$server['site_name'] = $nodeinfo['instance']['name'];
			}
			if (!empty($nodeinfo['instance']['description'])) {
				$server['info'] = $nodeinfo['instance']['description'];
			}
		}

		if (!empty($nodeinfo['software'])) {
			if (isset($nodeinfo['software']['name'])) {
				$server['platform'] = strtolower($nodeinfo['software']['name']);
			}

			if (!empty($nodeinfo['software']['version']) && isset($server['platform'])) {
				$server['version'] = $nodeinfo['software']['version'];
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $server['version']);

				// qoto advertises itself as Mastodon
				if (($server['platform'] == 'mastodon') && substr($nodeinfo['software']['version'], -5) == '-qoto') {
					$server['platform'] = 'qoto';
				}

				if (isset($nodeinfo['software']['repository'])) {
					$server['repository'] = strtolower($nodeinfo['software']['repository']);
				}
				if (isset($nodeinfo['software']['homepage'])) {
					$server['homepage'] = strtolower($nodeinfo['software']['homepage']);
				}
			}
		}

		// Special treatment for NextCloud, since there you can freely define your software name
		if (!empty($nodeinfo['rootUrl']) && in_array(parse_url($nodeinfo['rootUrl'], PHP_URL_PATH), ['/index.php/apps/social', '/apps/social'])) {
			$server['platform'] = 'nextcloud';
		}

		if (!empty($nodeinfo['metadata']['nodeName'])) {
			$server['site_name'] = $nodeinfo['metadata']['nodeName'];
		}
		if (!empty($nodeinfo['metadata']['nodeDescription'])) {
			$server['info'] = $nodeinfo['metadata']['nodeDescription'];
		}

		if (!empty($nodeinfo['usage']['users']['total'])) {
			$server['registered-users'] = max($nodeinfo['usage']['users']['total'], 1);
		}

		if (!empty($nodeinfo['usage']['users']['activeMonth'])) {
			$server['active-month-users'] = max($nodeinfo['usage']['users']['activeMonth'], 0);
		}

		if (!empty($nodeinfo['usage']['users']['activeHalfyear'])) {
			$server['active-halfyear-users'] = max($nodeinfo['usage']['users']['activeHalfyear'], 0);
		}

		if (!empty($nodeinfo['usage']['localPosts'])) {
			$server['local-posts'] = max($nodeinfo['usage']['localPosts'], 0);
		}

		if (!empty($nodeinfo['usage']['localComments'])) {
			$server['local-comments'] = max($nodeinfo['usage']['localComments'], 0);
		}

		if (!empty($nodeinfo['protocols'])) {
			$protocols = [];
			if (is_string($nodeinfo['protocols'])) {
				$protocols[$nodeinfo['protocols']] = true;
			} else {
				foreach ($nodeinfo['protocols'] as $protocol) {
					if (is_string($protocol)) {
						$protocols[$protocol] = true;
					}
				}
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

		if (empty($server['network'])) {
			$server['network'] = Protocol::PHANTOM;
		}

		return $server;
	}

	/**
	 * Parses NodeInfo2
	 *
	 * @see https://github.com/jaywink/nodeinfo2
	 *
	 * @return array Server data
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function parseNodeinfo2(ICanHandleHttpResponses $httpResult): array
	{
		if (!$httpResult->isSuccess()) {
			return [];
		}

		$nodeinfo = json_decode($httpResult->getBodyString(), true);

		if (!is_array($nodeinfo)) {
			return [];
		}

		$server = [
			'detection-method' => self::DETECT_NODEINFO2_10,
			'register_policy'  => Register::CLOSED
		];

		if (!empty($nodeinfo['openRegistrations'])) {
			$server['register_policy'] = Register::OPEN;
		}

		if (!empty($nodeinfo['server'])) {
			if (!empty($nodeinfo['server']['software'])) {
				$server['platform'] = strtolower($nodeinfo['server']['software']);
			}

			if (!empty($nodeinfo['server']['version'])) {
				$server['version'] = $nodeinfo['server']['version'];
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace('=(.+)-(.{4,})=ism', '$1', $server['version']);
			}

			if (!empty($nodeinfo['server']['name'])) {
				$server['site_name'] = $nodeinfo['server']['name'];
			}
		}

		if (!empty($nodeinfo['usage']['users']['total'])) {
			$server['registered-users'] = max($nodeinfo['usage']['users']['total'], 1);
		}

		if (!empty($nodeinfo['usage']['users']['activeMonth'])) {
			$server['active-month-users'] = max($nodeinfo['usage']['users']['activeMonth'], 0);
		}

		if (!empty($nodeinfo['usage']['users']['activeHalfyear'])) {
			$server['active-halfyear-users'] = max($nodeinfo['usage']['users']['activeHalfyear'], 0);
		}

		if (!empty($nodeinfo['usage']['localPosts'])) {
			$server['local-posts'] = max($nodeinfo['usage']['localPosts'], 0);
		}

		if (!empty($nodeinfo['usage']['localComments'])) {
			$server['local-comments'] = max($nodeinfo['usage']['localComments'], 0);
		}

		if (!empty($nodeinfo['protocols'])) {
			$protocols = [];
			if (is_string($nodeinfo['protocols'])) {
				$protocols[$nodeinfo['protocols']] = true;
			} else {
				foreach ($nodeinfo['protocols'] as $protocol) {
					if (is_string($protocol)) {
						$protocols[$protocol] = true;
					}
				}
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

		if (empty($server['platform'])) {
			return [];
		}

		if (empty($server['network'])) {
			$server['network'] = Protocol::PHANTOM;
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
	private static function fetchSiteinfo(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/siteinfo.json', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
			$serverdata['detection-method'] = self::DETECT_SITEINFO_JSON;
		}

		if (!empty($data['platform'])) {
			$serverdata['platform'] = strtolower($data['platform']);
			$serverdata['version']  = $data['version'] ?? 'N/A';
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

		if (!empty($data['channels_active_monthly'])) {
			$serverdata['active-month-users'] = max($data['channels_active_monthly'], 0);
		}

		if (!empty($data['channels_active_halfyear'])) {
			$serverdata['active-halfyear-users'] = max($data['channels_active_halfyear'], 0);
		}

		if (!empty($data['local_posts'])) {
			$serverdata['local-posts'] = max($data['local_posts'], 0);
		}

		if (!empty($data['local_comments'])) {
			$serverdata['local-comments'] = max($data['local_comments'], 0);
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
	 * Fetches server data via an ActivityPub account with url of that server
	 *
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 *
	 * @throws Exception
	 */
	private static function fetchDataFromSystemActor(array $data, array $serverdata): array
	{
		if (empty($data)) {
			return ['server' => $serverdata, 'actor' => ''];
		}

		$actor = JsonLD::compact($data, false);
		if (in_array(JsonLD::fetchElement($actor, '@type'), ActivityPub\Receiver::ACCOUNT_TYPES)) {
			$serverdata['network']   = Protocol::ACTIVITYPUB;
			$serverdata['site_name'] = JsonLD::fetchElement($actor, 'as:name', '@value');
			$serverdata['info']      = JsonLD::fetchElement($actor, 'as:summary', '@value');
			if (self::isNomad($actor)) {
				$serverdata['platform']         = self::getNomadName($actor['@id']);
				$serverdata['version']          = self::getNomadVersion($actor['@id']);
				$serverdata['detection-method'] = self::DETECT_SYSTEM_ACTOR;
			} elseif (!empty($actor['as:generator'])) {
				$generator                      = explode(' ', JsonLD::fetchElement($actor['as:generator'], 'as:name', '@value'));
				$serverdata['platform']         = strtolower(array_shift($generator));
				$serverdata['version']          = self::getNomadVersion($actor['@id']);
				$serverdata['detection-method'] = self::DETECT_SYSTEM_ACTOR;
			} else {
				$serverdata['detection-method'] = self::DETECT_AP_ACTOR;
			}
			return ['server' => $serverdata, 'actor' => $actor['@id']];
		} elseif ((JsonLD::fetchElement($actor, '@type') == 'as:Collection')) {
			// By now only Ktistec seems to provide collections this way
			$serverdata['platform']         = 'ktistec';
			$serverdata['network']          = Protocol::ACTIVITYPUB;
			$serverdata['detection-method'] = self::DETECT_AP_COLLECTION;

			$actors = JsonLD::fetchElementArray($actor, 'as:items');
			if (!empty($actors) && !empty($actors[0]['@id'])) {
				$actor_url = $actor['@id'] . $actors[0]['@id'];
			} else {
				$actor_url = '';
			}

			return ['server' => $serverdata, 'actor' => $actor_url];
		}
		return ['server' => $serverdata, 'actor' => ''];
	}

	/**
	 * Detect if the given actor is a nomad account
	 *
	 * @param array $actor
	 * @return boolean
	 */
	private static function isNomad(array $actor): bool
	{
		$tags = JsonLD::fetchElementArray($actor, 'as:tag');
		if (empty($tags)) {
			return false;
		}

		foreach ($tags as $tag) {
			if ((($tag['as:name'] ?? '') == 'Protocol') && in_array('nomad', [$tag['sc:value'] ?? '', $tag['as:content'] ?? ''])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Fetch the name of Nomad implementation
	 *
	 * @param string $url
	 * @return string
	 */
	private static function getNomadName(string $url): string
	{
		$name       = 'nomad';
		$curlResult = DI::httpClient()->get($url . '/manifest', 'application/manifest+json', [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			if ($curlResult->getReturnCode() == 418) {
				$name = 'streams';
			}
			return $name;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $name;
		}

		return $data['name'] ?? $name;
	}

	/**
	 * Fetch the version of the Nomad installation
	 *
	 * @param string $url
	 * @return string
	 */
	private static function getNomadVersion(string $url): string
	{
		$curlResult = DI::httpClient()->get($url . '/api/z/1.0/version', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			return '';
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (!is_string($data)) {
			return '';
		}

		return $data;
	}

	private static function getZotData(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url, 'application/x-zot+json');
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}
		$json = $curlResult->getBodyString();
		$data = json_decode($json, true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['site'])) {
			$serverdata = self::getFromZotData($data['site'], $serverdata);
		} else {
			$serverdata = self::getFromZotData($data, $serverdata);
		}
		return $serverdata;
	}

	private static function getFromZotData(array $data, array $serverdata): array
	{
		if (!empty($data['version'])) {
			$serverdata['version'] = $data['version'];
		}

		if (!empty($data['openWebAuth'])) {
			$serverdata['openwebauth'] = $data['openWebAuth'];
		}

		if (!empty($data['authRedirect'])) {
			$serverdata['authredirect'] = $data['authRedirect'];
		}

		if (!empty($data['sitename'])) {
			$serverdata['site_name'] = $data['sitename'];
		}

		if (!empty($data['about'])) {
			$serverdata['info'] = $data['about'];
		}

		if (empty($serverdata['info']) && !empty($data['location'])) {
			$serverdata['info'] = $data['location'];
		}

		if (!empty($data['project']) && in_array($data['project'], ['friendica', 'hubzilla', 'streams', 'osada', 'mistpark', 'roadhouse', 'zap'])) {
			$serverdata['platform'] = $data['project'];
		}

		if (!empty($data['accounts'])) {
			$serverdata['registered-users'] = $data['accounts'];
		}

		if (!empty($data['register_policy'])) {
			switch ($data['register_policy']) {
				case 'open':
					$serverdata['register_policy'] = Register::OPEN;
					break;
				case 'closed':
					$serverdata['register_policy'] = Register::CLOSED;
					break;
				case 'approve':
					$serverdata['register_policy'] = Register::APPROVE;
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
	private static function validHostMeta(string $url): bool
	{
		$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
		$curlResult  = DI::httpClient()->get($url . Probe::HOST_META, HttpClientAccept::XRD_XML, [HttpClientOptions::TIMEOUT => $xrd_timeout, HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		$xrd = XML::parseString($curlResult->getBodyString(), true);
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
	private static function detectNetworkViaContacts(string $url, array $serverdata): array
	{
		$contact = DBA::selectFirst('contact', ['network'], ["`network` != ? AND NOT `failed` AND `uid` = ? AND `baseurl` IN (?, ?)", Protocol::PHANTOM, 0, $url, Strings::normaliseLink($url)]);
		if (empty($contact)) {
			return $serverdata;
		}
		$serverdata['network'] = $contact['network'];

		if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
			$serverdata['detection-method'] = self::DETECT_CONTACTS;
		}
		return $serverdata;
	}

	private static function detectMastodonForks(array $serverdata): array
	{
		if (strpos($serverdata['version'], 'glitch') !== false) {
			$serverdata['platform'] = 'glitchsoc';
		}

		if (strpos($serverdata['version'], 'chuckya') !== false) {
			$serverdata['platform'] = 'chuckya';
		}

		if (strpos($serverdata['version'], 'sakura') !== false) {
			$serverdata['platform'] = 'sakura';
		}

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
	private static function checkPoCo(string $url, array $serverdata): array
	{
		$serverdata['poco'] = '';

		$curlResult = DI::httpClient()->get($url . '/poco', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['totalResults'])) {
			$serverdata['registered-users'] = max($data['totalResults'], $serverdata['registered-users'] ?? 0, 1);
			$serverdata['directory-type']   = self::DT_POCO;
			$serverdata['poco']             = $url . '/poco';
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
	public static function checkMastodonDirectory(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/api/v1/directory?limit=1', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!is_countable($data)) {
			DI::logger()->notice('Unexpected return type', ['url' => $url, 'data' => $data, 'serverdata' => $serverdata]);
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
	private static function detectPeertube(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/api/v1/config', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['instance']) && !empty($data['serverVersion'])) {
			$serverdata['platform'] = 'peertube';
			$serverdata['version']  = $data['serverVersion'];
			$serverdata['network']  = Protocol::ACTIVITYPUB;

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

			if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
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
	 * @param bool   $validHostMeta
	 *
	 * @return array server data
	 */
	private static function detectNextcloud(string $url, array $serverdata, bool $validHostMeta): array
	{
		$curlResult = DI::httpClient()->get($url . '/status.php', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		if (!empty($data['version'])) {
			$serverdata['platform'] = 'nextcloud';
			$serverdata['version']  = $data['version'];

			if ($validHostMeta) {
				$serverdata['network'] = Protocol::ACTIVITYPUB;
			}

			if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
				$serverdata['detection-method'] = self::DETECT_STATUS_PHP;
			}
		}

		return $serverdata;
	}

	/**
	 * Fetches weekly usage data
	 *
	 * @param string $url        URL of the given server
	 * @param array  $serverdata array with server data
	 *
	 * @return array server data
	 */
	private static function fetchWeeklyUsage(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/api/v1/instance/activity', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		$current_week = [];
		foreach ($data as $week) {
			// Use only data from a full week
			if (empty($week['week']) || (time() - $week['week']) < 7 * 24 * 60 * 60) {
				continue;
			}

			// Most likely the data is sorted correctly. But we better are safe than sorry
			if (empty($current_week['week']) || ($current_week['week'] < $week['week'])) {
				$current_week = $week;
			}
		}

		if (!empty($current_week['logins'])) {
			$serverdata['active-week-users'] = max($current_week['logins'], 0);
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
	private static function detectMastodonAlikes(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/api/v1/instance', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data)) {
			return $serverdata;
		}

		$valid = false;

		if (!empty($data['version'])) {
			$serverdata['platform'] = 'mastodon';
			$serverdata['version']  = $data['version'] ?? '';
			$serverdata['network']  = Protocol::ACTIVITYPUB;

			$valid = true;
		}

		if (!empty($data['title'])) {
			$serverdata['site_name'] = $data['title'];
		}

		if (!empty($data['title']) && empty($serverdata['platform']) && ($serverdata['network'] == Protocol::PHANTOM)) {
			$serverdata['platform'] = 'mastodon';
			$serverdata['network']  = Protocol::ACTIVITYPUB;

			$valid = true;
		}

		if (!empty($data['description'])) {
			$serverdata['info'] = trim($data['description']);
		}

		if (!empty($data['stats']['user_count'])) {
			$serverdata['registered-users'] = max($data['stats']['user_count'], 1);
		}

		if (!empty($serverdata['version']) && preg_match('/.*?\(compatible;\s(.*)\s(.*)\)/ism', $serverdata['version'], $matches)) {
			$serverdata['platform'] = strtolower($matches[1]);
			$serverdata['version']  = $matches[2];

			$valid = true;
		}

		if (!empty($serverdata['version']) && strstr(strtolower($serverdata['version']), 'pleroma')) {
			$serverdata['platform'] = 'pleroma';
			$serverdata['version']  = trim(str_ireplace('pleroma', '', $serverdata['version']));

			$valid = true;
		}

		if (!empty($serverdata['platform']) && strstr($serverdata['platform'], 'pleroma')) {
			$serverdata['version']  = trim(str_ireplace('pleroma', '', $serverdata['platform']));
			$serverdata['platform'] = 'pleroma';

			$valid = true;
		}

		if ($valid && in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
			$serverdata['detection-method'] = self::DETECT_MASTODON_API;
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
	private static function detectHubzilla(string $url, array $serverdata): array
	{
		$curlResult = DI::httpClient()->get($url . '/api/statusnet/config.json', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess() || ($curlResult->getBodyString() == '')) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data) || empty($data['site'])) {
			return $serverdata;
		}

		if (!empty($data['site']['name'])) {
			$serverdata['site_name'] = $data['site']['name'];
		}

		if (!empty($data['site']['platform'])) {
			$serverdata['platform'] = strtolower($data['site']['platform']['PLATFORM_NAME']);
			$serverdata['version']  = $data['site']['platform']['STD_VERSION'];
			$serverdata['network']  = Protocol::ZOT;
		}

		if (!empty($data['site']['hubzilla'])) {
			$serverdata['platform'] = strtolower($data['site']['hubzilla']['PLATFORM_NAME']);
			$serverdata['version']  = $data['site']['hubzilla']['RED_VERSION'];
			$serverdata['network']  = Protocol::ZOT;
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

		$private    = false;
		$inviteonly = false;
		$closed     = false;

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

		if (($serverdata['network'] != Protocol::PHANTOM) && in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
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
	private static function toBoolean($val): bool
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
	private static function detectGNUSocial(string $url, array $serverdata): array
	{
		// Test for GNU Social
		$curlResult = DI::httpClient()->get($url . '/api/gnusocial/version.json', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if ($curlResult->isSuccess() && ($curlResult->getBodyString() != '{"error":"not implemented"}') &&
			($curlResult->getBodyString() != '') && (strlen($curlResult->getBodyString()) < 30)) {
			$serverdata['platform'] = 'gnusocial';
			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239) . chr(187) . chr(191), '', $curlResult->getBodyString());
			$serverdata['version'] = str_replace(["\r", "\n", "\t"], '', $serverdata['version']);
			$serverdata['version'] = trim($serverdata['version'], '"');
			$serverdata['network'] = Protocol::OSTATUS;

			if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
				$serverdata['detection-method'] = self::DETECT_GNUSOCIAL;
			}

			return $serverdata;
		}

		// Test for Statusnet
		$curlResult = DI::httpClient()->get($url . '/api/statusnet/version.json', HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if ($curlResult->isSuccess() && ($curlResult->getBodyString() != '{"error":"not implemented"}') &&
			($curlResult->getBodyString() != '') && (strlen($curlResult->getBodyString()) < 30)) {

			// Remove junk that some GNU Social servers return
			$serverdata['version'] = str_replace(chr(239) . chr(187) . chr(191), '', $curlResult->getBodyString());
			$serverdata['version'] = str_replace(["\r", "\n", "\t"], '', $serverdata['version']);
			$serverdata['version'] = trim($serverdata['version'], '"');

			if (!empty($serverdata['version']) && strtolower(substr($serverdata['version'], 0, 7)) == 'pleroma') {
				$serverdata['platform'] = 'pleroma';
				$serverdata['version']  = trim(str_ireplace('pleroma', '', $serverdata['version']));
				$serverdata['network']  = Protocol::ACTIVITYPUB;
			} else {
				$serverdata['platform'] = 'statusnet';
				$serverdata['network']  = Protocol::OSTATUS;
			}

			if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
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
	private static function detectFriendica(string $url, array $serverdata): array
	{
		// There is a bug in some versions of Friendica that will return an ActivityStream actor when the content type "application/json" is requested.
		// Because of this me must not use ACCEPT_JSON here.
		$curlResult = DI::httpClient()->get($url . '/friendica/json', HttpClientAccept::DEFAULT, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
		if (!$curlResult->isSuccess()) {
			$curlResult = DI::httpClient()->get($url . '/friendika/json', HttpClientAccept::DEFAULT, [HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]);
			$friendika  = true;
			$platform   = 'Friendika';
		} else {
			$friendika = false;
			$platform  = 'Friendica';
		}

		if (!$curlResult->isSuccess()) {
			return $serverdata;
		}

		$data = json_decode($curlResult->getBodyString(), true);
		if (empty($data) || empty($data['version'])) {
			return $serverdata;
		}

		if (in_array($serverdata['detection-method'], self::DETECT_UNSPECIFIC)) {
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
				DI::logger()->info('Register policy is invalid', ['policy' => $register_policy, 'server' => $url]);
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
	 *
	 * @return array server data
	 */
	private static function analyseRootBody($curlResult, array $serverdata): array
	{
		if (empty($curlResult->getBodyString())) {
			return $serverdata;
		}

		if (!file_exists(__DIR__ . '/../../static/platforms.config.php')) {
			throw new HTTPException\InternalServerErrorException('Invalid platform file');
		}

		/** @var array $grouped_platforms */
		$grouped_platforms = require __DIR__ . '/../../static/platforms.config.php';

		$platforms = array_merge(
			$grouped_platforms['ap_platforms'],
			$grouped_platforms['dfrn_platforms'],
			$grouped_platforms['zap_platforms'],
			$grouped_platforms['platforms'],
		);

		$doc = new DOMDocument();
		@$doc->loadHTML($curlResult->getBodyString());
		$xpath = new DOMXPath($doc);

		$assigned = false;

		// We can only detect honk via some HTML element on their page
		if ($xpath->query('//div[@id="honksonpage"]')->count() == 1) {
			$serverdata['platform'] = 'honk';
			$serverdata['network']  = Protocol::ACTIVITYPUB;

			$assigned = true;
		}

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
				'twitter:app:name:googleplay', 'twitter:app:name:iphone', 'twitter:app:name:ipad', 'generator'])) {
				$platform = str_ireplace(array_keys($platforms), array_values($platforms), $attr['content']);
				$platform = str_replace('/', ' ', $platform);

				$platform_parts = explode(' ', $platform);
				if ((count($platform_parts) >= 2) && in_array(strtolower($platform_parts[0]), array_values($platforms))) {
					$platform = $platform_parts[0];

					$serverdata['version'] = $platform_parts[1];
				}
				if (in_array($platform, array_values($grouped_platforms['dfrn_platforms']))) {
					$serverdata['network'] = Protocol::DFRN;
				} elseif (in_array($platform, array_values($grouped_platforms['ap_platforms']))) {
					$serverdata['network'] = Protocol::ACTIVITYPUB;
				} elseif (in_array($platform, array_values($grouped_platforms['zap_platforms']))) {
					$serverdata['network'] = Protocol::ZOT;
				}
				if (in_array($platform, array_values($platforms))) {
					$serverdata['platform'] = $platform;

					$assigned = true;
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

			if (in_array($attr['property'], ['og:platform', 'generator'])) {
				if (in_array($attr['content'], array_keys($platforms))) {
					$serverdata['platform'] = $platforms[$attr['content']];

					$assigned = true;
				}

				if (in_array($attr['content'], array_keys($grouped_platforms['ap_platforms']))) {
					$serverdata['network'] = Protocol::ACTIVITYPUB;
				} elseif (in_array($attr['content'], array_values($grouped_platforms['zap_platforms']))) {
					$serverdata['network'] = Protocol::ZOT;
				}
			}
		}

		$list = $xpath->query('//link[@rel="me"]');
		foreach ($list as $node) {
			foreach ($node->attributes as $attribute) {
				if (parse_url(trim($attribute->value), PHP_URL_HOST) == 'micro.blog') {
					$serverdata['version']  = trim($serverdata['platform'] . ' ' . $serverdata['version']);
					$serverdata['platform'] = 'microblog';
					$serverdata['network']  = Protocol::ACTIVITYPUB;

					$assigned = true;
				}
			}
		}

		if ($serverdata['platform'] != 'microblog') {
			$list = $xpath->query('//link[@rel="micropub"]');
			foreach ($list as $node) {
				foreach ($node->attributes as $attribute) {
					if (trim($attribute->value) == 'https://micro.blog/micropub') {
						$serverdata['version']  = trim($serverdata['platform'] . ' ' . $serverdata['version']);
						$serverdata['platform'] = 'microblog';
						$serverdata['network']  = Protocol::ACTIVITYPUB;

						$assigned = true;
					}
				}
			}
		}

		if ($assigned && in_array($serverdata['detection-method'], [self::DETECT_MANUAL, self::DETECT_HEADER])) {
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
	private static function analyseRootHeader($curlResult, array $serverdata): array
	{
		if ($curlResult->getHeader('server') == 'Mastodon') {
			$serverdata['platform'] = 'mastodon';
			$serverdata['network']  = Protocol::ACTIVITYPUB;
		} elseif ($curlResult->inHeader('x-diaspora-version')) {
			$serverdata['platform'] = 'diaspora';
			$serverdata['network']  = Protocol::DIASPORA;
			$serverdata['version']  = $curlResult->getHeader('x-diaspora-version')[0] ?? '';
		} elseif ($curlResult->inHeader('x-friendica-version')) {
			$serverdata['platform'] = 'friendica';
			$serverdata['network']  = Protocol::DFRN;
			$serverdata['version']  = $curlResult->getHeader('x-friendica-version')[0] ?? '';
		} else {
			return $serverdata;
		}

		if ($serverdata['detection-method'] == self::DETECT_MANUAL) {
			$serverdata['detection-method'] = self::DETECT_HEADER;
		}

		return $serverdata;
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

		$last_update = date('c', time() - (60 * 60 * 24 * $requery_days));

		$gservers = DBA::select(
			'gserver',
			['id', 'url', 'nurl', 'network', 'poco', 'directory-type'],
			["NOT `blocked` AND NOT `failed` AND `directory-type` != ? AND `last_poco_query` < ?", GServer::DT_NONE, $last_update],
			['order' => ['RAND()']]
		);

		while ($gserver = DBA::fetch($gservers)) {
			DI::logger()->info('Update peer list', ['server' => $gserver['url'], 'id' => $gserver['id']]);
			Worker::add(Worker::PRIORITY_LOW, 'UpdateServerPeers', $gserver['url']);

			DI::logger()->info('Update directory', ['server' => $gserver['url'], 'id' => $gserver['id']]);
			Worker::add(Worker::PRIORITY_LOW, 'UpdateServerDirectory', $gserver);

			$fields = ['last_poco_query' => DateTimeFormat::utcNow()];
			self::update($fields, ['nurl' => $gserver['nurl']]);

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
		$last = DI::keyValue()->get('poco_last_federation_discovery');

		if ($last) {
			$next = $last + (24 * 60 * 60);

			if ($next > time()) {
				return;
			}
		}

		// Discover federated servers
		$protocols = ['activitypub', 'diaspora', 'dfrn', 'ostatus'];
		foreach ($protocols as $protocol) {
			$query      = '{nodes(protocol:"' . $protocol . '"){host}}';
			$curlResult = DI::httpClient()->fetch('https://the-federation.info/graphql?query=' . urlencode($query), HttpClientAccept::JSON, 0, '', HttpClientRequest::SERVERDISCOVER);
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

		// Discover Mastodon servers
		$accesstoken = DI::config()->get('system', 'instances_social_key');

		if (!empty($accesstoken)) {
			$api        = 'https://instances.social/api/1.0/instances/list?count=0';
			$curlResult = DI::httpClient()->get($api, HttpClientAccept::JSON, [HttpClientOptions::HEADERS => ['Authorization' => ['Bearer ' . $accesstoken], HttpClientOptions::REQUEST => HttpClientRequest::SERVERDISCOVER]]);
			if ($curlResult->isSuccess()) {
				$servers = json_decode($curlResult->getBodyString(), true);

				if (!empty($servers['instances'])) {
					foreach ($servers['instances'] as $server) {
						$url = (is_null($server['https_score']) ? 'http' : 'https') . '://' . $server['name'];
						self::add($url);
					}
				}
			}
		}

		DI::keyValue()->set('poco_last_federation_discovery', time());
	}

	public static function updateFromProbeArray(array $data)
	{
		if (empty($data['gsid']) || empty($data['openwebauth'])) {
			return;
		}

		$gserver = DBA::selectFirst('gserver', ['url', 'openwebauth'], ['id' => $data['gsid']]);
		if (!DBA::isResult($gserver)) {
			return;
		}

		if ($data['openwebauth'] == $gserver['openwebauth']) {
			return;
		}

		$serverdata = self::getZotData($gserver['url'], []);
		if (empty($serverdata)) {
			$serverdata = ['openwebauth' => $data['openwebauth']];
		}

		self::update($serverdata, ['id' => $data['gsid']]);
	}

	/**
	 * Set the protocol for the given server
	 *
	 * @param int $gsid     Server id
	 * @param int $protocol Protocol id
	 *
	 * @throws Exception
	 */
	public static function setProtocol(int $gsid, int $protocol)
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

		DI::logger()->info('Protocol for server', ['protocol' => $protocol, 'old' => $old, 'id' => $gsid, 'url' => $gserver['url']]);
		self::update(['protocol' => $protocol], ['id' => $gsid]);
	}

	/**
	 * Fetch the protocol of the given server
	 *
	 * @param int $gsid Server id
	 *
	 * @return ?int One of Post\DeliveryData protocol constants or null if unknown or gserver is missing
	 *
	 * @throws Exception
	 */
	public static function getProtocol(int $gsid): ?int
	{
		if (empty($gsid)) {
			return null;
		}

		$gserver = DBA::selectFirst('gserver', ['protocol'], ['id' => $gsid]);
		if (DBA::isResult($gserver)) {
			return $gserver['protocol'];
		}

		return null;
	}

	/**
	 * Update rows in the gserver table.
	 * Enforces gserver table field maximum sizes to avoid "Data too long" database errors
	 *
	 * @param array $fields
	 * @param array $condition
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	public static function update(array $fields, array $condition): bool
	{
		$fields = DI::dbaDefinition()->truncateFieldsForTable('gserver', $fields);

		return DBA::update('gserver', $fields, $condition);
	}

	/**
	 * Insert a row into the gserver table.
	 * Enforces gserver table field maximum sizes to avoid "Data too long" database errors
	 *
	 * @param array $fields
	 * @param int   $duplicate_mode What to do on a duplicated entry
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	public static function insert(array $fields, int $duplicate_mode = Database::INSERT_DEFAULT): bool
	{
		$fields = DI::dbaDefinition()->truncateFieldsForTable('gserver', $fields);

		return DBA::insert('gserver', $fields, $duplicate_mode);
	}
}
