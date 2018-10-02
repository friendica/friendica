<?php

/**
 * @file src/Util/HTTPSignature.php
 */
namespace Friendica\Util;

use Friendica\Core\Config;
use Friendica\Database\DBA;

/**
 * @brief Implements HTTP Signatures per draft-cavage-http-signatures-07.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Web/HTTPSig.php
 *
 * @see https://tools.ietf.org/html/draft-cavage-http-signatures-07
 */

class HTTPSignature
{
	/**
	 * @brief RFC5843
	 *
	 * Disabled until Friendica's ActivityPub implementation
	 * is ready.
	 *
	 * @see https://tools.ietf.org/html/rfc5843
	 *
	 * @param string  $body The value to create the digest for
	 * @param boolean $set  (optional, default true)
	 *   If set send a Digest HTTP header
	 *
	 * @return string The generated digest of $body
	 */
//	public static function generateDigest($body, $set = true)
//	{
//		$digest = base64_encode(hash('sha256', $body, true));
//
//		if($set) {
//			header('Digest: SHA-256=' . $digest);
//		}
//		return $digest;
//	}

	// See draft-cavage-http-signatures-08
	public static function verify($data, $key = '')
	{
		$body      = $data;
		$headers   = null;
		$spoofable = false;
		$result = [
			'signer'         => '',
			'header_signed'  => false,
			'header_valid'   => false,
			'content_signed' => false,
			'content_valid'  => false
		];

		// Decide if $data arrived via controller submission or curl.
		if (is_array($data) && $data['header']) {
			if (!$data['success']) {
				return $result;
			}

			$h = new HTTPHeaders($data['header']);
			$headers = $h->fetch();
			$body = $data['body'];
		} else {
			$headers = [];
			$headers['(request-target)'] = strtolower($_SERVER['REQUEST_METHOD']).' '.$_SERVER['REQUEST_URI'];

			foreach ($_SERVER as $k => $v) {
				if (strpos($k, 'HTTP_') === 0) {
					$field = str_replace('_', '-', strtolower(substr($k, 5)));
					$headers[$field] = $v;
				}
			}
		}

		$sig_block = null;

		if (array_key_exists('signature', $headers)) {
			$sig_block = self::parseSigheader($headers['signature']);
		} elseif (array_key_exists('authorization', $headers)) {
			$sig_block = self::parseSigheader($headers['authorization']);
		}

		if (!$sig_block) {
			logger('no signature provided.');
			return $result;
		}

		// Warning: This log statement includes binary data
		// logger('sig_block: ' . print_r($sig_block,true), LOGGER_DATA);

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

		$algorithm = null;
		if ($sig_block['algorithm'] === 'rsa-sha256') {
			$algorithm = 'sha256';
		}
		if ($sig_block['algorithm'] === 'rsa-sha512') {
			$algorithm = 'sha512';
		}

		if ($key && function_exists($key)) {
			$result['signer'] = $sig_block['keyId'];
			$key = $key($sig_block['keyId']);
		}

		logger('Got keyID ' . $sig_block['keyId']);

		// We don't use Activity Pub at the moment.
//		if (!$key) {
//			$result['signer'] = $sig_block['keyId'];
//			$key = self::getActivitypubKey($sig_block['keyId']);
//		}

		if (!$key) {
			return $result;
		}

		$x = Crypto::rsaVerify($signed_data, $sig_block['signature'], $key, $algorithm);

		logger('verified: ' . $x, LOGGER_DEBUG);

		if (!$x) {
			return $result;
		}

		if (!$spoofable) {
			$result['header_valid'] = true;
		}

		if (in_array('digest', $signed_headers)) {
			$result['content_signed'] = true;
			$digest = explode('=', $headers['digest']);

			if ($digest[0] === 'SHA-256') {
				$hashalg = 'sha256';
			}
			if ($digest[0] === 'SHA-512') {
				$hashalg = 'sha512';
			}

			// The explode operation will have stripped the '=' padding, so compare against unpadded base64.
			if (rtrim(base64_encode(hash($hashalg, $body, true)), '=') === $digest[1]) {
				$result['content_valid'] = true;
			}
		}

		logger('Content_Valid: ' . $result['content_valid']);

		return $result;
	}

