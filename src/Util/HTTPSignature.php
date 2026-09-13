<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Exception;
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
use gapple\StructuredFields\Bytes;
use gapple\StructuredFields\InnerList;
use gapple\StructuredFields\Item as StructuredFieldsItem;
use gapple\StructuredFields\ParseException;
use gapple\StructuredFields\Parser;
use gapple\StructuredFields\Serializer;
use phpseclib3\Crypt\Common\PublicKey as CryptPublicKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

/**
 * Implements HTTP Signatures per draft-cavage-http-signatures-07.
 *
 * Incoming requests that carry a "Signature-Input" header are verified against
 * RFC 9421 instead, which Mastodon and others are migrating to. Outgoing
 * requests are still signed the cavage way.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Web/HTTPSig.php
 *
 * Other parts of the code for HTTP signing are taken from the Osada project.
 * https://framagit.org/macgirvin/osada
 *
 * @see https://tools.ietf.org/html/draft-cavage-http-signatures-07
 * @see https://www.rfc-editor.org/rfc/rfc9421
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
			'header_valid'  => false,
		];

		// Decide if $data arrived via controller submission or curl.
		$headers = [];

		$headers['(request-target)'] = strtolower(DI::args()->getMethod()) . ' ' . $_SERVER['REQUEST_URI'];

		foreach ($_SERVER as $k => $v) {
			if (str_starts_with((string) $k, 'HTTP_')) {
				$field = str_replace('_', '-', strtolower(substr((string) $k, 5)));

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
			if (strpos((string) $h, '.')) {
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
			$headers .= strtolower((string) $k) . ': ' . trim((string) $v) . "\n";
			if ($fields) {
				$fields .= ' ';
			}
			$fields .= strtolower((string) $k);
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
		preg_match_all($regex, (string) $header, $matches, PREG_SET_ORDER);

		$headers = [];
		foreach ($matches as $match) {
			$headers[$match[1]] = trim((string) $match[2], '"');
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
			'signature' => base64_decode((string) preg_replace('/\s+/', '', $headers['signature'] ?? '')),
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
			return (string) Crypto::unencapsulate($headers, $prvkey);
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

		$host           = strtolower(parse_url($target, PHP_URL_HOST));
		$content_length = (string) strlen($content);
		$date           = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

		$headers                 = self::signCavagePost($content, $target, $owner);
		$headers['Content-Type'] = 'application/activity+json';

		$timeout     = DI::config()->get('system', 'curl_timeout');
		$postResult  = DI::httpClient()->post($target, $content, $headers, $timeout, HttpClientRequest::ACTIVITYPUB);
		$return_code = $postResult->getReturnCode();

		// Draft-cavage first, RFC 9421 as the fallback, as Mastodon 4.7 does it.
		if (!empty($owner['uprvkey']) && in_array($return_code, [400, 401])) {
			DI::logger()->info('Retrying the delivery with an RFC 9421 signature', ['target' => $target, 'return-code' => $return_code]);

			$headers = [
				'Date'           => $date,
				'Content-Length' => $content_length,
				'Host'           => $host,
				'Content-Type'   => 'application/activity+json',
			] + self::signRequestRfc9421('POST', $target, $content, $owner['uprvkey'], $owner['url'] . '#main-key');

			$postResult  = DI::httpClient()->post($target, $content, $headers, $timeout, HttpClientRequest::ACTIVITYPUB);
			$return_code = $postResult->getReturnCode();
		}

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
	public static function setInboxStatus(string $url, bool $success, bool $shared = false, ?int $gsid = null)
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
			$new_previous_stamp = strtotime((string) $status['failure']);
			$old_previous_stamp = strtotime((string) $status['previous']);

			// Only set "previous" with at least one day difference.
			// We use this to assure to not accidentally archive too soon.
			if (($new_previous_stamp - $old_previous_stamp) >= 86400) {
				$fields['previous'] = $status['failure'];
			}
		}

		if (!$success) {
			if ($status['success'] <= DBA::NULL_DATETIME) {
				$stamp1 = strtotime((string) $status['created']);
			} else {
				$stamp1 = strtotime((string) $status['success']);
			}

			$stamp2         = strtotime($now);
			$previous_stamp = strtotime((string) $status['previous']);

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
		} else {
			$owner = User::getSystemAccount();
		}

		if (!$owner) {
			throw new Exception('Could not find owner for uid ' . $uid);
		}

		if (!empty($owner['uprvkey'])) {
			$header = self::signGet($header, $request, $owner);
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

		// Older Friendica instances only verify the path of a signed GET request.
		// If our query-string aware signature is rejected, retry without it. See issue 14973.
		if (!empty($owner['uprvkey']) && empty($opts['nobody']) && in_array($return_code, [401, 403]) && (string) parse_url($request, PHP_URL_QUERY) !== '') {
			DI::logger()->info('Retrying with a path-only signature', ['url' => $request, 'return-code' => $return_code]);
			$curl_opts[HttpClientOptions::HEADERS] = self::signGet($header, $request, $owner, false);
			$curlResult                            = DI::httpClient()->get($request, HttpClientAccept::JSON_AS, $curl_opts);
			$return_code                           = $curlResult->getReturnCode();
		}

		// Draft-cavage first, RFC 9421 as the fallback, as Mastodon 4.7 does it.
		if (!empty($owner['uprvkey']) && empty($opts['nobody']) && in_array($return_code, [401, 403])) {
			DI::logger()->info('Retrying the request with an RFC 9421 signature', ['url' => $request, 'return-code' => $return_code]);
			$curl_opts[HttpClientOptions::HEADERS] = self::signGetRfc9421($header, $request, $owner);
			$curlResult                            = DI::httpClient()->get($request, HttpClientAccept::JSON_AS, $curl_opts);
			$return_code                           = $curlResult->getReturnCode();

			if (in_array($return_code, [401, 403])) {
				DI::logger()->info('Signature was rejected in every variant', ['url' => $request, 'return-code' => $return_code]);
			}
		}

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
		if (!empty($http_headers['HTTP_SIGNATURE_INPUT'])) {
			$keyId = self::rfc9421KeyId((string) $http_headers['HTTP_SIGNATURE_INPUT']);
		} elseif (!empty($http_headers['HTTP_SIGNATURE'])) {
			$keyId = self::parseSigheader($http_headers['HTTP_SIGNATURE'])['keyId'] ?? '';
		} else {
			DI::logger()->debug('No signature header', ['header' => $http_headers]);
			return [];
		}

		if (empty($keyId)) {
			DI::logger()->debug('No keyId', ['header' => $http_headers]);
			return [];
		}

		$url = (strpos((string) $keyId, '#') ? substr((string) $keyId, 0, strpos((string) $keyId, '#')) : $keyId);
		if (!Network::isValidHttpUrl($url)) {
			return [];
		}

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
		if (!empty($http_headers['HTTP_SIGNATURE_INPUT'])) {
			$signer = self::getSignerRfc9421($content, $http_headers, $update);
			if ($signer !== false) {
				return $signer;
			}
			// The RFC 9421 signature did not verify. Fall through to a draft-cavage
			// attempt on the "Signature" header, as the SWICG report recommends for
			// senders that are in the middle of the migration.
			if (!empty($http_headers['HTTP_SIGNATURE'])) {
				DI::logger()->info('Falling back from RFC 9421 to a draft-cavage signature');
			}
		}

		if (empty($http_headers['HTTP_SIGNATURE'])) {
			DI::logger()->debug('Request carries neither a draft-cavage nor an RFC 9421 signature');
			return false;
		}

		if (empty($http_headers['HTTP_SIGNATURE_INPUT'])) {
			DI::logger()->info('Verifying a draft-cavage signature');
		}

		$actor = self::signedActor($content);
		if ($actor === false) {
			return false;
		}

		$request_uri = (string) ($http_headers['REQUEST_URI'] ?? '');
		$path        = (string) parse_url($request_uri, PHP_URL_PATH);
		$query       = parse_url($request_uri, PHP_URL_QUERY);
		$target      = $path . (is_string($query) && $query !== '' ? '?' . $query : '');

		$headers = self::collectHeaders($http_headers);

		// draft-cavage requires the path and the query string, but Friendica used
		// to sign the path only. Verification falls back to that form below.
		$headers['(request-target)'] = strtolower(DI::args()->getMethod()) . ' ' . $target;

		$sig_block = self::parseSigheader($http_headers['HTTP_SIGNATURE']);

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

		$signed_data = self::buildSignedData($sig_block['headers'], $headers);

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

		$key = self::fetchSignerKey($sig_block['keyId'], $actor, $update);
		if (is_null($key)) {
			return null;
		}
		if (empty($key['pubkey'])) {
			DI::logger()->info('Empty pubkey');
			return false;
		}

		$legacy_target = false;
		if (!Crypto::rsaVerify($signed_data, $sig_block['signature'], $key['pubkey'], $algorithm)) {
			// Retry with the legacy path-only "(request-target)" for senders that
			// still sign it that way. See issue 14973.
			if (!empty($query) && in_array('(request-target)', $sig_block['headers'])) {
				$legacy_headers                     = $headers;
				$legacy_headers['(request-target)'] = strtolower(DI::args()->getMethod()) . ' ' . $path;
				$legacy_target                      = Crypto::rsaVerify(self::buildSignedData($sig_block['headers'], $legacy_headers), $sig_block['signature'], $key['pubkey'], $algorithm);
			}
			if (!$legacy_target) {
				DI::logger()->info('Draft-cavage signature could not be verified', ['signer' => $key['url'], 'algorithm' => $algorithm, 'signed-headers' => $sig_block['headers']]);
				return false;
			}
		}

		DI::logger()->info('Draft-cavage signature matches', ['signer' => $key['url'], 'algorithm' => $algorithm, 'legacy-request-target' => $legacy_target]);

		$hasGoodSignedContent = false;

		// Check the digest when it is part of the signed data
		if (!empty($content) && in_array('digest', $sig_block['headers'])) {
			$digest = explode('=', (string) $headers['digest'], 2);
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
			$created = strtotime((string) $headers['date']);
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

		DI::logger()->info('Draft-cavage signature is valid', ['signer' => $key['url']]);
		return $key['url'];
	}

	/**
	 * Verifies an incoming request that is signed per RFC 9421
	 *
	 * @param string   $content      Body of the request
	 * @param array    $http_headers Server variables of the request
	 * @param ?boolean $update       See getSigner()
	 *
	 * @return string|null|false Signer
	 */
	private static function getSignerRfc9421(string $content, array $http_headers, ?bool $update)
	{
		$actor = self::signedActor($content);
		if ($actor === false) {
			return false;
		}

		// Web Bot Auth (draft-meunier-web-bot-auth-architecture) reuses RFC 9421 for
		// crawlers. It is not an ActivityPub signer, so there is nothing to resolve.
		if (!empty($http_headers['HTTP_SIGNATURE_AGENT'])) {
			DI::logger()->debug('Request carries a Signature-Agent header, not an ActivityPub signature', ['signature-agent' => $http_headers['HTTP_SIGNATURE_AGENT']]);
			return false;
		}

		try {
			$input      = Parser::parseDictionary((string) $http_headers['HTTP_SIGNATURE_INPUT']);
			$signatures = Parser::parseDictionary((string) ($http_headers['HTTP_SIGNATURE'] ?? ''));
		} catch (ParseException $exception) {
			DI::logger()->info('Cannot parse the signature headers', ['message' => $exception->getMessage()]);
			return false;
		}

		DI::logger()->info('Verifying an RFC 9421 signature');

		$headers = self::collectHeaders($http_headers);

		$context = [
			'method'    => strtoupper((string) ($http_headers['REQUEST_METHOD'] ?? DI::args()->getMethod())),
			'scheme'    => DI::baseUrl()->getScheme(),
			'authority' => self::rfc9421Authority(),
			'target'    => (string) ($http_headers['REQUEST_URI'] ?? ''),
		];

		// The signature label is not fixed, so every entry is a verification candidate.
		foreach ($input as $label => $parameters) {
			if (!$parameters instanceof InnerList) {
				continue;
			}

			$signature = $signatures->{$label} ?? null;
			if (!$signature instanceof StructuredFieldsItem || !$signature->getValue() instanceof Bytes) {
				DI::logger()->info('No signature for the given label', ['label' => $label]);
				continue;
			}

			$components = [];
			foreach ($parameters->getValue() as $item) {
				$components[] = strtolower((string) $item->getValue());
			}

			$params  = $parameters->getParameters();
			$keyId   = (string) ($params->keyid ?? '');
			$created = (int) ($params->created ?? 0);
			$expires = (int) ($params->expires ?? 0);

			if (($params->tag ?? '') === 'web-bot-auth') {
				DI::logger()->debug('Web Bot Auth signature, not an ActivityPub signer', ['label' => $label]);
				continue;
			}

			if ($keyId === '' || $components === []) {
				DI::logger()->info('Missing keyId or components', ['label' => $label]);
				continue;
			}

			$algorithms = self::rfc9421Algorithms((string) ($params->alg ?? ''));
			if ($algorithms === []) {
				continue;
			}

			try {
				$signature_params = Serializer::serializeList([$parameters]);
			} catch (\Throwable $th) {
				DI::logger()->info('Cannot serialize the signature parameters', ['label' => $label, 'message' => $th->getMessage()]);
				continue;
			}

			$base = self::rfc9421SignatureBase($components, $signature_params, $context, $headers);
			if (is_null($base)) {
				DI::logger()->info('Cannot build the signature base', ['label' => $label, 'components' => $components]);
				continue;
			}

			$key = self::fetchSignerKey($keyId, $actor, $update);
			if (is_null($key)) {
				return null;
			}
			if (empty($key['pubkey'])) {
				DI::logger()->info('Empty pubkey', ['label' => $label]);
				continue;
			}

			// An RSA key can be used with either PKCS#1 v1.5 or PSS padding and the
			// "alg" parameter is often omitted, so more than one candidate is tried.
			$algorithm = '';
			foreach (self::keyAlgorithms($key['pubkey'], $algorithms) as $candidate) {
				if (self::verifySignature($base, (string) $signature->getValue(), $key['pubkey'], $candidate)) {
					$algorithm = $candidate;
					break;
				}
			}

			if ($algorithm === '') {
				DI::logger()->info('RFC 9421 signature could not be verified', ['label' => $label, 'signer' => $key['url'], 'algorithms' => $algorithms, 'components' => $components]);
				DI::logger()->debug('Rejected RFC 9421 signature base', ['label' => $label, 'base' => $base, 'signature-input' => $http_headers['HTTP_SIGNATURE_INPUT'] ?? '']);
				continue;
			}

			DI::logger()->info('RFC 9421 signature matches', ['label' => $label, 'signer' => $key['url'], 'algorithm' => $algorithm]);

			if (!self::rfc9421CheckContent($content, $components, $headers, $created, $expires)) {
				return false;
			}

			DI::logger()->info('RFC 9421 signature is valid', ['label' => $label, 'signer' => $key['url']]);
			return $key['url'];
		}

		DI::logger()->info('No valid RFC 9421 signature found');
		return false;
	}

	/**
	 * Builds the RFC 9421 signature base for the given covered components
	 *
	 * @param array  $components Lower-cased component identifiers
	 * @param string $params     Serialized signature parameters (the inner list of "Signature-Input")
	 * @param array  $context    'method', 'scheme', 'authority' and 'target' (path and query) of the request
	 * @param array  $headers    Request header fields, lower-cased
	 *
	 * @return string|null The signature base, or null when a covered component cannot be resolved
	 *                     (unsupported, response-only or missing from the request)
	 */
	public static function rfc9421SignatureBase(array $components, string $params, array $context, array $headers): ?string
	{
		$base = '';
		foreach ($components as $component) {
			$value = self::rfc9421ComponentValue($component, $context, $headers);
			if (is_null($value)) {
				return null;
			}
			$base .= '"' . $component . '": ' . $value . "\n";
		}

		return $base . '"@signature-params": ' . $params;
	}

	/**
	 * Resolves a single RFC 9421 component to its value in the signature base
	 *
	 * @return string|null null for components we do not support or that are absent
	 */
	private static function rfc9421ComponentValue(string $component, array $context, array $headers): ?string
	{
		switch ($component) {
			case '@method':
				return $context['method'];
			case '@authority':
				return $context['authority'];
			case '@scheme':
				return $context['scheme'];
			case '@target-uri':
				return $context['scheme'] . '://' . $context['authority'] . $context['target'];
			case '@path':
				// RFC 9421 §2.2.6: an empty path is "/"
				return (string) parse_url((string) $context['target'], PHP_URL_PATH) ?: '/';
			case '@query':
				$query = parse_url((string) $context['target'], PHP_URL_QUERY);
				return '?' . (is_string($query) ? $query : '');
			case '@request-target':
				$query = parse_url((string) $context['target'], PHP_URL_QUERY);
				return ((string) parse_url((string) $context['target'], PHP_URL_PATH) ?: '/') . (is_string($query) && $query !== '' ? '?' . $query : '');
		}

		// Derived components we do not implement and response-only ones such as "@status"
		if (str_starts_with($component, '@')) {
			return null;
		}

		if (!array_key_exists($component, $headers)) {
			return null;
		}

		return trim((string) preg_replace('/\s*\n\s*/', ' ', (string) $headers[$component]));
	}

	/**
	 * Returns the "@authority" of this instance (host and non-default port)
	 */
	private static function rfc9421Authority(): string
	{
		$authority = strtolower((string) DI::baseUrl()->getHost());
		$port      = DI::baseUrl()->getPort();
		if (!empty($port)) {
			$authority .= ':' . $port;
		}

		return $authority;
	}

	/**
	 * Maps an RFC 9421 "alg" parameter to the verifySignature() identifiers to try
	 *
	 * The parameter is often omitted (RFC 9421 §3.3.7). An RSA key can then be used
	 * with either PKCS#1 v1.5 / SHA-256 or PSS / SHA-512 (the FASP profile uses the
	 * latter without an "alg"), so both are returned and tried in turn.
	 *
	 * @return array Empty for unsupported algorithms
	 */
	private static function rfc9421Algorithms(string $alg): array
	{
		switch ($alg) {
			case '':
				return ['sha256', 'rsa-pss-sha512', 'ed25519'];
			case 'rsa-v1_5-sha256':
			case 'rsa-sha256':
				return ['sha256'];
			case 'rsa-pss-sha512':
				return ['rsa-pss-sha512'];
			case 'ed25519':
				return ['ed25519'];
			default:
				DI::logger()->info('Unsupported algorithm', ['alg' => $alg]);
				return [];
		}
	}

	/**
	 * Narrows the algorithm candidates to those that match the key material
	 *
	 * A multibase key is Ed25519, a PEM key is RSA. This keeps verifySignature()
	 * from running an RSA verification against an Ed25519 key and vice versa.
	 *
	 * @param string $pubkey     RSA PEM or Ed25519 multibase key
	 * @param array  $candidates Algorithm identifiers from rfc9421Algorithms()
	 * @return array
	 */
	private static function keyAlgorithms(string $pubkey, array $candidates): array
	{
		if (str_starts_with($pubkey, 'z6Mk')) {
			return array_values(array_intersect($candidates, ['ed25519']));
		}

		return array_values(array_diff($candidates, ['ed25519']));
	}

	/**
	 * Checks the content binding and the time window of an RFC 9421 signature
	 */
	private static function rfc9421CheckContent(string $content, array $components, array $headers, int $created, int $expires): bool
	{
		// The digest only protects an actual body. Some senders cover (and send) a
		// "content-digest" on an empty GET too; a mismatch there is harmless.
		if ($content !== '') {
			if (!in_array('content-digest', $components)) {
				DI::logger()->info('Body is present but "content-digest" is not signed');
				return false;
			}
			if (empty($headers['content-digest']) || !self::verifyContentDigest((string) $headers['content-digest'], $content)) {
				DI::logger()->info('Content-Digest does not match');
				return false;
			}
		}

		// Without "created" a signature has no lower time bound and could be replayed
		// forever, so it is required just like the "Date" header on the cavage path.
		if (empty($created)) {
			DI::logger()->info('Missing "created" parameter');
			return false;
		}

		$current = time();
		$expired = !empty($expires) ? min($expires, $created + 3600) : $created + 3600;

		// Grace period of 60 seconds for slight time differences between the servers
		if (($created - 60) > $current) {
			DI::logger()->notice('Signature created in the future', ['created' => date(DateTimeFormat::MYSQL, $created), 'current' => date(DateTimeFormat::MYSQL, $current)]);
			return false;
		}
		if ($current > $expired) {
			DI::logger()->notice('Signature expired', ['expired' => date(DateTimeFormat::MYSQL, $expired), 'current' => date(DateTimeFormat::MYSQL, $current)]);
			return false;
		}

		// A signed GET has no body, so it has to cover the request target
		$bound = (bool) array_intersect($components, ['@target-uri', '@path', '@query', '@request-target', '@authority']);
		if ($content === '' && !$bound) {
			DI::logger()->info('No good signed content');
			return false;
		}

		return true;
	}

	/**
	 * Verifies a "Content-Digest" header (RFC 9530) against the request body
	 */
	private static function verifyContentDigest(string $header, string $body): bool
	{
		try {
			$digest = Parser::parseDictionary($header);
		} catch (ParseException) {
			DI::logger()->info('Cannot parse the Content-Digest header', ['header' => $header]);
			return false;
		}

		$algorithms = ['sha-256' => 'sha256', 'sha-512' => 'sha512'];
		$verified   = false;

		foreach ($digest as $name => $item) {
			if (!isset($algorithms[$name]) || !$item instanceof StructuredFieldsItem || !$item->getValue() instanceof Bytes) {
				continue;
			}
			if (!hash_equals(hash($algorithms[$name], $body, true), (string) $item->getValue())) {
				return false;
			}
			$verified = true;
		}

		return $verified;
	}

	/**
	 * Extracts the "keyid" of the first signature in a "Signature-Input" header
	 */
	private static function rfc9421KeyId(string $header): string
	{
		try {
			$input = Parser::parseDictionary($header);
		} catch (ParseException $exception) {
			DI::logger()->info('Cannot parse the Signature-Input header', ['message' => $exception->getMessage()]);
			return '';
		}

		foreach ($input as $parameters) {
			if ($parameters instanceof InnerList) {
				$keyId = $parameters->getParameters()->keyid ?? '';
				if ($keyId !== '') {
					return (string) $keyId;
				}
			}
		}

		return '';
	}

	/**
	 * Verifies a signature base against a public key
	 *
	 * @param string $algorithm 'sha256', 'sha512', 'rsa-pss-sha512' or 'ed25519'
	 * @param string $pubkey    An RSA public key in PEM form, or an Ed25519 key in multibase form
	 */
	private static function verifySignature(string $data, string $signature, string $pubkey, string $algorithm): bool
	{
		if ($algorithm === 'ed25519') {
			if (!function_exists('sodium_crypto_sign_verify_detached')) {
				DI::logger()->warning('The sodium extension is not available, cannot verify an Ed25519 signature');
				return false;
			}
			$key = Crypto::ed25519PublicKeyFromMultibase($pubkey);
			return (strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) && sodium_crypto_sign_verify_detached($signature, $data, $key);
		}

		if ($algorithm === 'rsa-pss-sha512') {
			try {
				$key = PublicKeyLoader::load($pubkey);
			} catch (\Throwable $th) {
				DI::logger()->info('Cannot load the public key', ['message' => $th->getMessage()]);
				return false;
			}
			if (!$key instanceof RSA) {
				return false;
			}
			$verifier = $key->withPadding(RSA::SIGNATURE_PSS)->withHash('sha512')->withMGFHash('sha512');
			return $verifier instanceof CryptPublicKey && $verifier->verify($data, $signature);
		}

		return Crypto::rsaVerify($data, $signature, $pubkey, $algorithm === 'sha512' ? 'sha512' : 'sha256');
	}

	/**
	 * Fetches the signer's key and takes care of tombstone actors
	 *
	 * @return array|null The key data, an empty array when no key was found, null for a tombstone
	 */
	private static function fetchSignerKey(string $keyId, string $actor, ?bool $update): ?array
	{
		$key = self::fetchKey($keyId, $actor, $update);
		if (empty($key)) {
			DI::logger()->info('Empty key');
			return [];
		}

		if (!empty($key['url']) && !empty($key['type']) && ($key['type'] == 'Tombstone')) {
			DI::logger()->info('Actor is a tombstone', ['key' => $key]);

			if (!Contact::isLocal($key['url'])) {
				// We now delete everything that we possibly knew from this actor
				Contact::deleteContactByUrl($key['url']);
			}
			return null;
		}

		return $key;
	}

	/**
	 * Returns the actor id that is referenced in a signed request body
	 *
	 * @return string|false The actor id, an empty string when there is no body, false on invalid content
	 */
	private static function signedActor(string $content)
	{
		if (empty($content)) {
			return '';
		}

		$object = json_decode($content, true);
		if (empty($object)) {
			DI::logger()->info('No object');
			return false;
		}

		return JsonLD::fetchElement($object, 'actor', 'id') ?? '';
	}

	/**
	 * Concatenates the signed header fields for a draft-cavage signature
	 */
	private static function buildSignedData(array $fields, array $headers): string
	{
		$signed_data = '';
		foreach ($fields as $field) {
			if (array_key_exists($field, $headers)) {
				$signed_data .= $field . ': ' . $headers[$field] . "\n";
			} else {
				DI::logger()->info('Requested header field not found', ['field' => $field, 'header' => $headers]);
			}
		}

		return rtrim($signed_data, "\n");
	}

	/**
	 * Turns the server variables of a request into a lower-cased header field map
	 */
	private static function collectHeaders(array $http_headers): array
	{
		$headers = [];

		// First take every header
		foreach ($http_headers as $k => $v) {
			$headers[str_replace('_', '-', strtolower((string) $k))] = $v;
		}

		// Now add every http header
		foreach ($http_headers as $k => $v) {
			if (str_starts_with((string) $k, 'HTTP_')) {
				$headers[str_replace('_', '-', strtolower(substr((string) $k, 5)))] = $v;
			}
		}

		return $headers;
	}

	/**
	 * Builds the "(request-target)" value for an outgoing draft-cavage signature
	 *
	 * Older Friendica releases only signed the path. Requests can fall back to
	 * that form, so the query string is optional here. See issue 14973.
	 */
	private static function requestTarget(string $method, string $url, bool $with_query = true): string
	{
		$target = (string) parse_url($url, PHP_URL_PATH);
		$query  = parse_url($url, PHP_URL_QUERY);
		if ($with_query && is_string($query) && $query !== '') {
			$target .= '?' . $query;
		}

		return strtolower($method) . ' ' . $target;
	}

	/**
	 * Adds the Date, Host and Signature headers for a signed GET request
	 */
	private static function signGet(array $header, string $request, array $owner, bool $with_query = true): array
	{
		$host = strtolower((string) parse_url($request, PHP_URL_HOST));
		$date = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

		$header['Date'] = $date;
		$header['Host'] = $host;

		$signed_data = '(request-target): ' . self::requestTarget('get', $request, $with_query) . "\ndate: " . $date . "\nhost: " . $host;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		$header['Signature'] = 'keyId="' . $owner['url'] . '#main-key",algorithm="rsa-sha256",headers="(request-target) date host",signature="' . $signature . '"';

		return $header;
	}

	/**
	 * Assembles the draft-cavage headers for an outgoing POST request
	 */
	private static function signCavagePost(string $content, string $target, array $owner): array
	{
		$host           = strtolower((string) parse_url($target, PHP_URL_HOST));
		$digest         = 'SHA-256=' . base64_encode(hash('sha256', $content, true));
		$content_length = (string) strlen($content);
		$date           = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

		$signed_data = '(request-target): ' . self::requestTarget('post', $target) . "\ndate: " . $date . "\ncontent-length: " . $content_length . "\ndigest: " . $digest . "\nhost: " . $host;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		return [
			'Date'           => $date,
			'Content-Length' => $content_length,
			'Digest'         => $digest,
			'Host'           => $host,
			'Signature'      => 'keyId="' . $owner['url'] . '#main-key",algorithm="rsa-sha256",headers="(request-target) date content-length digest host",signature="' . $signature . '"',
		];
	}

	/**
	 * Replaces the signature headers of a GET request with an RFC 9421 signature
	 */
	private static function signGetRfc9421(array $header, string $request, array $owner): array
	{
		$header['Date'] = DateTimeFormat::utcNow(DateTimeFormat::HTTP);
		$header['Host'] = strtolower((string) parse_url($request, PHP_URL_HOST));

		return array_merge($header, self::signRequestRfc9421('GET', $request, null, $owner['uprvkey'], $owner['url'] . '#main-key'));
	}

	/**
	 * Signs an outgoing request per RFC 9421
	 *
	 * The covered components match Mastodon 4.7: "@method" and "@target-uri", plus
	 * "content-digest" when there is a body. The actor's RSA key is used with
	 * RSASSA-PKCS1-v1_5 and SHA-256.
	 *
	 * @param string      $method HTTP method
	 * @param string      $url    Full request URL
	 * @param string|null $body   Request body, or null for a GET request
	 * @param string      $prvkey The signer's private key
	 * @param string      $keyId  The key id to reference
	 *
	 * @return array The "Signature-Input", "Signature" and (for a body) "Content-Digest" headers
	 */
	public static function signRequestRfc9421(string $method, string $url, ?string $body, string $prvkey, string $keyId): array
	{
		$context    = self::rfc9421RequestContext($method, $url);
		$components = ['@method', '@target-uri'];
		$headers    = [];

		if (!is_null($body)) {
			$digest                    = 'sha-256=:' . base64_encode(hash('sha256', $body, true)) . ':';
			$components[]              = 'content-digest';
			$headers['Content-Digest'] = $digest;
		}

		$params = '("' . implode('" "', $components) . '");created=' . time() . ';keyid="' . $keyId . '";alg="rsa-v1_5-sha256"';

		$base      = self::rfc9421SignatureBase($components, $params, $context, ['content-digest' => $headers['Content-Digest'] ?? '']);
		$signature = base64_encode(Crypto::rsaSign($base, $prvkey, 'sha256'));

		$headers['Signature-Input'] = 'sig1=' . $params;
		$headers['Signature']       = 'sig1=:' . $signature . ':';

		return $headers;
	}

	/**
	 * Turns a request URL into the context array that rfc9421SignatureBase() expects
	 */
	private static function rfc9421RequestContext(string $method, string $url): array
	{
		$parts     = parse_url($url) ?: [];
		$scheme    = strtolower($parts['scheme'] ?? 'https');
		$authority = strtolower($parts['host'] ?? '');

		$default = ($scheme === 'https') ? 443 : 80;
		if (!empty($parts['port']) && ($parts['port'] != $default)) {
			$authority .= ':' . $parts['port'];
		}

		$target = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

		return ['method' => strtoupper($method), 'scheme' => $scheme, 'authority' => $authority, 'target' => $target];
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

		// Some implementations use an opaque token (a key thumbprint) as the keyId.
		// Without a resolvable URL we cannot attribute the key to an actor.
		if (!Network::isValidHttpUrl($url)) {
			DI::logger()->info('The keyId is not an HTTP URL and cannot be resolved', ['keyId' => $id]);
			return [];
		}

		$profile = APContact::getByURL($url, $update);
		if (!empty($profile)) {
			DI::logger()->info('Taking key from id', ['id' => $id]);
			return ['url' => $url, 'pubkey' => $profile['pubkey'], 'type' => $profile['type']];
		}

		// The keyId can point to a stand-alone key document instead of an actor.
		// Follow its "owner" / "controller" to the actor that uses the key.
		$owner = self::keyDocumentOwner($url);
		if (($owner != '') && ($owner != $url)) {
			$profile = APContact::getByURL($owner, $update);
			if (!empty($profile)) {
				DI::logger()->info('Taking key from the key document owner', ['id' => $id, 'owner' => $owner]);
				return ['url' => $owner, 'pubkey' => $profile['pubkey'], 'type' => $profile['type']];
			}
		}

		if ($url != $actor) {
			$profile = APContact::getByURL($actor);
			if (!empty($profile)) {
				DI::logger()->info('Taking key from actor', ['actor' => $actor]);
				return ['url' => $actor, 'pubkey' => $profile['pubkey'], 'type' => $profile['type']];
			}
		}

		DI::logger()->notice('Key could not be fetched', ['url' => $url, 'actor' => $actor]);
		return [];
	}

	/**
	 * Returns the "owner" / "controller" of a stand-alone key document
	 *
	 * @param string $url The key id
	 * @return string The actor URL, empty when the document is not a key or has no owner
	 */
	private static function keyDocumentOwner(string $url): string
	{
		$data = self::fetch($url);
		if (empty($data) || !in_array($data['type'] ?? '', ['CryptographicKey', 'Key', 'Multikey'])) {
			return '';
		}

		$owner = $data['owner'] ?? $data['controller'] ?? '';
		return is_array($owner) ? (string) ($owner['id'] ?? '') : (string) $owner;
	}
}
