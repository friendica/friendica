<?php

/**
 * @file src/Util/HTTPSignature.php
 */
namespace Friendica\Util;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\Model\User;
use Friendica\Model\APContact;
use Friendica\Protocol\ActivityPub;

/**
 * @brief Implements HTTP Signatures per draft-cavage-http-signatures-07.
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
	 * @brief Verifies a magic request
	 *
	 * @param $key
	 *
	 * @return array with verification data
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
			App::logger('no signature provided.');
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

		App::logger('Got keyID ' . $sig_block['keyId']);

		if (!$key) {
			return $result;
		}

		$x = Crypto::rsaVerify($signed_data, $sig_block['signature'], $key, $algorithm);

		App::logger('verified: ' . $x, LOGGER_DEBUG);

		if (!$x) {
			return $result;
		}

		if (!$spoofable) {
			$result['header_valid'] = true;
		}

		return $result;
	}

	/**
	 * @brief
	 *
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
	 * @brief
	 *
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
	 * @brief
	 *
	 * @param string $header
	 * @return array associate array with
	 *   - \e string \b keyID
	 *   - \e string \b algorithm
	 *   - \e array  \b headers
	 *   - \e string \b signature
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
		}

		if (preg_match('/headers="(.*?)"/ism', $header, $matches)) {
			$ret['headers'] = explode(' ', $matches[1]);
		}

		if (preg_match('/signature="(.*?)"/ism', $header, $matches)) {
			$ret['signature'] = base64_decode(preg_replace('/\s+/', '', $matches[1]));
		}

		if (($ret['signature']) && ($ret['algorithm']) && (!$ret['headers'])) {
			$ret['headers'] = ['date'];
		}

		return $ret;
	}

	/**
	 * @brief
	 *
	 * @param string $header
	 * @param string $prvkey (optional), if not set use site private key
	 *
	 * @return array|string associative array, empty string if failue
	 *   - \e string \b iv
	 *   - \e string \b key
	 *   - \e string \b alg
	 *   - \e string \b data
	 */
	private static function decryptSigheader($header, $prvkey = null)
	{
		$iv = $key = $alg = $data = null;

		if (!$prvkey) {
			$prvkey = Config::get('system', 'prvkey');
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
	 * @brief Transmit given data to a target for a user
	 *
	 * @param array $data Data that is about to be send
	 * @param string $target The URL of the inbox
	 * @param integer $uid User id of the sender
	 *
	 * @return boolean Was the transmission successful?
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

		$headers = ['Content-Length: ' . $content_length, 'Digest: ' . $digest, 'Host: ' . $host];

		$signed_data = "(request-target): post " . $path . "\ncontent-length: " . $content_length . "\ndigest: " . $digest . "\nhost: " . $host;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		$headers[] = 'Signature: keyId="' . $owner['url'] . '#main-key' . '",algorithm="rsa-sha256",headers="(request-target) content-length digest host",signature="' . $signature . '"';

		$headers[] = 'Content-Type: application/activity+json';

		$postResult = Network::post($target, $content, $headers);
		$return_code = $postResult->getReturnCode();

		App::logger('Transmit to ' . $target . ' returned ' . $return_code);

		return ($return_code >= 200) && ($return_code <= 299);
	}

	/**
	 * @brief Gets a signer from a given HTTP request
	 *
	 * @param $content
	 * @param $http_headers
	 *
	 * @return signer string
	 */
	public static function getSigner($content, $http_headers)
	{
		$object = json_decode($content, true);

		if (empty($object)) {
			return false;
		}

		$actor = JsonLD::fetchElement($object, 'actor', 'id');

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

		// Check the digest when it is part of the signed data
		if (in_array('digest', $sig_block['headers'])) {
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
		}

		/// @todo Check if the signed date field is in an acceptable range

		// Check the content-length when it is part of the signed data
		if (in_array('content-length', $sig_block['headers'])) {
			if (strlen($content) != $headers['content-length']) {
				return false;
			}
		}

		return $key['url'];
	}

	/**
	 * @brief fetches a key for a given id and actor
	 *
	 * @param $id
	 * @param $actor
	 *
	 * @return array with actor url and public key
	 */
	private static function fetchKey($id, $actor)
	{
		$url = (strpos($id, '#') ? substr($id, 0, strpos($id, '#')) : $id);

		$profile = APContact::getByURL($url);
		if (!empty($profile)) {
			App::logger('Taking key from id ' . $id, LOGGER_DEBUG);
			return ['url' => $url, 'pubkey' => $profile['pubkey']];
		} elseif ($url != $actor) {
			$profile = APContact::getByURL($actor);
			if (!empty($profile)) {
				App::logger('Taking key from actor ' . $actor, LOGGER_DEBUG);
				return ['url' => $actor, 'pubkey' => $profile['pubkey']];
			}
		}

		return false;
	}
}
