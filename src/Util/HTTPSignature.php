<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Protocol\ActivityPub\Receiver;

/**
 * Implements HTTP Signatures per draft-cavage-http-signatures-07.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Web/HTTPSig.php
 *
 * Other parts of the code for HTTP signing are taken from the Osada project.
 * https://framagit.org/macgirvin/osada
 *
 * @see https://tools.ietf.org/html/draft-cavage-http-signatures-07
 */

class HTTPSignature
{
	// See draft-cavage-http-signatures-08
	/**
	 * Verifies a magic request
	 *
	 * @param $key
	 *
	 * @return array with verification data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function verifyMagic(string $key): array
	{
		$headers   = null;
		$spoofable = false;
		$result    = [
			'signer'        => '',
			'header_signed' => false,
			'header_valid'  => false
		];

		// Decide if $data arrived via controller submission or curl.
		$headers = [];

		$headers['(request-target)'] = strtolower(DI::args()->getMethod()) . ' ' . $_SERVER['REQUEST_URI'];

		foreach ($_SERVER as $k => $v) {
			if (strpos($k, 'HTTP_') === 0) {
				$field = str_replace('_', '-', strtolower(substr($k, 5)));

				$headers[$field] = $v;
			}
		}

		$sig_block = null;

		$sig_block = self::parseSigheader($headers['authorization']);

		if (!$sig_block) {
			DI::logger()->notice('no signature provided.');
			return $result;
		}

		$result['header_signed'] = true;

		$signed_headers = $sig_block['headers'];
		if (!$signed_headers) {
			$signed_headers = ['date'];
		}

		$signed_data = '';
		foreach ($signed_headers as $h) {
			if (array_key_exists($h, $headers)) {
				$signed_data .= $h . ': ' . $headers[$h] . "\n";
			}
			if (strpos($h, '.')) {
				$spoofable = true;
			}
		}

		$signed_data = rtrim($signed_data, "\n");

		$algorithm = 'sha512';

		if ($key && function_exists($key)) {
			$result['signer'] = $sig_block['keyId'];

			$key = $key($sig_block['keyId']);
		}

		DI::logger()->info('Got keyID ' . $sig_block['keyId']);

		if (!$key) {
			return $result;
		}

		$x = Crypto::rsaVerify($signed_data, $sig_block['signature'], $key, $algorithm);

		DI::logger()->info('verified: ' . $x);

		if (!$x) {
			return $result;
		}

		if (!$spoofable) {
			$result['header_valid'] = true;
		}

		return $result;
	}

	/**
	 * @param array   $head
	 * @param string  $prvkey
	 * @param string  $keyid (optional, default 'Key')
	 *
	 * @return array
	 */
	public static function createSig(array $head, string $prvkey, string $keyid = 'Key'): array
	{
		$return_headers = [];
		if (!empty($head)) {
			$return_headers = $head;
		}

		$alg       = 'sha512';
		$algorithm = 'rsa-sha512';

		$x = self::sign($head, $prvkey, $alg);

		$headerval = 'keyId="' . $keyid . '",algorithm="' . $algorithm
			. '",headers="' . $x['headers'] . '",signature="' . $x['signature'] . '"';

		$return_headers['Authorization'] = ['Signature ' . $headerval];

		return $return_headers;
	}

	/**
	 * @param array  $head
	 * @param string $prvkey
	 * @param string $alg (optional) default 'sha256'
	 *
	 * @return array
	 */
	private static function sign(array $head, string $prvkey, string $alg = 'sha256'): array
	{
		$ret     = [];
		$headers = '';
		$fields  = '';

		foreach ($head as $k => $v) {
			if (is_array($v)) {
				$v = implode(', ', $v);
			}
			$headers .= strtolower($k) . ': ' . trim($v) . "\n";
			if ($fields) {
				$fields .= ' ';
			}
			$fields .= strtolower($k);
		}
		// strip the trailing linefeed
		$headers = rtrim($headers, "\n");

		$sig = base64_encode(Crypto::rsaSign($headers, $prvkey, $alg));

		$ret['headers']   = $fields;
		$ret['signature'] = $sig;

		return $ret;
	}

