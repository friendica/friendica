<?php

/**
 * @file src/Model/User.php
 * @brief This file includes the User class with user related database functions
 */

namespace Friendica\Model;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Photo;
use Friendica\Object\Image;
use Friendica\Util\Crypto;
use dba;
use Exception;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/enotify.php';
require_once 'include/network.php';
require_once 'library/openid.php';
require_once 'include/pgettext.php';
require_once 'include/text.php';
/**
 * @brief This class handles User related functions
 */
class User
{
	/**
	 * @brief Get owner data by user id
	 *
	 * @param int $uid
	 * @return boolean|array
	 */
	public static function getOwnerDataById($uid) {
		$r = dba::fetch_first("SELECT
			`contact`.*,
			`user`.`prvkey` AS `uprvkey`,
			`user`.`timezone`,
			`user`.`nickname`,
			`user`.`sprvkey`,
			`user`.`spubkey`,
			`user`.`page-flags`,
			`user`.`account-type`,
			`user`.`prvnets`
			FROM `contact`
			INNER JOIN `user`
				ON `user`.`uid` = `contact`.`uid`
			WHERE `contact`.`uid` = ?
			AND `contact`.`self`
			LIMIT 1",
			$uid
		);
		if (!DBM::is_result($r)) {
			return false;
		}
		return $r;
	}

	/**
	 * @brief Returns the default group for a given user and network
	 *
	 * @param int $uid User id
	 * @param string $network network name
	 *
	 * @return int group id
	 */
	public static function getDefaultGroup($uid, $network = '')
	{
		$default_group = 0;

		if ($network == NETWORK_OSTATUS) {
			$default_group = PConfig::get($uid, "ostatus", "default_group");
		}

		if ($default_group != 0) {
			return $default_group;
		}

		$user = dba::selectFirst('user', ['def_gid'], ['uid' => $uid]);

		if (DBM::is_result($user)) {
			$default_group = $user["def_gid"];
		}

		return $default_group;
	}


	/**
	 * @brief Authenticate a user with a clear text password
	 *
	 * User info can be any of the following:
	 * - User DB object
	 * - User Id
	 * - User email or username or nickname
	 * - User array with at least the uid and the hashed password
	 *
	 * @param mixed $user_info
	 * @param string $password
	 * @return boolean
	 */
	public static function authenticate($user_info, $password)
	{
		if (is_object($user_info)) {
			$user = (array) $user_info;
		} elseif (is_int($user_info)) {
			$user = dba::selectFirst('user', ['uid', 'password'],
				[
					'uid' => $user_info,
					'blocked' => 0,
					'account_expired' => 0,
					'account_removed' => 0,
					'verified' => 1
				]
			);
		} elseif (is_string($user_info)) {
			$user = dba::fetch_first('SELECT `uid`, `password`
				FROM `user`
				WHERE (`email` = ? OR `username` = ? OR `nickname` = ?)
				AND `blocked` = 0
				AND `account_expired` = 0
				AND `account_removed` = 0
				AND `verified` = 1
				LIMIT 1',
				$user_info,
				$user_info,
				$user_info
			);
		} else {
			$user = $user_info;
		}

		if (!DBM::is_result($user) || !isset($user['uid']) || !isset($user['password'])) {
			return false;
		}

		$password_hashed = hash('whirlpool', $password);

		if ($password_hashed !== $user['password']) {
			return false;
		}

		return $user['uid'];
	}

