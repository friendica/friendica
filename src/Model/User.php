<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model;

use DivineOmega\DOFileCachePSR6\CacheItemPool;
use DivineOmega\PasswordExposed;
use ErrorException;
use Exception;
use Friendica\Content\Pager;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Security\TwoFactor\Model\AppSpecificPassword;
use Friendica\Network\HTTPException;
use Friendica\Object\Image;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;
use ImagickException;
use LightOpenID;

/**
 * This class handles User related functions
 */
class User
{
	/**
	 * Page/profile types
	 *
	 * PAGE_FLAGS_NORMAL is a typical personal profile account
	 * PAGE_FLAGS_SOAPBOX automatically approves all friend requests as Contact::SHARING, (readonly)
	 * PAGE_FLAGS_COMMUNITY automatically approves all friend requests as Contact::SHARING, but with
	 *      write access to wall and comments (no email and not included in page owner's ACL lists)
	 * PAGE_FLAGS_FREELOVE automatically approves all friend requests as full friends (Contact::FRIEND).
	 *
	 * @{
	 */
	const PAGE_FLAGS_NORMAL    = 0;
	const PAGE_FLAGS_SOAPBOX   = 1;
	const PAGE_FLAGS_COMMUNITY = 2;
	const PAGE_FLAGS_FREELOVE  = 3;
	const PAGE_FLAGS_BLOG      = 4;
	const PAGE_FLAGS_PRVGROUP  = 5;
	/**
	 * @}
	 */

	/**
	 * Account types
	 *
	 * ACCOUNT_TYPE_PERSON - the account belongs to a person
	 *	Associated page types: PAGE_FLAGS_NORMAL, PAGE_FLAGS_SOAPBOX, PAGE_FLAGS_FREELOVE
	 *
	 * ACCOUNT_TYPE_ORGANISATION - the account belongs to an organisation
	 *	Associated page type: PAGE_FLAGS_SOAPBOX
	 *
	 * ACCOUNT_TYPE_NEWS - the account is a news reflector
	 *	Associated page type: PAGE_FLAGS_SOAPBOX
	 *
	 * ACCOUNT_TYPE_COMMUNITY - the account is community forum
	 *	Associated page types: PAGE_COMMUNITY, PAGE_FLAGS_PRVGROUP
	 *
	 * ACCOUNT_TYPE_RELAY - the account is a relay
	 *      This will only be assigned to contacts, not to user accounts
	 * @{
	 */
	const ACCOUNT_TYPE_PERSON =       0;
	const ACCOUNT_TYPE_ORGANISATION = 1;
	const ACCOUNT_TYPE_NEWS =         2;
	const ACCOUNT_TYPE_COMMUNITY =    3;
	const ACCOUNT_TYPE_RELAY =        4;
	const ACCOUNT_TYPE_DELETED =    127;
	/**
	 * @}
	 */

	private static $owner;

	/**
	 * Returns the numeric account type by their string
	 *
	 * @param string $accounttype as string constant
	 * @return int|null Numeric account type - or null when not set
	 */
	public static function getAccountTypeByString(string $accounttype)
	{
		switch ($accounttype) {
			case 'person':
				return User::ACCOUNT_TYPE_PERSON;
			case 'organisation':
				return User::ACCOUNT_TYPE_ORGANISATION;
			case 'news':
				return User::ACCOUNT_TYPE_NEWS;
			case 'community':
				return User::ACCOUNT_TYPE_COMMUNITY;
			default:
				return null;
			break;
		}
	}

	/**
	 * Fetch the system account
	 *
	 * @return array system account
	 */
	public static function getSystemAccount()
	{
		$system = Contact::selectFirst([], ['self' => true, 'uid' => 0]);
		if (!DBA::isResult($system)) {
			self::createSystemAccount();
			$system = Contact::selectFirst([], ['self' => true, 'uid' => 0]);
			if (!DBA::isResult($system)) {
				return [];
			}
		}

		$system['sprvkey'] = $system['uprvkey'] = $system['prvkey'];
		$system['spubkey'] = $system['upubkey'] = $system['pubkey'];
		$system['nickname'] = $system['nick'];
		$system['page-flags'] = User::PAGE_FLAGS_SOAPBOX;
		$system['account-type'] = $system['contact-type'];
		$system['guid'] = '';
		$system['picdate'] = '';
		$system['theme'] = '';
		$system['publish'] = false;
		$system['net-publish'] = false;
		$system['hide-friends'] = true;
		$system['prv_keywords'] = '';
		$system['pub_keywords'] = '';
		$system['address'] = '';
		$system['locality'] = '';
		$system['region'] = '';
		$system['postal-code'] = '';
		$system['country-name'] = '';
		$system['homepage'] = DI::baseUrl()->get();
		$system['dob'] = '0000-00-00';

		// Ensure that the user contains data
		$user = DBA::selectFirst('user', ['prvkey', 'guid'], ['uid' => 0]);
		if (empty($user['prvkey']) || empty($user['guid'])) {
			$fields = [
				'username' => $system['name'],
				'nickname' => $system['nick'],
				'register_date' => $system['created'],
				'pubkey' => $system['pubkey'],
				'prvkey' => $system['prvkey'],
				'spubkey' => $system['spubkey'],
				'sprvkey' => $system['sprvkey'],
				'guid' => System::createUUID(),
				'verified' => true,
				'page-flags' => User::PAGE_FLAGS_SOAPBOX,
				'account-type' => User::ACCOUNT_TYPE_RELAY,
			];

			DBA::update('user', $fields, ['uid' => 0]);

			$system['guid'] = $fields['guid'];
		} else {
			$system['guid'] = $user['guid'];
		}

		return $system;
	}

