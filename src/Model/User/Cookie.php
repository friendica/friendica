<?php

namespace Friendica\Model\User;

use Friendica\App;
use Friendica\Core\Config\IConfiguration;

/**
 * Interacting with the Friendica Cookie of a user
 */
class Cookie
{
	/** @var int Default expire duration in days */
	const DEFAULT_EXPIRE = 7;
	/** @var string The name of the Friendica cookie */
	const NAME = 'Friendica';
	/** @var string The path of the Friendica cookie */
	const PATH = '/';
	/** @var string The domain name of the Friendica cookie */
	const DOMAIN = '';
	/** @var bool True, if the cookie should only be accessible through HTTP */
	const HTTPONLY = true;

	/** @var string The remote address of this node */
	private $remoteAddr = '0.0.0.0';
	/** @var bool True, if the connection is ssl enabled */
	private $sslEnabled = false;
	/** @var string The private key of this Friendica node */
	private $sitePrivateKey;
	/** @var int The default cookie lifetime */
	private $lifetime = self::DEFAULT_EXPIRE * 24 * 60 * 60;
	/** @var array The $_COOKIE array */
	private $cookie;

	public function __construct(IConfiguration $config, App\BaseURL $baseURL, array $server = [], array $cookie = [])
	{
		if (!empty($server['REMOTE_ADDR'])) {
			$this->remoteAddr = $server['REMOTE_ADDR'];
		}

		$this->sslEnabled     = $baseURL->getSSLPolicy() === App\BaseURL::SSL_POLICY_FULL;
		$this->sitePrivateKey = $config->get('system', 'site_prvkey');

		$authCookieDays = $config->get('system', 'auth_cookie_lifetime',
			self::DEFAULT_EXPIRE);
		$this->lifetime = $authCookieDays * 24 * 60 * 60;
		$this->cookie   = $cookie;
	}

	/**
	 * Checks if the Friendica cookie is set for a user
	 *
	 * @param string $hash       The cookie hash
	 * @param string $password   The user password
	 * @param string $privateKey The private Key of the user
	 *
	 * @return boolean True, if the cookie is set
	 *
	 */
	public function check(string $hash, string $password, string $privateKey)
	{
		return hash_equals(
			$this->getHash($password, $privateKey),
			$hash
		);
	}

	/**
	 * Set the Friendica cookie for a user
	 *
	 * @param int      $uid        The user id
	 * @param string   $password   The user password
	 * @param string   $privateKey The user private key
	 * @param int|null $seconds    optional the seconds
	 *
	 * @return bool
	 */
	public function set(int $uid, string $password, string $privateKey, int $seconds = null)
	{
		if (!isset($seconds)) {
			$seconds = $this->lifetime + time();
		} elseif (isset($seconds) && $seconds != 0) {
			$seconds = $seconds + time();
		}

		$value = json_encode([
			'uid'  => $uid,
			'hash' => $this->getHash($password, $privateKey),
			'ip'   => $this->remoteAddr,
		]);

		return $this->setCookie(self::NAME, $value, $seconds, $this->sslEnabled);
	}

	/**
	 * Returns the data of the Friendicas user cookie
	 *
	 * @return mixed|null The JSON data, null if not set
	 */
	public function getData()
	{
		// When the "Friendica" cookie is set, take the value to authenticate and renew the cookie.
		if (isset($this->cookie[self::NAME])) {
			$data = json_decode($this->cookie[self::NAME]);
			if (!empty($data)) {
				return $data;
			}
		}

		return null;
	}

	/**
	 * Clears the Friendica cookie of this user after leaving the page
	 */
	public function clear()
	{
		// make sure cookie is deleted on browser close, as a security measure
		return $this->setCookie(self::NAME, '', -3600, $this->sslEnabled);
	}

	/**
	 * Calculate the hash that is needed for the Friendica cookie
	 *
	 * @param string $password   The user password
	 * @param string $privateKey The private key of the user
	 *
	 * @return string Hashed data
	 */
	private function getHash(string $password, string $privateKey)
	{
		return hash_hmac(
			'sha256',
			hash_hmac('sha256', $password, $privateKey),
			$this->sitePrivateKey
		);
	}

	/**
	 * Send a cookie - protected, internal function for test-mocking possibility
	 *
	 * @link  https://php.net/manual/en/function.setcookie.php
	 *
	 * @param string $name
	 * @param string $value  [optional]
	 * @param int    $expire [optional]
	 * @param bool   $secure [optional]
	 *
	 * @return bool If output exists prior to calling this function,
	 *
	 */
	protected function setCookie(string $name, string $value = null, int $expire = null,
	                             bool $secure = null)
	{
		return setcookie($name, $value, $expire, self::PATH, self::DOMAIN, $secure, self::HTTPONLY);
	}
}