	/**
	 * @param string $header
	 * @return array associative array with
	 *   - \e string \b keyID
	 *   - \e string \b created
	 *   - \e string \b expires
	 *   - \e string \b algorithm
	 *   - \e array  \b headers
	 *   - \e string \b signature
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function parseSigheader(string $header): array
	{
		// Remove obsolete folds
		$header = preg_replace('/\n\s+/', ' ', $header);

		$token = "[!#$%&'*+.^_`|~0-9A-Za-z-]";

		$quotedString = '"(?:\\\\.|[^"\\\\])*"';

		$regex = "/($token+)=($quotedString|$token+)/ism";

		$matches = [];
		preg_match_all($regex, $header, $matches, PREG_SET_ORDER);

		$headers = [];
		foreach ($matches as $match) {
			$headers[$match[1]] = trim($match[2] ?: $match[3], '"');
		}

		// if the header is encrypted, decrypt with (default) site private key and continue
		if (!empty($headers['iv'])) {
			$header = self::decryptSigheader($headers, DI::config()->get('system', 'prvkey'));
			return self::parseSigheader($header);
		}

		$return = [
			'keyId'     => $headers['keyId']     ?? '',
			'algorithm' => $headers['algorithm'] ?? 'rsa-sha256',
			'created'   => $headers['created']   ?? null,
			'expires'   => $headers['expires']   ?? null,
			'headers'   => explode(' ', $headers['headers'] ?? ''),
			'signature' => base64_decode(preg_replace('/\s+/', '', $headers['signature'] ?? '')),
		];

		if (!empty($return['signature']) && !empty($return['algorithm']) && empty($return['headers'])) {
			$return['headers'] = ['date'];
		}

		return $return;
	}

	/**
	 * @param array  $headers Signature headers
	 * @param string $prvkey  The site private key
	 * @return string Decrypted signature string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function decryptSigheader(array $headers, string $prvkey): string
	{
		if (!empty($headers['iv']) && !empty($headers['key']) && !empty($headers['data'])) {
			return Crypto::unencapsulate($headers, $prvkey);
		}

		return '';
	}

	/*
	 * Functions for ActivityPub
	 */

	/**
	 * Post given data to a target for a user, returns the result class
	 *
	 * @param array  $data   Data that is about to be sent
	 * @param string $target The URL of the inbox
	 * @param array  $owner  Sender owner-view record
	 *
	 * @return ICanHandleHttpResponses
	 */
	public static function post(array $data, string $target, array $owner): ICanHandleHttpResponses
	{
		$content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		// Header data that is about to be signed.
		$host           = strtolower(parse_url($target, PHP_URL_HOST));
		$path           = parse_url($target, PHP_URL_PATH);
		$digest         = 'SHA-256=' . base64_encode(hash('sha256', $content, true));
		$content_length = strlen($content);
		$date           = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

		$headers = [
			'Date'           => $date,
			'Content-Length' => $content_length,
			'Digest'         => $digest,
			'Host'           => $host
		];

		$signed_data = "(request-target): post " . $path . "\ndate: " . $date . "\ncontent-length: " . $content_length . "\ndigest: " . $digest . "\nhost: " . $host;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		$headers['Signature'] = 'keyId="' . $owner['url'] . '#main-key' . '",algorithm="rsa-sha256",headers="(request-target) date content-length digest host",signature="' . $signature . '"';

		$headers['Content-Type'] = 'application/activity+json';

		$postResult  = DI::httpClient()->post($target, $content, $headers, DI::config()->get('system', 'curl_timeout'), HttpClientRequest::ACTIVITYPUB);
		$return_code = $postResult->getReturnCode();

		DI::logger()->info('Transmit to ' . $target . ' returned ' . $return_code);

		self::setInboxStatus($target, ($return_code >= 200) && ($return_code <= 299));

		if (($return_code >= 200) && ($return_code <= 299)) {
			Item::incrementOutbound(Protocol::ACTIVITYPUB);
		}

		return $postResult;
	}

	/**
	 * Route activities locally
	 *
	 * @param array  $data
	 * @param string $target
	 * @param array  $owner
	 * @return boolean
	 */
	private static function routeLocal(array $data, string $target, array $owner): bool
	{
		$uid = self::getUserIdForInbox($target);
		if (is_null($uid)) {
			return false;
		}

		$activity     = JsonLD::compact($data);
		$type         = JsonLD::fetchElement($activity, '@type');
		$trust_source = true;
		$object_data  = Receiver::prepareObjectData($activity, $uid, true, $trust_source, $owner['url']);
		if (empty($object_data)) {
			return false;
		}

		DI::logger()->debug('Process directly', ['uid' => $uid, 'target' => $target, 'type' => $type]);
		return Receiver::routeActivities($object_data, $type, true, true, $uid);
	}