	/**
	 * Create the system account
	 *
	 * @return void
	 */
	private static function createSystemAccount()
	{
		$system_actor_name = self::getActorName();
		if (empty($system_actor_name)) {
			return;
		}

		$keys = Crypto::newKeypair(4096);
		if ($keys === false) {
			throw new Exception(DI::l10n()->t('SERIOUS ERROR: Generation of security keys failed.'));
		}

		$system = [];
		$system['uid'] = 0;
		$system['created'] = DateTimeFormat::utcNow();
		$system['self'] = true;
		$system['network'] = Protocol::ACTIVITYPUB;
		$system['name'] = 'System Account';
		$system['addr'] = $system_actor_name . '@' . DI::baseUrl()->getHostname();
		$system['nick'] = $system_actor_name;
		$system['url'] = DI::baseUrl() . '/friendica';

		$system['avatar'] = $system['photo'] = Contact::getDefaultAvatar($system, Proxy::SIZE_SMALL);
		$system['thumb'] = Contact::getDefaultAvatar($system, Proxy::SIZE_THUMB);
		$system['micro'] = Contact::getDefaultAvatar($system, Proxy::SIZE_MICRO);

		$system['nurl'] = Strings::normaliseLink($system['url']);
		$system['pubkey'] = $keys['pubkey'];
		$system['prvkey'] = $keys['prvkey'];
		$system['blocked'] = 0;
		$system['pending'] = 0;
		$system['contact-type'] = Contact::TYPE_RELAY; // In AP this is translated to 'Application'
		$system['name-date'] = DateTimeFormat::utcNow();
		$system['uri-date'] = DateTimeFormat::utcNow();
		$system['avatar-date'] = DateTimeFormat::utcNow();
		$system['closeness'] = 0;
		$system['baseurl'] = DI::baseUrl();
		$system['gsid'] = GServer::getID($system['baseurl']);
		DBA::insert('contact', $system);
	}

	/**
	 * Detect a usable actor name
	 *
	 * @return string actor account name
	 */
	public static function getActorName()
	{
		$system_actor_name = DI::config()->get('system', 'actor_name');
		if (!empty($system_actor_name)) {
			$self = Contact::selectFirst(['nick'], ['uid' => 0, 'self' => true]);
			if (!empty($self['nick'])) {
				if ($self['nick'] != $system_actor_name) {
					// Reset the actor name to the already used name
					DI::config()->set('system', 'actor_name', $self['nick']);
					$system_actor_name = $self['nick'];
				}
			}
			return $system_actor_name;
		}

		// List of possible actor names
		$possible_accounts = ['friendica', 'actor', 'system', 'internal'];
		foreach ($possible_accounts as $name) {
			if (!DBA::exists('user', ['nickname' => $name, 'account_removed' => false, 'expire' => false]) &&
				!DBA::exists('userd', ['username' => $name])) {
				DI::config()->set('system', 'actor_name', $name);
				return $name;
			}
		}
		return '';
	}

	/**
	 * Returns true if a user record exists with the provided id
	 *
	 * @param  integer $uid
	 * @return boolean
	 * @throws Exception
	 */
	public static function exists($uid)
	{
		return DBA::exists('user', ['uid' => $uid]);
	}

	/**
	 * @param  integer       $uid
	 * @param array          $fields
	 * @return array|boolean User record if it exists, false otherwise
	 * @throws Exception
	 */
	public static function getById($uid, array $fields = [])
	{
		return !empty($uid) ? DBA::selectFirst('user', $fields, ['uid' => $uid]) : [];
	}

	/**
	 * Returns a user record based on it's GUID
	 *
	 * @param string $guid   The guid of the user
	 * @param array  $fields The fields to retrieve
	 * @param bool   $active True, if only active records are searched
	 *
	 * @return array|boolean User record if it exists, false otherwise
	 * @throws Exception
	 */
	public static function getByGuid(string $guid, array $fields = [], bool $active = true)
	{
		if ($active) {
			$cond = ['guid' => $guid, 'account_expired' => false, 'account_removed' => false];
		} else {
			$cond = ['guid' => $guid];
		}

		return DBA::selectFirst('user', $fields, $cond);
	}

	/**
	 * @param  string        $nickname
	 * @param array          $fields
	 * @return array|boolean User record if it exists, false otherwise
	 * @throws Exception
	 */
	public static function getByNickname($nickname, array $fields = [])
	{
		return DBA::selectFirst('user', $fields, ['nickname' => $nickname]);
	}

	/**
	 * Returns the user id of a given profile URL
	 *
	 * @param string $url
	 *
	 * @return integer user id
	 * @throws Exception
	 */
	public static function getIdForURL(string $url)
	{
		// Avoid database queries when the local node hostname isn't even part of the url.
		if (!Contact::isLocal($url)) {
			return 0;
		}

		$self = Contact::selectFirst(['uid'], ['self' => true, 'nurl' => Strings::normaliseLink($url)]);
		if (!empty($self['uid'])) {
			return $self['uid'];
		}

		$self = Contact::selectFirst(['uid'], ['self' => true, 'addr' => $url]);
		if (!empty($self['uid'])) {
			return $self['uid'];
		}

		$self = Contact::selectFirst(['uid'], ['self' => true, 'alias' => [$url, Strings::normaliseLink($url)]]);
		if (!empty($self['uid'])) {
			return $self['uid'];
		}

		return 0;
	}

	/**
	 * Get a user based on its email
	 *
	 * @param string        $email
	 * @param array          $fields
	 *
	 * @return array|boolean User record if it exists, false otherwise
	 *
	 * @throws Exception
	 */
	public static function getByEmail($email, array $fields = [])
	{
		return DBA::selectFirst('user', $fields, ['email' => $email]);
	}

	/**
	 * Fetch the user array of the administrator. The first one if there are several.
	 *
	 * @param array $fields
	 * @return array user
	 */
	public static function getFirstAdmin(array $fields = [])
	{
		if (!empty(DI::config()->get('config', 'admin_nickname'))) {
			return self::getByNickname(DI::config()->get('config', 'admin_nickname'), $fields);
		} elseif (!empty(DI::config()->get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', DI::config()->get('config', 'admin_email')));
			return self::getByEmail($adminList[0], $fields);
		} else {
			return [];
		}
	}

	/**
	 * Get owner data by user id
	 *
	 * @param int     $uid
	 * @param boolean $repairMissing Repair the owner data if it's missing
	 * @return boolean|array
	 * @throws Exception
	 */
	public static function getOwnerDataById(int $uid, bool $repairMissing = true)
	{
		if ($uid == 0) {
			return self::getSystemAccount();
		}

		if (!empty(self::$owner[$uid])) {
			return self::$owner[$uid];
		}

		$owner = DBA::selectFirst('owner-view', [], ['uid' => $uid]);
		if (!DBA::isResult($owner)) {
			if (!DBA::exists('user', ['uid' => $uid]) || !$repairMissing) {
				return false;
			}
			if (!DBA::exists('profile', ['uid' => $uid])) {
				DBA::insert('profile', ['uid' => $uid]);
			}
			if (!DBA::exists('contact', ['uid' => $uid, 'self' => true])) {
				Contact::createSelfFromUserId($uid);
			}
			$owner = self::getOwnerDataById($uid, false);
		}

		if (empty($owner['nickname'])) {
			return false;
		}

		if (!$repairMissing || $owner['account_expired']) {
			return $owner;
		}

		// Check if the returned data is valid, otherwise fix it. See issue #6122

		// Check for correct url and normalised nurl
		$url = DI::baseUrl() . '/profile/' . $owner['nickname'];
		$repair = empty($owner['network']) || ($owner['url'] != $url) || ($owner['nurl'] != Strings::normaliseLink($owner['url']));

		if (!$repair) {
			// Check if "addr" is present and correct
			$addr = $owner['nickname'] . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3);
			$repair = ($addr != $owner['addr']) || empty($owner['prvkey']) || empty($owner['pubkey']);
		}

		if (!$repair) {
			// Check if the avatar field is filled and the photo directs to the correct path
			$avatar = Photo::selectFirst(['resource-id'], ['uid' => $uid, 'profile' => true]);
			if (DBA::isResult($avatar)) {
				$repair = empty($owner['avatar']) || !strpos($owner['photo'], $avatar['resource-id']);
			}
		}

		if ($repair) {
			Contact::updateSelfFromUserID($uid);
			// Return the corrected data and avoid a loop
			$owner = self::getOwnerDataById($uid, false);
		}

		self::$owner[$uid] = $owner;
		return $owner;
	}

