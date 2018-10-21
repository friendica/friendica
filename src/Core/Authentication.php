<?php
/**
 * @file /src/Core/Authentication.php
 */

namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
* Handle Authentification, Session and Cookies
*/
class Authentication extends BaseObject
{
	/**
	 * @brief Calculate the hash that is needed for the "Friendica" cookie
	 *
	 * @param array $user Record from "user" table
	 *
	 * @return string Hashed data
	 */
	public static function getCookieHashForUser($user)
	{
		return(hash("sha256", Config::get("system", "site_prvkey") .
				$user["prvkey"] .
				$user["password"]));
	}

	/**
	 * @brief Set the "Friendica" cookie
	 *
	 * @param int $time
	 * @param array $user Record from "user" table
	 */
	public static  function setCookie($time, $user = [])
	{
		if ($time != 0) {
			$time = $time + time();
		}

		if ($user) {
			$value = json_encode(["uid" => $user["uid"],
				"hash" => self::getCookieHashForUser($user),
				"ip" => defaults($_SERVER, 'REMOTE_ADDR', '0.0.0.0')]);
		} else {
			$value = "";
		}

		setcookie("Friendica", $value, $time, "/", "", (Config::get('system', 'ssl_policy') == SSL_POLICY_FULL), true);
	}

	/**
	 * @brief Sets the provided user's authenticated session
	 *
	 * @todo Should be moved to Friendica\Core\Session once it's created
	 *
	 * @param type $user_record
	 * @param type $login_initial
	 * @param type $interactive
	 * @param type $login_refresh
	 */
	public static function setAuthenticatedSessionForUser($user_record, $login_initial = false, $interactive = false, $login_refresh = false)
	{
		$a = self::getApp();

		$_SESSION['uid'] = $user_record['uid'];
		$_SESSION['theme'] = $user_record['theme'];
		$_SESSION['mobile-theme'] = PConfig::get($user_record['uid'], 'system', 'mobile_theme');
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $user_record['page-flags'];
		$_SESSION['my_url'] = $a->getbaseUrl() . '/profile/' . $user_record['nickname'];
		$_SESSION['my_address'] = $user_record['nickname'] . '@' . substr($a->getbaseUrl(), strpos($a->getbaseUrl(), '://') + 3);
		$_SESSION['addr'] = defaults($_SERVER, 'REMOTE_ADDR', '0.0.0.0');

		$a->user = $user_record;

		if ($interactive) {
			if ($a->user['login_date'] <= DBA::NULL_DATETIME) {
				$_SESSION['return_path'] = 'profile_photo/new';
				$a->module = 'profile_photo';
				info(L10n::t("Welcome ") . $a->user['username'] . EOL);
				info(L10n::t('Please upload a profile photo.') . EOL);
			} else {
				info(L10n::t("Welcome back ") . $a->user['username'] . EOL);
			}
		}

		$member_since = strtotime($a->user['register_date']);
		if (time() < ($member_since + ( 60 * 60 * 24 * 14))) {
			$_SESSION['new_member'] = true;
		} else {
			$_SESSION['new_member'] = false;
		}
		if (strlen($a->user['timezone'])) {
			date_default_timezone_set($a->user['timezone']);
			$a->timezone = $a->user['timezone'];
		}

		$master_record = $a->user;

		if ((x($_SESSION, 'submanage')) && intval($_SESSION['submanage'])) {
			$user = DBA::selectFirst('user', [], ['uid' => $_SESSION['submanage']]);
			if (DBA::isResult($user)) {
				$master_record = $user;
			}
		}

		if ($master_record['parent-uid'] == 0) {
			// First add our own entry
			$a->identities = [['uid' => $master_record['uid'],
					'username' => $master_record['username'],
					'nickname' => $master_record['nickname']]];

			// Then add all the children
			$r = DBA::select('user', ['uid', 'username', 'nickname'],
				['parent-uid' => $master_record['uid'], 'account_removed' => false]);
			if (DBA::isResult($r)) {
				$a->identities = array_merge($a->identities, DBA::toArray($r));
			}
		} else {
			// Just ensure that the array is always defined
			$a->identities = [];

			// First entry is our parent
			$r = DBA::select('user', ['uid', 'username', 'nickname'],
				['uid' => $master_record['parent-uid'], 'account_removed' => false]);
			if (DBA::isResult($r)) {
				$a->identities = DBA::toArray($r);
			}

			// Then add all siblings
			$r = DBA::select('user', ['uid', 'username', 'nickname'],
				['parent-uid' => $master_record['parent-uid'], 'account_removed' => false]);
			if (DBA::isResult($r)) {
				$a->identities = array_merge($a->identities, DBA::toArray($r));
			}
		}

		$r = DBA::p("SELECT `user`.`uid`, `user`.`username`, `user`.`nickname`
			FROM `manage`
			INNER JOIN `user` ON `manage`.`mid` = `user`.`uid`
			WHERE `user`.`account_removed` = 0 AND `manage`.`uid` = ?",
			$master_record['uid']
		);
		if (DBA::isResult($r)) {
			$a->identities = array_merge($a->identities, DBA::toArray($r));
		}

		if ($login_initial) {
			logger('auth_identities: ' . print_r($a->identities, true), LOGGER_DEBUG);
		}
		if ($login_refresh) {
			logger('auth_identities refresh: ' . print_r($a->identities, true), LOGGER_DEBUG);
		}

		$contact = DBA::selectFirst('contact', [], ['uid' => $_SESSION['uid'], 'self' => true]);
		if (DBA::isResult($contact)) {
			$a->contact = $contact;
			$a->cid = $contact['id'];
			$_SESSION['cid'] = $a->cid;
		}

		header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] . '"');

		if ($login_initial || $login_refresh) {
			DBA::update('user', ['login_date' => DateTimeFormat::utcNow()], ['uid' => $_SESSION['uid']]);

			// Set the login date for all identities of the user
			DBA::update('user', ['login_date' => DateTimeFormat::utcNow()],
				['parent-uid' => $master_record['uid'], 'account_removed' => false]);
		}

		if ($login_initial) {
			/*
			 * If the user specified to remember the authentication, then set a cookie
			 * that expires after one week (the default is when the browser is closed).
			 * The cookie will be renewed automatically.
			 * The week ensures that sessions will expire after some inactivity.
			 */
			if (!empty($_SESSION['remember'])) {
				logger('Injecting cookie for remembered user ' . $a->user['nickname']);
				self::setCookie(604800, $user_record);
				unset($_SESSION['remember']);
			}
		}

		if ($login_initial) {
			Addon::callHooks('logged_in', $a->user);

			if (($a->module !== 'home') && isset($_SESSION['return_path'])) {
				$a->internalRedirect($_SESSION['return_path']);
			}
		}
	}

	/**
	 * @brief Kills the "Friendica" cookie and all session data
	 */
	public static function deleteSession()
	{
		self::setCookie(-3600); // make sure cookie is deleted on browser close, as a security measure
		session_unset();
		session_destroy();
	}
}

