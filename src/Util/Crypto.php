<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Core\Hook;
use Friendica\DI;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Crypto class
 */
class Crypto
{
	// supported algorithms are 'sha256', 'sha1'
	/**
	 * @param string $data data
	 * @param string $key  key
	 * @param string $alg  algorithm
	 * @return string
	 */
	public static function rsaSign($data, $key, $alg = 'sha256')
	{
		if (empty($key)) {
			DI::logger()->warning('Empty key parameter');
		}
		openssl_sign($data, $sig, $key, (($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
		return $sig;
	}

	/**
	 * @param string $data data
	 * @param string $sig  signature
	 * @param string $key  key
	 * @param string $alg  algorithm
	 * @return boolean
	 */
	public static function rsaVerify($data, $sig, $key, $alg = 'sha256')
	{
		if (empty($key)) {
			DI::logger()->warning('Empty key parameter');
		}
		return openssl_verify($data, $sig, $key, (($alg == 'sha1') ? OPENSSL_ALGO_SHA1 : $alg));
	}

	/**
	 * Transform RSA public keys to standard PEM output
	 *
	 * @param string $key A RSA public key
	 *
	 * @return string The PEM output of this key
	 */
	public static function rsaToPem(string $key)
	{
		return (string)PublicKeyLoader::load($key);
	}

	/**
	 * @param integer $bits number of bits
	 * @return mixed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function newKeypair($bits)
	{
		$openssl_options = [
			'digest_alg'       => 'sha1',
			'private_key_bits' => $bits,
			'encrypt_key'      => false
		];

		$conf = DI::config()->get('system', 'openssl_conf_file');
		if ($conf) {
			$openssl_options['config'] = $conf;
		}
		$result = openssl_pkey_new($openssl_options);

		if (empty($result)) {
			DI::logger()->notice('new_keypair: failed');
			return false;
		}

		// Get private key
		$response = ['prvkey' => '', 'pubkey' => ''];

		openssl_pkey_export($result, $response['prvkey']);

		// Get public key
		$pkey               = openssl_pkey_get_details($result);
		$response['pubkey'] = $pkey["key"];

		return $response;
	}

	/**
	 * Encrypt a string with 'aes-256-cbc' cipher method.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $key   The key used for encryption.
	 * @param string $iv    A non-NULL Initialization Vector.
	 *
	 * @return string|boolean Encrypted string or false on failure.
	 */
	private static function encryptAES256CBC($data, $key, $iv)
	{
		return openssl_encrypt($data, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * Decrypt a string with 'aes-256-cbc' cipher method.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $key   The key used for decryption.
	 * @param string $iv    A non-NULL Initialization Vector.
	 *
	 * @return string|boolean Decrypted string or false on failure.
	 */
	private static function decryptAES256CBC($data, $key, $iv)
	{
		return openssl_decrypt($data, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $pubkey The public key.
	 * @param string $alg    The algorithm used for encryption.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function encapsulate($data, $pubkey, $alg = 'aes256cbc')
	{
		if ($alg === 'aes256cbc') {
			return self::encapsulateAes($data, $pubkey);
		}
		return self::encapsulateOther($data, $pubkey, $alg);
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $pubkey The public key.
	 * @param string $alg    The algorithm used for encryption.
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function encapsulateOther($data, $pubkey, $alg)
	{
		if (!$pubkey) {
			DI::logger()->notice('no key. data: '.$data);
		}
		$fn = 'encrypt' . strtoupper($alg);
		if (method_exists(__CLASS__, $fn)) {
			$result         = ['encrypted' => true];
			$key            = random_bytes(256);
			$iv             = random_bytes(256);
			$result['data'] = Strings::base64UrlEncode(self::$fn($data, $key, $iv), true);

			// log the offending call so we can track it down
			if (!openssl_public_encrypt($key, $k, $pubkey)) {
				$x = debug_backtrace();
				DI::logger()->notice('RSA failed', ['trace' => $x[0]]);
			}

			$result['alg'] = $alg;
			$result['key'] = Strings::base64UrlEncode($k, true);
			openssl_public_encrypt($iv, $i, $pubkey);
			$result['iv'] = Strings::base64UrlEncode($i, true);

			return $result;
		} else {
			$x = ['data' => $data, 'pubkey' => $pubkey, 'alg' => $alg, 'result' => $data];
			Hook::callAll('other_encapsulate', $x);

			return $x['result'];
		}
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param string $data
	 * @param string $pubkey
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function encapsulateAes($data, $pubkey)
	{
		if (!$pubkey) {
			DI::logger()->notice('aes_encapsulate: no key. data: ' . $data);
		}

		$key            = random_bytes(32);
		$iv             = random_bytes(16);
		$result         = ['encrypted' => true];
		$result['data'] = Strings::base64UrlEncode(self::encryptAES256CBC($data, $key, $iv), true);

		// log the offending call so we can track it down
		if (!openssl_public_encrypt($key, $k, $pubkey)) {
			$x = debug_backtrace();
			DI::logger()->notice('aes_encapsulate: RSA failed.', ['data' => $x[0]]);
		}

		$result['alg'] = 'aes256cbc';
		$result['key'] = Strings::base64UrlEncode($k, true);
		openssl_public_encrypt($iv, $i, $pubkey);
		$result['iv'] = Strings::base64UrlEncode($i, true);

		return $result;
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param array $data ['iv' => $iv, 'key' => $key, 'alg' => $alg, 'data' => $data]
	 * @param string $prvkey The private key used for decryption.
	 *
	 * @return string|boolean The decrypted string or false on failure.
	 * @throws \Exception
	 */
	public static function unencapsulate(array $data, $prvkey)
	{
		if (!$data) {
			return;
		}

		$alg = $data['alg'] ?? 'aes256cbc';
		if ($alg === 'aes256cbc') {
			return self::unencapsulateAes($data['data'], $prvkey);
		}

		return self::unencapsulateOther($data, $prvkey, $alg);
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param array $data
	 * @param string $prvkey The private key used for decryption.
	 * @param string $alg
	 *
	 * @return string|boolean The decrypted string or false on failure.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function unencapsulateOther(array $data, $prvkey, $alg)
	{
		$fn = 'decrypt' . strtoupper($alg);

		if (method_exists(__CLASS__, $fn)) {
			openssl_private_decrypt(Strings::base64UrlDecode($data['key']), $k, $prvkey);
			openssl_private_decrypt(Strings::base64UrlDecode($data['iv']), $i, $prvkey);

			return self::$fn(Strings::base64UrlDecode($data['data']), $k, $i);
		} else {
			$x = ['data' => $data, 'prvkey' => $prvkey, 'alg' => $alg, 'result' => $data];
			Hook::callAll('other_unencapsulate', $x);

			return $x['result'];
		}
	}

	/**
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/crypto.php
	 *
	 * @param array  $data
	 * @param string $prvkey The private key used for decryption.
	 *
	 * @return string|boolean The decrypted string or false on failure.
	 * @throws \Exception
	 */
	private static function unencapsulateAes($data, $prvkey)
	{
		openssl_private_decrypt(Strings::base64UrlDecode($data['key']), $k, $prvkey);
		openssl_private_decrypt(Strings::base64UrlDecode($data['iv']), $i, $prvkey);

		return self::decryptAES256CBC(Strings::base64UrlDecode($data['data']), $k, $i);
	}


	/**
	 * Creates cryptographic secure random digits
	 *
	 * @param string $digits The count of digits
	 * @return int The random Digits
	 *
	 * @throws \Exception In case 'random_int' isn't usable
	 */
	public static function randomDigits($digits)
	{
		return random_int(0, 10 ** $digits - 1);
	}
}