	/**
	 * Get owner data by nick name
	 *
	 * @param int $nick
	 * @return boolean|array
	 * @throws Exception
	 */
	public static function getOwnerDataByNick($nick)
	{
		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $nick]);

		if (!DBA::isResult($user)) {
			return false;
		}

		return self::getOwnerDataById($user['uid']);
	}

	/**
	 * Returns the default group for a given user and network
	 *
	 * @param int    $uid     User id
	 * @param string $network network name
	 *
	 * @return int group id
	 * @throws Exception
	 */
	public static function getDefaultGroup($uid, $network = '')
	{
		$user = DBA::selectFirst('user', ['def_gid'], ['uid' => $uid]);
		if (DBA::isResult($user)) {
			$default_group = $user["def_gid"];
		} else {
			$default_group = 0;
		}

		return $default_group;
	}


	/**
	 * Authenticate a user with a clear text password
	 *
	 * @param mixed  $user_info
	 * @param string $password
	 * @param bool   $third_party
	 * @return int|boolean
	 * @deprecated since version 3.6
	 * @see        User::getIdFromPasswordAuthentication()
	 */
	public static function authenticate($user_info, $password, $third_party = false)
	{
		try {
			return self::getIdFromPasswordAuthentication($user_info, $password, $third_party);
		} catch (Exception $ex) {
			return false;
		}
	}

	/**
	 * Authenticate a user with a clear text password
	 *
	 * Returns the user id associated with a successful password authentication
	 *
	 * @param mixed  $user_info
	 * @param string $password
	 * @param bool   $third_party
	 * @return int User Id if authentication is successful
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\NotFoundException
	 */
	public static function getIdFromPasswordAuthentication($user_info, $password, $third_party = false)
	{
		// Addons registered with the "authenticate" hook may create the user on the
		// fly. `getAuthenticationInfo` will fail if the user doesn't exist yet. If
		// the user doesn't exist, we should give the addons a chance to create the
		// user in our database, if applicable, before re-throwing the exception if
		// they fail.
		try {
			$user = self::getAuthenticationInfo($user_info);
		} catch (Exception $e) {
			$username = (is_string($user_info) ? $user_info : $user_info['nickname'] ?? '');

			// Addons can create users, and since this 'catch' branch should only
			// execute if getAuthenticationInfo can't find an existing user, that's
			// exactly what will happen here. Creating a numeric username would create
			// abiguity with user IDs, possibly opening up an attack vector.
			// So let's be very careful about that.
			if (empty($username) || is_numeric($username)) {
				throw $e;
			}

			return self::getIdFromAuthenticateHooks($username, $password);
		}

		if ($third_party && DI::pConfig()->get($user['uid'], '2fa', 'verified')) {
			// Third-party apps can't verify two-factor authentication, we use app-specific passwords instead
			if (AppSpecificPassword::authenticateUser($user['uid'], $password)) {
				return $user['uid'];
			}
		} elseif (strpos($user['password'], '$') === false) {
			//Legacy hash that has not been replaced by a new hash yet
			if (self::hashPasswordLegacy($password) === $user['password']) {
				self::updatePasswordHashed($user['uid'], self::hashPassword($password));

				return $user['uid'];
			}
		} elseif (!empty($user['legacy_password'])) {
			//Legacy hash that has been double-hashed and not replaced by a new hash yet
			//Warning: `legacy_password` is not necessary in sync with the content of `password`
			if (password_verify(self::hashPasswordLegacy($password), $user['password'])) {
				self::updatePasswordHashed($user['uid'], self::hashPassword($password));

				return $user['uid'];
			}
		} elseif (password_verify($password, $user['password'])) {
			//New password hash
			if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
				self::updatePasswordHashed($user['uid'], self::hashPassword($password));
			}

			return $user['uid'];
		} else {
			return self::getIdFromAuthenticateHooks($user['nickname'], $password); // throws
		}

		throw new HTTPException\ForbiddenException(DI::l10n()->t('Login failed'));
	}

	/**
	 * Try to obtain a user ID via "authenticate" hook addons
	 *
	 * Returns the user id associated with a successful password authentication
	 *
	 * @param string $username
	 * @param string $password
	 * @return int User Id if authentication is successful
	 * @throws HTTPException\ForbiddenException
	 */
	public static function getIdFromAuthenticateHooks($username, $password)
	{
		$addon_auth = [
			'username'      => $username,
			'password'      => $password,
			'authenticated' => 0,
			'user_record'   => null
		];

		/*
		 * An addon indicates successful login by setting 'authenticated' to non-zero value and returning a user record
		 * Addons should never set 'authenticated' except to indicate success - as hooks may be chained
		 * and later addons should not interfere with an earlier one that succeeded.
		 */
		Hook::callAll('authenticate', $addon_auth);

		if ($addon_auth['authenticated'] && $addon_auth['user_record']) {
			return $addon_auth['user_record']['uid'];
		}

		throw new HTTPException\ForbiddenException(DI::l10n()->t('Login failed'));
	}

	/**
	 * Returns authentication info from various parameters types
	 *
	 * User info can be any of the following:
	 * - User DB object
	 * - User Id
	 * - User email or username or nickname
	 * - User array with at least the uid and the hashed password
	 *
	 * @param mixed $user_info
	 * @return array
	 * @throws HTTPException\NotFoundException
	 */
	public static function getAuthenticationInfo($user_info)
	{
		$user = null;

		if (is_object($user_info) || is_array($user_info)) {
			if (is_object($user_info)) {
				$user = (array) $user_info;
			} else {
				$user = $user_info;
			}

			if (
				!isset($user['uid'])
				|| !isset($user['password'])
				|| !isset($user['legacy_password'])
			) {
				throw new Exception(DI::l10n()->t('Not enough information to authenticate'));
			}
		} elseif (is_int($user_info) || is_string($user_info)) {
			if (is_int($user_info)) {
				$user = DBA::selectFirst(
					'user',
					['uid', 'nickname', 'password', 'legacy_password'],
					[
						'uid' => $user_info,
						'blocked' => 0,
						'account_expired' => 0,
						'account_removed' => 0,
						'verified' => 1
					]
				);
			} else {
				$fields = ['uid', 'nickname', 'password', 'legacy_password'];
				$condition = [
					"(`email` = ? OR `username` = ? OR `nickname` = ?)
					AND NOT `blocked` AND NOT `account_expired` AND NOT `account_removed` AND `verified`",
					$user_info, $user_info, $user_info
				];
				$user = DBA::selectFirst('user', $fields, $condition);
			}

			if (!DBA::isResult($user)) {
				throw new HTTPException\NotFoundException(DI::l10n()->t('User not found'));
			}
		}

		return $user;
	}

	/**
	 * Generates a human-readable random password
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function generateNewPassword()
	{
		return ucfirst(Strings::getRandomName(8)) . random_int(1000, 9999);
	}

	/**
	 * Checks if the provided plaintext password has been exposed or not
	 *
	 * @param string $password
	 * @return bool
	 * @throws Exception
	 */
	public static function isPasswordExposed($password)
	{
		$cache = new CacheItemPool();
		$cache->changeConfig([
			'cacheDirectory' => get_temppath() . '/password-exposed-cache/',
		]);

		try {
			$passwordExposedChecker = new PasswordExposed\PasswordExposedChecker(null, $cache);

			return $passwordExposedChecker->passwordExposed($password) === PasswordExposed\PasswordStatus::EXPOSED;
		} catch (Exception $e) {
			Logger::error('Password Exposed Exception: ' . $e->getMessage(), [
				'code' => $e->getCode(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			]);

			return false;
		}
	}

	/**
	 * Legacy hashing function, kept for password migration purposes
	 *
	 * @param string $password
	 * @return string
	 */
	private static function hashPasswordLegacy($password)
	{
		return hash('whirlpool', $password);
	}

	/**
	 * Global user password hashing function
	 *
	 * @param string $password
	 * @return string
	 * @throws Exception
	 */
	public static function hashPassword($password)
	{
		if (!trim($password)) {
			throw new Exception(DI::l10n()->t('Password can\'t be empty'));
		}

		return password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * Updates a user row with a new plaintext password
	 *
	 * @param int    $uid
	 * @param string $password
	 * @return bool
	 * @throws Exception
	 */
	public static function updatePassword($uid, $password)
	{
		$password = trim($password);

		if (empty($password)) {
			throw new Exception(DI::l10n()->t('Empty passwords are not allowed.'));
		}

		if (!DI::config()->get('system', 'disable_password_exposed', false) && self::isPasswordExposed($password)) {
			throw new Exception(DI::l10n()->t('The new password has been exposed in a public data dump, please choose another.'));
		}

		$allowed_characters = '!"#$%&\'()*+,-./;<=>?@[\]^_`{|}~';

		if (!preg_match('/^[a-z0-9' . preg_quote($allowed_characters, '/') . ']+$/i', $password)) {
			throw new Exception(DI::l10n()->t('The password can\'t contain accentuated letters, white spaces or colons (:)'));
		}

		return self::updatePasswordHashed($uid, self::hashPassword($password));
	}

	/**
	 * Updates a user row with a new hashed password.
	 * Empties the password reset token field just in case.
	 *
	 * @param int    $uid
	 * @param string $pasword_hashed
	 * @return bool
	 * @throws Exception
	 */
	private static function updatePasswordHashed($uid, $pasword_hashed)
	{
		$fields = [
			'password' => $pasword_hashed,
			'pwdreset' => null,
			'pwdreset_time' => null,
			'legacy_password' => false
		];
		return DBA::update('user', $fields, ['uid' => $uid]);
	}

	/**
	 * Checks if a nickname is in the list of the forbidden nicknames
	 *
	 * Check if a nickname is forbidden from registration on the node by the
	 * admin. Forbidden nicknames (e.g. role namess) can be configured in the
	 * admin panel.
	 *
	 * @param string $nickname The nickname that should be checked
	 * @return boolean True is the nickname is blocked on the node
	 */
	public static function isNicknameBlocked($nickname)
	{
		$forbidden_nicknames = DI::config()->get('system', 'forbidden_nicknames', '');
		if (!empty($forbidden_nicknames)) {
			$forbidden = explode(',', $forbidden_nicknames);
			$forbidden = array_map('trim', $forbidden);
		} else {
			$forbidden = [];
		}

		// Add the name of the internal actor to the "forbidden" list
		$actor_name = self::getActorName();
		if (!empty($actor_name)) {
			$forbidden[] = $actor_name;
		}

		if (empty($forbidden)) {
			return false;
		}

		// check if the nickname is in the list of blocked nicknames
		if (in_array(strtolower($nickname), $forbidden)) {
			return true;
		}

		// else return false
		return false;
	}

	/**
	 * Catch-all user creation function
	 *
	 * Creates a user from the provided data array, either form fields or OpenID.
	 * Required: { username, nickname, email } or { openid_url }
	 *
	 * Performs the following:
	 * - Sends to the OpenId auth URL (if relevant)
	 * - Creates new key pairs for crypto
	 * - Create self-contact
	 * - Create profile image
	 *
	 * @param  array $data
	 * @return array
	 * @throws ErrorException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ImagickException
	 * @throws Exception
	 */
	public static function create(array $data)
	{
		$return = ['user' => null, 'password' => ''];

		$using_invites = DI::config()->get('system', 'invitation_only');

		$invite_id  = !empty($data['invite_id'])  ? Strings::escapeTags(trim($data['invite_id']))  : '';
		$username   = !empty($data['username'])   ? Strings::escapeTags(trim($data['username']))   : '';
		$nickname   = !empty($data['nickname'])   ? Strings::escapeTags(trim($data['nickname']))   : '';
		$email      = !empty($data['email'])      ? Strings::escapeTags(trim($data['email']))      : '';
		$openid_url = !empty($data['openid_url']) ? Strings::escapeTags(trim($data['openid_url'])) : '';
		$photo      = !empty($data['photo'])      ? Strings::escapeTags(trim($data['photo']))      : '';
		$password   = !empty($data['password'])   ? trim($data['password'])           : '';
		$password1  = !empty($data['password1'])  ? trim($data['password1'])          : '';
		$confirm    = !empty($data['confirm'])    ? trim($data['confirm'])            : '';
		$blocked    = !empty($data['blocked']);
		$verified   = !empty($data['verified']);
		$language   = !empty($data['language'])   ? Strings::escapeTags(trim($data['language']))   : 'en';

		$netpublish = $publish = !empty($data['profile_publish_reg']);

		if ($password1 != $confirm) {
			throw new Exception(DI::l10n()->t('Passwords do not match. Password unchanged.'));
		} elseif ($password1 != '') {
			$password = $password1;
		}

		if ($using_invites) {
			if (!$invite_id) {
				throw new Exception(DI::l10n()->t('An invitation is required.'));
			}

			if (!Register::existsByHash($invite_id)) {
				throw new Exception(DI::l10n()->t('Invitation could not be verified.'));
			}
		}

		/// @todo Check if this part is really needed. We should have fetched all this data in advance
		if (empty($username) || empty($email) || empty($nickname)) {
			if ($openid_url) {
				if (!Network::isUrlValid($openid_url)) {
					throw new Exception(DI::l10n()->t('Invalid OpenID url'));
				}
				$_SESSION['register'] = 1;
				$_SESSION['openid'] = $openid_url;

				$openid = new LightOpenID(DI::baseUrl()->getHostname());
				$openid->identity = $openid_url;
				$openid->returnUrl = DI::baseUrl() . '/openid';
				$openid->required = ['namePerson/friendly', 'contact/email', 'namePerson'];
				$openid->optional = ['namePerson/first', 'media/image/aspect11', 'media/image/default'];
				try {
					$authurl = $openid->authUrl();
				} catch (Exception $e) {
					throw new Exception(DI::l10n()->t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.') . EOL . EOL . DI::l10n()->t('The error message was:') . $e->getMessage(), 0, $e);
				}
				System::externalRedirect($authurl);
				// NOTREACHED
			}

			throw new Exception(DI::l10n()->t('Please enter the required information.'));
		}

		if (!Network::isUrlValid($openid_url)) {
			$openid_url = '';
		}

		// collapse multiple spaces in name
		$username = preg_replace('/ +/', ' ', $username);

		$username_min_length = max(1, min(64, intval(DI::config()->get('system', 'username_min_length', 3))));
		$username_max_length = max(1, min(64, intval(DI::config()->get('system', 'username_max_length', 48))));

		if ($username_min_length > $username_max_length) {
			Logger::log(DI::l10n()->t('system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.', $username_min_length, $username_max_length), Logger::WARNING);
			$tmp = $username_min_length;
			$username_min_length = $username_max_length;
			$username_max_length = $tmp;
		}

		if (mb_strlen($username) < $username_min_length) {
			throw new Exception(DI::l10n()->tt('Username should be at least %s character.', 'Username should be at least %s characters.', $username_min_length));
		}

		if (mb_strlen($username) > $username_max_length) {
			throw new Exception(DI::l10n()->tt('Username should be at most %s character.', 'Username should be at most %s characters.', $username_max_length));
		}

		// So now we are just looking for a space in the full name.
		$loose_reg = DI::config()->get('system', 'no_regfullname');
		if (!$loose_reg) {
			$username = mb_convert_case($username, MB_CASE_TITLE, 'UTF-8');
			if (strpos($username, ' ') === false) {
				throw new Exception(DI::l10n()->t("That doesn't appear to be your full (First Last) name."));
			}
		}

		if (!Network::isEmailDomainAllowed($email)) {
			throw new Exception(DI::l10n()->t('Your email domain is not among those allowed on this site.'));
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !Network::isEmailDomainValid($email)) {
			throw new Exception(DI::l10n()->t('Not a valid email address.'));
		}
		if (self::isNicknameBlocked($nickname)) {
			throw new Exception(DI::l10n()->t('The nickname was blocked from registration by the nodes admin.'));
		}

		if (DI::config()->get('system', 'block_extended_register', false) && DBA::exists('user', ['email' => $email])) {
			throw new Exception(DI::l10n()->t('Cannot use that email.'));
		}

		// Disallow somebody creating an account using openid that uses the admin email address,
		// since openid bypasses email verification. We'll allow it if there is not yet an admin account.
		if (DI::config()->get('config', 'admin_email') && strlen($openid_url)) {
			$adminlist = explode(',', str_replace(' ', '', strtolower(DI::config()->get('config', 'admin_email'))));
			if (in_array(strtolower($email), $adminlist)) {
				throw new Exception(DI::l10n()->t('Cannot use that email.'));
			}
		}

		$nickname = $data['nickname'] = strtolower($nickname);

		if (!preg_match('/^[a-z0-9][a-z0-9_]*$/', $nickname)) {
			throw new Exception(DI::l10n()->t('Your nickname can only contain a-z, 0-9 and _.'));
		}

		// Check existing and deleted accounts for this nickname.
		if (
			DBA::exists('user', ['nickname' => $nickname])
			|| DBA::exists('userd', ['username' => $nickname])
		) {
			throw new Exception(DI::l10n()->t('Nickname is already registered. Please choose another.'));
		}

		$new_password = strlen($password) ? $password : User::generateNewPassword();
		$new_password_encoded = self::hashPassword($new_password);

		$return['password'] = $new_password;

		$keys = Crypto::newKeypair(4096);
		if ($keys === false) {
			throw new Exception(DI::l10n()->t('SERIOUS ERROR: Generation of security keys failed.'));
		}

		$prvkey = $keys['prvkey'];
		$pubkey = $keys['pubkey'];

		// Create another keypair for signing/verifying salmon protocol messages.
		$sres = Crypto::newKeypair(512);
		$sprvkey = $sres['prvkey'];
		$spubkey = $sres['pubkey'];

		$insert_result = DBA::insert('user', [
			'guid'     => System::createUUID(),
			'username' => $username,
			'password' => $new_password_encoded,
			'email'    => $email,
			'openid'   => $openid_url,
			'nickname' => $nickname,
			'pubkey'   => $pubkey,
			'prvkey'   => $prvkey,
			'spubkey'  => $spubkey,
			'sprvkey'  => $sprvkey,
			'verified' => $verified,
			'blocked'  => $blocked,
			'language' => $language,
			'timezone' => 'UTC',
			'register_date' => DateTimeFormat::utcNow(),
			'default-location' => ''
		]);

		if ($insert_result) {
			$uid = DBA::lastInsertId();
			$user = DBA::selectFirst('user', [], ['uid' => $uid]);
		} else {
			throw new Exception(DI::l10n()->t('An error occurred during registration. Please try again.'));
		}

		if (!$uid) {
			throw new Exception(DI::l10n()->t('An error occurred during registration. Please try again.'));
		}

		// if somebody clicked submit twice very quickly, they could end up with two accounts
		// due to race condition. Remove this one.
		$user_count = DBA::count('user', ['nickname' => $nickname]);
		if ($user_count > 1) {
			DBA::delete('user', ['uid' => $uid]);

			throw new Exception(DI::l10n()->t('Nickname is already registered. Please choose another.'));
		}

		$insert_result = DBA::insert('profile', [
			'uid' => $uid,
			'name' => $username,
			'photo' => DI::baseUrl() . "/photo/profile/{$uid}.jpg",
			'thumb' => DI::baseUrl() . "/photo/avatar/{$uid}.jpg",
			'publish' => $publish,
			'net-publish' => $netpublish,
		]);
		if (!$insert_result) {
			DBA::delete('user', ['uid' => $uid]);

			throw new Exception(DI::l10n()->t('An error occurred creating your default profile. Please try again.'));
		}

		// Create the self contact
		if (!Contact::createSelfFromUserId($uid)) {
			DBA::delete('user', ['uid' => $uid]);

			throw new Exception(DI::l10n()->t('An error occurred creating your self contact. Please try again.'));
		}

		// Create a group with no members. This allows somebody to use it
		// right away as a default group for new contacts.
		$def_gid = Group::create($uid, DI::l10n()->t('Friends'));
		if (!$def_gid) {
			DBA::delete('user', ['uid' => $uid]);

			throw new Exception(DI::l10n()->t('An error occurred creating your default contact group. Please try again.'));
		}

		$fields = ['def_gid' => $def_gid];
		if (DI::config()->get('system', 'newuser_private') && $def_gid) {
			$fields['allow_gid'] = '<' . $def_gid . '>';
		}

		DBA::update('user', $fields, ['uid' => $uid]);

		// if we have no OpenID photo try to look up an avatar
		if (!strlen($photo)) {
			$photo = Network::lookupAvatarByEmail($email);
		}

		// unless there is no avatar-addon loaded
		if (strlen($photo)) {
			$photo_failure = false;

			$filename = basename($photo);
			$curlResult = DI::httpClient()->get($photo);
			if ($curlResult->isSuccess()) {
				$img_str = $curlResult->getBody();
				$type = $curlResult->getContentType();
			} else {
				$img_str = '';
				$type = '';
			}

			$type = Images::getMimeTypeByData($img_str, $photo, $type);

			$Image = new Image($img_str, $type);
			if ($Image->isValid()) {
				$Image->scaleToSquare(300);

				$resource_id = Photo::newResource();

				$r = Photo::store($Image, $uid, 0, $resource_id, $filename, DI::l10n()->t('Profile Photos'), 4);

				if ($r === false) {
					$photo_failure = true;
				}

				$Image->scaleDown(80);

				$r = Photo::store($Image, $uid, 0, $resource_id, $filename, DI::l10n()->t('Profile Photos'), 5);

				if ($r === false) {
					$photo_failure = true;
				}

				$Image->scaleDown(48);

				$r = Photo::store($Image, $uid, 0, $resource_id, $filename, DI::l10n()->t('Profile Photos'), 6);

				if ($r === false) {
					$photo_failure = true;
				}

				if (!$photo_failure) {
					Photo::update(['profile' => 1], ['resource-id' => $resource_id]);
				}
			}

			Contact::updateSelfFromUserID($uid, true);
		}

		Hook::callAll('register_account', $uid);

		$return['user'] = $user;
		return $return;
	}

	/**
	 * Update a user entry and distribute the changes if needed
	 *
	 * @param array $fields
	 * @param integer $uid
	 * @return boolean
	 */
	public static function update(array $fields, int $uid): bool
	{
		$old_owner = self::getOwnerDataById($uid);
		if (empty($old_owner)) {
			return false;
		}

		if (!DBA::update('user', $fields, ['uid' => $uid])) {
			return false;
		}

		$update = Contact::updateSelfFromUserID($uid);

		$owner = self::getOwnerDataById($uid);
		if (empty($owner)) {
			return false;
		}

		if ($old_owner['name'] != $owner['name']) {
			Profile::update(['name' => $owner['name']], $uid);
		}

		if ($update) {
			Profile::publishUpdate($uid);
		}

		return true;
	}

	/**
	 * Sets block state for a given user
	 *
	 * @param int  $uid   The user id
	 * @param bool $block Block state (default is true)
	 *
	 * @return bool True, if successfully blocked

	 * @throws Exception
	 */
	public static function block(int $uid, bool $block = true)
	{
		return DBA::update('user', ['blocked' => $block], ['uid' => $uid]);
	}

	/**
	 * Allows a registration based on a hash
	 *
	 * @param string $hash
	 *
	 * @return bool True, if the allow was successful
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws Exception
	 */
	public static function allow(string $hash)
	{
		$register = Register::getByHash($hash);
		if (!DBA::isResult($register)) {
			return false;
		}

		$user = User::getById($register['uid']);
		if (!DBA::isResult($user)) {
			return false;
		}

		Register::deleteByHash($hash);

		DBA::update('user', ['blocked' => false, 'verified' => true], ['uid' => $register['uid']]);

		$profile = DBA::selectFirst('profile', ['net-publish'], ['uid' => $register['uid']]);

		if (DBA::isResult($profile) && $profile['net-publish'] && DI::config()->get('system', 'directory')) {
			$url = DI::baseUrl() . '/profile/' . $user['nickname'];
			Worker::add(PRIORITY_LOW, "Directory", $url);
		}

		$l10n = DI::l10n()->withLang($register['language']);

		return User::sendRegisterOpenEmail(
			$l10n,
			$user,
			DI::config()->get('config', 'sitename'),
			DI::baseUrl()->get(),
			($register['password'] ?? '') ?: 'Sent in a previous email'
		);
	}

	/**
	 * Denys a pending registration
	 *
	 * @param string $hash The hash of the pending user
	 *
	 * This does not have to go through user_remove() and save the nickname
	 * permanently against re-registration, as the person was not yet
	 * allowed to have friends on this system
	 *
	 * @return bool True, if the deny was successfull
	 * @throws Exception
	 */
	public static function deny(string $hash)
	{
		$register = Register::getByHash($hash);
		if (!DBA::isResult($register)) {
			return false;
		}

		$user = User::getById($register['uid']);
		if (!DBA::isResult($user)) {
			return false;
		}

		// Delete the avatar
		Photo::delete(['uid' => $register['uid']]);

		return DBA::delete('user', ['uid' => $register['uid']]) &&
		       Register::deleteByHash($register['hash']);
	}

	/**
	 * Creates a new user based on a minimal set and sends an email to this user
	 *
	 * @param string $name  The user's name
	 * @param string $email The user's email address
	 * @param string $nick  The user's nick name
	 * @param string $lang  The user's language (default is english)
	 *
	 * @return bool True, if the user was created successfully
	 * @throws HTTPException\InternalServerErrorException
	 * @throws ErrorException
	 * @throws ImagickException
	 */
	public static function createMinimal(string $name, string $email, string $nick, string $lang = L10n::DEFAULT)
	{
		if (empty($name) ||
		    empty($email) ||
		    empty($nick)) {
			throw new HTTPException\InternalServerErrorException('Invalid arguments.');
		}

		$result = self::create([
			'username' => $name,
			'email' => $email,
			'nickname' => $nick,
			'verified' => 1,
			'language' => $lang
		]);

		$user = $result['user'];
		$preamble = Strings::deindent(DI::l10n()->t('
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'));
		$body = Strings::deindent(DI::l10n()->t('
		The login details are as follows:

		Site Location:	%1$s
		Login Name:		%2$s
		Password:		%3$s

		You may change your password from your account "Settings" page after logging
		in.

		Please take a few moments to review the other account settings on that page.

		You may also wish to add some basic information to your default profile
		(on the "Profiles" page) so that other people can easily find you.

		We recommend setting your full name, adding a profile photo,
		adding some profile "keywords" (very useful in making new friends) - and
		perhaps what country you live in; if you do not wish to be more specific
		than that.

		We fully respect your right to privacy, and none of these items are necessary.
		If you are new and do not know anybody here, they may help
		you to make some new and interesting friends.

		If you ever want to delete your account, you can do so at %1$s/removeme

		Thank you and welcome to %4$s.'));

		$preamble = sprintf($preamble, $user['username'], DI::config()->get('config', 'sitename'));
		$body = sprintf($body, DI::baseUrl()->get(), $user['nickname'], $result['password'], DI::config()->get('config', 'sitename'));

		$email = DI::emailer()
			->newSystemMail()
			->withMessage(DI::l10n()->t('Registration details for %s', DI::config()->get('config', 'sitename')), $preamble, $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();
		return DI::emailer()->send($email);
	}

	/**
	 * Sends pending registration confirmation email
	 *
	 * @param array  $user     User record array
	 * @param string $sitename
	 * @param string $siteurl
	 * @param string $password Plaintext password
	 * @return NULL|boolean from notification() and email() inherited
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function sendRegisterPendingEmail($user, $sitename, $siteurl, $password)
	{
		$body = Strings::deindent(DI::l10n()->t(
			'
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		',
			$user['username'],
			$sitename,
			$siteurl,
			$user['nickname'],
			$password
		));

		$email = DI::emailer()
			->newSystemMail()
			->withMessage(DI::l10n()->t('Registration at %s', $sitename), $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();
		return DI::emailer()->send($email);
	}

	/**
	 * Sends registration confirmation
	 *
	 * It's here as a function because the mail is sent from different parts
	 *
	 * @param L10n   $l10n     The used language
	 * @param array  $user     User record array
	 * @param string $sitename
	 * @param string $siteurl
	 * @param string $password Plaintext password
	 *
	 * @return NULL|boolean from notification() and email() inherited
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function sendRegisterOpenEmail(L10n $l10n, $user, $sitename, $siteurl, $password)
	{
		$preamble = Strings::deindent($l10n->t(
			'
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			',
			$user['username'],
			$sitename
		));
		$body = Strings::deindent($l10n->t(
			'
			The login details are as follows:

			Site Location:	%3$s
			Login Name:		%1$s
			Password:		%5$s

			You may change your password from your account "Settings" page after logging
			in.

			Please take a few moments to review the other account settings on that page.

			You may also wish to add some basic information to your default profile
			' . "\x28" . 'on the "Profiles" page' . "\x29" . ' so that other people can easily find you.

			We recommend setting your full name, adding a profile photo,
			adding some profile "keywords" ' . "\x28" . 'very useful in making new friends' . "\x29" . ' - and
			perhaps what country you live in; if you do not wish to be more specific
			than that.

			We fully respect your right to privacy, and none of these items are necessary.
			If you are new and do not know anybody here, they may help
			you to make some new and interesting friends.

			If you ever want to delete your account, you can do so at %3$s/removeme

			Thank you and welcome to %2$s.',
			$user['nickname'],
			$sitename,
			$siteurl,
			$user['username'],
			$password
		));

		$email = DI::emailer()
			->newSystemMail()
			->withMessage(DI::l10n()->t('Registration details for %s', $sitename), $preamble, $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();
		return DI::emailer()->send($email);
	}

	/**
	 * @param int $uid user to remove
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function remove(int $uid)
	{
		if (empty($uid)) {
			return false;
		}

		Logger::log('Removing user: ' . $uid);

		$user = DBA::selectFirst('user', [], ['uid' => $uid]);

		Hook::callAll('remove_user', $user);

		// save username (actually the nickname as it is guaranteed
		// unique), so it cannot be re-registered in the future.
		DBA::insert('userd', ['username' => $user['nickname']]);

		// Remove all personal settings, especially connector settings
		DBA::delete('pconfig', ['uid' => $uid]);

		// The user and related data will be deleted in Friendica\Worker\ExpireAndRemoveUsers
		DBA::update('user', ['account_removed' => true, 'account_expires_on' => DateTimeFormat::utc('now + 7 day')], ['uid' => $uid]);
		Worker::add(PRIORITY_HIGH, 'Notifier', Delivery::REMOVAL, $uid);

		// Send an update to the directory
		$self = DBA::selectFirst('contact', ['url'], ['uid' => $uid, 'self' => true]);
		Worker::add(PRIORITY_LOW, 'Directory', $self['url']);

		// Remove the user relevant data
		Worker::add(PRIORITY_NEGLIGIBLE, 'RemoveUser', $uid);

		return true;
	}

	/**
	 * Return all identities to a user
	 *
	 * @param int $uid The user id
	 * @return array All identities for this user
	 *
	 * Example for a return:
	 *    [
	 *        [
	 *            'uid' => 1,
	 *            'username' => 'maxmuster',
	 *            'nickname' => 'Max Mustermann'
	 *        ],
	 *        [
	 *            'uid' => 2,
	 *            'username' => 'johndoe',
	 *            'nickname' => 'John Doe'
	 *        ]
	 *    ]
	 * @throws Exception
	 */
	public static function identities($uid)
	{
		if (empty($uid)) {
			return [];
		}

		$identities = [];

		$user = DBA::selectFirst('user', ['uid', 'nickname', 'username', 'parent-uid'], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return $identities;
		}

		if ($user['parent-uid'] == 0) {
			// First add our own entry
			$identities = [[
				'uid' => $user['uid'],
				'username' => $user['username'],
				'nickname' => $user['nickname']
			]];

			// Then add all the children
			$r = DBA::select(
				'user',
				['uid', 'username', 'nickname'],
				['parent-uid' => $user['uid'], 'account_removed' => false]
			);
			if (DBA::isResult($r)) {
				$identities = array_merge($identities, DBA::toArray($r));
			}
		} else {
			// First entry is our parent
			$r = DBA::select(
				'user',
				['uid', 'username', 'nickname'],
				['uid' => $user['parent-uid'], 'account_removed' => false]
			);
			if (DBA::isResult($r)) {
				$identities = DBA::toArray($r);
			}

			// Then add all siblings
			$r = DBA::select(
				'user',
				['uid', 'username', 'nickname'],
				['parent-uid' => $user['parent-uid'], 'account_removed' => false]
			);
			if (DBA::isResult($r)) {
				$identities = array_merge($identities, DBA::toArray($r));
			}
		}

		$r = DBA::p(
			"SELECT `user`.`uid`, `user`.`username`, `user`.`nickname`
			FROM `manage`
			INNER JOIN `user` ON `manage`.`mid` = `user`.`uid`
			WHERE `user`.`account_removed` = 0 AND `manage`.`uid` = ?",
			$user['uid']
		);
		if (DBA::isResult($r)) {
			$identities = array_merge($identities, DBA::toArray($r));
		}

		return $identities;
	}

	/**
	 * Check if the given user id has delegations or is delegated
	 *
	 * @param int $uid 
	 * @return bool 
	 */
	public static function hasIdentities(int $uid):bool
	{
		if (empty($uid)) {
			return false;
		}

		$user = DBA::selectFirst('user', ['parent-uid'], ['uid' => $uid, 'account_removed' => false]);
		if (!DBA::isResult($user)) {
			return false;
		}

		if ($user['parent-uid'] != 0) {
			return true;
		}

		if (DBA::exists('user', ['parent-uid' => $uid, 'account_removed' => false])) {
			return true;
		}

		if (DBA::exists('manage', ['uid' => $uid])) {
			return true;
		}

		return false;
	}

	/**
	 * Returns statistical information about the current users of this node
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public static function getStatistics()
	{
		$statistics = [
			'total_users'           => 0,
			'active_users_halfyear' => 0,
			'active_users_monthly'  => 0,
			'active_users_weekly'   => 0,
		];

		$userStmt = DBA::select('owner-view', ['uid', 'login_date', 'last-item'],
			["`verified` AND `login_date` > ? AND NOT `blocked`
			AND NOT `account_removed` AND NOT `account_expired`",
			DBA::NULL_DATETIME]);
		if (!DBA::isResult($userStmt)) {
			return $statistics;
		}

		$halfyear = time() - (180 * 24 * 60 * 60);
		$month = time() - (30 * 24 * 60 * 60);
		$week = time() - (7 * 24 * 60 * 60);

		while ($user = DBA::fetch($userStmt)) {
			$statistics['total_users']++;

			if ((strtotime($user['login_date']) > $halfyear) || (strtotime($user['last-item']) > $halfyear)
			) {
				$statistics['active_users_halfyear']++;
			}

			if ((strtotime($user['login_date']) > $month) || (strtotime($user['last-item']) > $month)
			) {
				$statistics['active_users_monthly']++;
			}

			if ((strtotime($user['login_date']) > $week) || (strtotime($user['last-item']) > $week)
			) {
				$statistics['active_users_weekly']++;
			}
		}
		DBA::close($userStmt);

		return $statistics;
	}

	/**
	 * Get all users of the current node
	 *
	 * @param int    $start Start count (Default is 0)
	 * @param int    $count Count of the items per page (Default is @see Pager::ITEMS_PER_PAGE)
	 * @param string $type  The type of users, which should get (all, bocked, removed)
	 * @param string $order Order of the user list (Default is 'contact.name')
	 * @param bool   $descending Order direction (Default is ascending)
	 *
	 * @return array The list of the users
	 * @throws Exception
	 */
	public static function getList($start = 0, $count = Pager::ITEMS_PER_PAGE, $type = 'all', $order = 'name', bool $descending = false)
	{
		$param = ['limit' => [$start, $count], 'order' => [$order => $descending]];
		$condition = [];
		switch ($type) {
			case 'active':
				$condition['account_removed'] = false;
				$condition['blocked'] = false;
				break;
			case 'blocked':
				$condition['account_removed'] = false;
				$condition['blocked'] = true;
				$condition['verified'] = true;
				break;
			case 'removed':
				$condition['account_removed'] = true;
				break;
		}

		return DBA::selectToArray('owner-view', [], $condition, $param);
	}
}
