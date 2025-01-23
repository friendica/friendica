<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security;

use Exception;
use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException\UnauthorizedException;

/**
 * Authentication via the basic auth method
 */
class BasicAuth
{
	/**
	 * @var bool|int
	 */
	protected static $current_user_id = 0;
	/**
	 * @var array
	 */
	protected static $current_token = [];

	/**
	 * Get current user id, returns 0 if $login is set to false and not logged in.
	 * When $login is true, the execution will stop when not logged in.
	 *
	 * @param bool $login Perform a login request if "true"
	 *
	 * @return int User ID
	 */
	public static function getCurrentUserID(bool $login)
	{
		if (empty(self::$current_user_id)) {
			self::$current_user_id = self::getUserIdByAuth($login);
		}

		return (int)self::$current_user_id;
	}

	public static function setCurrentUserID(int $uid = null)
	{
		self::$current_user_id = $uid;
	}

	/**
	 * Fetch a dummy application token
	 *
	 * @return array token
	 */
	public static function getCurrentApplicationToken()
	{
		if (empty(self::getCurrentUserID(true))) {
			return [];
		}

		//if (!empty(self::$current_token)) {
		//	return self::$current_token;
		//}

		$source = $_REQUEST['source'] ?? '';

		// Support for known clients that doesn't send a source name
		if (empty($source) && !empty($_SERVER['HTTP_USER_AGENT'])) {
			if(strpos($_SERVER['HTTP_USER_AGENT'], "Twidere") !== false) {
				$source = 'Twidere';
			}

			DI::logger()->info('Unrecognized user-agent', ['http_user_agent' => $_SERVER['HTTP_USER_AGENT']]);
		} else {
			DI::logger()->info('Empty user-agent');
		}

		if (empty($source)) {
			$source = 'api';
		}

		self::$current_token = [
			'uid'        => self::$current_user_id,
			'id'         => 0,
			'name'       => $source,
			'website'    => '',
			'created_at' => DBA::NULL_DATETIME,
			'read'       => true,
			'write'      => true,
			'follow'     => true,
			'push'       => false];

		return self::$current_token;
	}

	/**
	 * Fetch the user id via the auth header information
	 *
	 * @param boolean $do_login Perform a login request if not logged in
	 *
	 * @return integer User ID
	 */
	private static function getUserIdByAuth(bool $do_login = true): int
	{
		self::$current_user_id = 0;

		// workaround for HTTP-auth in CGI mode
		if (!empty($_SERVER['REDIRECT_REMOTE_USER'])) {
			$userpass = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6));
			if (!empty($userpass) && strpos($userpass, ':')) {
				list($name, $password)    = explode(':', $userpass);
				$_SERVER['PHP_AUTH_USER'] = $name;
				$_SERVER['PHP_AUTH_PW']   = $password;
			}
		}

		$user     = $_SERVER['PHP_AUTH_USER'] ?? '';
		$password = $_SERVER['PHP_AUTH_PW']   ?? '';

		// allow "user@server" login (but ignore 'server' part)
		$at = strstr($user, "@", true);
		if ($at) {
			$user = $at;
		}

		// next code from mod/auth.php. needs better solution
		$record = null;

		$addon_auth = [
			'username'      => trim($user),
			'password'      => trim($password),
			'authenticated' => 0,
			'user_record'   => null,
		];

		/*
		* An addon indicates successful login by setting 'authenticated' to non-zero value and returning a user record
		* Addons should never set 'authenticated' except to indicate success - as hooks may be chained
		* and later addons should not interfere with an earlier one that succeeded.
		*/
		Hook::callAll('authenticate', $addon_auth);

		if ($addon_auth['authenticated'] && !empty($addon_auth['user_record'])) {
			$record = $addon_auth['user_record'];
		} else {
			try {
				$user_id = User::getIdFromPasswordAuthentication(trim($user), trim($password), true);
				$record  = DBA::selectFirst('user', [], ['uid' => $user_id]);
			} catch (Exception $ex) {
				$record = [];
			}
		}

		if (empty($record)) {
			if (!$do_login) {
				return 0;
			}
			DI::logger()->debug('Access denied', ['parameters' => $_SERVER]);
			// Checking for commandline for the tests, we have to avoid to send a header
			if (DI::config()->get('system', 'basicauth') && (php_sapi_name() !== 'cli')) {
				header('WWW-Authenticate: Basic realm="Friendica"');
			}
			throw new UnauthorizedException("This API requires login");
		}

		DI::auth()->setForUser($record, false, false, false);

		Hook::callAll('logged_in', $record);

		self::$current_user_id = DI::userSession()->getLocalUserId();

		return self::$current_user_id;
	}
}
