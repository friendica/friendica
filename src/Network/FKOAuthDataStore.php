<?php

/**
 * @file src/Protocol/FKOAuthDataStore.php
 * OAuth server
 * Based on oauth2-php <http://code.google.com/p/oauth2-php/>
 *
 */

namespace Friendica\Network;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;
use dba;
use OAuthDataStore;

define('REQUEST_TOKEN_DURATION', 300);
define('ACCESS_TOKEN_DURATION', 31536000);

require_once 'include/dba.php';

require_once "library/OAuth1.php";

/**
 * @brief OAuthDataStore class
 */
class FKOAuthDataStore extends OAuthDataStore
{
	/**
	 * @return string
	 */
	private static function genToken()
	{
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
	}

	/**
	 * @param string $consumer_key key
	 * @return mixed
	 */
	public function lookup_consumer($consumer_key)
	{
		logger(__function__ . ":" . $consumer_key);

		$s = dba::select('clients', ['client_id', 'pw', 'redirect_uri'], ['client_id' => $consumer_key]);
		$r = dba::inArray($s);

		if (DBM::isResult($r)) {
			return new \OAuthConsumer($r[0]['client_id'], $r[0]['pw'], $r[0]['redirect_uri']);
		}

		return null;
	}

	/**
	 * @param string $consumer   consumer
	 * @param string $token_type type
	 * @param string $token      token
	 * @return mixed
	 */
	public function lookup_token($consumer, $token_type, $token)
	{
		logger(__function__ . ":" . $consumer . ", " . $token_type . ", " . $token);

		$s = dba::select('tokens', ['id', 'secret', 'scope', 'expires', 'uid'], ['client_id' => $consumer->key, 'scope' => $token_type, 'id' => $token]);
		$r = dba::inArray($s);

		if (DBM::isResult($r)) {
			$ot = new \OAuthToken($r[0]['id'], $r[0]['secret']);
			$ot->scope = $r[0]['scope'];
			$ot->expires = $r[0]['expires'];
			$ot->uid = $r[0]['uid'];
			return $ot;
		}

		return null;
	}

	/**
	 * @param string $consumer  consumer
	 * @param string $token     token
	 * @param string $nonce     nonce
	 * @param string $timestamp timestamp
	 * @return mixed
	 */
	public function lookup_nonce($consumer, $token, $nonce, $timestamp)
	{
		$token = dba::selectFirst('tokens', ['id', 'secret'], ['client_id' => $consumer->key, 'id' => $nonce, 'expires' => $timestamp]);
		if (DBM::isResult($token)) {
			return new \OAuthToken($token['id'], $token['secret']);
		}

		return null;
	}

	/**
	 * @param string $consumer consumer
	 * @param string $callback optional, default null
	 * @return mixed
	 */
	public function new_request_token($consumer, $callback = null)
	{
		logger(__function__ . ":" . $consumer . ", " . $callback);
		$key = self::genToken();
		$sec = self::genToken();

		if ($consumer->key) {
			$k = $consumer->key;
		} else {
			$k = $consumer;
		}

		$r = dba::insert(
			'tokens',
			[
				'id' => $key,
				'secret' => $sec,
				'client_id' => $k,
				'scope' => 'request',
				'expires' => time() + REQUEST_TOKEN_DURATION]
		);

		if (!$r) {
			return null;
		}

		return new \OAuthToken($key, $sec);
	}

	/**
	 * @param string $token    token
	 * @param string $consumer consumer
	 * @param string $verifier optional, defult null
	 * @return object
	 */
	public function new_access_token($token, $consumer, $verifier = null)
	{
		logger(__function__ . ":" . $token . ", " . $consumer . ", " . $verifier);

		// return a new access token attached to this consumer
		// for the user associated with this token if the request token
		// is authorized
		// should also invalidate the request token

		$ret = null;

		// get user for this verifier
		$uverifier = Config::get("oauth", $verifier);
		logger(__function__ . ":" . $verifier . "," . $uverifier);

		if (is_null($verifier) || ($uverifier !== false)) {
			$key = self::genToken();
			$sec = self::genToken();
			$r = dba::insert(
				'tokens',
				[
					'id' => $key,
					'secret' => $sec,
					'client_id' => $consumer->key,
					'scope' => 'access',
					'expires' => time() + ACCESS_TOKEN_DURATION,
					'uid' => $uverifier]
			);

			if ($r) {
				$ret = new \OAuthToken($key, $sec);
			}
		}

		dba::delete('tokens', ['id' => $token->key]);

		if (!is_null($ret) && !is_null($uverifier)) {
			Config::delete("oauth", $verifier);
		}

		return $ret;
	}
}
