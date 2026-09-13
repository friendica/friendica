<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\ParseUrl;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Base class for the ATProtocol
 * @see https://atproto.com/
 */
final class ATProtocol
{
	public const STATUS_UNKNOWN    = 0;
	public const STATUS_TOKEN_OK   = 1;
	public const STATUS_SUCCESS    = 2;
	public const STATUS_API_FAIL   = 10;
	public const STATUS_DID_FAIL   = 11;
	public const STATUS_PDS_FAIL   = 12;
	public const STATUS_TOKEN_FAIL = 13;

	private ?int $uid = null;

	/**
	 * Initialize the AT Protocol service.
	 *
	 * @param LoggerInterface $logger
	 * @param Database $db
	 * @param IManageConfigValues $config
	 * @param IManagePersonalConfigValues $pConfig
	 * @param ICanSendHttpRequests $httpClient
	 */
	public function __construct(private readonly LoggerInterface $logger, private readonly Database $db, private readonly IManageConfigValues $config, private readonly IManagePersonalConfigValues $pConfig, private readonly ICanSendHttpRequests $httpClient) {}

	/**
	 * Get the AppView API URL
	 *
	 * @return string
	 */
	public function getApi(): string
	{
		$uid = $this->getUser();
		if ($uid !== 0) {
			$api = $this->pConfig->get($uid, 'bluesky', 'pds');
			if ($api) {
				return $api;
			}

			$this->logger->warning('PDS for user could not be fetched', ['uid' => $uid]);
		}

		return $this->getUserPds(0);
	}

