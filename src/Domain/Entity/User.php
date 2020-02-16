<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table user
 *
 * The local users
 */
class User extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $uid;

	/**
	 * @var int
	 * The parent user that has full control about this user
	 */
	private $parentUid = '0';

	/**
	 * @var string
	 * A unique identifier for this user
	 */
	private $guid = '';

	/**
	 * @var string
	 * Name that this user is known by
	 */
	private $username = '';

	/**
	 * @var string
	 * encrypted password
	 */
	private $password = '';

	/**
	 * @var bool
	 * Is the password hash double-hashed?
	 */
	private $legacyPassword = '0';

	/**
	 * @var string
	 * nick- and user name
	 */
	private $nickname = '';

	/**
	 * @var string
	 * the users email address
	 */
	private $email = '';

	/**
	 * @var string
	 */
	private $openid = '';

	/**
	 * @var string
	 * PHP-legal timezone
	 */
	private $timezone = '';

	/**
	 * @var string
	 * default language
	 */
	private $language = 'en';

	/**
	 * @var string
	 * timestamp of registration
	 */
	private $registerDate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * timestamp of last login
	 */
	private $loginDate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Default for item.location
	 */
	private $defaultLocation = '';

	/**
	 * @var bool
	 * 1 allows to display the location
	 */
	private $allowLocation = '0';

	/**
	 * @var string
	 * user theme preference
	 */
	private $theme = '';

	/**
	 * @var string
	 * RSA public key 4096 bit
	 */
	private $pubkey;

	/**
	 * @var string
	 * RSA private key 4096 bit
	 */
	private $prvkey;

	/**
	 * @var string
	 */
	private $spubkey;

	/**
	 * @var string
	 */
	private $sprvkey;

	/**
	 * @var bool
	 * user is verified through email
	 */
	private $verified = '0';

	/**
	 * @var bool
	 * 1 for user is blocked
	 */
	private $blocked = '0';

	/**
	 * @var bool
	 * Prohibit contacts to post to the profile page of the user
	 */
	private $blockwall = '0';

	/**
	 * @var bool
	 * Hide profile details from unkown viewers
	 */
	private $hidewall = '0';

	/**
	 * @var bool
	 * Prohibit contacts to tag the post of this user
	 */
	private $blocktags = '0';

	/**
	 * @var bool
	 * Permit unknown people to send private mails to this user
	 */
	private $unkmail = '0';

	/**
	 * @var int
	 */
	private $cntunkmail = '10';

	/**
	 * @var string
	 * email notification options
	 */
	private $notifyFlags = '65535';

	/**
	 * @var string
	 * page/profile type
	 */
	private $pageFlags = '0';

	/**
	 * @var string
	 */
	private $accountType = '0';

	/**
	 * @var bool
	 */
	private $prvnets = '0';

	/**
	 * @var string
	 * Password reset request token
	 */
	private $pwdreset;

	/**
	 * @var string
	 * Timestamp of the last password reset request
	 */
	private $pwdresetTime;

	/**
	 * @var int
	 */
	private $maxreq = '10';

	/**
	 * @var int
	 */
	private $expire = '0';

	/**
	 * @var bool
	 * if 1 the account is removed
	 */
	private $accountRemoved = '0';

	/**
	 * @var bool
	 */
	private $accountExpired = '0';

	/**
	 * @var string
	 * timestamp when account expires and will be deleted
	 */
	private $accountExpiresOn = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * timestamp of last warning of account expiration
	 */
	private $expireNotificationSent = '0001-01-01 00:00:00';

	/**
	 * @var int
	 */
	private $defGid = '0';

	/**
	 * @var string
	 * default permission for this user
	 */
	private $allowCid;

	/**
	 * @var string
	 * default permission for this user
	 */
	private $allowGid;

	/**
	 * @var string
	 * default permission for this user
	 */
	private $denyCid;

	/**
	 * @var string
	 * default permission for this user
	 */
	private $denyGid;

	/**
	 * @var string
	 */
	private $openidserver;

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'uid' => $this->uid,
			'parent-uid' => $this->parentUid,
			'guid' => $this->guid,
			'username' => $this->username,
			'password' => $this->password,
			'legacy_password' => $this->legacyPassword,
			'nickname' => $this->nickname,
			'email' => $this->email,
			'openid' => $this->openid,
			'timezone' => $this->timezone,
			'language' => $this->language,
			'register_date' => $this->registerDate,
			'login_date' => $this->loginDate,
			'default-location' => $this->defaultLocation,
			'allow_location' => $this->allowLocation,
			'theme' => $this->theme,
			'pubkey' => $this->pubkey,
			'prvkey' => $this->prvkey,
			'spubkey' => $this->spubkey,
			'sprvkey' => $this->sprvkey,
			'verified' => $this->verified,
			'blocked' => $this->blocked,
			'blockwall' => $this->blockwall,
			'hidewall' => $this->hidewall,
			'blocktags' => $this->blocktags,
			'unkmail' => $this->unkmail,
			'cntunkmail' => $this->cntunkmail,
			'notify-flags' => $this->notifyFlags,
			'page-flags' => $this->pageFlags,
			'account-type' => $this->accountType,
			'prvnets' => $this->prvnets,
			'pwdreset' => $this->pwdreset,
			'pwdreset_time' => $this->pwdresetTime,
			'maxreq' => $this->maxreq,
			'expire' => $this->expire,
			'account_removed' => $this->accountRemoved,
			'account_expired' => $this->accountExpired,
			'account_expires_on' => $this->accountExpiresOn,
			'expire_notification_sent' => $this->expireNotificationSent,
			'def_gid' => $this->defGid,
			'allow_cid' => $this->allowCid,
			'allow_gid' => $this->allowGid,
			'deny_cid' => $this->denyCid,
			'deny_gid' => $this->denyGid,
			'openidserver' => $this->openidserver,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @return int
	 * Get The parent user that has full control about this user
	 */
	public function getParentUid()
	{
		return $this->parentUid;
	}

	/**
	 * @param int $parentUid
	 * Set The parent user that has full control about this user
	 */
	public function setParentUid(int $parentUid)
	{
		$this->parentUid = $parentUid;
	}

	/**
	 * Get User
	 *
	 * @return User
	 */
	public function getUser()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for uid is not implemented yet');
	}

	/**
	 * @return string
	 * Get A unique identifier for this user
	 */
	public function getGuid()
	{
		return $this->guid;
	}

	/**
	 * @param string $guid
	 * Set A unique identifier for this user
	 */
	public function setGuid(string $guid)
	{
		$this->guid = $guid;
	}

	/**
	 * @return string
	 * Get Name that this user is known by
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * @param string $username
	 * Set Name that this user is known by
	 */
	public function setUsername(string $username)
	{
		$this->username = $username;
	}

	/**
	 * @return string
	 * Get encrypted password
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param string $password
	 * Set encrypted password
	 */
	public function setPassword(string $password)
	{
		$this->password = $password;
	}

	/**
	 * @return bool
	 * Get Is the password hash double-hashed?
	 */
	public function getLegacyPassword()
	{
		return $this->legacyPassword;
	}

	/**
	 * @param bool $legacyPassword
	 * Set Is the password hash double-hashed?
	 */
	public function setLegacyPassword(bool $legacyPassword)
	{
		$this->legacyPassword = $legacyPassword;
	}

	/**
	 * @return string
	 * Get nick- and user name
	 */
	public function getNickname()
	{
		return $this->nickname;
	}

	/**
	 * @param string $nickname
	 * Set nick- and user name
	 */
	public function setNickname(string $nickname)
	{
		$this->nickname = $nickname;
	}

	/**
	 * @return string
	 * Get the users email address
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param string $email
	 * Set the users email address
	 */
	public function setEmail(string $email)
	{
		$this->email = $email;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getOpenid()
	{
		return $this->openid;
	}

	/**
	 * @param string $openid
	 * Set
	 */
	public function setOpenid(string $openid)
	{
		$this->openid = $openid;
	}

	/**
	 * @return string
	 * Get PHP-legal timezone
	 */
	public function getTimezone()
	{
		return $this->timezone;
	}

	/**
	 * @param string $timezone
	 * Set PHP-legal timezone
	 */
	public function setTimezone(string $timezone)
	{
		$this->timezone = $timezone;
	}

	/**
	 * @return string
	 * Get default language
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @param string $language
	 * Set default language
	 */
	public function setLanguage(string $language)
	{
		$this->language = $language;
	}

	/**
	 * @return string
	 * Get timestamp of registration
	 */
	public function getRegisterDate()
	{
		return $this->registerDate;
	}

	/**
	 * @param string $registerDate
	 * Set timestamp of registration
	 */
	public function setRegisterDate(string $registerDate)
	{
		$this->registerDate = $registerDate;
	}

	/**
	 * @return string
	 * Get timestamp of last login
	 */
	public function getLoginDate()
	{
		return $this->loginDate;
	}

	/**
	 * @param string $loginDate
	 * Set timestamp of last login
	 */
	public function setLoginDate(string $loginDate)
	{
		$this->loginDate = $loginDate;
	}

	/**
	 * @return string
	 * Get Default for item.location
	 */
	public function getDefaultLocation()
	{
		return $this->defaultLocation;
	}

	/**
	 * @param string $defaultLocation
	 * Set Default for item.location
	 */
	public function setDefaultLocation(string $defaultLocation)
	{
		$this->defaultLocation = $defaultLocation;
	}

	/**
	 * @return bool
	 * Get 1 allows to display the location
	 */
	public function getAllowLocation()
	{
		return $this->allowLocation;
	}

	/**
	 * @param bool $allowLocation
	 * Set 1 allows to display the location
	 */
	public function setAllowLocation(bool $allowLocation)
	{
		$this->allowLocation = $allowLocation;
	}

	/**
	 * @return string
	 * Get user theme preference
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param string $theme
	 * Set user theme preference
	 */
	public function setTheme(string $theme)
	{
		$this->theme = $theme;
	}

	/**
	 * @return string
	 * Get RSA public key 4096 bit
	 */
	public function getPubkey()
	{
		return $this->pubkey;
	}

	/**
	 * @param string $pubkey
	 * Set RSA public key 4096 bit
	 */
	public function setPubkey(string $pubkey)
	{
		$this->pubkey = $pubkey;
	}

	/**
	 * @return string
	 * Get RSA private key 4096 bit
	 */
	public function getPrvkey()
	{
		return $this->prvkey;
	}

	/**
	 * @param string $prvkey
	 * Set RSA private key 4096 bit
	 */
	public function setPrvkey(string $prvkey)
	{
		$this->prvkey = $prvkey;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSpubkey()
	{
		return $this->spubkey;
	}

	/**
	 * @param string $spubkey
	 * Set
	 */
	public function setSpubkey(string $spubkey)
	{
		$this->spubkey = $spubkey;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSprvkey()
	{
		return $this->sprvkey;
	}

	/**
	 * @param string $sprvkey
	 * Set
	 */
	public function setSprvkey(string $sprvkey)
	{
		$this->sprvkey = $sprvkey;
	}

	/**
	 * @return bool
	 * Get user is verified through email
	 */
	public function getVerified()
	{
		return $this->verified;
	}

	/**
	 * @param bool $verified
	 * Set user is verified through email
	 */
	public function setVerified(bool $verified)
	{
		$this->verified = $verified;
	}

	/**
	 * @return bool
	 * Get 1 for user is blocked
	 */
	public function getBlocked()
	{
		return $this->blocked;
	}

	/**
	 * @param bool $blocked
	 * Set 1 for user is blocked
	 */
	public function setBlocked(bool $blocked)
	{
		$this->blocked = $blocked;
	}

	/**
	 * @return bool
	 * Get Prohibit contacts to post to the profile page of the user
	 */
	public function getBlockwall()
	{
		return $this->blockwall;
	}

	/**
	 * @param bool $blockwall
	 * Set Prohibit contacts to post to the profile page of the user
	 */
	public function setBlockwall(bool $blockwall)
	{
		$this->blockwall = $blockwall;
	}

	/**
	 * @return bool
	 * Get Hide profile details from unkown viewers
	 */
	public function getHidewall()
	{
		return $this->hidewall;
	}

	/**
	 * @param bool $hidewall
	 * Set Hide profile details from unkown viewers
	 */
	public function setHidewall(bool $hidewall)
	{
		$this->hidewall = $hidewall;
	}

	/**
	 * @return bool
	 * Get Prohibit contacts to tag the post of this user
	 */
	public function getBlocktags()
	{
		return $this->blocktags;
	}

	/**
	 * @param bool $blocktags
	 * Set Prohibit contacts to tag the post of this user
	 */
	public function setBlocktags(bool $blocktags)
	{
		$this->blocktags = $blocktags;
	}

	/**
	 * @return bool
	 * Get Permit unknown people to send private mails to this user
	 */
	public function getUnkmail()
	{
		return $this->unkmail;
	}

	/**
	 * @param bool $unkmail
	 * Set Permit unknown people to send private mails to this user
	 */
	public function setUnkmail(bool $unkmail)
	{
		$this->unkmail = $unkmail;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getCntunkmail()
	{
		return $this->cntunkmail;
	}

	/**
	 * @param int $cntunkmail
	 * Set
	 */
	public function setCntunkmail(int $cntunkmail)
	{
		$this->cntunkmail = $cntunkmail;
	}

	/**
	 * @return string
	 * Get email notification options
	 */
	public function getNotifyFlags()
	{
		return $this->notifyFlags;
	}

	/**
	 * @param string $notifyFlags
	 * Set email notification options
	 */
	public function setNotifyFlags(string $notifyFlags)
	{
		$this->notifyFlags = $notifyFlags;
	}

	/**
	 * @return string
	 * Get page/profile type
	 */
	public function getPageFlags()
	{
		return $this->pageFlags;
	}

	/**
	 * @param string $pageFlags
	 * Set page/profile type
	 */
	public function setPageFlags(string $pageFlags)
	{
		$this->pageFlags = $pageFlags;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAccountType()
	{
		return $this->accountType;
	}

	/**
	 * @param string $accountType
	 * Set
	 */
	public function setAccountType(string $accountType)
	{
		$this->accountType = $accountType;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getPrvnets()
	{
		return $this->prvnets;
	}

	/**
	 * @param bool $prvnets
	 * Set
	 */
	public function setPrvnets(bool $prvnets)
	{
		$this->prvnets = $prvnets;
	}

	/**
	 * @return string
	 * Get Password reset request token
	 */
	public function getPwdreset()
	{
		return $this->pwdreset;
	}

	/**
	 * @param string $pwdreset
	 * Set Password reset request token
	 */
	public function setPwdreset(string $pwdreset)
	{
		$this->pwdreset = $pwdreset;
	}

	/**
	 * @return string
	 * Get Timestamp of the last password reset request
	 */
	public function getPwdresetTime()
	{
		return $this->pwdresetTime;
	}

	/**
	 * @param string $pwdresetTime
	 * Set Timestamp of the last password reset request
	 */
	public function setPwdresetTime(string $pwdresetTime)
	{
		$this->pwdresetTime = $pwdresetTime;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getMaxreq()
	{
		return $this->maxreq;
	}

	/**
	 * @param int $maxreq
	 * Set
	 */
	public function setMaxreq(int $maxreq)
	{
		$this->maxreq = $maxreq;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getExpire()
	{
		return $this->expire;
	}

	/**
	 * @param int $expire
	 * Set
	 */
	public function setExpire(int $expire)
	{
		$this->expire = $expire;
	}

	/**
	 * @return bool
	 * Get if 1 the account is removed
	 */
	public function getAccountRemoved()
	{
		return $this->accountRemoved;
	}

	/**
	 * @param bool $accountRemoved
	 * Set if 1 the account is removed
	 */
	public function setAccountRemoved(bool $accountRemoved)
	{
		$this->accountRemoved = $accountRemoved;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getAccountExpired()
	{
		return $this->accountExpired;
	}

	/**
	 * @param bool $accountExpired
	 * Set
	 */
	public function setAccountExpired(bool $accountExpired)
	{
		$this->accountExpired = $accountExpired;
	}

	/**
	 * @return string
	 * Get timestamp when account expires and will be deleted
	 */
	public function getAccountExpiresOn()
	{
		return $this->accountExpiresOn;
	}

	/**
	 * @param string $accountExpiresOn
	 * Set timestamp when account expires and will be deleted
	 */
	public function setAccountExpiresOn(string $accountExpiresOn)
	{
		$this->accountExpiresOn = $accountExpiresOn;
	}

	/**
	 * @return string
	 * Get timestamp of last warning of account expiration
	 */
	public function getExpireNotificationSent()
	{
		return $this->expireNotificationSent;
	}

	/**
	 * @param string $expireNotificationSent
	 * Set timestamp of last warning of account expiration
	 */
	public function setExpireNotificationSent(string $expireNotificationSent)
	{
		$this->expireNotificationSent = $expireNotificationSent;
	}

	/**
	 * @return int
	 * Get
	 */
	public function getDefGid()
	{
		return $this->defGid;
	}

	/**
	 * @param int $defGid
	 * Set
	 */
	public function setDefGid(int $defGid)
	{
		$this->defGid = $defGid;
	}

	/**
	 * @return string
	 * Get default permission for this user
	 */
	public function getAllowCid()
	{
		return $this->allowCid;
	}

	/**
	 * @param string $allowCid
	 * Set default permission for this user
	 */
	public function setAllowCid(string $allowCid)
	{
		$this->allowCid = $allowCid;
	}

	/**
	 * @return string
	 * Get default permission for this user
	 */
	public function getAllowGid()
	{
		return $this->allowGid;
	}

	/**
	 * @param string $allowGid
	 * Set default permission for this user
	 */
	public function setAllowGid(string $allowGid)
	{
		$this->allowGid = $allowGid;
	}

	/**
	 * @return string
	 * Get default permission for this user
	 */
	public function getDenyCid()
	{
		return $this->denyCid;
	}

	/**
	 * @param string $denyCid
	 * Set default permission for this user
	 */
	public function setDenyCid(string $denyCid)
	{
		$this->denyCid = $denyCid;
	}

	/**
	 * @return string
	 * Get default permission for this user
	 */
	public function getDenyGid()
	{
		return $this->denyGid;
	}

	/**
	 * @param string $denyGid
	 * Set default permission for this user
	 */
	public function setDenyGid(string $denyGid)
	{
		$this->denyGid = $denyGid;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getOpenidserver()
	{
		return $this->openidserver;
	}

	/**
	 * @param string $openidserver
	 * Set
	 */
	public function setOpenidserver(string $openidserver)
	{
		$this->openidserver = $openidserver;
	}
}