	/**
	 * Fetch the user id for a given inbox
	 *
	 * @param string $inbox
	 * @return integer|null
	 */
	private static function getUserIdForInbox(string $inbox): ?int
	{
		$gsid = GServer::getID(DI::baseUrl());
		if (!$gsid) {
			return null;
		}
		if (DBA::exists('apcontact', ['gsid' => $gsid, 'sharedinbox' => $inbox])) {
			return 0;
		}
		$apcontact = DBA::selectFirst('apcontact', ['url'], ['gsid' => $gsid, 'inbox' => $inbox]);
		if (empty($apcontact['url'])) {
			return null;
		}
		return User::getIdForURL($apcontact['url']);
	}

	/**
	 * Transmit given data to a target for a user
	 *
	 * @param array  $data   Data that is about to be sent
	 * @param string $target The URL of the inbox
	 * @param array  $owner  Sender owner-vew record
	 *
	 * @return boolean Was the transmission successful?
	 */
	public static function transmit(array $data, string $target, array $owner): bool
	{
		if (DI::baseUrl()->isLocalUrl($target) && self::routeLocal($data, $target, $owner)) {
			return true;
		}

		try {
			$postResult = self::post($data, $target, $owner);
		} catch (\Throwable $th) {
			DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
			return false;
		}
		$return_code = $postResult->getReturnCode();

		return ($return_code >= 200) && ($return_code <= 299);
	}

	/**
	 * Set the delivery status for a given inbox
	 *
	 * @param string  $url     The URL of the inbox
	 * @param boolean $success Transmission status
	 * @param boolean $shared  The inbox is a shared inbox
	 * @param int     $gsid    Server ID
	 * @throws \Exception
	 */
	public static function setInboxStatus(string $url, bool $success, bool $shared = false, int $gsid = null)
	{
		$now = DateTimeFormat::utcNow();

		$status = DBA::selectFirst('inbox-status', [], ['url' => $url]);
		if (!DBA::isResult($status)) {
			$insertFields = ['url' => $url, 'uri-id' => ItemURI::getIdByURI($url), 'created' => $now, 'shared' => $shared];
			if (!empty($gsid)) {
				$insertFields['gsid'] = $gsid;
			}
			DBA::insert('inbox-status', $insertFields, Database::INSERT_IGNORE);

			$status = DBA::selectFirst('inbox-status', [], ['url' => $url]);
			if (empty($status)) {
				DI::logger()->warning('Unable to insert inbox-status row', $insertFields);
				return;
			}
		}

		if ($success) {
			$fields = ['success' => $now];
		} else {
			$fields = ['failure' => $now];
		}

		if (!empty($gsid)) {
			$fields['gsid'] = $gsid;
		}

		if ($status['failure'] > DBA::NULL_DATETIME) {
			$new_previous_stamp = strtotime($status['failure']);
			$old_previous_stamp = strtotime($status['previous']);

			// Only set "previous" with at least one day difference.
			// We use this to assure to not accidentally archive too soon.
			if (($new_previous_stamp - $old_previous_stamp) >= 86400) {
				$fields['previous'] = $status['failure'];
			}
		}

		if (!$success) {
			if ($status['success'] <= DBA::NULL_DATETIME) {
				$stamp1 = strtotime($status['created']);
			} else {
				$stamp1 = strtotime($status['success']);
			}

			$stamp2         = strtotime($now);
			$previous_stamp = strtotime($status['previous']);

			// Archive the inbox when there had been failures for five days.
			// Additionally ensure that at least one previous attempt has to be in between.
			if ((($stamp2 - $stamp1) >= 86400 * 5) && ($previous_stamp > $stamp1)) {
				$fields['archive'] = true;
			}
		} else {
			$fields['archive'] = false;
		}

		if (empty($status['uri-id'])) {
			$fields['uri-id'] = ItemURI::getIdByURI($url);
		}

		DBA::update('inbox-status', $fields, ['url' => $url]);

		if (!empty($status['gsid'])) {
			if ($success) {
				GServer::setReachableById($status['gsid'], Protocol::ACTIVITYPUB);
			} elseif ($status['shared']) {
				GServer::setFailureById($status['gsid']);
			}
		}
	}

