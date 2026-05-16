<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\User;

use Friendica\App\BaseURL;
use Friendica\App\Request;
use Friendica\Core\Config\Capability\IManageConfigValues;

/**
 * Interacting with the Friendica Cookie of a user
 */
class Cookie
{
	/** @var int Default expire duration in days */
	public const DEFAULT_EXPIRE = 7;
	/** @var string The name of the Friendica cookie */
	public const NAME = 'Friendica';
	/** @var string The path of the Friendica cookie */
	public const PATH = '/';
	/** @var string The domain name of the Friendica cookie */
	public const DOMAIN = '';
	/** @var bool True, if the cookie should only be accessible through HTTP */
	public const HTTPONLY = true;

	/** @var string The remote address of this node */
	private $remoteAddr;
	/** @var bool True, if the connection is ssl enabled */
	private $sslEnabled;
	/** @var string The private key of this Friendica node */
	private $sitePrivateKey;
	/** @var int The default cookie lifetime */
	private $lifetime;
	/** @var array The Friendica cookie data array */
	private $data;

	/**
	 * @param Request             $request The current http request
	 * @param IManageConfigValues $config
	 * @param BaseURL             $baseURL
	 * @param array               $COOKIE The $_COOKIE array
	 */
	public function __construct(Request $request, IManageConfigValues $config, BaseURL $baseURL, array $COOKIE = [])
	{
		$this->sslEnabled     = $baseURL->getScheme() === 'https';
		$this->sitePrivateKey = $config->get('system', 'site_prvkey');

		$authCookieDays = $config->get(
			'system',
			'auth_cookie_lifetime',
			self::DEFAULT_EXPIRE,
		);
		$this->lifetime = $authCookieDays * 24 * 60 * 60;

		$this->remoteAddr = $request->getRemoteAddress();

		$this->data = json_decode($COOKIE[self::NAME] ?? '[]', true) ?: [];
	}

	/**
	 * Returns the value for a key of the Friendica cookie
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed|null The value for the provided cookie key
	 */
	public function get(string $key, $default = null)
	{
		return $this->data[$key] ?? $default;
	}

	/**
	 * Set a single cookie key value.
	 * Overwrites an existing value with the same key.
	 *
	 * @param $key
	 * @param $value
	 * @return bool
	 */
	public function set($key, $value): bool
	{
		return $this->setMultiple([$key => $value]);
	}

	/**
	 * Sets multiple cookie key values.
	 * Overwrites existing values with the same key.
	 *
	 * @param array $values
	 * @return bool
	 */
	public function setMultiple(array $values): bool
	{
		$this->data = $values + $this->data;

		return $this->send();
	}

	/**
	 * Remove a cookie key
	 *
	 * @param string $key
	 */
	public function unset(string $key)
	{
		if (isset($this->data[$key])) {
			unset($this->data[$key]);

			$this->send();
		}
	}

	/**
	 * Resets the cookie to a given data set
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function reset(array $data): bool
	{
		return $this->clear()
			   && $this->setMultiple($data);
	}

	/**
	 * Clears the Friendica cookie
	 */
	public function clear(): bool
	{
		$this->data = [];
		// make sure cookie is deleted on browser close, as a security measure
		return $this->setCookie('', -3600, $this->sslEnabled);
	}

	/**
	 * Send the cookie, should be called every time $this->data is changed or to refresh the cookie.
	 *
	 * @return bool
	 */
	public function send(): bool
	{
		return $this->setCookie(
			json_encode(['ip' => $this->remoteAddr] + $this->data),
			$this->lifetime + time(),
			$this->sslEnabled,
		);
	}

	/**
	 * setcookie() wrapper: protected, internal function for test-mocking possibility
	 *
	 * @link  https://php.net/manual/en/function.setcookie.php
	 *
	 * @param string $value  [optional]
	 * @param int    $expire [optional]
	 * @param bool   $secure [optional]
	 *
	 * @return bool If output exists prior to calling this function,
	 *
	 */
	protected function setCookie(
		string $value = null,
		int $expire = null,
		bool $secure = null
	): bool {
		return setcookie(self::NAME, $value, ['expires' => $expire, 'path' => self::PATH, 'domain' => self::DOMAIN, 'secure' => $secure, 'httponly' => self::HTTPONLY]);
	}

	/**
	 * Calculate a hash of a user's private data for storage in the cookie.
	 * Hashed twice, with the user's own private key first, then the node's private key second.
	 *
	 * @param string $privateData User private data
	 * @param string $privateKey  User private key
	 *
	 * @return string Hashed data
	 */
	public function hashPrivateData(string $privateData, string $privateKey): string
	{
		return hash_hmac(
			'sha256',
			hash_hmac('sha256', $privateData, $privateKey),
			$this->sitePrivateKey,
		);
	}

	/**
	 * @param string $hash        Hash from a cookie key value
	 * @param string $privateData User private data
	 * @param string $privateKey  User private key
	 *
	 * @return boolean
	 *
	 */
	public function comparePrivateDataHash(string $hash, string $privateData, string $privateKey): bool
	{
		return hash_equals(
			$this->hashPrivateData($privateData, $privateKey),
			$hash,
		);
	}
}
