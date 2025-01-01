<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use DOMDocument;
use DOMXPath;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Base class for the ATProtocol
 * @see https://atproto.com/
 */
final class ATProtocol
{
	const STATUS_UNKNOWN    = 0;
	const STATUS_TOKEN_OK   = 1;
	const STATUS_SUCCESS    = 2;
	const STATUS_API_FAIL   = 10;
	const STATUS_DID_FAIL   = 11;
	const STATUS_PDS_FAIL   = 12;
	const STATUS_TOKEN_FAIL = 13;

	const APPVIEW_API = 'https://public.api.bsky.app'; // Path to the public Bluesky AppView API.
	const DIRECTORY   = 'https://plc.directory';       // Path to the directory server service to fetch the PDS of a given DID
	const WEB         = 'https://bsky.app';            // Path to the web interface with the user profile and posts
	const HOSTNAME    = 'bsky.social';                 // Host name to be added to the handle if incomplete

	/** @var LoggerInterface */
	private $logger;

	/** @var Database */
	private $db;

	/** @var \Friendica\Core\Config\Capability\IManageConfigValues */
	private $config;

	/** @var IManagePersonalConfigValues */
	private $pConfig;

	/** @var ICanSendHttpRequests */
	private $httpClient;

	public function __construct(LoggerInterface $logger, Database $database, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, ICanSendHttpRequests $httpClient)
	{
		$this->logger     = $logger;
		$this->db         = $database;
		$this->config     = $config;
		$this->pConfig    = $pConfig;
		$this->httpClient = $httpClient;
	}

	/**
	 * Returns an array of user ids who want to import the Bluesky timeline
	 *
	 * @return array user ids
	 */
	public function getUids(): array
	{
		$uids         = [];
		$abandon_days = intval($this->config->get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}

		$abandon_limit = date(DateTimeFormat::MYSQL, time() - $abandon_days * 86400);

		$pconfigs = $this->db->selectToArray('pconfig', [], ["`cat` = ? AND `k` = ? AND `v`", 'bluesky', 'import']);
		foreach ($pconfigs as $pconfig) {
			if (empty($this->getUserDid($pconfig['uid']))) {
				continue;
			}

			if ($abandon_days != 0) {
				if (!$this->db->exists('user', ["`uid` = ? AND `login_date` >= ?", $pconfig['uid'], $abandon_limit])) {
					continue;
				}
			}
			$uids[] = $pconfig['uid'];
		}
		return $uids;
	}

	/**
	 * Fetches XRPC data
	 * @see https://atproto.com/specs/xrpc#lexicon-http-endpoints
	 *
	 * @param string  $url        for example "app.bsky.feed.getTimeline"
	 * @param array   $parameters Array with parameters
	 * @param integer $uid        User ID
	 * @return stdClass|null Fetched data
	 */
	public function XRPCGet(string $url, array $parameters = [], int $uid = 0): ?stdClass
	{
		if (!empty($parameters)) {
			$url .= '?' . http_build_query($parameters);
		}

		if ($uid == 0) {
			return $this->get(ATProtocol::APPVIEW_API . '/xrpc/' . $url);
		}

		$pds = $this->getUserPds($uid);
		if (empty($pds)) {
			return null;
		}

		$headers = ['Authorization' => ['Bearer ' . $this->getUserToken($uid)]];

		$languages = User::getWantedLanguages($uid);
		if (!empty($languages)) {
			$headers['Accept-Language'] = implode(',', $languages);
		}

		$data = $this->get($pds . '/xrpc/' . $url, [HttpClientOptions::HEADERS => $headers]);

		if ($data === null) {
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_API_FAIL);

			return null;
		}

		if (!empty($data->code) && ($data->code < 200 || $data->code >= 400)) {
			if (!empty($data->message)) {
				$this->pConfig->set($uid, 'bluesky', 'status-message', $data->message);
			} elseif (!empty($data->code)) {
				$this->pConfig->set($uid, 'bluesky', 'status-message', 'Error Code: ' . $data->code);
			}

			return $data;
		}