	/**
	 * Fetches JSON data for a user
	 *
	 * @param string  $request request url
	 * @param integer $uid     User id of the requester
	 *
	 * @return array JSON array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetch(string $request, int $uid = 0): array
	{
		try {
			$curlResult = self::fetchRaw($request, $uid);
		} catch (\Exception $exception) {
			DI::logger()->notice('Error fetching url', ['url' => $request, 'exception' => $exception]);
			return [];
		}

		if (!$curlResult->isSuccess() || empty($curlResult->getBodyString())) {
			DI::logger()->debug('Fetching was unsuccessful', ['url' => $request, 'return-code' => $curlResult->getReturnCode(), 'error-number' => $curlResult->getErrorNumber(), 'error' => $curlResult->getError()]);
			return [];
		}

		$content = json_decode($curlResult->getBodyString(), true);
		if (empty($content) || !is_array($content)) {
			return [];
		}

		if (!self::isValidContentType($curlResult->getContentType(), $request)) {
			return [];
		}

		return $content;
	}

	/**
	 * Check if the provided content type is a valid LD JSON mime type
	 *
	 * @param string $contentType
	 * @return boolean
	 */
	public static function isValidContentType(string $contentType, string $url = ''): bool
	{
		if (in_array(current(explode(';', $contentType)), ['application/activity+json', 'application/ld+json'])) {
			return true;
		}

		if (current(explode(';', $contentType)) == 'application/json') {
			DI::logger()->notice('Unexpected content type, possibly from a remote system that is not standard compliant.', ['content-type' => $contentType, 'url' => $url]);
		}
		return false;
	}

	/**
	 * Fetches raw data for a user
	 *
	 * @param string  $request request url
	 * @param integer $uid     User id of the requester
	 * @param array   $opts    (optional parameters) associative array with:
	 *                         'accept_content' => supply Accept: header with 'accept_content' as the value
	 *                         'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                         'nobody' => only return the header
	 *                         'cookiejar' => path to cookie jar file
	 *
	 * @return \Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses CurlResult
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchRaw(string $request, int $uid = 0, array $opts = [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::JSON_AS]])
	{
		$header = [];

		if (!empty($uid)) {
			$owner = User::getOwnerDataById($uid);
			if (!$owner) {
				return;
			}
		} else {
			$owner = User::getSystemAccount();
			if (!$owner) {
				return;
			}
		}

		if (!empty($owner['uprvkey'])) {
			// Header data that is about to be signed.
			$host = strtolower(parse_url($request, PHP_URL_HOST));
			$path = parse_url($request, PHP_URL_PATH);
			$date = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

			$header['Date'] = $date;
			$header['Host'] = $host;

			$signed_data = "(request-target): get " . $path . "\ndate: " . $date . "\nhost: " . $host;

			$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

			$header['Signature'] = 'keyId="' . $owner['url'] . '#main-key' . '",algorithm="rsa-sha256",headers="(request-target) date host",signature="' . $signature . '"';
		}

		$curl_opts                             = $opts;
		$curl_opts[HttpClientOptions::HEADERS] = $header;
		$curl_opts[HttpClientOptions::REQUEST] = HttpClientRequest::ACTIVITYPUB;

		if (!empty($opts['nobody'])) {
			$curlResult = DI::httpClient()->head($request, $curl_opts);
		} else {
			$curlResult = DI::httpClient()->get($request, HttpClientAccept::JSON_AS, $curl_opts);
		}
		$return_code = $curlResult->getReturnCode();

		DI::logger()->info('Fetched for user ' . $uid . ' from ' . $request . ' returned ' . $return_code);

		return $curlResult;
	}

	/**
	 * Fetch the apcontact entry of the keyId in the given header
	 *
	 * @param array $http_headers
	 *
	 * @return array APContact entry
	 */
	public static function getKeyIdContact(array $http_headers): array
	{
		if (empty($http_headers['HTTP_SIGNATURE'])) {
			DI::logger()->debug('No HTTP_SIGNATURE header', ['header' => $http_headers]);
			return [];
		}

		$sig_block = self::parseSigHeader($http_headers['HTTP_SIGNATURE']);

		if (empty($sig_block['keyId'])) {
			DI::logger()->debug('No keyId', ['sig_block' => $sig_block]);
			return [];
		}

		$url = (strpos($sig_block['keyId'], '#') ? substr($sig_block['keyId'], 0, strpos($sig_block['keyId'], '#')) : $sig_block['keyId']);
		return APContact::getByURL($url);
	}