	/**
	 * Fetch the public key for Activity Pub contact.
	 *
	 * @param string|int The identifier (contact addr or contact ID).
	 * @return string|boolean The public key or false on failure.
	 */
	private static function getActivitypubKey($id)
	{
		if (strpos($id, 'acct:') === 0) {
			$contact = DBA::selectFirst('contact', ['pubkey'], ['uid' => 0, 'addr' => str_replace('acct:', '', $id)]);
		} else {
			$contact = DBA::selectFirst('contact', ['pubkey'], ['id' => $id, 'network' => 'activitypub']);
		}

		if (DBA::isResult($contact)) {
			return $contact['pubkey'];
		}

		if(function_exists('as_fetch')) {
			$r = as_fetch($id);
		}

		if ($r) {
			$j = json_decode($r, true);

			if (array_key_exists('publicKey', $j) && array_key_exists('publicKeyPem', $j['publicKey'])) {
				if ((array_key_exists('id', $j['publicKey']) && $j['publicKey']['id'] !== $id) && $j['id'] !== $id) {
					return false;
				}

				return $j['publicKey']['publicKeyPem'];
			}
		}

		return false;
	}

	/**
	 * @brief
	 *
	 * @param string  $request
	 * @param array   $head
	 * @param string  $prvkey
	 * @param string  $keyid (optional, default 'Key')
	 * @param boolean $send_headers (optional, default false)
	 *   If set send a HTTP header
	 * @param boolean $auth (optional, default false)
	 * @param string  $alg (optional, default 'sha256')
	 * @param string  $crypt_key (optional, default null)
	 * @param string  $crypt_algo (optional, default 'aes256ctr')
	 *
	 * @return array
	 */
	public static function createSig($request, $head, $prvkey, $keyid = 'Key', $send_headers = false, $auth = false, $alg = 'sha256', $crypt_key = null, $crypt_algo = 'aes256ctr')
	{
		$return_headers = [];

		if ($alg === 'sha256') {
			$algorithm = 'rsa-sha256';
		}

		if ($alg === 'sha512') {
			$algorithm = 'rsa-sha512';
		}

		$x = self::sign($request, $head, $prvkey, $alg);

		$headerval = 'keyId="' . $keyid . '",algorithm="' . $algorithm
			. '",headers="' . $x['headers'] . '",signature="' . $x['signature'] . '"';

		if ($crypt_key) {
			$x = Crypto::encapsulate($headerval, $crypt_key, $crypt_algo);
			$headerval = 'iv="' . $x['iv'] . '",key="' . $x['key'] . '",alg="' . $x['alg'] . '",data="' . $x['data'] . '"';
		}

		if ($auth) {
			$sighead = 'Authorization: Signature ' . $headerval;
		} else {
			$sighead = 'Signature: ' . $headerval;
		}

		if ($head) {
			foreach ($head as $k => $v) {
				if ($send_headers) {
					// This is for ActivityPub implementation.
					// Since the Activity Pub implementation isn't
					// ready at the moment, we comment it out.
					// header($k . ': ' . $v);
				} else {
					$return_headers[] = $k . ': ' . $v;
				}
			}
		}

		if ($send_headers) {
			// This is for ActivityPub implementation.
			// Since the Activity Pub implementation isn't
			// ready at the moment, we comment it out.
			// header($sighead);
		} else {
			$return_headers[] = $sighead;
		}

		return $return_headers;
	}

	/**
	 * @brief
	 *
	 * @param string $request
	 * @param array  $head
	 * @param string $prvkey
	 * @param string $alg (optional) default 'sha256'
	 *
	 * @return array
	 */
	private static function sign($request, $head, $prvkey, $alg = 'sha256')
	{
		$ret = [];
		$headers = '';
		$fields  = '';

		if ($request) {
			$headers = '(request-target)' . ': ' . trim($request) . "\n";
			$fields = '(request-target)';
		}

		if ($head) {
			foreach ($head as $k => $v) {
				$headers .= strtolower($k) . ': ' . trim($v) . "\n";
				if ($fields) {
					$fields .= ' ';
				}
				$fields .= strtolower($k);
			}
			// strip the trailing linefeed
			$headers = rtrim($headers, "\n");
		}

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
}
