<?php

/**
 * @file src/Core/Session.php
 */
namespace Friendica\Core;

use Friendica\Core\Session\CacheSessionHandler;
use Friendica\Core\Session\DatabaseSessionHandler;

/**
 * High-level Session service class
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Session
{
	public static $exists = false;
	public static $expire = 180000;

	public static function init()
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', 1);

		if (Config::get('system', 'ssl_policy') == SSL_POLICY_FULL) {
			ini_set('session.cookie_secure', 1);
		}

		$session_handler = Config::get('system', 'session_handler', 'database');
		if ($session_handler != 'native') {
			if ($session_handler == 'cache' && Config::get('system', 'cache_driver', 'database') != 'database') {
				$SessionHandler = new CacheSessionHandler();
			} else {
				$SessionHandler = new DatabaseSessionHandler();
			}

			session_set_save_handler($SessionHandler);
		}
	}

	public static function exists($name)
	{
		return isset($_SESSION[$name]);
	}

	public static function get($name)
	{
		return defaults($_SESSION, $name, null);
	}

	public static function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}
}