	/**
	 * Gets a signer from a given HTTP request
	 *
	 * @param string   $content      Body of the request
	 * @param array    $http_headers array containing the HTTP headers
	 * @param ?boolean $update true = always update, false = never update, null = update when not found or outdated
	 *
	 * @return string|null|false Signer
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getSigner(string $content, array $http_headers, ?bool $update = null)
	{
		if (empty($http_headers['HTTP_SIGNATURE'])) {
			DI::logger()->debug('No HTTP_SIGNATURE header');
			return false;
		}

		if (!empty($content)) {
			$object = json_decode($content, true);
			if (empty($object)) {
				DI::logger()->info('No object');
				return false;
			}

			$actor = JsonLD::fetchElement($object, 'actor', 'id') ?? '';
		} else {
			$actor = '';
		}

		$headers = [];

		$headers['(request-target)'] = strtolower(DI::args()->getMethod()) . ' ' . parse_url($http_headers['REQUEST_URI'], PHP_URL_PATH);

		// First take every header
		foreach ($http_headers as $k => $v) {
			$field = str_replace('_', '-', strtolower($k));

			$headers[$field] = $v;
		}

		// Now add every http header
		foreach ($http_headers as $k => $v) {
			if (strpos($k, 'HTTP_') === 0) {
				$field = str_replace('_', '-', strtolower(substr($k, 5)));

				$headers[$field] = $v;
			}
		}

		$sig_block = self::parseSigHeader($http_headers['HTTP_SIGNATURE']);

		// Add fields from the signature block to the header. See issue 8845
		if (!empty($sig_block['created']) && empty($headers['(created)'])) {
			$headers['(created)'] = $sig_block['created'];
		}

		if (!empty($sig_block['expires']) && empty($headers['(expires)'])) {
			$headers['(expires)'] = $sig_block['expires'];
		}

		if (empty($sig_block) || empty($sig_block['headers']) || empty($sig_block['keyId'])) {
			DI::logger()->info('No headers or keyId');
			return false;
		}

		$signed_data = '';
		foreach ($sig_block['headers'] as $h) {
			if (array_key_exists($h, $headers)) {
				$signed_data .= $h . ': ' . $headers[$h] . "\n";
			} else {
				DI::logger()->info('Requested header field not found', ['field' => $h, 'header' => $headers]);
			}
		}
		$signed_data = rtrim($signed_data, "\n");

		if (empty($signed_data)) {
			DI::logger()->info('Signed data is empty');
			return false;
		}

		$algorithm = null;

		// Wildcard value where signing algorithm should be derived from keyId
		// @see https://tools.ietf.org/html/draft-ietf-httpbis-message-signatures-00#section-4.1
		// Defaulting to SHA256 as it seems to be the prevalent implementation
		// @see https://arewehs2019yet.vpzom.click
		if ($sig_block['algorithm'] === 'hs2019') {
			$algorithm = 'sha256';
		}

		if ($sig_block['algorithm'] === 'rsa-sha256') {
			$algorithm = 'sha256';
		}

		if ($sig_block['algorithm'] === 'rsa-sha512') {
			$algorithm = 'sha512';
		}

		if (empty($algorithm)) {
			DI::logger()->info('No algorithm');
			return false;
		}

		$key = self::fetchKey($sig_block['keyId'], $actor, $update);
		if (empty($key)) {
			DI::logger()->info('Empty key');
			return false;
		}

		if (!empty($key['url']) && !empty($key['type']) && ($key['type'] == 'Tombstone')) {
			DI::logger()->info('Actor is a tombstone', ['key' => $key]);

			if (!Contact::isLocal($key['url'])) {
				// We now delete everything that we possibly knew from this actor
				Contact::deleteContactByUrl($key['url']);
			}
			return null;
		}

		if (empty($key['pubkey'])) {
			DI::logger()->info('Empty pubkey');
			return false;
		}

		if (!Crypto::rsaVerify($signed_data, $sig_block['signature'], $key['pubkey'], $algorithm)) {
			DI::logger()->info('Verification failed', ['signed_data' => $signed_data, 'algorithm' => $algorithm, 'header' => $sig_block['headers'], 'http_headers' => $http_headers]);
			return false;
		}

		$hasGoodSignedContent = false;

		// Check the digest when it is part of the signed data
		if (!empty($content) && in_array('digest', $sig_block['headers'])) {
			$digest = explode('=', $headers['digest'], 2);
			if ($digest[0] === 'SHA-256') {
				$hashalg = 'sha256';
			}
			if ($digest[0] === 'SHA-512') {
				$hashalg = 'sha512';
			}

			/// @todo add all hashes from the rfc

			if (!empty($hashalg) && base64_encode(hash($hashalg, $content, true)) != $digest[1]) {
				DI::logger()->info('Digest does not match');
				return false;
			}

			$hasGoodSignedContent = true;
		}

		if (in_array('date', $sig_block['headers']) && !empty($headers['date'])) {
			$created = strtotime($headers['date']);
		} elseif (in_array('(created)', $sig_block['headers']) && !empty($sig_block['created'])) {
			$created = $sig_block['created'];
		} else {
			$created = 0;
		}

		if (in_array('(expires)', $sig_block['headers']) && !empty($sig_block['expires'])) {
			$expired = min($sig_block['expires'], $created + 3600);
		} else {
			$expired = $created + 3600;
		}

		//  Check if the signed date field is in an acceptable range
		if (!empty($created)) {
			$current = time();

			// Calculate with a grace period of 60 seconds to avoid slight time differences between the servers
			if (($created - 60) > $current) {
				DI::logger()->notice('Signature created in the future', ['created' => date(DateTimeFormat::MYSQL, $created), 'expired' => date(DateTimeFormat::MYSQL, $expired), 'current' => date(DateTimeFormat::MYSQL, $current)]);
				return false;
			}

			if ($current > $expired) {
				DI::logger()->notice('Signature expired', ['created' => date(DateTimeFormat::MYSQL, $created), 'expired' => date(DateTimeFormat::MYSQL, $expired), 'current' => date(DateTimeFormat::MYSQL, $current)]);
				return false;
			}

			DI::logger()->debug('Valid creation date', ['created' => date(DateTimeFormat::MYSQL, $created), 'expired' => date(DateTimeFormat::MYSQL, $expired), 'current' => date(DateTimeFormat::MYSQL, $current)]);
			$hasGoodSignedContent = true;
		}

		// Check the content-length when it is part of the signed data
		if (in_array('content-length', $sig_block['headers'])) {
			if (strlen($content) != $headers['content-length']) {
				DI::logger()->info('Content length does not match');
				return false;
			}
		}

		// Ensure that the authentication had been done with some content
		// Without this check someone could authenticate with fakeable data
		if (!$hasGoodSignedContent) {
			DI::logger()->info('No good signed content');
			return false;
		}

		return $key['url'];
	}

	/**
	 * fetches a key for a given id and actor
	 *
	 * @param string   $id    keyId of the signature block
	 * @param string   $actor Actor URI
	 * @param ?boolean $update true = always update, false = never update, null = update when not found or outdated
	 *
	 * @return array with actor url and public key
	 * @throws \Exception
	 */
	private static function fetchKey(string $id, string $actor, ?bool $update = null): array
	{
		$url = (strpos($id, '#') ? substr($id, 0, strpos($id, '#')) : $id);

		$profile = APContact::getByURL($url, $update);
		if (!empty($profile)) {
			DI::logger()->info('Taking key from id', ['id' => $id]);
			return ['url' => $url, 'pubkey' => $profile['pubkey'], 'type' => $profile['type']];
		} elseif ($url != $actor) {
			$profile = APContact::getByURL($actor);
			if (!empty($profile)) {
				DI::logger()->info('Taking key from actor', ['actor' => $actor]);
				return ['url' => $actor, 'pubkey' => $profile['pubkey'], 'type' => $profile['type']];
			}
		}

		DI::logger()->notice('Key could not be fetched', ['url' => $url, 'actor' => $actor]);
		return [];
	}
}
