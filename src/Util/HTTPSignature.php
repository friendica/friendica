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

namespace Friendica\Util;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\User;

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
	public static function verifyMagic($key)
	{
		$headers   = null;
		$spoofable = false;
		$result = [
			'signer'         => '',
			'header_signed'  => false,
			'header_valid'   => false
		];

		// Decide if $data arrived via controller submission or curl.
		$headers = [];
		$headers['(request-target)'] = strtolower($_SERVER['REQUEST_METHOD']).' '.$_SERVER['REQUEST_URI'];

		foreach ($_SERVER as $k => $v) {
			if (strpos($k, 'HTTP_') === 0) {
				$field = str_replace('_', '-', strtolower(substr($k, 5)));
				$headers[$field] = $v;
			}
		}

		$sig_block = null;

		$sig_block = self::parseSigheader($headers['authorization']);

		if (!$sig_block) {
			Logger::log('no signature provided.');
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

		Logger::log('Got keyID ' . $sig_block['keyId'], Logger::DEBUG);

		if (!$key) {
			return $result;
		}

		$x = Crypto::rsaVerify($signed_data, $sig_block['signature'], $key, $algorithm);

		Logger::log('verified: ' . $x, Logger::DEBUG);

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
	public static function createSig($head, $prvkey, $keyid = 'Key')
	{
		$return_headers = [];

		$alg = 'sha512';
		$algorithm = 'rsa-sha512';

		$x = self::sign($head, $prvkey, $alg);

		$headerval = 'keyId="' . $keyid . '",algorithm="' . $algorithm
			. '",headers="' . $x['headers'] . '",signature="' . $x['signature'] . '"';

		$sighead = 'Authorization: Signature ' . $headerval;

		if ($head) {
			foreach ($head as $k => $v) {
				$return_headers[] = $k . ': ' . $v;
			}
		}

		$return_headers[] = $sighead;

		return $return_headers;
	}

	/**
	 * @param array  $head
	 * @param string $prvkey
	 * @param string $alg (optional) default 'sha256'
	 *
	 * @return array
	 */
	private static function sign($head, $prvkey, $alg = 'sha256')
	{
		$ret = [];
		$headers = '';
		$fields  = '';

		foreach ($head as $k => $v) {
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
	 * @return array associate array with
	 *   - \e string \b keyID
	 *   - \e string \b algorithm
	 *   - \e array  \b headers
	 *   - \e string \b signature
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function parseSigheader($header)
	{
		$ret = [];
		$matches = [];

		// if the header is encrypted, decrypt with (default) site private key and continue
		if (preg_match('/iv="(.*?)"/ism', $header, $matches)) {
			$header = self::decryptSigheader($header);
		}

		if (preg_match('/keyId="(.*?)"/ism', $header, $matches)) {
			$ret['keyId'] = $matches[1];
		}

		if (preg_match('/algorithm="(.*?)"/ism', $header, $matches)) {
			$ret['algorithm'] = $matches[1];
		} else {
			$ret['algorithm'] = 'rsa-sha256';
		}

		if (preg_match('/headers="(.*?)"/ism', $header, $matches)) {
			$ret['headers'] = explode(' ', $matches[1]);
		}

		if (preg_match('/signature="(.*?)"/ism', $header, $matches)) {
			$ret['signature'] = base64_decode(preg_replace('/\s+/', '', $matches[1]));
		}

		if (!empty($ret['signature']) && !empty($ret['algorithm']) && empty($ret['headers'])) {
			$ret['headers'] = ['date'];
		}

		return $ret;
	}

	/**
	 * @param string $header
	 * @param string $prvkey (optional), if not set use site private key
	 *
	 * @return array|string associative array, empty string if failue
	 *   - \e string \b iv
	 *   - \e string \b key
	 *   - \e string \b alg
	 *   - \e string \b data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function decryptSigheader($header, $prvkey = null)
	{
		$iv = $key = $alg = $data = null;

		if (!$prvkey) {
			$prvkey = DI::config()->get('system', 'prvkey');
		}

		$matches = [];

		if (preg_match('/iv="(.*?)"/ism', $header, $matches)) {
			$iv = $matches[1];
		}

		if (preg_match('/key="(.*?)"/ism', $header, $matches)) {
			$key = $matches[1];
		}

		if (preg_match('/alg="(.*?)"/ism', $header, $matches)) {
			$alg = $matches[1];
		}

		if (preg_match('/data="(.*?)"/ism', $header, $matches)) {
			$data = $matches[1];
		}

		if ($iv && $key && $alg && $data) {
			return Crypto::unencapsulate(['iv' => $iv, 'key' => $key, 'alg' => $alg, 'data' => $data], $prvkey);
		}

		return '';
	}

	/*
	 * Functions for ActivityPub
	 */

	/**
	 * Transmit given data to a target for a user
	 *
	 * @param array   $data   Data that is about to be send
	 * @param string  $target The URL of the inbox
	 * @param integer $uid    User id of the sender
	 *
	 * @return boolean Was the transmission successful?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function transmit($data, $target, $uid)
	{
		$owner = User::getOwnerDataById($uid);

		if (!$owner) {
			return;
		}

		$content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		// Header data that is about to be signed.
		$host = parse_url($target, PHP_URL_HOST);
		$path = parse_url($target, PHP_URL_PATH);
		$digest = 'SHA-256=' . base64_encode(hash('sha256', $content, true));
		$content_length = strlen($content);
		$date = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

		$headers = ['Date: ' . $date, 'Content-Length: ' . $content_length, 'Digest: ' . $digest, 'Host: ' . $host];

		$signed_data = "(request-target): post " . $path . "\ndate: ". $date . "\ncontent-length: " . $content_length . "\ndigest: " . $digest . "\nhost: " . $host;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		$headers[] = 'Signature: keyId="' . $owner['url'] . '#main-key' . '",algorithm="rsa-sha256",headers="(request-target) date content-length digest host",signature="' . $signature . '"';

		$headers[] = 'Content-Type: application/activity+json';

		$postResult = DI::httpRequest()->post($target, $content, $headers);
		$return_code = $postResult->getReturnCode();

		Logger::log('Transmit to ' . $target . ' returned ' . $return_code, Logger::DEBUG);

		$success = ($return_code >= 200) && ($return_code <= 299);

		self::setInboxStatus($target, $success);

		return $success;
	}

	/**
	 * Set the delivery status for a given inbox
	 *
	 * @param string  $url     The URL of the inbox
	 * @param boolean $success Transmission status
	 */
	static private function setInboxStatus($url, $success)
	{
		$now = DateTimeFormat::utcNow();

		$status = DBA::selectFirst('inbox-status', [], ['url' => $url]);
		if (!DBA::isResult($status)) {
			DBA::insert('inbox-status', ['url' => $url, 'created' => $now]);
			$status = DBA::selectFirst('inbox-status', [], ['url' => $url]);
		}

		if ($success) {
			$fields = ['success' => $now];
		} else {
			$fields = ['failure' => $now];
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

			$stamp2 = strtotime($now);
			$previous_stamp = strtotime($status['previous']);

			// Archive the inbox when there had been failures for five days.
			// Additionally ensure that at least one previous attempt has to be in between.
			if ((($stamp2 - $stamp1) >= 86400 * 5) && ($previous_stamp > $stamp1)) {
				$fields['archive'] = true;
			}
		} else {
			$fields['archive'] = false;
		}

		DBA::update('inbox-status', $fields, ['url' => $url]);
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
	public static function fetch($request, $uid)
	{
		$opts = ['accept_content' => 'application/activity+json, application/ld+json'];
		$curlResult = self::fetchRaw($request, $uid, false, $opts);

		if (empty($curlResult)) {
			return false;
		}

		if (!$curlResult->isSuccess() || empty($curlResult->getBody())) {
			return false;
		}

		$content = json_decode($curlResult->getBody(), true);
		if (empty($content) || !is_array($content)) {
			return false;
		}

		return $content;
	}

	/**
	 * Fetches raw data for a user
	 *
	 * @param string  $request request url
	 * @param integer $uid     User id of the requester
	 * @param boolean $binary  TRUE if asked to return binary results (file download) (default is "false")
	 * @param array   $opts    (optional parameters) assoziative array with:
	 *                         'accept_content' => supply Accept: header with 'accept_content' as the value
	 *                         'timeout' => int Timeout in seconds, default system config value or 60 seconds
	 *                         'http_auth' => username:password
	 *                         'novalidate' => do not validate SSL certs, default is to validate using our CA list
	 *                         'nobody' => only return the header
	 *                         'cookiejar' => path to cookie jar file
	 *
	 * @return object CurlResult
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchRaw($request, $uid = 0, $binary = false, $opts = [])
	{
		if (!empty($uid)) {
			$owner = User::getOwnerDataById($uid);
			if (!$owner) {
				return;
			}

			// Header data that is about to be signed.
			$host = parse_url($request, PHP_URL_HOST);
			$path = parse_url($request, PHP_URL_PATH);
			$date = DateTimeFormat::utcNow(DateTimeFormat::HTTP);

			$headers = ['Date: ' . $date, 'Host: ' . $host];

			$signed_data = "(request-target): get " . $path . "\ndate: ". $date . "\nhost: " . $host;

			$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

			$headers[] = 'Signature: keyId="' . $owner['url'] . '#main-key' . '",algorithm="rsa-sha256",headers="(request-target) date host",signature="' . $signature . '"';
		} else {
			$headers = [];
		}

		if (!empty($opts['accept_content'])) {
			$headers[] = 'Accept: ' . $opts['accept_content'];
		}

		$curl_opts = $opts;
		$curl_opts['header'] = $headers;

		$curlResult = DI::httpRequest()->curl($request, false, $curl_opts);
		$return_code = $curlResult->getReturnCode();

		Logger::log('Fetched for user ' . $uid . ' from ' . $request . ' returned ' . $return_code, Logger::DEBUG);

		return $curlResult;
	}

	/**
	 * Gets a signer from a given HTTP request
	 *
	 * @param $content
	 * @param $http_headers
	 *
	 * @return string Signer
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getSigner($content, $http_headers)
	{
		if (empty($http_headers['HTTP_SIGNATURE'])) {
			return false;
		}

		if (!empty($content)) {
			$object = json_decode($content, true);
			if (empty($object)) {
				return false;
			}

			$actor = JsonLD::fetchElement($object, 'actor', 'id');
		} else {
			$actor = '';
		}

		$headers = [];
		$headers['(request-target)'] = strtolower($http_headers['REQUEST_METHOD']) . ' ' . $http_headers['REQUEST_URI'];

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

		if (empty($sig_block) || empty($sig_block['headers']) || empty($sig_block['keyId'])) {
			return false;
		}

		$signed_data = '';
		foreach ($sig_block['headers'] as $h) {
			if (array_key_exists($h, $headers)) {
				$signed_data .= $h . ': ' . $headers[$h] . "\n";
			}
		}
		$signed_data = rtrim($signed_data, "\n");

		if (empty($signed_data)) {
			return false;
		}

		$algorithm = null;

		if ($sig_block['algorithm'] === 'rsa-sha256') {
			$algorithm = 'sha256';
		}

		if ($sig_block['algorithm'] === 'rsa-sha512') {
			$algorithm = 'sha512';
		}

		if (empty($algorithm)) {
			return false;
		}

		$key = self::fetchKey($sig_block['keyId'], $actor);

		if (empty($key)) {
			return false;
		}

		if (!Crypto::rsaVerify($signed_data, $sig_block['signature'], $key['pubkey'], $algorithm)) {
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
				return false;
			}

			$hasGoodSignedContent = true;
		}

		//  Check if the signed date field is in an acceptable range
		if (in_array('date', $sig_block['headers'])) {
			$diff = abs(strtotime($headers['date']) - time());
			if ($diff > 300) {
				Logger::log("Header date '" . $headers['date'] . "' is with " . $diff . " seconds out of the 300 second frame. The signature is invalid.");
				return false;
			}
			$hasGoodSignedContent = true;
		}

		// Check the content-length when it is part of the signed data
		if (in_array('content-length', $sig_block['headers'])) {
			if (strlen($content) != $headers['content-length']) {
				return false;
			}
		}

		// Ensure that the authentication had been done with some content
		// Without this check someone could authenticate with fakeable data
		if (!$hasGoodSignedContent) {
			return false;
		}

		return $key['url'];
	}

	/**
	 * fetches a key for a given id and actor
	 *
	 * @param $id
	 * @param $actor
	 *
	 * @return array with actor url and public key
	 * @throws \Exception
	 */
	private static function fetchKey($id, $actor)
	{
		$url = (strpos($id, '#') ? substr($id, 0, strpos($id, '#')) : $id);

		$profile = APContact::getByURL($url);
		if (!empty($profile)) {
			Logger::log('Taking key from id ' . $id, Logger::DEBUG);
			return ['url' => $url, 'pubkey' => $profile['pubkey']];
		} elseif ($url != $actor) {
			$profile = APContact::getByURL($actor);
			if (!empty($profile)) {
				Logger::log('Taking key from actor ' . $actor, Logger::DEBUG);
				return ['url' => $actor, 'pubkey' => $profile['pubkey']];
			}
		}

		return false;
	}
}