		$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_SUCCESS);
		$this->pConfig->set($uid, 'bluesky', 'status-message', '');

		return $data;
	}

	/**
	 * Fetch data from the given URL via GET and return it as a JSON class
	 *
	 * @param string $url HTTP URL
	 * @param array $opts HTTP options
	 * @return stdClass|null Fetched data
	 */
	public function get(string $url, array $opts = []): ?stdClass
	{
		try {
			$curlResult = $this->httpClient->get($url, HttpClientAccept::JSON, $opts);
		} catch (\Exception $e) {
			$this->logger->notice('Exception on get', ['url' => $url, 'exception' => $e]);
			return null;
		}

		$data = json_decode($curlResult->getBodyString());
		if (!$curlResult->isSuccess()) {
			$this->logger->notice('API Error', ['url' => $url, 'code' => $curlResult->getReturnCode(), 'error' => $data ?: $curlResult->getBodyString()]);
			if (!$data) {
				return null;
			}
			$data->code = $curlResult->getReturnCode();
		} elseif (($curlResult->getReturnCode() < 200) || ($curlResult->getReturnCode() >= 400)) {
			$this->logger->notice('Unexpected return code', ['url' => $url, 'code' => $curlResult->getReturnCode(), 'error' => $data ?: $curlResult->getBodyString()]);
			$data->code = $curlResult->getReturnCode();
		}

		Item::incrementInbound(Protocol::BLUESKY);
		return $data;
	}

	/**
	 * Perform an XRPC post for a given user
	 * @see https://atproto.com/specs/xrpc#lexicon-http-endpoints
	 *
	 * @param int            $uid        User ID
	 * @param string         $url        Endpoints like "com.atproto.repo.createRecord"
	 * @param array|stdClass $parameters array or StdClass with parameters
	 */
	public function XRPCPost(int $uid, string $url, $parameters): ?stdClass
	{
		$data = $this->post($uid, '/xrpc/' . $url, json_encode($parameters), ['Content-type' => 'application/json', 'Authorization' => ['Bearer ' . $this->getUserToken($uid)]]);
		return $data;
	}

	/**
	 * Post data to the user PDS
	 *
	 * @param integer $uid   User ID
	 * @param string $url    HTTP URL without the hostname
	 * @param string $params Parameter string
	 * @param array $headers HTTP header information
	 * @return stdClass|null
	 */
	public function post(int $uid, string $url, string $params, array $headers): ?stdClass
	{
		$pds = $this->getUserPds($uid);
		if (empty($pds)) {
			return null;
		}

		try {
			$curlResult = $this->httpClient->post($pds . $url, $params, $headers);
		} catch (\Exception $e) {
			$this->logger->notice('Exception on post', ['exception' => $e]);
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_API_FAIL);
			$this->pConfig->set($uid, 'bluesky', 'status-message', $e->getMessage());
			return null;
		}

		$data = json_decode($curlResult->getBodyString(), false);

		if (!$curlResult->isSuccess()) {
			$this->logger->notice('API Error', ['url' => $url, 'code' => $curlResult->getReturnCode(), 'error' => $data ?: $curlResult->getBodyString()]);
			if (!$data) {
				$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_API_FAIL);

				return null;
			}
			$data->code = $curlResult->getReturnCode();
		}

		if (!empty($data->code) && ($data->code >= 200) && ($data->code < 400)) {
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_SUCCESS);
			$this->pConfig->set($uid, 'bluesky', 'status-message', '');
		} else {
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_API_FAIL);
			if (!empty($data->message)) {
				$this->pConfig->set($uid, 'bluesky', 'status-message', $data->message);
			} elseif (!empty($data->code)) {
				$this->pConfig->set($uid, 'bluesky', 'status-message', 'Error Code: ' . $data->code);
			}
		}
		return $data;
	}

	/**
	 * Fetches the PDS for a given user
	 * @see https://atproto.com/guides/glossary#pds-personal-data-server
	 *
	 * @param integer $uid User ID or 0
	 * @return string|null PDS or null if the user has got no PDS assigned. If UID set to 0, the public api URL is used
	 */
	private function getUserPds(int $uid): ?string
	{
		if ($uid == 0) {
			return self::APPVIEW_API;
		}

		$pds = $this->pConfig->get($uid, 'bluesky', 'pds');
		if (!empty($pds)) {
			return $pds;
		}

		$did = $this->getUserDid($uid);
		if (empty($did)) {
			return null;
		}

		$pds = $this->getPdsOfDid($did);
		if (empty($pds)) {
			return null;
		}

		$this->pConfig->set($uid, 'bluesky', 'pds', $pds);
		return $pds;
	}

	/**
	 * Fetch the DID for a given user
	 * @see https://atproto.com/guides/glossary#did-decentralized-id
	 *
	 * @param integer $uid     User ID
	 * @param boolean $refresh Default "false". If set to true, the DID is detected from the handle again.
	 * @return string|null     DID or null if no DID has been found.
	 */
	public function getUserDid(int $uid, bool $refresh = false): ?string
	{
		if (!$refresh) {
			$did = $this->pConfig->get($uid, 'bluesky', 'did');
			if (!empty($did)) {
				return $did;
			}
		}

		$handle = $this->pConfig->get($uid, 'bluesky', 'handle');
		if (empty($handle)) {
			return null;
		}

		$did = $this->getDid($handle);
		if (empty($did)) {
			return null;
		}

		$this->logger->debug('Got DID for user', ['uid' => $uid, 'handle' => $handle, 'did' => $did]);
		$this->pConfig->set($uid, 'bluesky', 'did', $did);
		return $did;
	}

	/**
	 * Fetches the DID for a given handle
	 *
	 * @param string $handle The user handle
	 * @return string DID (did:plc:...)
	 */
	public function getDid(string $handle): string
	{
		if ($handle == '') {
			return '';
		}

		if (strpos($handle, '.') === false) {
			$handle .= '.' . self::HOSTNAME;
		}

		// At first we use the AppView API which *should* cover all cases.
		$data = $this->get(self::APPVIEW_API . '/xrpc/com.atproto.identity.resolveHandle?handle=' . urlencode($handle));
		if (!empty($data) && !empty($data->did)) {
			$this->logger->debug('Got DID by system PDS call', ['handle' => $handle, 'did' => $data->did]);
			return $data->did;
		}

		// Then we query the DNS, which is used for third party handles (DNS should be faster than wellknown)
		$did = $this->getDidByDns($handle);
		if ($did != '') {
			$this->logger->debug('Got DID by DNS', ['handle' => $handle, 'did' => $did]);
			return $did;
		}

		// Then we query wellknown, which should mostly cover the rest.
		$did = $this->getDidByWellknown($handle);
		if ($did != '') {
			$this->logger->debug('Got DID by wellknown', ['handle' => $handle, 'did' => $did]);
			return $did;
		}

		$this->logger->notice('No DID detected', ['handle' => $handle]);
		return '';
	}

	/**
	 * Fetches a DID for a given profile URL
	 *
	 * @param string $url HTTP path to the profile in the format https://bsky.app/profile/username
	 * @return string DID (did:plc:...)
	 */
	public function getDidByProfile(string $url): string
	{
		if (preg_match('#^' . self::WEB . '/profile/(.+)#', $url, $matches)) {
			$did = $this->getDid($matches[1]);
			if (!empty($did)) {
				return $did;
			}
		}
		try {
			$curlResult = $this->httpClient->get($url, HttpClientAccept::HTML, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTINFO]);
		} catch (\Throwable $th) {
			return '';
		}
		if (!$curlResult->isSuccess()) {
			return '';
		}
		$profile = $curlResult->getBodyString();
		if (empty($profile)) {
			return '';
		}

		$doc = new DOMDocument();
		try {
			@$doc->loadHTML($profile);
		} catch (\Throwable $th) {
			return '';
		}
		$xpath = new DOMXPath($doc);
		$list  = $xpath->query('//p[@id]');
		foreach ($list as $node) {
			foreach ($node->attributes as $attribute) {
				if ($attribute->name == 'id') {
					$ids[$attribute->value] = $node->textContent;
				}
			}
		}

		if (empty($ids['bsky_handle']) || empty($ids['bsky_did'])) {
			return '';
		}

		if (!$this->isValidDid($ids['bsky_did'], $ids['bsky_handle'])) {
			$this->logger->notice('Invalid DID', ['handle' => $ids['bsky_handle'], 'did' => $ids['bsky_did']]);
			return '';
		}

		return $ids['bsky_did'];
	}

	/**
	 * Fetches the DID of a given handle via a HTTP request to the .well-known URL.
	 * This is one of the ways, custom handles can be authorized.
	 *
	 * @param string $handle The user handle
	 * @return string DID (did:plc:...)
	 */
	private function getDidByWellknown(string $handle): string
	{
		$curlResult = $this->httpClient->get('http://' . $handle . '/.well-known/atproto-did');
		if ($curlResult->isSuccess() && substr($curlResult->getBodyString(), 0, 4) == 'did:') {
			$did = $curlResult->getBodyString();
			if (!$this->isValidDid($did, $handle)) {
				$this->logger->notice('Invalid DID', ['handle' => $handle, 'did' => $did]);
				return '';
			}
			return $did;
		}
		return '';
	}

	/**
	 * Fetches the DID of a given handle via a DND request.
	 * This is one of the ways, custom handles can be authorized.
	 *
	 * @param string $handle The user handle
	 * @return string DID (did:plc:...)
	 */
	private function getDidByDns(string $handle): string
	{
		$records = @dns_get_record('_atproto.' . $handle . '.', DNS_TXT);
		if (empty($records)) {
			return '';
		}
		foreach ($records as $record) {
			if (!empty($record['txt']) && substr($record['txt'], 0, 4) == 'did=') {
				$did = substr($record['txt'], 4);
				if (!$this->isValidDid($did, $handle)) {
					$this->logger->notice('Invalid DID', ['handle' => $handle, 'did' => $did]);
					return '';
				}
				return $did;
			}
		}
		return '';
	}

	/**
	 * Fetch the PDS of a given DID
	 *
	 * @param string $did DID (did:plc:...)
	 * @return string|null URL of the PDS, e.g. https://enoki.us-east.host.bsky.network
	 */
	public function getPdsOfDid(string $did): ?string
	{
		$data = $this->get(self::DIRECTORY . '/' . $did);
		if (empty($data) || empty($data->service)) {
			return null;
		}

		foreach ($data->service as $service) {
			if (($service->id == '#atproto_pds') && ($service->type == 'AtprotoPersonalDataServer') && !empty($service->serviceEndpoint)) {
				return $service->serviceEndpoint;
			}
		}

		return null;
	}

	/**
	 * Checks if the provided DID matches the handle
	 *
	 * @param string $did DID (did:plc:...)
	 * @param string $handle The user handle
	 * @return boolean
	 */
	private function isValidDid(string $did, string $handle): bool
	{
		$data = $this->get(self::DIRECTORY . '/' . $did);
		if (empty($data) || empty($data->alsoKnownAs)) {
			return false;
		}

		return in_array('at://' . $handle, $data->alsoKnownAs);
	}

	/**
	 * Fetches the user token for a given user
	 *
	 * @param integer $uid User ID
	 * @return string user token
	 */
	public function getUserToken(int $uid): string
	{
		$token   = $this->pConfig->get($uid, 'bluesky', 'access_token');
		$created = $this->pConfig->get($uid, 'bluesky', 'token_created');
		if (empty($token)) {
			return '';
		}

		if ($created + 300 < time()) {
			return $this->refreshUserToken($uid);
		}
		return $token;
	}

	/**
	 * Refresh and returns the user token for a given user.
	 *
	 * @param integer $uid User ID
	 * @return string user token
	 */
	private function refreshUserToken(int $uid): string
	{
		$token = $this->pConfig->get($uid, 'bluesky', 'refresh_token');

		$data = $this->post($uid, '/xrpc/com.atproto.server.refreshSession', '', ['Authorization' => ['Bearer ' . $token]]);
		if (empty($data) || empty($data->accessJwt)) {
			$this->logger->debug('Refresh failed', ['return' => $data]);
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_TOKEN_FAIL);
			return '';
		}

		$this->logger->debug('Refreshed token', ['return' => $data]);
		$this->pConfig->set($uid, 'bluesky', 'access_token', $data->accessJwt);
		$this->pConfig->set($uid, 'bluesky', 'refresh_token', $data->refreshJwt);
		$this->pConfig->set($uid, 'bluesky', 'token_created', time());
		return $data->accessJwt;
	}

	/**
	 * Create a user token for the given user
	 *
	 * @param integer $uid      User ID
	 * @param string  $password Application password
	 * @return string user token
	 */
	public function createUserToken(int $uid, string $password): string
	{
		$did = $this->getUserDid($uid);
		if (empty($did)) {
			return '';
		}

		$data = $this->post($uid, '/xrpc/com.atproto.server.createSession', json_encode(['identifier' => $did, 'password' => $password]), ['Content-type' => 'application/json']);
		if (empty($data) || empty($data->accessJwt)) {
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_TOKEN_FAIL);
			return '';
		}

		$this->logger->debug('Created token', ['return' => $data]);
		$this->pConfig->set($uid, 'bluesky', 'access_token', $data->accessJwt);
		$this->pConfig->set($uid, 'bluesky', 'refresh_token', $data->refreshJwt);
		$this->pConfig->set($uid, 'bluesky', 'token_created', time());
		$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_TOKEN_OK);
		$this->pConfig->set($uid, 'bluesky', 'status-message', '');
		return $data->accessJwt;
	}
}