	/**
	 * Get user IDs that import the AT Protocol timeline
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
	 * Fetch XRPC data
	 * @see https://atproto.com/specs/xrpc#lexicon-http-endpoints
	 *
	 * @param string  $url        for example "app.bsky.feed.getTimeline"
	 * @param array   $parameters Array with parameters
	 * @param integer $uid        User ID. When set to null, the value from "setApiForUser" will be used
	 * @return stdClass|null Fetched data
	 */
	public function XRPCGet(string $url, array $parameters = [], ?int $uid = null): ?stdClass
	{
		if (!empty($parameters)) {
			$url .= '?' . http_build_query($parameters);
		}

		if (is_null($uid)) {
			$uid = $this->getUser();
		}

		$pds = $this->getUserPds($uid);
		if (empty($pds)) {
			return null;
		}

		if ($uid === 0) {
			return $this->get($pds . '/xrpc/' . $url);
		}

		$headers = ['Authorization' => ['Bearer ' . $this->getUserToken($uid)]];

		$languages = User::getWantedLanguages($uid);
		if (!empty($languages)) {
			$headers['Accept-Language'] = implode(',', $languages);
		}

		$data = $this->get($pds . '/xrpc/' . $url, [HttpClientOptions::HEADERS => $headers]);

		if ($data === null) {
			$this->pConfig->set($uid, 'bluesky', 'status', self::STATUS_API_FAIL);
			$this->pConfig->set($uid, 'bluesky', 'status-message', 'Unknown error occured while fetching data from Bluesky');
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
		if (!$data || !is_object($data)) {
			$this->logger->notice('Invalid data returned', ['url' => $url, 'code' => $curlResult->getReturnCode()]);
			return null;
		}

		if (!$curlResult->isSuccess()) {
			$this->logger->notice('API Error', ['url' => $url, 'code' => $curlResult->getReturnCode(), 'error' => $data]);
			$data->code = $curlResult->getReturnCode();
		} elseif (($curlResult->getReturnCode() < 200) || ($curlResult->getReturnCode() >= 400)) {
			$this->logger->notice('Unexpected return code', ['url' => $url, 'code' => $curlResult->getReturnCode(), 'error' => $data]);
			$data->code = $curlResult->getReturnCode();
		}

		Item::incrementInbound(Protocol::ATPROTO);
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
				if (!empty($curlResult->getBodyString())) {
					$this->pConfig->set($uid, 'bluesky', 'status-message', $curlResult->getBodyString());
				} elseif (!empty($curlResult->getReturnCode())) {
					$this->pConfig->set($uid, 'bluesky', 'status-message', 'Error Code: ' . $curlResult->getReturnCode());
				} else {
					$this->pConfig->set($uid, 'bluesky', 'status-message', 'Unknown error occured while posting to Bluesky');
				}

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
			} else {
				$this->pConfig->set($uid, 'bluesky', 'status-message', 'Unknown error occured while posting to Bluesky');
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
			return $this->config->get('atprotocol', 'appview_api');
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
		$handle = trim($handle, '@');
		if ($handle == '') {
			return '';
		}

		if (str_contains($handle, '@') || str_contains($handle, '/') || !str_contains($handle, '.')) {
			return '';
		}

		// At first we use the AppView API which *should* cover all cases.
		$data = $this->get($this->getApi() . '/xrpc/com.atproto.identity.resolveHandle?handle=' . urlencode($handle));
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
		$data = ParseUrl::getSiteinfoCached($url);
		return $data['atprotocol']['did'] ?? '';
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
		if ($curlResult->isSuccess() && str_starts_with($curlResult->getBodyString(), 'did:')) {
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
	 * Fetches the DID of a given handle via a DNS request.
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
			if (!empty($record['txt']) && str_starts_with((string) $record['txt'], 'did=')) {
				$did = substr((string) $record['txt'], 4);
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
	 * Fetch the PDS URL for a given DID
	 *
	 * @param string $did DID (did:plc:...)
	 * @return string|null URL of the PDS, e.g. https://enoki.us-east.host.bsky.network
	 */
	public function getPdsOfDid(string $did): ?string
	{
		$data = $this->get($this->getPLCDirectory() . '/' . $did);
		if (empty($data) || empty($data->service)) {
			return null;
		}

		foreach ($data->service as $service) {
			if (($service->id == '#atproto_pds') && ($service->type == 'AtprotoPersonalDataServer') && !empty($service->serviceEndpoint)) {
				if (!$this->isValidPdsEndpoint($service->serviceEndpoint)) {
					$this->logger->notice('Invalid PDS endpoint', ['did' => $did, 'endpoint' => $service->serviceEndpoint]);
					return null;
				}
				return $service->serviceEndpoint;
			}
		}

		return null;
	}

	/**
	 * Checks whether a PDS endpoint taken from a DID document is safe to send requests to.
	 *
	 * The endpoint is stored per user and gets the bearer token attached on every API call.
	 * It therefore has to be a plain https origin.
	 * A query or fragment would swallow the '/xrpc/...' path that is appended to it.
	 *
	 * @param mixed $endpoint The serviceEndpoint value of the DID document
	 * @return bool
	 */
	private function isValidPdsEndpoint($endpoint): bool
	{
		if (!is_string($endpoint)) {
			return false;
		}

		$parts = parse_url($endpoint);
		if (!is_array($parts)) {
			return false;
		}

		return (($parts['scheme'] ?? '') === 'https')
			&& !empty($parts['host'])
			&& empty($parts['user'])
			&& empty($parts['pass'])
			&& empty($parts['query'])
			&& empty($parts['fragment']);
	}

	/**
	 * Set the AppView API for this class for a given user ID
	 *
	 * @param integer $uid
	 * @return void
	 */
	public function setApiForUser(int $uid)
	{
		$this->uid = $uid;

		if (!$this->getUserPds($uid)) {
			$this->uid = 0;
		}
	}

	/**
	 * Get the user ID for which the API URL is set
	 *
	 * @return integer
	 */
	public function getUser(): int
	{
		if (is_null($this->uid)) {
			$this->logger->notice('API user not set.');
			$this->uid = 0;
		}

		return $this->uid;
	}

	/**
	 * Get the user ID for the selected protocol
	 *
	 * @param integer $protocol
	 * @return integer|null
	 */
	public function getUserForProtocol(int $protocol): ?int
	{
		return match ($protocol) {
			Conversation::PARCEL_JETSTREAM => 0,
			Conversation::PARCEL_CONNECTOR => $this->getUser(),
			default                        => null,
		};
	}

	/**
	 * Get the DID PLC directory
	 *
	 * @return string
	 */
	public function getPLCDirectory(): string
	{
		return $this->config->get('atprotocol', 'plc_directory');
	}

	/**
	 * Get the Jetstream address
	 *
	 * @return string
	 */
	public function getJetstream(): string
	{
		return $this->config->get('atprotocol', 'jetstream');
	}

	/**
	 * Get the web address for a given user ID
	 *
	 * @param integer $uid
	 * @return string
	 */
	public function getWebForUser(int $uid): string
	{
		return $this->pConfig->get($uid, 'bluesky', 'web') ?? $this->config->get('atprotocol', 'web');
	}

	/**
	 * Checks if the provided DID matches the handle
	 *
	 * @param string $did DID (did:plc:...)
	 * @param string $handle The user handle
	 * @return boolean
	 */
	public function isValidDid(string $did, string $handle): bool
	{
		$data = $this->get($this->getPLCDirectory() . '/' . $did);
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

	/**
	 * Get the profile link for a given uri and user id
	 *
	 * @param string  $uri The post uri
	 * @param integer $uid User id to get the web for (0 for global)
	 * @return string Profile link
	 */
	public function getPostLink(string $uri, int $uid = 0): string
	{
		$web       = $this->getWebForUser($uid);
		$frontends = $this->config->get('atprotocol', 'frontends');
		if ($web && is_array($frontends) && isset($frontends[$web])) {
			$parts = $this->getUriObject($uri);
			if (is_object($parts)) {
				return str_replace(['{did}', '{collection}', '{rkey}'], [$parts->repo, $parts->collection, $parts->rkey], $frontends[$web][2]);
			}
		}
		return '';
	}

	/**
	 * Fetch a record from the AT Protocol repository
	 *
	 * @param string $uri AT URI in the format at://did/collection/rkey
	 * @return stdClass|null The fetched record or null on failure
	 */
	public function getRecord(string $uri): ?stdClass
	{
		$parts = $this->getUriObject($uri);
		if (!is_object($parts)) {
			return null;
		}

		$url = $this->getPdsOfDid($parts->repo);
		if (!$url) {
			return null;
		}

		$url .= '/xrpc/com.atproto.repo.getRecord?' . http_build_query(['repo' => $parts->repo, 'collection' => $parts->collection, 'rkey' => $parts->rkey]);
		return $this->get($url);
	}

	/**
	 * Create a new record in the AT Protocol repository
	 *
	 * @param integer        $uid        User ID
	 * @param string         $collection Collection name, e.g. "app.bsky.feed.post"
	 * @param array|stdClass $record     The record data to create
	 * @return stdClass|null The created record response or null on failure
	 */
	public function createRecord(int $uid, string $collection, $record): ?stdClass
	{
		$post = [
			'collection' => $collection,
			'repo'       => $this->getUserDid($uid),
			'record'     => $record,
		];

		return $this->XRPCPost($uid, 'com.atproto.repo.createRecord', $post);
	}

	/**
	 * Delete a record from the AT Protocol repository
	 *
	 * @param integer $uid User ID
	 * @param string  $uri AT URI in the format at://did/collection/rkey
	 * @return stdClass|null The deletion response or null on failure
	 */
	public function deleteRecord(int $uid, string $uri): ?stdClass
	{
		$parts = $this->getUriObject($uri);
		if (!is_object($parts)) {
			return null;
		}

		return $this->XRPCPost($uid, 'com.atproto.repo.deleteRecord', ['repo' => $parts->repo, 'collection' => $parts->collection, 'rkey' => $parts->rkey]);
	}

	/**
	 * Update an existing record in the AT Protocol repository
	 *
	 * @param integer        $uid    User ID
	 * @param string         $uri    AT URI in the format at://did/collection/rkey
	 * @param array|stdClass $record The updated record data
	 * @return stdClass|null The update response or null on failure
	 */
	public function putRecord(int $uid, string $uri, $record): ?stdClass
	{
		$parts = $this->getUriObject($uri);
		if (!is_object($parts)) {
			return null;
		}

		return $this->XRPCPost($uid, 'com.atproto.repo.putRecord', ['repo' => $parts->repo, 'collection' => $parts->collection, 'rkey' => $parts->rkey, 'record' => $record]);
	}

	/**
	 * Parse an AT URI into repository, collection and record key.
	 *
	 * @param string $uri
	 * @return stdClass|null
	 */
	public function getUriObject(string $uri): ?stdClass
	{
		$parts = explode('/', $uri);
		if (!$parts || count($parts) !== 5 || $parts[0] !== 'at:' || $parts[1] !== '') {
			return null;
		}

		$class = new stdClass();

		$class->repo       = $parts[2];
		$class->collection = $parts[3];
		$class->rkey       = explode(':', $parts[4])[0];

		return $class;
	}
}