	/**
	 * @brief Catch-all user creation function
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
	 * @param array $data
	 * @return string
	 * @throw Exception
	 */
	public static function create(array $data)
	{
		$a = get_app();
		$return = ['user' => null, 'password' => ''];

		$using_invites = Config::get('system', 'invitation_only');
		$num_invites   = Config::get('system', 'number_invites');

		$invite_id  = x($data, 'invite_id')  ? notags(trim($data['invite_id']))  : '';
		$username   = x($data, 'username')   ? notags(trim($data['username']))   : '';
		$nickname   = x($data, 'nickname')   ? notags(trim($data['nickname']))   : '';
		$email      = x($data, 'email')      ? notags(trim($data['email']))      : '';
		$openid_url = x($data, 'openid_url') ? notags(trim($data['openid_url'])) : '';
		$photo      = x($data, 'photo')      ? notags(trim($data['photo']))      : '';
		$password   = x($data, 'password')   ? trim($data['password'])           : '';
		$password1  = x($data, 'password1')  ? trim($data['password1'])          : '';
		$confirm    = x($data, 'confirm')    ? trim($data['confirm'])            : '';
		$blocked    = x($data, 'blocked')    ? intval($data['blocked'])          : 0;
		$verified   = x($data, 'verified')   ? intval($data['verified'])         : 0;

		$publish = x($data, 'profile_publish_reg') && intval($data['profile_publish_reg']) ? 1 : 0;
		$netpublish = strlen(Config::get('system', 'directory')) ? $publish : 0;

		if ($password1 != $confirm) {
			throw new Exception(t('Passwords do not match. Password unchanged.'));
		} elseif ($password1 != '') {
			$password = $password1;
		}

		if ($using_invites) {
			if (!$invite_id) {
				throw new Exception(t('An invitation is required.'));
			}

			if (!dba::exists('register', ['hash' => $invite_id])) {
				throw new Exception(t('Invitation could not be verified.'));
			}
		}

		if (!x($username) || !x($email) || !x($nickname)) {
			if ($openid_url) {
				if (!validate_url($openid_url)) {
					throw new Exception(t('Invalid OpenID url'));
				}
				$_SESSION['register'] = 1;
				$_SESSION['openid'] = $openid_url;

				$openid = new \LightOpenID;
				$openid->identity = $openid_url;
				$openid->returnUrl = System::baseUrl() . '/openid';
				$openid->required = ['namePerson/friendly', 'contact/email', 'namePerson'];
				$openid->optional = ['namePerson/first', 'media/image/aspect11', 'media/image/default'];
				try {
					$authurl = $openid->authUrl();
				} catch (Exception $e) {
					throw new Exception(t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.') . EOL . EOL . t('The error message was:') . $e->getMessage(), 0, $e);
				}
				goaway($authurl);
				// NOTREACHED
			}

			throw new Exception(t('Please enter the required information.'));
		}

		if (!validate_url($openid_url)) {
			$openid_url = '';
		}

		$err = '';

		// collapse multiple spaces in name
		$username = preg_replace('/ +/', ' ', $username);

		if (mb_strlen($username) > 48) {
			throw new Exception(t('Please use a shorter name.'));
		}
		if (mb_strlen($username) < 3) {
			throw new Exception(t('Name too short.'));
		}

		// So now we are just looking for a space in the full name.
		$loose_reg = Config::get('system', 'no_regfullname');
		if (!$loose_reg) {
			$username = mb_convert_case($username, MB_CASE_TITLE, 'UTF-8');
			if (!strpos($username, ' ')) {
				throw new Exception(t("That doesn't appear to be your full \x28First Last\x29 name."));
			}
		}

		if (!allowed_email($email)) {
			throw new Exception(t('Your email domain is not among those allowed on this site.'));
		}

		if (!valid_email($email) || !validate_email($email)) {
			throw new Exception(t('Not a valid email address.'));
		}

		if (dba::exists('user', ['email' => $email])) {
			throw new Exception(t('Cannot use that email.'));
		}

		// Disallow somebody creating an account using openid that uses the admin email address,
		// since openid bypasses email verification. We'll allow it if there is not yet an admin account.
		if (x($a->config, 'admin_email') && strlen($openid_url)) {
			$adminlist = explode(',', str_replace(' ', '', strtolower($a->config['admin_email'])));
			if (in_array(strtolower($email), $adminlist)) {
				throw new Exception(t('Cannot use that email.'));
			}
		}

		$nickname = $data['nickname'] = strtolower($nickname);

		if (!preg_match('/^[a-z0-9][a-z0-9\_]*$/', $nickname)) {
			throw new Exception(t('Your "nickname" can only contain "a-z", "0-9" and "_".'));
		}

		// Check existing and deleted accounts for this nickname.
		if (dba::exists('user', ['nickname' => $nickname])
			|| dba::exists('userd', ['username' => $nickname])
		) {
			throw new Exception(t('Nickname is already registered. Please choose another.'));
		}

		$new_password = strlen($password) ? $password : autoname(6) . mt_rand(100, 9999);
		$new_password_encoded = hash('whirlpool', $new_password);

		$return['password'] = $new_password;

		$keys = Crypto::newKeypair(4096);
		if ($keys === false) {
			throw new Exception(t('SERIOUS ERROR: Generation of security keys failed.'));
		}

		$prvkey = $keys['prvkey'];
		$pubkey = $keys['pubkey'];

		// Create another keypair for signing/verifying salmon protocol messages.
		$sres = Crypto::newKeypair(512);
		$sprvkey = $sres['prvkey'];
		$spubkey = $sres['pubkey'];

		$insert_result = dba::insert('user', [
			'guid'     => generate_user_guid(),
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
			'timezone' => 'UTC',
			'register_date' => datetime_convert(),
			'default-location' => ''
		]);

		if ($insert_result) {
			$uid = dba::lastInsertId();
			$user = dba::selectFirst('user', [], ['uid' => $uid]);
		} else {
			throw new Exception(t('An error occurred during registration. Please try again.'));
		}

		if (!$uid) {
			throw new Exception(t('An error occurred during registration. Please try again.'));
		}

		// if somebody clicked submit twice very quickly, they could end up with two accounts
		// due to race condition. Remove this one.
		$user_count = dba::count('user', ['nickname' => $nickname]);
		if ($user_count > 1) {
			dba::delete('user', ['uid' => $uid]);

			throw new Exception(t('Nickname is already registered. Please choose another.'));
		}

		$insert_result = dba::insert('profile', [
			'uid' => $uid,
			'name' => $username,
			'photo' => System::baseUrl() . "/photo/profile/{$uid}.jpg",
			'thumb' => System::baseUrl() . "/photo/avatar/{$uid}.jpg",
			'publish' => $publish,
			'is-default' => 1,
			'net-publish' => $netpublish,
			'profile-name' => t('default')
		]);
		if (!$insert_result) {
			dba::delete('user', ['uid' => $uid]);

			throw new Exception(t('An error occurred creating your default profile. Please try again.'));
		}

		// Create the self contact
		if (!Contact::createSelfFromUserId($uid)) {
			dba::delete('user', ['uid' => $uid]);

			throw new Exception(t('An error occurred creating your self contact. Please try again.'));
		}

		// Create a group with no members. This allows somebody to use it
		// right away as a default group for new contacts.
		$def_gid = Group::create($uid, t('Friends'));
		if (!$def_gid) {
			dba::delete('user', ['uid' => $uid]);

			throw new Exception(t('An error occurred creating your default contact group. Please try again.'));
		}

		$fields = ['def_gid' => $def_gid];
		if (Config::get('system', 'newuser_private') && $def_gid) {
			$fields['allow_gid'] = '<' . $def_gid . '>';
		}

		dba::update('user', $fields, ['uid' => $uid]);

		// if we have no OpenID photo try to look up an avatar
		if (!strlen($photo)) {
			$photo = avatar_img($email);
		}

		// unless there is no avatar-plugin loaded
		if (strlen($photo)) {
			$photo_failure = false;

			$filename = basename($photo);
			$img_str = fetch_url($photo, true);
			// guess mimetype from headers or filename
			$type = Image::guessType($photo, true);

			$Image = new Image($img_str, $type);
			if ($Image->isValid()) {
				$Image->scaleToSquare(175);

				$hash = photo_new_resource();

				$r = Photo::store($Image, $uid, 0, $hash, $filename, t('Profile Photos'), 4);

				if ($r === false) {
					$photo_failure = true;
				}

				$Image->scaleDown(80);

				$r = Photo::store($Image, $uid, 0, $hash, $filename, t('Profile Photos'), 5);

				if ($r === false) {
					$photo_failure = true;
				}

				$Image->scaleDown(48);

				$r = Photo::store($Image, $uid, 0, $hash, $filename, t('Profile Photos'), 6);

				if ($r === false) {
					$photo_failure = true;
				}

				if (!$photo_failure) {
					dba::update('photo', ['profile' => 1], ['resource-id' => $hash]);
				}
			}
		}

		Addon::callHooks('register_account', $uid);

		$return['user'] = $user;
		return $return;
	}

	/**
	 * @brief Sends pending registration confiŕmation email
	 *
	 * @param string $email
	 * @param string $sitename
	 * @param string $username
	 * @return NULL|boolean from notification() and email() inherited
	 */
	public static function sendRegisterPendingEmail($email, $sitename, $username)
	{
		$body = deindent(t('
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.
		'));

		$body = sprintf($body, $username, $sitename);

		return notification([
			'type' => SYSTEM_EMAIL,
			'to_email' => $email,
			'subject'=> sprintf( t('Registration at %s'), $sitename),
			'body' => $body]);
	}

	/**
	 * @brief Sends registration confirmation
	 *
	 * It's here as a function because the mail is sent from different parts
	 *
	 * @param string $email
	 * @param string $sitename
	 * @param string $siteurl
	 * @param string $username
	 * @param string $password
	 * @return NULL|boolean from notification() and email() inherited
	 */
	public static function sendRegisterOpenEmail($email, $sitename, $siteurl, $username, $password)
	{
		$preamble = deindent(t('
			Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
		'));
		$body = deindent(t('
			The login details are as follows:
				Site Location:	%3$s
				Login Name:	%1$s
				Password:	%5$s

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


			Thank you and welcome to %2$s.'));

		$preamble = sprintf($preamble, $username, $sitename);
		$body = sprintf($body, $email, $sitename, $siteurl, $username, $password);

		return notification([
			'type' => SYSTEM_EMAIL,
			'to_email' => $email,
			'subject'=> sprintf( t('Registration details for %s'), $sitename),
			'preamble'=> $preamble,
			'body' => $body]);
	}

	/**
	 * @param object $uid user to remove
	 * @return void
	 */
	public static function remove($uid)
	{
		if (!$uid) {
			return;
		}

		logger('Removing user: ' . $uid);

		$user = dba::selectFirst('user', [], ['uid' => $uid]);

		Addon::callHooks('remove_user', $user);

		// save username (actually the nickname as it is guaranteed
		// unique), so it cannot be re-registered in the future.
		dba::insert('userd', ['username' => $user['nickname']]);

		// The user and related data will be deleted in "cron_expire_and_remove_users" (cronjobs.php)
		dba::update('user', ['account_removed' => true, 'account_expires_on' => datetime_convert()], ['uid' => $uid]);
		Worker::add(PRIORITY_HIGH, "Notifier", "removeme", $uid);

		// Send an update to the directory
		Worker::add(PRIORITY_LOW, "Directory", $user['url']);

		if ($uid == local_user()) {
			unset($_SESSION['authenticated']);
			unset($_SESSION['uid']);
			goaway(System::baseUrl());
		}
	}
}
