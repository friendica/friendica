<?php

/**
 * @file src/Core/Session.php
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\Core\Session\CacheSessionHandler;
use Friendica\Core\Session\DatabaseSessionHandler;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * High-level Session service class
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
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

		if (Config::get('system', 'ssl_policy') == App\BaseURL::SSL_POLICY_FULL) {
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

	/**
	 * Retrieves a key from the session super global or the defaults if the key is missing or the value is falsy.
	 *
	 * Handle the case where session_start() hasn't been called and the super global isn't available.
	 *
	 * @param string $name
	 * @param mixed $defaults
	 * @return mixed
	 */
	public static function get($name, $defaults = null)
	{
		return $_SESSION[$name] ?? $defaults;
	}

	/**
	 * Sets a single session variable.
	 * Overrides value of existing key.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public static function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * Sets multiple session variables.
	 * Overrides values for existing keys.
	 *
	 * @param array $values
	 */
	public static function setMultiple(array $values)
	{
		$_SESSION = $values + $_SESSION;
	}

	/**
	 * Removes a session variable.
	 * Ignores missing keys.
	 *
	 * @param $name
	 */
	public static function remove($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * Clears the current session array
	 */
	public static function clear()
	{
		$_SESSION = [];
	}

	/**
	 * @brief Sets the provided user's authenticated session
	 *
	 * @param App   $a
	 * @param array $user_record
	 * @param bool  $login_initial
	 * @param bool  $interactive
	 * @param bool  $login_refresh
	 * @throws \Friendica\Network\HTTPException\ForbiddenException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function setAuthenticatedForUser(App $a, array $user_record, $login_initial = false, $interactive = false, $login_refresh = false)
	{
		self::setMultiple([
			'uid'           => $user_record['uid'],
			'theme'         => $user_record['theme'],
			'mobile-theme'  => PConfig::get($user_record['uid'], 'system', 'mobile_theme'),
			'authenticated' => 1,
			'page_flags'    => $user_record['page-flags'],
			'my_url'        => $a->getBaseURL() . '/profile/' . $user_record['nickname'],
			'my_address'    => $user_record['nickname'] . '@' . substr($a->getBaseURL(), strpos($a->getBaseURL(), '://') + 3),
			'addr'          => ($_SERVER['REMOTE_ADDR'] ?? '') ?: '0.0.0.0'
		]);

		self::setVisitorsContacts();

		$member_since = strtotime($user_record['register_date']);
		self::set('new_member', time() < ($member_since + ( 60 * 60 * 24 * 14)));

		if (strlen($user_record['timezone'])) {
			date_default_timezone_set($user_record['timezone']);
			$a->timezone = $user_record['timezone'];
		}

		$masterUid = $user_record['uid'];

		if (self::get('submanage')) {
			$user = DBA::selectFirst('user', ['uid'], ['uid' => self::get('submanage')]);
			if (DBA::isResult($user)) {
				$masterUid = $user['uid'];
			}
		}

		$a->identities = User::identities($masterUid);

		if ($login_initial) {
			$a->getLogger()->info('auth_identities: ' . print_r($a->identities, true));
		}

		if ($login_refresh) {
			$a->getLogger()->info('auth_identities refresh: ' . print_r($a->identities, true));
		}

		$contact = DBA::selectFirst('contact', [], ['uid' => $user_record['uid'], 'self' => true]);
		if (DBA::isResult($contact)) {
			$a->contact = $contact;
			$a->cid = $contact['id'];
			self::set('cid', $a->cid);
		}

		header('X-Account-Management-Status: active; name="' . $user_record['username'] . '"; id="' . $user_record['nickname'] . '"');

		if ($login_initial || $login_refresh) {
			DBA::update('user', ['login_date' => DateTimeFormat::utcNow()], ['uid' => $user_record['uid']]);

			// Set the login date for all identities of the user
			DBA::update('user', ['login_date' => DateTimeFormat::utcNow()],
				['parent-uid' => $masterUid, 'account_removed' => false]);
		}

		if ($login_initial) {
			/*
			 * If the user specified to remember the authentication, then set a cookie
			 * that expires after one week (the default is when the browser is closed).
			 * The cookie will be renewed automatically.
			 * The week ensures that sessions will expire after some inactivity.
			 */
			;
			if (self::get('remember')) {
				$a->getLogger()->info('Injecting cookie for remembered user ' . $user_record['nickname']);
				Authentication::setCookie(604800, $user_record);
				self::remove('remember');
			}
		}

		Authentication::twoFactorCheck($user_record['uid'], $a);

		if ($interactive) {
			if ($user_record['login_date'] <= DBA::NULL_DATETIME) {
				info(L10n::t('Welcome %s', $user_record['username']));
				info(L10n::t('Please upload a profile photo.'));
				$a->internalRedirect('settings/profile/photo/new');
			} else {
				info(L10n::t("Welcome back %s", $user_record['username']));
			}
		}

		$a->user = $user_record;

		if ($login_initial) {
			Hook::callAll('logged_in', $a->user);

			if ($a->module !== 'home' && self::exists('return_path')) {
				$a->internalRedirect(self::get('return_path'));
			}
		}
	}

	/**
	 * Returns contact ID for given user ID
	 *
	 * @param integer $uid User ID
	 * @return integer Contact ID of visitor for given user ID
	 */
	public static function getRemoteContactID($uid)
	{
		if (empty($_SESSION['remote'][$uid])) {
			return 0;
		}

		return $_SESSION['remote'][$uid];
	}

	/**
	 * Returns User ID for given contact ID of the visitor
	 *
	 * @param integer $cid Contact ID
	 * @return integer User ID for given contact ID of the visitor
	 */
	public static function getUserIDForVisitorContactID($cid)
	{
		if (empty($_SESSION['remote'])) {
			return false;
		}

		return array_search($cid, $_SESSION['remote']);
	}

	/**
	 * Set the session variable that contains the contact IDs for the visitor's contact URL
	 *
	 * @param string $url Contact URL
	 */
	public static function setVisitorsContacts()
	{
		$_SESSION['remote'] = [];

		$remote_contacts = DBA::select('contact', ['id', 'uid'], ['nurl' => Strings::normaliseLink($_SESSION['my_url']), 'rel' => [Contact::FOLLOWER, Contact::FRIEND], 'self' => false]);
		while ($contact = DBA::fetch($remote_contacts)) {
			if (($contact['uid'] == 0) || Contact::isBlockedByUser($contact['id'], $contact['uid'])) {
				continue;
			}

			$_SESSION['remote'][$contact['uid']] = $contact['id'];
		}
		DBA::close($remote_contacts);
	}

	/**
	 * Returns if the current visitor is authenticated
	 *
	 * @return boolean "true" when visitor is either a local or remote user
	 */
	public static function isAuthenticated()
	{
		if (empty($_SESSION['authenticated'])) {
			return false;
		}

		return $_SESSION['authenticated'];
	}
}
