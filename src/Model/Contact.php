<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\App\BaseURL;
use Friendica\Content\Pager;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Notify\Type;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * functions for interacting with a contact
 */
class Contact
{
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_NORMAL
	 */
	const PAGE_NORMAL    = User::PAGE_FLAGS_NORMAL;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_SOAPBOX
	 */
	const PAGE_SOAPBOX   = User::PAGE_FLAGS_SOAPBOX;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_COMMUNITY
	 */
	const PAGE_COMMUNITY = User::PAGE_FLAGS_COMMUNITY;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_FREELOVE
	 */
	const PAGE_FREELOVE  = User::PAGE_FLAGS_FREELOVE;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_BLOG
	 */
	const PAGE_BLOG      = User::PAGE_FLAGS_BLOG;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_PRVGROUP
	 */
	const PAGE_PRVGROUP  = User::PAGE_FLAGS_PRVGROUP;
	/**
	 * @}
	 */

	/**
	 * Account types
	 *
	 * TYPE_UNKNOWN - the account has been imported from gcontact where this is the default type value
	 *
	 * TYPE_PERSON - the account belongs to a person
	 *	Associated page types: PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE
	 *
	 * TYPE_ORGANISATION - the account belongs to an organisation
	 *	Associated page type: PAGE_SOAPBOX
	 *
	 * TYPE_NEWS - the account is a news reflector
	 *	Associated page type: PAGE_SOAPBOX
	 *
	 * TYPE_COMMUNITY - the account is community forum
	 *	Associated page types: PAGE_COMMUNITY, PAGE_PRVGROUP
	 *
	 * TYPE_RELAY - the account is a relay
	 *      This will only be assigned to contacts, not to user accounts
	 * @{
	 */
	const TYPE_UNKNOWN =     -1;
	const TYPE_PERSON =       User::ACCOUNT_TYPE_PERSON;
	const TYPE_ORGANISATION = User::ACCOUNT_TYPE_ORGANISATION;
	const TYPE_NEWS =         User::ACCOUNT_TYPE_NEWS;
	const TYPE_COMMUNITY =    User::ACCOUNT_TYPE_COMMUNITY;
	const TYPE_RELAY =        User::ACCOUNT_TYPE_RELAY;
	/**
	 * @}
	 */

	/**
	 * Contact_is
	 *
	 * Relationship types
	 * @{
	 */
	const FOLLOWER = 1;
	const SHARING  = 2;
	const FRIEND   = 3;
	/**
	 * @}
	 */

	/**
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array
	 * @throws \Exception
	 */
	public static function selectToArray(array $fields = [], array $condition = [], array $params = [])
	{
		return DBA::selectToArray('contact', $fields, $condition, $params);
	}

	/**
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array
	 * @throws \Exception
	 */
	public static function selectFirst(array $fields = [], array $condition = [], array $params = [])
	{
		$contact = DBA::selectFirst('contact', $fields, $condition, $params);

		return $contact;
	}

	/**
	 * Insert a row into the contact table
	 * Important: You can't use DBA::lastInsertId() after this call since it will be set to 0.
	 *
	 * @param array        $fields              field array
	 * @param bool         $on_duplicate_update Do an update on a duplicate entry
	 *
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public static function insert(array $fields, bool $on_duplicate_update = false)
	{
		$ret = DBA::insert('contact', $fields, $on_duplicate_update);
		$contact = DBA::selectFirst('contact', ['nurl', 'uid'], ['id' => DBA::lastInsertId()]);
		if (!DBA::isResult($contact)) {
			// Shouldn't happen
			return $ret;
		}

		// Search for duplicated contacts and get rid of them
		self::removeDuplicates($contact['nurl'], $contact['uid']);

		return $ret;
	}

	/**
	 * @param integer $id     Contact ID
	 * @param array   $fields Array of selected fields, empty for all
	 * @return array|boolean Contact record if it exists, false otherwise
	 * @throws \Exception
	 */
	public static function getById($id, $fields = [])
	{
		return DBA::selectFirst('contact', $fields, ['id' => $id]);
	}

	/**
	 * Tests if the given contact is a follower
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id a follower?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isFollower($cid, $uid)
	{
		if (self::isBlockedByUser($cid, $uid)) {
			return false;
		}

		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata['user'])) {
			return false;
		}

		$condition = ['id' => $cdata['user'], 'rel' => [self::FOLLOWER, self::FRIEND]];
		return DBA::exists('contact', $condition);
	}

	/**
	 * Tests if the given contact url is a follower
	 *
	 * @param string $url Contact URL
	 * @param int    $uid User ID
	 *
	 * @return boolean is the contact id a follower?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isFollowerByURL($url, $uid)
	{
		$cid = self::getIdForURL($url, $uid, true);

		if (empty($cid)) {
			return false;
		}

		return self::isFollower($cid, $uid);
	}

	/**
	 * Tests if the given user follow the given contact
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact url being followed?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSharing($cid, $uid)
	{
		if (self::isBlockedByUser($cid, $uid)) {
			return false;
		}

		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata['user'])) {
			return false;
		}

		$condition = ['id' => $cdata['user'], 'rel' => [self::SHARING, self::FRIEND]];
		return DBA::exists('contact', $condition);
	}

	/**
	 * Tests if the given user follow the given contact url
	 *
	 * @param string $url Contact URL
	 * @param int    $uid User ID
	 *
	 * @return boolean is the contact url being followed?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSharingByURL($url, $uid)
	{
		$cid = self::getIdForURL($url, $uid, true);

		if (empty($cid)) {
			return false;
		}

		return self::isSharing($cid, $uid);
	}

	/**
	 * Get the basepath for a given contact link
	 *
	 * @param string $url The contact link
	 * @param boolean $dont_update Don't update the contact
	 *
	 * @return string basepath
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getBasepath($url, $dont_update = false)
	{
		$contact = DBA::selectFirst('contact', ['id', 'baseurl'], ['uid' => 0, 'nurl' => Strings::normaliseLink($url)]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (!empty($contact['baseurl'])) {
			return $contact['baseurl'];
		} elseif ($dont_update) {
			return '';
		}

		// Update the existing contact
		self::updateFromProbe($contact['id'], '', true);

		// And fetch the result
		$contact = DBA::selectFirst('contact', ['baseurl'], ['id' => $contact['id']]);
		if (empty($contact['baseurl'])) {
			Logger::info('No baseurl for contact', ['url' => $url]);
			return '';
		}

		Logger::info('Found baseurl for contact', ['url' => $url, 'baseurl' => $contact['baseurl']]);
		return $contact['baseurl'];
	}

	/**
	 * Check if the given contact url is on the same server
	 *
	 * @param string $url The contact link
	 *
	 * @return boolean Is it the same server?
	 */
	public static function isLocal($url)
	{
		return Strings::compareLink(self::getBasepath($url, true), DI::baseUrl());
	}

	/**
	 * Check if the given contact ID is on the same server
	 *
	 * @param string $url The contact link
	 *
	 * @return boolean Is it the same server?
	 */
	public static function isLocalById(int $cid)
	{
		$contact = DBA::selectFirst('contact', ['url', 'baseurl'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		if (empty($contact['baseurl'])) {
			$baseurl = self::getBasepath($contact['url'], true);
		} else {
			$baseurl = $contact['baseurl'];
		}

		return Strings::compareLink($baseurl, DI::baseUrl());
	}

	/**
	 * Returns the public contact id of the given user id
	 *
	 * @param  integer $uid User ID
	 *
	 * @return integer|boolean Public contact id for given user id
	 * @throws \Exception
	 */
	public static function getPublicIdByUserId($uid)
	{
		$self = DBA::selectFirst('contact', ['url'], ['self' => true, 'uid' => $uid]);
		if (!DBA::isResult($self)) {
			return false;
		}
		return self::getIdForURL($self['url'], 0, true);
	}

	/**
	 * Returns the contact id for the user and the public contact id for a given contact id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return array with public and user's contact id
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getPublicAndUserContacID($cid, $uid)
	{
		if (empty($uid) || empty($cid)) {
			return [];
		}

		$contact = DBA::selectFirst('contact', ['id', 'uid', 'url'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return [];
		}

		// We quit when the user id don't match the user id of the provided contact
		if (($contact['uid'] != $uid) && ($contact['uid'] != 0)) {
			return [];
		}

		if ($contact['uid'] != 0) {
			$pcid = Contact::getIdForURL($contact['url'], 0, true, ['url' => $contact['url']]);
			if (empty($pcid)) {
				return [];
			}
			$ucid = $contact['id'];
		} else {
			$pcid = $contact['id'];
			$ucid = Contact::getIdForURL($contact['url'], $uid, true);
		}

		return ['public' => $pcid, 'user' => $ucid];
	}

	/**
	 * Returns contact details for a given contact id in combination with a user id
	 *
	 * @param int $cid A contact ID
	 * @param int $uid The User ID
	 * @param array $fields The selected fields for the contact
	 *
	 * @return array The contact details
	 *
	 * @throws \Exception
	 */
	public static function getContactForUser($cid, $uid, array $fields = [])
	{
		$contact = DBA::selectFirst('contact', $fields, ['id' => $cid, 'uid' => $uid]);

		if (!DBA::isResult($contact)) {
			return [];
		} else {
			return $contact;
		}
	}

	/**
	 * Block contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $blocked Is the contact blocked or unblocked?
	 * @throws \Exception
	 */
	public static function setBlockedForUser($cid, $uid, $blocked)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['blocked' => $blocked], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['blocked' => $blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "block" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 * @throws \Exception
	 */
	public static function isBlockedByUser($cid, $uid)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$public_blocked = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['blocked'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_blocked = $public_contact['blocked'];
			}
		}

		$user_blocked = $public_blocked;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['blocked'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_blocked = $user_contact['blocked'];
			}
		}

		if ($user_blocked != $public_blocked) {
			DBA::update('user-contact', ['blocked' => $user_blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_blocked;
	}

	/**
	 * Ignore contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $ignored Is the contact ignored or unignored?
	 * @throws \Exception
	 */
	public static function setIgnoredForUser($cid, $uid, $ignored)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['readonly' => $ignored], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['ignored' => $ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "ignore" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id ignored for the given user?
	 * @throws \Exception
	 */
	public static function isIgnoredByUser($cid, $uid)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$public_ignored = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['ignored'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_ignored = $public_contact['ignored'];
			}
		}

		$user_ignored = $public_ignored;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['readonly'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_ignored = $user_contact['readonly'];
			}
		}

		if ($user_ignored != $public_ignored) {
			DBA::update('user-contact', ['ignored' => $user_ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_ignored;
	}

	/**
	 * Set "collapsed" for contact id and user id
	 *
	 * @param int     $cid       Either public contact id or user's contact id
	 * @param int     $uid       User ID
	 * @param boolean $collapsed are the contact's posts collapsed or uncollapsed?
	 * @throws \Exception
	 */
	public static function setCollapsedForUser($cid, $uid, $collapsed)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		DBA::update('user-contact', ['collapsed' => $collapsed], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "collapsed" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isCollapsedByUser($cid, $uid)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$collapsed = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['collapsed'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$collapsed = $public_contact['collapsed'];
			}
		}

		return $collapsed;
	}

	/**
	 * Returns a list of contacts belonging in a group
	 *
	 * @param int $gid
	 * @return array
	 * @throws \Exception
	 */
	public static function getByGroupId($gid)
	{
		$return = [];

		if (intval($gid)) {
			$stmt = DBA::p('SELECT `group_member`.`contact-id`, `contact`.*
				FROM `contact`
				INNER JOIN `group_member`
					ON `contact`.`id` = `group_member`.`contact-id`
				WHERE `gid` = ?
				AND `contact`.`uid` = ?
				AND NOT `contact`.`self`
				AND NOT `contact`.`deleted`
				AND NOT `contact`.`blocked`
				AND NOT `contact`.`pending`
				ORDER BY `contact`.`name` ASC',
				$gid,
				local_user()
			);

			if (DBA::isResult($stmt)) {
				$return = DBA::toArray($stmt);
			}
		}

		return $return;
	}

	/**
	 * Creates the self-contact for the provided user id
	 *
	 * @param int $uid
	 * @return bool Operation success
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function createSelfFromUserId($uid)
	{
		// Only create the entry if it doesn't exist yet
		if (DBA::exists('contact', ['uid' => $uid, 'self' => true])) {
			return true;
		}

		$user = DBA::selectFirst('user', ['uid', 'username', 'nickname'], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$return = DBA::insert('contact', [
			'uid'         => $user['uid'],
			'created'     => DateTimeFormat::utcNow(),
			'self'        => 1,
			'name'        => $user['username'],
			'nick'        => $user['nickname'],
			'photo'       => DI::baseUrl() . '/photo/profile/' . $user['uid'] . '.jpg',
			'thumb'       => DI::baseUrl() . '/photo/avatar/'  . $user['uid'] . '.jpg',
			'micro'       => DI::baseUrl() . '/photo/micro/'   . $user['uid'] . '.jpg',
			'blocked'     => 0,
			'pending'     => 0,
			'url'         => DI::baseUrl() . '/profile/' . $user['nickname'],
			'nurl'        => Strings::normaliseLink(DI::baseUrl() . '/profile/' . $user['nickname']),
			'addr'        => $user['nickname'] . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3),
			'request'     => DI::baseUrl() . '/dfrn_request/' . $user['nickname'],
			'notify'      => DI::baseUrl() . '/dfrn_notify/'  . $user['nickname'],
			'poll'        => DI::baseUrl() . '/dfrn_poll/'    . $user['nickname'],
			'confirm'     => DI::baseUrl() . '/dfrn_confirm/' . $user['nickname'],
			'poco'        => DI::baseUrl() . '/poco/'         . $user['nickname'],
			'name-date'   => DateTimeFormat::utcNow(),
			'uri-date'    => DateTimeFormat::utcNow(),
			'avatar-date' => DateTimeFormat::utcNow(),
			'closeness'   => 0
		]);

		return $return;
	}

	/**
	 * Updates the self-contact for the provided user id
	 *
	 * @param int     $uid
	 * @param boolean $update_avatar Force the avatar update
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function updateSelfFromUserID($uid, $update_avatar = false)
	{
		$fields = ['id', 'name', 'nick', 'location', 'about', 'keywords', 'avatar',
			'xmpp', 'contact-type', 'forum', 'prv', 'avatar-date', 'url', 'nurl', 'unsearchable',
			'photo', 'thumb', 'micro', 'addr', 'request', 'notify', 'poll', 'confirm', 'poco'];
		$self = DBA::selectFirst('contact', $fields, ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($self)) {
			return;
		}

		$fields = ['nickname', 'page-flags', 'account-type'];
		$user = DBA::selectFirst('user', $fields, ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return;
		}

		$fields = ['name', 'photo', 'thumb', 'about', 'address', 'locality', 'region',
			'country-name', 'pub_keywords', 'xmpp', 'net-publish'];
		$profile = DBA::selectFirst('profile', $fields, ['uid' => $uid]);
		if (!DBA::isResult($profile)) {
			return;
		}

		$file_suffix = 'jpg';

		$fields = ['name' => $profile['name'], 'nick' => $user['nickname'],
			'avatar-date' => $self['avatar-date'], 'location' => Profile::formatLocation($profile),
			'about' => $profile['about'], 'keywords' => $profile['pub_keywords'],
			'contact-type' => $user['account-type'],
			'xmpp' => $profile['xmpp']];

		$avatar = Photo::selectFirst(['resource-id', 'type'], ['uid' => $uid, 'profile' => true]);
		if (DBA::isResult($avatar)) {
			if ($update_avatar) {
				$fields['avatar-date'] = DateTimeFormat::utcNow();
			}

			// Creating the path to the avatar, beginning with the file suffix
			$types = Images::supportedTypes();
			if (isset($types[$avatar['type']])) {
				$file_suffix = $types[$avatar['type']];
			}

			// We are adding a timestamp value so that other systems won't use cached content
			$timestamp = strtotime($fields['avatar-date']);

			$prefix = DI::baseUrl() . '/photo/' .$avatar['resource-id'] . '-';
			$suffix = '.' . $file_suffix . '?ts=' . $timestamp;

			$fields['photo'] = $prefix . '4' . $suffix;
			$fields['thumb'] = $prefix . '5' . $suffix;
			$fields['micro'] = $prefix . '6' . $suffix;
		} else {
			// We hadn't found a photo entry, so we use the default avatar
			$fields['photo'] = DI::baseUrl() . '/images/person-300.jpg';
			$fields['thumb'] = DI::baseUrl() . '/images/person-80.jpg';
			$fields['micro'] = DI::baseUrl() . '/images/person-48.jpg';
		}

		$fields['avatar'] = DI::baseUrl() . '/photo/profile/' .$uid . '.' . $file_suffix;
		$fields['forum'] = $user['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$fields['prv'] = $user['page-flags'] == User::PAGE_FLAGS_PRVGROUP;
		$fields['unsearchable'] = !$profile['net-publish'];

		// it seems as if ported accounts can have wrong values, so we make sure that now everything is fine.
		$fields['url'] = DI::baseUrl() . '/profile/' . $user['nickname'];
		$fields['nurl'] = Strings::normaliseLink($fields['url']);
		$fields['addr'] = $user['nickname'] . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3);
		$fields['request'] = DI::baseUrl() . '/dfrn_request/' . $user['nickname'];
		$fields['notify'] = DI::baseUrl() . '/dfrn_notify/' . $user['nickname'];
		$fields['poll'] = DI::baseUrl() . '/dfrn_poll/'. $user['nickname'];
		$fields['confirm'] = DI::baseUrl() . '/dfrn_confirm/' . $user['nickname'];
		$fields['poco'] = DI::baseUrl() . '/poco/' . $user['nickname'];

		$update = false;

		foreach ($fields as $field => $content) {
			if ($self[$field] != $content) {
				$update = true;
			}
		}

		if ($update) {
			if ($fields['name'] != $self['name']) {
				$fields['name-date'] = DateTimeFormat::utcNow();
			}
			$fields['updated'] = DateTimeFormat::utcNow();
			DBA::update('contact', $fields, ['id' => $self['id']]);

			// Update the public contact as well
			DBA::update('contact', $fields, ['uid' => 0, 'nurl' => $self['nurl']]);

			// Update the profile
			$fields = ['photo' => DI::baseUrl() . '/photo/profile/' .$uid . '.' . $file_suffix,
				'thumb' => DI::baseUrl() . '/photo/avatar/' . $uid .'.' . $file_suffix];
			DBA::update('profile', $fields, ['uid' => $uid]);
		}
	}

	/**
	 * Marks a contact for removal
	 *
	 * @param int $id contact id
	 * @return null
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function remove($id)
	{
		// We want just to make sure that we don't delete our "self" contact
		$contact = DBA::selectFirst('contact', ['uid'], ['id' => $id, 'self' => false]);
		if (!DBA::isResult($contact) || !intval($contact['uid'])) {
			return;
		}

		// Archive the contact
		DBA::update('contact', ['archive' => true, 'network' => Protocol::PHANTOM, 'deleted' => true], ['id' => $id]);

		// Delete it in the background
		Worker::add(PRIORITY_MEDIUM, 'RemoveContact', $id);
	}

	/**
	 * Sends an unfriend message. Does not remove the contact
	 *
	 * @param array   $user     User unfriending
	 * @param array   $contact  Contact unfriended
	 * @param boolean $dissolve Remove the contact on the remote side
	 * @return void
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function terminateFriendship(array $user, array $contact, $dissolve = false)
	{
		if (empty($contact['network'])) {
			return;
		}

		$protocol = $contact['network'];
		if (($protocol == Protocol::DFRN) && !self::isLegacyDFRNContact($contact)) {
			$protocol = Protocol::ACTIVITYPUB;
		}

		if (($protocol == Protocol::DFRN) && $dissolve) {
			DFRN::deliver($user, $contact, 'placeholder', true);
		} elseif (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
			// create an unfollow slap
			$item = [];
			$item['verb'] = Activity::O_UNFOLLOW;
			$item['gravity'] = GRAVITY_ACTIVITY;
			$item['follow'] = $contact["url"];
			$item['body'] = '';
			$item['title'] = '';
			$item['guid'] = '';
			$item['uri-id'] = 0;
			$item['attach'] = '';
			$slap = OStatus::salmon($item, $user);

			if (!empty($contact['notify'])) {
				Salmon::slapper($user, $contact['notify'], $slap);
			}
		} elseif ($protocol == Protocol::DIASPORA) {
			Diaspora::sendUnshare($user, $contact);
		} elseif ($protocol == Protocol::ACTIVITYPUB) {
			ActivityPub\Transmitter::sendContactUndo($contact['url'], $contact['id'], $user['uid']);

			if ($dissolve) {
				ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $user['uid']);
			}
		}
	}

	/**
	 * Marks a contact for archival after a communication issue delay
	 *
	 * Contact has refused to recognise us as a friend. We will start a countdown.
	 * If they still don't recognise us in 32 days, the relationship is over,
	 * and we won't waste any more time trying to communicate with them.
	 * This provides for the possibility that their database is temporarily messed
	 * up or some other transient event and that there's a possibility we could recover from it.
	 *
	 * @param array $contact contact to mark for archival
	 * @return null
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function markForArchival(array $contact)
	{
		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'archive', 'self', 'term-date'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		} elseif (!isset($contact['url'])) {
			Logger::log('Empty contact: ' . json_encode($contact) . ' - ' . System::callstack(20), Logger::DEBUG);
		}

		Logger::log('Contact '.$contact['id'].' is marked for archival', Logger::DEBUG);

		// Contact already archived or "self" contact? => nothing to do
		if ($contact['archive'] || $contact['self']) {
			return;
		}

		if ($contact['term-date'] <= DBA::NULL_DATETIME) {
			DBA::update('contact', ['term-date' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			DBA::update('contact', ['term-date' => DateTimeFormat::utcNow()], ['`nurl` = ? AND `term-date` <= ? AND NOT `self`', Strings::normaliseLink($contact['url']), DBA::NULL_DATETIME]);
		} else {
			/* @todo
			 * We really should send a notification to the owner after 2-3 weeks
			 * so they won't be surprised when the contact vanishes and can take
			 * remedial action if this was a serious mistake or glitch
			 */

			/// @todo Check for contact vitality via probing
			$archival_days = DI::config()->get('system', 'archival_days', 32);

			$expiry = $contact['term-date'] . ' + ' . $archival_days . ' days ';
			if (DateTimeFormat::utcNow() > DateTimeFormat::utc($expiry)) {
				/* Relationship is really truly dead. archive them rather than
				 * delete, though if the owner tries to unarchive them we'll start
				 * the whole process over again.
				 */
				DBA::update('contact', ['archive' => true], ['id' => $contact['id']]);
				DBA::update('contact', ['archive' => true], ['nurl' => Strings::normaliseLink($contact['url']), 'self' => false]);
				GContact::updateFromPublicContactURL($contact['url']);
			}
		}
	}

	/**
	 * Cancels the archival countdown
	 *
	 * @see   Contact::markForArchival()
	 *
	 * @param array $contact contact to be unmarked for archival
	 * @return null
	 * @throws \Exception
	 */
	public static function unmarkForArchival(array $contact)
	{
		// Always unarchive the relay contact entry
		if (!empty($contact['batch']) && !empty($contact['term-date']) && ($contact['term-date'] > DBA::NULL_DATETIME)) {
			$fields = ['term-date' => DBA::NULL_DATETIME, 'archive' => false];
			$condition = ['uid' => 0, 'network' => Protocol::FEDERATED, 'batch' => $contact['batch'], 'contact-type' => self::TYPE_RELAY];
			DBA::update('contact', $fields, $condition);
		}

		$condition = ['`id` = ? AND (`term-date` > ? OR `archive`)', $contact['id'], DBA::NULL_DATETIME];
		$exists = DBA::exists('contact', $condition);

		// We don't need to update, we never marked this contact for archival
		if (!$exists) {
			return;
		}

		Logger::log('Contact '.$contact['id'].' is marked as vital again', Logger::DEBUG);

		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'batch'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		}

		// It's a miracle. Our dead contact has inexplicably come back to life.
		$fields = ['term-date' => DBA::NULL_DATETIME, 'archive' => false];
		DBA::update('contact', $fields, ['id' => $contact['id']]);
		DBA::update('contact', $fields, ['nurl' => Strings::normaliseLink($contact['url']), 'self' => false]);
		GContact::updateFromPublicContactURL($contact['url']);
	}

	/**
	 * Get contact data for a given profile link
	 *
	 * The function looks at several places (contact table and gcontact table) for the contact
	 * It caches its result for the same script execution to prevent duplicate calls
	 *
	 * @param string $url     The profile link
	 * @param int    $uid     User id
	 * @param array  $default If not data was found take this data as default value
	 *
	 * @return array Contact data
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getDetailsByURL($url, $uid = -1, array $default = [])
	{
		static $cache = [];

		if ($url == '') {
			return $default;
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		if (isset($cache[$url][$uid])) {
			return $cache[$url][$uid];
		}

		$ssl_url = str_replace('http://', 'https://', $url);

		$nurl = Strings::normaliseLink($url);

		// Fetch contact data from the contact table for the given user
		$s = DBA::p("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`, `rel`, `pending`
		FROM `contact` WHERE `nurl` = ? AND `uid` = ?", $nurl, $uid);
		$r = DBA::toArray($s);

		// Fetch contact data from the contact table for the given user, checking with the alias
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
				`keywords`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`, `rel`, `pending`
			FROM `contact` WHERE `alias` IN (?, ?, ?) AND `uid` = ?", $nurl, $url, $ssl_url, $uid);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`, `rel`, `pending`
			FROM `contact` WHERE `nurl` = ? AND `uid` = 0", $nurl);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically) - checked with the alias
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`, `rel`, `pending`
			FROM `contact` WHERE `alias` IN (?, ?, ?) AND `uid` = 0", $nurl, $url, $ssl_url);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the gcontact table
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT 0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, '' AS `xmpp`,
			`keywords`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, 0 AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`, 2 AS `rel`, 0 AS `pending`
			FROM `gcontact` WHERE `nurl` = ?", $nurl);
			$r = DBA::toArray($s);
		}

		if (DBA::isResult($r)) {
			$authoritativeResult = true;
			// If there is more than one entry we filter out the connector networks
			if (count($r) > 1) {
				foreach ($r as $id => $result) {
					if (!in_array($result["network"], Protocol::NATIVE_SUPPORT)) {
						unset($r[$id]);
					}
				}
			}

			$profile = array_shift($r);

			// "bd" always contains the upcoming birthday of a contact.
			// "birthday" might contain the birthday including the year of birth.
			if ($profile["birthday"] > DBA::NULL_DATE) {
				$bd_timestamp = strtotime($profile["birthday"]);
				$month = date("m", $bd_timestamp);
				$day = date("d", $bd_timestamp);

				$current_timestamp = time();
				$current_year = date("Y", $current_timestamp);
				$current_month = date("m", $current_timestamp);
				$current_day = date("d", $current_timestamp);

				$profile["bd"] = $current_year . "-" . $month . "-" . $day;
				$current = $current_year . "-" . $current_month . "-" . $current_day;

				if ($profile["bd"] < $current) {
					$profile["bd"] = ( ++$current_year) . "-" . $month . "-" . $day;
				}
			} else {
				$profile["bd"] = DBA::NULL_DATE;
			}
		} else {
			$authoritativeResult = false;
			$profile = $default;
		}

		if (empty($profile["photo"]) && isset($default["photo"])) {
			$profile["photo"] = $default["photo"];
		}

		if (empty($profile["name"]) && isset($default["name"])) {
			$profile["name"] = $default["name"];
		}

		if (empty($profile["network"]) && isset($default["network"])) {
			$profile["network"] = $default["network"];
		}

		if (empty($profile["thumb"]) && isset($profile["photo"])) {
			$profile["thumb"] = $profile["photo"];
		}

		if (empty($profile["micro"]) && isset($profile["thumb"])) {
			$profile["micro"] = $profile["thumb"];
		}

		if ((empty($profile["addr"]) || empty($profile["name"])) && !empty($profile["gid"])
			&& in_array($profile["network"], Protocol::FEDERATED)
		) {
			Worker::add(PRIORITY_LOW, "UpdateGContact", $url);
		}

		// Show contact details of Diaspora contacts only if connected
		if (empty($profile["cid"]) && ($profile["network"] ?? "") == Protocol::DIASPORA) {
			$profile["location"] = "";
			$profile["about"] = "";
			$profile["birthday"] = DBA::NULL_DATE;
		}

		// Only cache the result if it came from the DB since this method is used in widely different contexts
		// @see display_fetch_author for an example of $default parameter diverging from the DB result
		if ($authoritativeResult) {
			$cache[$url][$uid] = $profile;
		}

		return $profile;
	}

	/**
	 * Get contact data for a given address
	 *
	 * The function looks at several places (contact table and gcontact table) for the contact
	 *
	 * @param string $addr The profile link
	 * @param int    $uid  User id
	 *
	 * @return array Contact data
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getDetailsByAddr($addr, $uid = -1)
	{
		if ($addr == '') {
			return [];
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		// Fetch contact data from the contact table for the given user
		$r = q("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`, `rel`, `pending`,`baseurl`
			FROM `contact` WHERE `addr` = '%s' AND `uid` = %d AND NOT `deleted`",
			DBA::escape($addr),
			intval($uid)
		);
		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBA::isResult($r)) {
			$r = q("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
				`keywords`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`, `rel`, `pending`, `baseurl`
				FROM `contact` WHERE `addr` = '%s' AND `uid` = 0 AND NOT `deleted`",
				DBA::escape($addr)
			);
		}

		// Fetch the data from the gcontact table
		if (!DBA::isResult($r)) {
			$r = q("SELECT 0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, '' AS `xmpp`,
				`keywords`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, `community` AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`, 2 AS `rel`, 0 AS `pending`, `server_url` AS `baseurl`
				FROM `gcontact` WHERE `addr` = '%s'",
				DBA::escape($addr)
			);
		}

		if (!DBA::isResult($r)) {
			$data = Probe::uri($addr);

			$profile = self::getDetailsByURL($data['url'], $uid, $data);
		} else {
			$profile = $r[0];
		}

		return $profile;
	}

	/**
	 * Returns the data array for the photo menu of a given contact
	 *
	 * @param array $contact contact
	 * @param int   $uid     optional, default 0
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function photoMenu(array $contact, $uid = 0)
	{
		$pm_url = '';
		$status_link = '';
		$photos_link = '';
		$contact_drop_link = '';
		$poke_link = '';

		if ($uid == 0) {
			$uid = local_user();
		}

		if (empty($contact['uid']) || ($contact['uid'] != $uid)) {
			if ($uid == 0) {
				$profile_link = self::magicLink($contact['url']);
				$menu = ['profile' => [DI::l10n()->t('View Profile'), $profile_link, true]];

				return $menu;
			}

			// Look for our own contact if the uid doesn't match and isn't public
			$contact_own = DBA::selectFirst('contact', [], ['nurl' => $contact['nurl'], 'network' => $contact['network'], 'uid' => $uid]);
			if (DBA::isResult($contact_own)) {
				return self::photoMenu($contact_own, $uid);
			}
		}

		$sparkle = false;
		if (($contact['network'] === Protocol::DFRN) && !$contact['self'] && empty($contact['pending'])) {
			$sparkle = true;
			$profile_link = DI::baseUrl() . '/redir/' . $contact['id'];
		} else {
			$profile_link = $contact['url'];
		}

		if ($profile_link === 'mailbox') {
			$profile_link = '';
		}

		if ($sparkle) {
			$status_link = $profile_link . '/status';
			$photos_link = str_replace('/profile/', '/photos/', $profile_link);
			$profile_link = $profile_link . '/profile';
		}

		if (self::canReceivePrivateMessages($contact) && empty($contact['pending'])) {
			$pm_url = DI::baseUrl() . '/message/new/' . $contact['id'];
		}

		if (($contact['network'] == Protocol::DFRN) && !$contact['self'] && empty($contact['pending'])) {
			$poke_link = 'contact/' . $contact['id'] . '/poke';
		}

		$contact_url = DI::baseUrl() . '/contact/' . $contact['id'];

		$posts_link = DI::baseUrl() . '/contact/' . $contact['id'] . '/conversations';

		if (!$contact['self']) {
			$contact_drop_link = DI::baseUrl() . '/contact/' . $contact['id'] . '/drop?confirm=1';
		}

		$follow_link = '';
		$unfollow_link = '';
		if (!$contact['self'] && in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
			if ($contact['uid'] && in_array($contact['rel'], [self::SHARING, self::FRIEND])) {
				$unfollow_link = 'unfollow?url=' . urlencode($contact['url']);
			} elseif(!$contact['pending']) {
				$follow_link = 'follow?url=' . urlencode($contact['url']);
			}
		}

		if (!empty($follow_link) || !empty($unfollow_link)) {
			$contact_drop_link = '';
		}

		/**
		 * Menu array:
		 * "name" => [ "Label", "link", (bool)Should the link opened in a new tab? ]
		 */
		if (empty($contact['uid'])) {
			$menu = [
				'profile' => [DI::l10n()->t('View Profile')  , $profile_link , true],
				'network' => [DI::l10n()->t('Network Posts') , $posts_link   , false],
				'edit'    => [DI::l10n()->t('View Contact')  , $contact_url  , false],
				'follow'  => [DI::l10n()->t('Connect/Follow'), $follow_link  , true],
				'unfollow'=> [DI::l10n()->t('UnFollow')      , $unfollow_link, true],
			];
		} else {
			$menu = [
				'status'  => [DI::l10n()->t('View Status')   , $status_link      , true],
				'profile' => [DI::l10n()->t('View Profile')  , $profile_link     , true],
				'photos'  => [DI::l10n()->t('View Photos')   , $photos_link      , true],
				'network' => [DI::l10n()->t('Network Posts') , $posts_link       , false],
				'edit'    => [DI::l10n()->t('View Contact')  , $contact_url      , false],
				'drop'    => [DI::l10n()->t('Drop Contact')  , $contact_drop_link, false],
				'pm'      => [DI::l10n()->t('Send PM')       , $pm_url           , false],
				'poke'    => [DI::l10n()->t('Poke')          , $poke_link        , false],
				'follow'  => [DI::l10n()->t('Connect/Follow'), $follow_link      , true],
				'unfollow'=> [DI::l10n()->t('UnFollow')      , $unfollow_link    , true],
			];

			if (!empty($contact['pending'])) {
				$intro = DBA::selectFirst('intro', ['id'], ['contact-id' => $contact['id']]);
				if (DBA::isResult($intro)) {
					$menu['follow'] = [DI::l10n()->t('Approve'), 'notifications/intros/' . $intro['id'], true];
				}
			}
		}

		$args = ['contact' => $contact, 'menu' => &$menu];

		Hook::callAll('contact_photo_menu', $args);

		$menucondensed = [];

		foreach ($menu as $menuname => $menuitem) {
			if ($menuitem[1] != '') {
				$menucondensed[$menuname] = $menuitem;
			}
		}

		return $menucondensed;
	}

	/**
	 * Returns ungrouped contact count or list for user
	 *
	 * Returns either the total number of ungrouped contacts for the given user
	 * id or a paginated list of ungrouped contacts.
	 *
	 * @param int $uid uid
	 * @return array
	 * @throws \Exception
	 */
	public static function getUngroupedList($uid)
	{
		return q("SELECT *
			   FROM `contact`
			   WHERE `uid` = %d
			   AND NOT `self`
			   AND NOT `deleted`
			   AND NOT `blocked`
			   AND NOT `pending`
			   AND `id` NOT IN (
			   	SELECT DISTINCT(`contact-id`)
			   	FROM `group_member`
			   	INNER JOIN `group` ON `group`.`id` = `group_member`.`gid`
			   	WHERE `group`.`uid` = %d
			   )", intval($uid), intval($uid));
	}

	/**
	 * Have a look at all contact tables for a given profile url.
	 * This function works as a replacement for probing the contact.
	 *
	 * @param string  $url Contact URL
	 * @param integer $cid Contact ID
	 *
	 * @return array Contact array in the "probe" structure
	*/
	private static function getProbeDataFromDatabase($url, $cid = null)
	{
		// The link could be provided as http although we stored it as https
		$ssl_url = str_replace('http://', 'https://', $url);

		$fields = ['id', 'uid', 'url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
			'photo', 'keywords', 'location', 'about', 'network',
			'priority', 'batch', 'request', 'confirm', 'poco'];

		if (!empty($cid)) {
			$data = DBA::selectFirst('contact', $fields, ['id' => $cid]);
			if (DBA::isResult($data)) {
				return $data;
			}
		}

		$data = DBA::selectFirst('contact', $fields, ['nurl' => Strings::normaliseLink($url)]);

		if (!DBA::isResult($data)) {
			$condition = ['alias' => [$url, Strings::normaliseLink($url), $ssl_url]];
			$data = DBA::selectFirst('contact', $fields, $condition);
		}

		if (DBA::isResult($data)) {
			// For security reasons we don't fetch key data from our users
			$data["pubkey"] = '';
			return $data;
		}

		$fields = ['url', 'addr', 'alias', 'notify', 'name', 'nick',
			'photo', 'keywords', 'location', 'about', 'network'];
		$data = DBA::selectFirst('gcontact', $fields, ['nurl' => Strings::normaliseLink($url)]);

		if (!DBA::isResult($data)) {
			$condition = ['alias' => [$url, Strings::normaliseLink($url), $ssl_url]];
			$data = DBA::selectFirst('contact', $fields, $condition);
		}

		if (DBA::isResult($data)) {
			$data["pubkey"] = '';
			$data["poll"] = '';
			$data["priority"] = 0;
			$data["batch"] = '';
			$data["request"] = '';
			$data["confirm"] = '';
			$data["poco"] = '';
			return $data;
		}

		$data = ActivityPub::probeProfile($url, false);
		if (!empty($data)) {
			return $data;
		}

		$fields = ['url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
			'photo', 'network', 'priority', 'batch', 'request', 'confirm'];
		$data = DBA::selectFirst('fcontact', $fields, ['url' => $url]);

		if (!DBA::isResult($data)) {
			$condition = ['alias' => [$url, Strings::normaliseLink($url), $ssl_url]];
			$data = DBA::selectFirst('contact', $fields, $condition);
		}

		if (DBA::isResult($data)) {
			$data["pubkey"] = '';
			$data["keywords"] = '';
			$data["location"] = '';
			$data["about"] = '';
			$data["poco"] = '';
			return $data;
		}

		return [];
	}

	/**
	 * Fetch the contact id for a given URL and user
	 *
	 * First lookup in the contact table to find a record matching either `url`, `nurl`,
	 * `addr` or `alias`.
	 *
	 * If there's no record and we aren't looking for a public contact, we quit.
	 * If there's one, we check that it isn't time to update the picture else we
	 * directly return the found contact id.
	 *
	 * Second, we probe the provided $url whether it's http://server.tld/profile or
	 * nick@server.tld. We quit if we can't get any info back.
	 *
	 * Third, we create the contact record if it doesn't exist
	 *
	 * Fourth, we update the existing record with the new data (avatar, alias, nick)
	 * if there's any updates
	 *
	 * @param string  $url       Contact URL
	 * @param integer $uid       The user id for the contact (0 = public contact)
	 * @param boolean $no_update Don't update the contact
	 * @param array   $default   Default value for creating the contact when every else fails
	 * @param boolean $in_loop   Internally used variable to prevent an endless loop
	 *
	 * @return integer Contact ID
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getIdForURL($url, $uid = 0, $no_update = false, $default = [], $in_loop = false)
	{
		Logger::log("Get contact data for url " . $url . " and user " . $uid . " - " . System::callstack(), Logger::DEBUG);

		$contact_id = 0;

		if ($url == '') {
			return 0;
		}

		/// @todo Verify if we can't use Contact::getDetailsByUrl instead of the following
		// We first try the nurl (http://server.tld/nick), most common case
		$fields = ['id', 'avatar', 'updated', 'network'];
		$options = ['order' => ['id']];
		$contact = DBA::selectFirst('contact', $fields, ['nurl' => Strings::normaliseLink($url), 'uid' => $uid, 'deleted' => false], $options);

		// Then the addr (nick@server.tld)
		if (!DBA::isResult($contact)) {
			$contact = DBA::selectFirst('contact', $fields, ['addr' => str_replace('acct:', '', $url), 'uid' => $uid, 'deleted' => false], $options);
		}

		// Then the alias (which could be anything)
		if (!DBA::isResult($contact)) {
			// The link could be provided as http although we stored it as https
			$ssl_url = str_replace('http://', 'https://', $url);
			$condition = ['`alias` IN (?, ?, ?) AND `uid` = ? AND NOT `deleted`', $url, Strings::normaliseLink($url), $ssl_url, $uid];
			$contact = DBA::selectFirst('contact', $fields, $condition, $options);
		}

		if (DBA::isResult($contact)) {
			$contact_id = $contact["id"];
			$update_contact = false;

			// Update the contact every 7 days (Don't update mail or feed contacts)
			if (in_array($contact['network'], Protocol::FEDERATED)) {
				$update_contact = ($contact['updated'] < DateTimeFormat::utc('now -7 days'));

				// We force the update if the avatar is empty
				if (empty($contact['avatar'])) {
					$update_contact = true;
				}
			} elseif (empty($default) && in_array($contact['network'], [Protocol::MAIL, Protocol::PHANTOM]) && ($uid == 0)) {
				// Update public mail accounts via their user's accounts
				$fields = ['network', 'addr', 'name', 'nick', 'avatar', 'photo', 'thumb', 'micro'];
				$mailcontact = DBA::selectFirst('contact', $fields, ["`addr` = ? AND `network` = ? AND `uid` != 0", $url, Protocol::MAIL]);
				if (!DBA::isResult($mailcontact)) {
					$mailcontact = DBA::selectFirst('contact', $fields, ["`nurl` = ? AND `network` = ? AND `uid` != 0", $url, Protocol::MAIL]);
				}

				if (DBA::isResult($mailcontact)) {
					DBA::update('contact', $mailcontact, ['id' => $contact_id]);
				}
			}

			// Update the contact in the background if needed but it is called by the frontend
			if ($update_contact && $no_update && in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
				Worker::add(PRIORITY_LOW, "UpdateContact", $contact_id, ($uid == 0 ? 'force' : ''));
			}

			if (!$update_contact || $no_update) {
				return $contact_id;
			}
		} elseif ($uid != 0) {
			// Non-existing user-specific contact, exiting
			return 0;
		}

		if ($no_update && empty($default)) {
			// When we don't want to update, we look if we know this contact in any way
			$data = self::getProbeDataFromDatabase($url, $contact_id);
			$background_update = true;
		} elseif ($no_update && !empty($default['network'])) {
			// If there are default values, take these
			$data = $default;
			$background_update = false;
		} else {
			$data = [];
			$background_update = false;
		}

		if (empty($data)) {
			$data = Probe::uri($url, "", $uid);
		}

		// Take the default values when probing failed
		if (!empty($default) && !in_array($data["network"], array_merge(Protocol::NATIVE_SUPPORT, [Protocol::PUMPIO]))) {
			$data = array_merge($data, $default);
		}

		if (empty($data) || ($data['network'] == Protocol::PHANTOM)) {
			Logger::info('No valid network found', ['url' => $url, 'data' => $data, 'callstack' => System::callstack(20)]);
			return 0;
		}

		if (!empty($data['baseurl'])) {
			$data['baseurl'] = GServer::cleanURL($data['baseurl']);
		}

		if (!empty($data['baseurl']) && empty($data['gsid'])) {
			$data['gsid'] = GServer::getID($data['baseurl']);
		}

		if (!$contact_id && !empty($data['alias']) && ($data['alias'] != $url) && !$in_loop) {
			$contact_id = self::getIdForURL($data["alias"], $uid, true, $default, true);
		}

		if (!$contact_id) {
			$fields = [
				'uid'       => $uid,
				'created'   => DateTimeFormat::utcNow(),
				'url'       => $data['url'],
				'nurl'      => Strings::normaliseLink($data['url']),
				'addr'      => $data['addr'] ?? '',
				'alias'     => $data['alias'] ?? '',
				'notify'    => $data['notify'] ?? '',
				'poll'      => $data['poll'] ?? '',
				'name'      => $data['name'] ?? '',
				'nick'      => $data['nick'] ?? '',
				'photo'     => $data['photo'] ?? '',
				'keywords'  => $data['keywords'] ?? '',
				'location'  => $data['location'] ?? '',
				'about'     => $data['about'] ?? '',
				'network'   => $data['network'],
				'pubkey'    => $data['pubkey'] ?? '',
				'rel'       => self::SHARING,
				'priority'  => $data['priority'] ?? 0,
				'batch'     => $data['batch'] ?? '',
				'request'   => $data['request'] ?? '',
				'confirm'   => $data['confirm'] ?? '',
				'poco'      => $data['poco'] ?? '',
				'baseurl'   => $data['baseurl'] ?? '',
				'gsid'      => $data['gsid'] ?? null,
				'name-date' => DateTimeFormat::utcNow(),
				'uri-date'  => DateTimeFormat::utcNow(),
				'avatar-date' => DateTimeFormat::utcNow(),
				'writable'  => 1,
				'blocked'   => 0,
				'readonly'  => 0,
				'pending'   => 0];

			$condition = ['nurl' => Strings::normaliseLink($data["url"]), 'uid' => $uid, 'deleted' => false];

			// Before inserting we do check if the entry does exist now.
			$contact = DBA::selectFirst('contact', ['id'], $condition, ['order' => ['id']]);
			if (!DBA::isResult($contact)) {
				Logger::info('Create new contact', $fields);

				self::insert($fields);

				// We intentionally aren't using lastInsertId here. There is a chance for duplicates.
				$contact = DBA::selectFirst('contact', ['id'], $condition, ['order' => ['id']]);
				if (!DBA::isResult($contact)) {
					Logger::info('Contact creation failed', $fields);
					// Shouldn't happen
					return 0;
				}
			} else {
				Logger::info('Contact had been created before', ['id' => $contact["id"], 'url' => $url, 'contact' => $fields]);
			}

			$contact_id = $contact["id"];
		}

		if (!empty($data['photo']) && ($data['network'] != Protocol::FEED)) {
			self::updateAvatar($data['photo'], $uid, $contact_id);
		}

		if (in_array($data["network"], array_merge(Protocol::NATIVE_SUPPORT, [Protocol::PUMPIO]))) {
			if ($background_update) {
				// Update in the background when we fetched the data solely from the database
				Worker::add(PRIORITY_MEDIUM, "UpdateContact", $contact_id, ($uid == 0 ? 'force' : ''));
			} else {
				// Else do a direct update
				self::updateFromProbe($contact_id, '', false);

				// Update the gcontact entry
				if ($uid == 0) {
					GContact::updateFromPublicContactID($contact_id);
					if (($data['network'] == Protocol::ACTIVITYPUB) && in_array(DI::config()->get('system', 'gcontact_discovery'), [GContact::DISCOVERY_DIRECT, GContact::DISCOVERY_RECURSIVE])) {
						GContact::discoverFollowers($data['url']);
					}
				}
			}
		} else {
			$fields = ['url', 'nurl', 'addr', 'alias', 'name', 'nick', 'keywords', 'location', 'about', 'avatar-date', 'baseurl', 'gsid'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact_id]);

			// This condition should always be true
			if (!DBA::isResult($contact)) {
				return $contact_id;
			}

			$updated = [
				'url' => $data['url'],
				'nurl' => Strings::normaliseLink($data['url']),
				'updated' => DateTimeFormat::utcNow()
			];

			$fields = ['addr', 'alias', 'name', 'nick', 'keywords', 'location', 'about', 'baseurl', 'gsid'];

			foreach ($fields as $field) {
				$updated[$field] = ($data[$field] ?? '') ?: $contact[$field];
			}

			if (($updated['addr'] != $contact['addr']) || (!empty($data['alias']) && ($data['alias'] != $contact['alias']))) {
				$updated['uri-date'] = DateTimeFormat::utcNow();
			}

			if (($data['name'] != $contact['name']) || ($data['nick'] != $contact['nick'])) {
				$updated['name-date'] = DateTimeFormat::utcNow();
			}

			DBA::update('contact', $updated, ['id' => $contact_id], $contact);
		}

		return $contact_id;
	}

	/**
	 * Checks if the contact is archived
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact archived?
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function isArchived(int $cid)
	{
		if ($cid == 0) {
			return false;
		}

		$contact = DBA::selectFirst('contact', ['archive', 'url', 'batch'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		if ($contact['archive']) {
			return true;
		}

		// Check status of ActivityPub endpoints
		$apcontact = APContact::getByURL($contact['url'], false);
		if (!empty($apcontact)) {
			if (!empty($apcontact['inbox']) && DBA::exists('inbox-status', ['archive' => true, 'url' => $apcontact['inbox']])) {
				return true;
			}

			if (!empty($apcontact['sharedinbox']) && DBA::exists('inbox-status', ['archive' => true, 'url' => $apcontact['sharedinbox']])) {
				return true;
			}
		}

		// Check status of Diaspora endpoints
		if (!empty($contact['batch'])) {
			$condition = ['archive' => true, 'uid' => 0, 'network' => Protocol::FEDERATED, 'batch' => $contact['batch'], 'contact-type' => self::TYPE_RELAY];
			return DBA::exists('contact', $condition);
                }

		return false;
	}

	/**
	 * Checks if the contact is blocked
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact blocked?
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function isBlocked($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$blocked = DBA::selectFirst('contact', ['blocked', 'url'], ['id' => $cid]);
		if (!DBA::isResult($blocked)) {
			return false;
		}

		if (Network::isUrlBlocked($blocked['url'])) {
			return true;
		}

		return (bool) $blocked['blocked'];
	}

	/**
	 * Checks if the contact is hidden
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact hidden?
	 * @throws \Exception
	 */
	public static function isHidden($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$hidden = DBA::selectFirst('contact', ['hidden'], ['id' => $cid]);
		if (!DBA::isResult($hidden)) {
			return false;
		}
		return (bool) $hidden['hidden'];
	}

	/**
	 * Returns posts from a given contact url
	 *
	 * @param string $contact_url Contact URL
	 * @param bool   $thread_mode
	 * @param int    $update
	 * @return string posts in HTML
	 * @throws \Exception
	 */
	public static function getPostsFromUrl($contact_url, $thread_mode = false, $update = 0)
	{
		return self::getPostsFromId(self::getIdForURL($contact_url), $thread_mode, $update);
	}

	/**
	 * Returns posts from a given contact id
	 *
	 * @param integer $cid
	 * @param bool    $thread_mode
	 * @param integer $update
	 * @return string posts in HTML
	 * @throws \Exception
	 */
	public static function getPostsFromId($cid, $thread_mode = false, $update = 0)
	{
		$a = DI::app();

		$contact = DBA::selectFirst('contact', ['contact-type', 'network'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (empty($contact["network"]) || in_array($contact["network"], Protocol::FEDERATED)) {
			$sql = "(`item`.`uid` = 0 OR (`item`.`uid` = ? AND NOT `item`.`global`))";
		} else {
			$sql = "`item`.`uid` = ?";
		}

		$contact_field = ((($contact["contact-type"] == self::TYPE_COMMUNITY) || ($contact['network'] == Protocol::MAIL)) ? 'owner-id' : 'author-id');

		if ($thread_mode) {
			$condition = ["`$contact_field` = ? AND `gravity` = ? AND " . $sql,
				$cid, GRAVITY_PARENT, local_user()];
		} else {
			$condition = ["`$contact_field` = ? AND `gravity` IN (?, ?) AND " . $sql,
				$cid, GRAVITY_PARENT, GRAVITY_COMMENT, local_user()];
		}

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		$params = ['order' => ['received' => true],
			'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		if ($thread_mode) {
			$r = Item::selectThreadForUser(local_user(), ['uri'], $condition, $params);

			$items = Item::inArray($r);

			$o = conversation($a, $items, 'contacts', $update, false, 'commented', local_user());
		} else {
			$r = Item::selectForUser(local_user(), [], $condition, $params);

			$items = Item::inArray($r);

			$o = conversation($a, $items, 'contact-posts', false);
		}

		if (!$update) {
			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Returns the account type name
	 *
	 * The function can be called with either the user or the contact array
	 *
	 * @param array $contact contact or user array
	 * @return string
	 */
	public static function getAccountType(array $contact)
	{
		// There are several fields that indicate that the contact or user is a forum
		// "page-flags" is a field in the user table,
		// "forum" and "prv" are used in the contact table. They stand for User::PAGE_FLAGS_COMMUNITY and User::PAGE_FLAGS_PRVGROUP.
		// "community" is used in the gcontact table and is true if the contact is User::PAGE_FLAGS_COMMUNITY or User::PAGE_FLAGS_PRVGROUP.
		if ((isset($contact['page-flags']) && (intval($contact['page-flags']) == User::PAGE_FLAGS_COMMUNITY))
			|| (isset($contact['page-flags']) && (intval($contact['page-flags']) == User::PAGE_FLAGS_PRVGROUP))
			|| (isset($contact['forum']) && intval($contact['forum']))
			|| (isset($contact['prv']) && intval($contact['prv']))
			|| (isset($contact['community']) && intval($contact['community']))
		) {
			$type = self::TYPE_COMMUNITY;
		} else {
			$type = self::TYPE_PERSON;
		}

		// The "contact-type" (contact table) and "account-type" (user table) are more general then the chaos from above.
		if (isset($contact["contact-type"])) {
			$type = $contact["contact-type"];
		}

		if (isset($contact["account-type"])) {
			$type = $contact["account-type"];
		}

		switch ($type) {
			case self::TYPE_ORGANISATION:
				$account_type = DI::l10n()->t("Organisation");
				break;

			case self::TYPE_NEWS:
				$account_type = DI::l10n()->t('News');
				break;

			case self::TYPE_COMMUNITY:
				$account_type = DI::l10n()->t("Forum");
				break;

			default:
				$account_type = "";
				break;
		}

		return $account_type;
	}

	/**
	 * Blocks a contact
	 *
	 * @param int $cid
	 * @return bool
	 * @throws \Exception
	 */
	public static function block($cid, $reason = null)
	{
		$return = DBA::update('contact', ['blocked' => true, 'block_reason' => $reason], ['id' => $cid]);

		return $return;
	}

	/**
	 * Unblocks a contact
	 *
	 * @param int $cid
	 * @return bool
	 * @throws \Exception
	 */
	public static function unblock($cid)
	{
		$return = DBA::update('contact', ['blocked' => false, 'block_reason' => null], ['id' => $cid]);

		return $return;
	}

	/**
	 * Updates the avatar links in a contact only if needed
	 *
	 * @param string $avatar Link to avatar picture
	 * @param int    $uid    User id of contact owner
	 * @param int    $cid    Contact id
	 * @param bool   $force  force picture update
	 *
	 * @return void
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public static function updateAvatar($avatar, $uid, $cid, $force = false)
	{
		$contact = DBA::selectFirst('contact', ['avatar', 'photo', 'thumb', 'micro', 'nurl'], ['id' => $cid, 'self' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		$data = [
			$contact['photo'] ?? '',
			$contact['thumb'] ?? '',
			$contact['micro'] ?? '',
		];

		foreach ($data as $image_uri) {
			$image_rid = Photo::ridFromURI($image_uri);
			if ($image_rid && !Photo::exists(['resource-id' => $image_rid, 'uid' => $uid])) {
				Logger::info('Regenerating avatar', ['contact uid' => $uid, 'cid' => $cid, 'missing photo' => $image_rid, 'avatar' => $contact['avatar']]);
				$force = true;
			}
		}

		if (($contact["avatar"] != $avatar) || $force) {
			$photos = Photo::importProfilePhoto($avatar, $uid, $cid, true);

			if ($photos) {
				$fields = ['avatar' => $avatar, 'photo' => $photos[0], 'thumb' => $photos[1], 'micro' => $photos[2], 'avatar-date' => DateTimeFormat::utcNow()];
				DBA::update('contact', $fields, ['id' => $cid]);

				// Update the public contact (contact id = 0)
				if ($uid != 0) {
					$pcontact = DBA::selectFirst('contact', ['id'], ['nurl' => $contact['nurl'], 'uid' => 0]);
					if (DBA::isResult($pcontact)) {
						DBA::update('contact', $fields, ['id' => $pcontact['id']]);
					}
				}
			}
		}
	}

	/**
	 * Helper function for "updateFromProbe". Updates personal and public contact
	 *
	 * @param integer $id      contact id
	 * @param integer $uid     user id
	 * @param string  $url     The profile URL of the contact
	 * @param array   $fields  The fields that are updated
	 *
	 * @throws \Exception
	 */
	private static function updateContact($id, $uid, $url, array $fields)
	{
		if (!DBA::update('contact', $fields, ['id' => $id])) {
			Logger::info('Couldn\'t update contact.', ['id' => $id, 'fields' => $fields]);
			return;
		}

		// Search for duplicated contacts and get rid of them
		if (self::removeDuplicates(Strings::normaliseLink($url), $uid) || ($uid != 0)) {
			return;
		}

		// Update the corresponding gcontact entry
		GContact::updateFromPublicContactID($id);

		// Archive or unarchive the contact. We only need to do this for the public contact.
		// The archive/unarchive function will update the personal contacts by themselves.
		$contact = DBA::selectFirst('contact', [], ['id' => $id]);
		if (!DBA::isResult($contact)) {
			Logger::info('Couldn\'t select contact for archival.', ['id' => $id]);
			return;
		}

		if (!empty($fields['success_update'])) {
			self::unmarkForArchival($contact);
		} elseif (!empty($fields['failure_update'])) {
			self::markForArchival($contact);
		}

		$condition = ['self' => false, 'nurl' => Strings::normaliseLink($url), 'network' => Protocol::FEDERATED];

		// These contacts are sharing with us, we don't poll them.
		// This means that we don't set the update fields in "OnePoll.php".
		$condition['rel'] = self::SHARING;
		DBA::update('contact', $fields, $condition);

		unset($fields['last-update']);
		unset($fields['success_update']);
		unset($fields['failure_update']);

		if (empty($fields)) {
			return;
		}

		// We are polling these contacts, so we mustn't set the update fields here.
		$condition['rel'] = [self::FOLLOWER, self::FRIEND];
		DBA::update('contact', $fields, $condition);
	}

	/**
	 * Remove duplicated contacts
	 *
	 * @param string  $nurl  Normalised contact url
	 * @param integer $uid   User id
	 * @return boolean
	 * @throws \Exception
	 */
	public static function removeDuplicates(string $nurl, int $uid)
	{
		$condition = ['nurl' => $nurl, 'uid' => $uid, 'deleted' => false, 'network' => Protocol::FEDERATED];
		$count = DBA::count('contact', $condition);
		if ($count <= 1) {
			return false;
		}

		$first_contact = DBA::selectFirst('contact', ['id', 'network'], $condition, ['order' => ['id']]);
		if (!DBA::isResult($first_contact)) {
			// Shouldn't happen - so we handle it
			return false;
		}

		$first = $first_contact['id'];
		Logger::info('Found duplicates', ['count' => $count, 'first' => $first, 'uid' => $uid, 'nurl' => $nurl]);
		if (($uid != 0 && ($first_contact['network'] == Protocol::DFRN))) {
			// Don't handle non public DFRN duplicates by now (legacy DFRN is very special because of the key handling)
			Logger::info('Not handling non public DFRN duplicate', ['uid' => $uid, 'nurl' => $nurl]);
			return false;
		}

		// Find all duplicates
		$condition = ["`nurl` = ? AND `uid` = ? AND `id` != ? AND NOT `self` AND NOT `deleted`", $nurl, $uid, $first];
		$duplicates = DBA::select('contact', ['id', 'network'], $condition);
		while ($duplicate = DBA::fetch($duplicates)) {
			if (!in_array($duplicate['network'], Protocol::FEDERATED)) {
				continue;
			}

			Worker::add(PRIORITY_HIGH, 'MergeContact', $first, $duplicate['id'], $uid);
		}
		DBA::close($duplicates);
		Logger::info('Duplicates handled', ['uid' => $uid, 'nurl' => $nurl]);
		return true;
	}

	/**
	 * @param integer $id      contact id
	 * @param string  $network Optional network we are probing for
	 * @param boolean $force   Optional forcing of network probing (otherwise we use the cached data)
	 * @return boolean
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbe($id, $network = '', $force = false)
	{
		/*
		  Warning: Never ever fetch the public key via Probe::uri and write it into the contacts.
		  This will reliably kill your communication with old Friendica contacts.
		 */

		// These fields aren't updated by this routine:
		// 'xmpp', 'sensitive'

		$fields = ['uid', 'avatar', 'name', 'nick', 'location', 'keywords', 'about', 'subscribe',
			'unsearchable', 'url', 'addr', 'batch', 'notify', 'poll', 'request', 'confirm', 'poco',
			'network', 'alias', 'baseurl', 'gsid', 'forum', 'prv', 'contact-type', 'pubkey'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$uid = $contact['uid'];
		unset($contact['uid']);

		$pubkey = $contact['pubkey'];
		unset($contact['pubkey']);

		$contact['photo'] = $contact['avatar'];
		unset($contact['avatar']);

		$ret = Probe::uri($contact['url'], $network, $uid, !$force);

		$updated = DateTimeFormat::utcNow();

		// We must not try to update relay contacts via probe. They are no real contacts.
		// We check after the probing to be able to correct falsely detected contact types.
		if (($contact['contact-type'] == self::TYPE_RELAY) &&
			(!Strings::compareLink($ret['url'], $contact['url']) || in_array($ret['network'], [Protocol::FEED, Protocol::PHANTOM]))) {
			self::updateContact($id, $uid, $contact['url'], ['last-update' => $updated, 'success_update' => $updated]);
			Logger::info('Not updating relais', ['id' => $id, 'url' => $contact['url']]);
			return true;
		}

		// If Probe::uri fails the network code will be different ("feed" or "unkn")
		if (in_array($ret['network'], [Protocol::FEED, Protocol::PHANTOM]) && ($ret['network'] != $contact['network'])) {
			if ($force && ($uid == 0)) {
				self::updateContact($id, $uid, $ret['url'], ['last-update' => $updated, 'failure_update' => $updated]);
			}
			return false;
		}

		if (isset($ret['hide']) && is_bool($ret['hide'])) {
			$ret['unsearchable'] = $ret['hide'];
		}

		if (isset($ret['account-type']) && is_int($ret['account-type'])) {
			$ret['forum'] = false;
			$ret['prv'] = false;
			$ret['contact-type'] = $ret['account-type'];
			if ($ret['contact-type'] == User::ACCOUNT_TYPE_COMMUNITY) {
				$apcontact = APContact::getByURL($ret['url'], false);
				if (isset($apcontact['manually-approve'])) {
					$ret['forum'] = (bool)!$apcontact['manually-approve'];
					$ret['prv'] = (bool)!$ret['forum'];
				}
			}
		}

		$new_pubkey = $ret['pubkey'];

		$update = false;

		// make sure to not overwrite existing values with blank entries except some technical fields
		$keep = ['batch', 'notify', 'poll', 'request', 'confirm', 'poco', 'baseurl'];
		foreach ($ret as $key => $val) {
			if (!array_key_exists($key, $contact)) {
				unset($ret[$key]);
			} elseif (($contact[$key] != '') && ($val === '') && !is_bool($ret[$key]) && !in_array($key, $keep)) {
				$ret[$key] = $contact[$key];
			} elseif ($ret[$key] != $contact[$key]) {
				$update = true;
			}
		}

		if (!empty($ret['photo']) && ($ret['network'] != Protocol::FEED)) {
			self::updateAvatar($ret['photo'], $uid, $id, $update || $force);
		}

		if (!$update) {
			if ($force) {
				self::updateContact($id, $uid, $ret['url'], ['last-update' => $updated, 'success_update' => $updated]);
			}

			// Update the public contact
			if ($uid != 0) {
				self::updateFromProbeByURL($ret['url']);
			}

			return true;
		}

		$ret['nurl'] = Strings::normaliseLink($ret['url']);
		$ret['updated'] = $updated;

		// Only fill the pubkey if it had been empty before. We have to prevent identity theft.
		if (empty($pubkey) && !empty($new_pubkey)) {
			$ret['pubkey'] = $new_pubkey;
		}

		if (($ret['addr'] != $contact['addr']) || (!empty($ret['alias']) && ($ret['alias'] != $contact['alias']))) {
			$ret['uri-date'] = DateTimeFormat::utcNow();
		}

		if (($ret['name'] != $contact['name']) || ($ret['nick'] != $contact['nick'])) {
			$ret['name-date'] = $updated;
		}

		if ($force && ($uid == 0)) {
			$ret['last-update'] = $updated;
			$ret['success_update'] = $updated;
		}

		unset($ret['photo']);

		self::updateContact($id, $uid, $ret['url'], $ret);

		return true;
	}

	public static function updateFromProbeByURL($url, $force = false)
	{
		$id = self::getIdForURL($url);

		if (empty($id)) {
			return $id;
		}

		self::updateFromProbe($id, '', $force);

		return $id;
	}

	/**
	 * Detects if a given contact array belongs to a legacy DFRN connection
	 *
	 * @param array $contact
	 * @return boolean
	 */
	public static function isLegacyDFRNContact($contact)
	{
		// Newer Friendica contacts are connected via AP, then these fields aren't set
		return !empty($contact['dfrn-id']) || !empty($contact['issued-id']);
	}

	/**
	 * Detects the communication protocol for a given contact url.
	 * This is used to detect Friendica contacts that we can communicate via AP.
	 *
	 * @param string $url contact url
	 * @param string $network Network of that contact
	 * @return string with protocol
	 */
	public static function getProtocol($url, $network)
	{
		if ($network != Protocol::DFRN) {
			return $network;
		}

		$apcontact = APContact::getByURL($url);
		if (!empty($apcontact) && !empty($apcontact['generator'])) {
			return Protocol::ACTIVITYPUB;
		} else {
			return $network;
		}
	}

	/**
	 * Takes a $uid and a url/handle and adds a new contact
	 *
	 * Currently if the contact is DFRN, interactive needs to be true, to redirect to the
	 * dfrn_request page.
	 *
	 * Otherwise this can be used to bulk add StatusNet contacts, Twitter contacts, etc.
	 *
	 * Returns an array
	 * $return['success'] boolean true if successful
	 * $return['message'] error text if success is false.
	 *
	 * Takes a $uid and a url/handle and adds a new contact
	 *
	 * @param array  $user        The user the contact should be created for
	 * @param string $url         The profile URL of the contact
	 * @param bool   $interactive
	 * @param string $network
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public static function createFromProbe(array $user, $url, $interactive = false, $network = '')
	{
		$result = ['cid' => -1, 'success' => false, 'message' => ''];

		// remove ajax junk, e.g. Twitter
		$url = str_replace('/#!/', '/', $url);

		if (!Network::isUrlAllowed($url)) {
			$result['message'] = DI::l10n()->t('Disallowed profile URL.');
			return $result;
		}

		if (Network::isUrlBlocked($url)) {
			$result['message'] = DI::l10n()->t('Blocked domain');
			return $result;
		}

		if (!$url) {
			$result['message'] = DI::l10n()->t('Connect URL missing.');
			return $result;
		}

		$arr = ['url' => $url, 'contact' => []];

		Hook::callAll('follow', $arr);

		if (empty($arr)) {
			$result['message'] = DI::l10n()->t('The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.');
			return $result;
		}

		if (!empty($arr['contact']['name'])) {
			$ret = $arr['contact'];
		} else {
			$ret = Probe::uri($url, $network, $user['uid'], false);
		}

		if (($network != '') && ($ret['network'] != $network)) {
			Logger::log('Expected network ' . $network . ' does not match actual network ' . $ret['network']);
			return $result;
		}

		// check if we already have a contact
		// the poll url is more reliable than the profile url, as we may have
		// indirect links or webfinger links

		$condition = ['uid' => $user['uid'], 'poll' => [$ret['poll'], Strings::normaliseLink($ret['poll'])], 'network' => $ret['network'], 'pending' => false];
		$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		if (!DBA::isResult($contact)) {
			$condition = ['uid' => $user['uid'], 'nurl' => Strings::normaliseLink($url), 'network' => $ret['network'], 'pending' => false];
			$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		}

		$protocol = self::getProtocol($url, $ret['network']);

		if (($protocol === Protocol::DFRN) && !DBA::isResult($contact)) {
			if ($interactive) {
				if (strlen(DI::baseUrl()->getUrlPath())) {
					$myaddr = bin2hex(DI::baseUrl() . '/profile/' . $user['nickname']);
				} else {
					$myaddr = bin2hex($user['nickname'] . '@' . DI::baseUrl()->getHostname());
				}

				DI::baseUrl()->redirect($ret['request'] . "&addr=$myaddr");

				// NOTREACHED
			}
		} elseif (DI::config()->get('system', 'dfrn_only') && ($ret['network'] != Protocol::DFRN)) {
			$result['message'] = DI::l10n()->t('This site is not configured to allow communications with other networks.') . EOL;
			$result['message'] .= DI::l10n()->t('No compatible communication protocols or feeds were discovered.') . EOL;
			return $result;
		}

		// This extra param just confuses things, remove it
		if ($protocol === Protocol::DIASPORA) {
			$ret['url'] = str_replace('?absolute=true', '', $ret['url']);
		}

		// do we have enough information?
		if (empty($ret['name']) || empty($ret['poll']) || (empty($ret['url']) && empty($ret['addr']))) {
			$result['message'] .= DI::l10n()->t('The profile address specified does not provide adequate information.') . EOL;
			if (empty($ret['poll'])) {
				$result['message'] .= DI::l10n()->t('No compatible communication protocols or feeds were discovered.') . EOL;
			}
			if (empty($ret['name'])) {
				$result['message'] .= DI::l10n()->t('An author or name was not found.') . EOL;
			}
			if (empty($ret['url'])) {
				$result['message'] .= DI::l10n()->t('No browser URL could be matched to this address.') . EOL;
			}
			if (strpos($url, '@') !== false) {
				$result['message'] .= DI::l10n()->t('Unable to match @-style Identity Address with a known protocol or email contact.') . EOL;
				$result['message'] .= DI::l10n()->t('Use mailto: in front of address to force email check.') . EOL;
			}
			return $result;
		}

		if ($protocol === Protocol::OSTATUS && DI::config()->get('system', 'ostatus_disabled')) {
			$result['message'] .= DI::l10n()->t('The profile address specified belongs to a network which has been disabled on this site.') . EOL;
			$ret['notify'] = '';
		}

		if (!$ret['notify']) {
			$result['message'] .= DI::l10n()->t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL;
		}

		$writeable = ((($protocol === Protocol::OSTATUS) && ($ret['notify'])) ? 1 : 0);

		$subhub = (($protocol === Protocol::OSTATUS) ? true : false);

		$hidden = (($protocol === Protocol::MAIL) ? 1 : 0);

		$pending = false;
		if ($protocol == Protocol::ACTIVITYPUB) {
			$apcontact = APContact::getByURL($url, false);
			if (isset($apcontact['manually-approve'])) {
				$pending = (bool)$apcontact['manually-approve'];
			}
		}

		if (in_array($protocol, [Protocol::MAIL, Protocol::DIASPORA, Protocol::ACTIVITYPUB])) {
			$writeable = 1;
		}

		if (DBA::isResult($contact)) {
			// update contact
			$new_relation = (($contact['rel'] == self::FOLLOWER) ? self::FRIEND : self::SHARING);

			$fields = ['rel' => $new_relation, 'subhub' => $subhub, 'readonly' => false];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		} else {
			$new_relation = (in_array($protocol, [Protocol::MAIL]) ? self::FRIEND : self::SHARING);

			// create contact record
			self::insert([
				'uid'     => $user['uid'],
				'created' => DateTimeFormat::utcNow(),
				'url'     => $ret['url'],
				'nurl'    => Strings::normaliseLink($ret['url']),
				'addr'    => $ret['addr'],
				'alias'   => $ret['alias'],
				'batch'   => $ret['batch'],
				'notify'  => $ret['notify'],
				'poll'    => $ret['poll'],
				'poco'    => $ret['poco'],
				'name'    => $ret['name'],
				'nick'    => $ret['nick'],
				'network' => $ret['network'],
				'baseurl' => $ret['baseurl'],
				'gsid'    => $ret['gsid'] ?? null,
				'protocol' => $protocol,
				'pubkey'  => $ret['pubkey'],
				'rel'     => $new_relation,
				'priority'=> $ret['priority'],
				'writable'=> $writeable,
				'hidden'  => $hidden,
				'blocked' => 0,
				'readonly'=> 0,
				'pending' => $pending,
				'subhub'  => $subhub
			]);
		}

		$contact = DBA::selectFirst('contact', [], ['url' => $ret['url'], 'network' => $ret['network'], 'uid' => $user['uid']]);
		if (!DBA::isResult($contact)) {
			$result['message'] .= DI::l10n()->t('Unable to retrieve contact information.') . EOL;
			return $result;
		}

		$contact_id = $contact['id'];
		$result['cid'] = $contact_id;

		Group::addMember(User::getDefaultGroup($user['uid'], $contact["network"]), $contact_id);

		// Update the avatar
		self::updateAvatar($ret['photo'], $user['uid'], $contact_id);

		// pull feed and consume it, which should subscribe to the hub.

		Worker::add(PRIORITY_HIGH, "OnePoll", $contact_id, "force");

		$owner = User::getOwnerDataById($user['uid']);

		if (DBA::isResult($owner)) {
			if (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
				// create a follow slap
				$item = [];
				$item['verb'] = Activity::FOLLOW;
				$item['gravity'] = GRAVITY_ACTIVITY;
				$item['follow'] = $contact["url"];
				$item['body'] = '';
				$item['title'] = '';
				$item['guid'] = '';
				$item['uri-id'] = 0;
				$item['attach'] = '';

				$slap = OStatus::salmon($item, $owner);

				if (!empty($contact['notify'])) {
					Salmon::slapper($owner, $contact['notify'], $slap);
				}
			} elseif ($protocol == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare($owner, $contact);
				Logger::log('share returns: ' . $ret);
			} elseif ($protocol == Protocol::ACTIVITYPUB) {
				$activity_id = ActivityPub\Transmitter::activityIDFromContact($contact_id);
				if (empty($activity_id)) {
					// This really should never happen
					return false;
				}

				$ret = ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $user['uid'], $activity_id);
				Logger::log('Follow returns: ' . $ret);
			}
		}

		$result['success'] = true;
		return $result;
	}

	/**
	 * Updated contact's SSL policy
	 *
	 * @param array  $contact    Contact array
	 * @param string $new_policy New policy, valid: self,full
	 *
	 * @return array Contact array with updated values
	 * @throws \Exception
	 */
	public static function updateSslPolicy(array $contact, $new_policy)
	{
		$ssl_changed = false;
		if ((intval($new_policy) == BaseURL::SSL_POLICY_SELFSIGN || $new_policy === 'self') && strstr($contact['url'], 'https:')) {
			$ssl_changed = true;
			$contact['url']     = 	str_replace('https:', 'http:', $contact['url']);
			$contact['request'] = 	str_replace('https:', 'http:', $contact['request']);
			$contact['notify']  = 	str_replace('https:', 'http:', $contact['notify']);
			$contact['poll']    = 	str_replace('https:', 'http:', $contact['poll']);
			$contact['confirm'] = 	str_replace('https:', 'http:', $contact['confirm']);
			$contact['poco']    = 	str_replace('https:', 'http:', $contact['poco']);
		}

		if ((intval($new_policy) == BaseURL::SSL_POLICY_FULL || $new_policy === 'full') && strstr($contact['url'], 'http:')) {
			$ssl_changed = true;
			$contact['url']     = 	str_replace('http:', 'https:', $contact['url']);
			$contact['request'] = 	str_replace('http:', 'https:', $contact['request']);
			$contact['notify']  = 	str_replace('http:', 'https:', $contact['notify']);
			$contact['poll']    = 	str_replace('http:', 'https:', $contact['poll']);
			$contact['confirm'] = 	str_replace('http:', 'https:', $contact['confirm']);
			$contact['poco']    = 	str_replace('http:', 'https:', $contact['poco']);
		}

		if ($ssl_changed) {
			$fields = ['url' => $contact['url'], 'request' => $contact['request'],
					'notify' => $contact['notify'], 'poll' => $contact['poll'],
					'confirm' => $contact['confirm'], 'poco' => $contact['poco']];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		}

		return $contact;
	}

	/**
	 * @param array  $importer Owner (local user) data
	 * @param array  $contact  Existing owner-specific contact data we want to expand the relationship with. Optional.
	 * @param array  $datarray An item-like array with at least the 'author-id' and 'author-url' keys for the contact. Mandatory.
	 * @param bool   $sharing  True: Contact is now sharing with Owner; False: Contact is now following Owner (default)
	 * @param string $note     Introduction additional message
	 * @return bool|null True: follow request is accepted; False: relationship is rejected; Null: relationship is pending
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function addRelationship(array $importer, array $contact, array $datarray, $sharing = false, $note = '')
	{
		// Should always be set
		if (empty($datarray['author-id'])) {
			return false;
		}

		$fields = ['url', 'name', 'nick', 'avatar', 'photo', 'network', 'blocked'];
		$pub_contact = DBA::selectFirst('contact', $fields, ['id' => $datarray['author-id']]);
		if (!DBA::isResult($pub_contact)) {
			// Should never happen
			return false;
		}

		// Contact is blocked at node-level
		if (self::isBlocked($datarray['author-id'])) {
			return false;
		}

		$url = ($datarray['author-link'] ?? '') ?: $pub_contact['url'];
		$name = $pub_contact['name'];
		$photo = ($pub_contact['avatar'] ?? '') ?: $pub_contact["photo"];
		$nick = $pub_contact['nick'];
		$network = $pub_contact['network'];

		// Ensure that we don't create a new contact when there already is one
		$cid = self::getIdForURL($url, $importer['uid']);
		if (!empty($cid)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		}

		if (!empty($contact)) {
			if (!empty($contact['pending'])) {
				Logger::info('Pending contact request already exists.', ['url' => $url, 'uid' => $importer['uid']]);
				return null;
			}

			// Contact is blocked at user-level
			if (!empty($contact['id']) && !empty($importer['id']) &&
				self::isBlockedByUser($contact['id'], $importer['id'])) {
				return false;
			}

			// Make sure that the existing contact isn't archived
			self::unmarkForArchival($contact);

			if (($contact['rel'] == self::SHARING)
				|| ($sharing && $contact['rel'] == self::FOLLOWER)) {
				DBA::update('contact', ['rel' => self::FRIEND, 'writable' => true, 'pending' => false],
						['id' => $contact['id'], 'uid' => $importer['uid']]);
			}

			// Ensure to always have the correct network type, independent from the connection request method
			self::updateFromProbe($contact['id'], '', true);

			return true;
		} else {
			// send email notification to owner?
			if (DBA::exists('contact', ['nurl' => Strings::normaliseLink($url), 'uid' => $importer['uid'], 'pending' => true])) {
				Logger::log('ignoring duplicated connection request from pending contact ' . $url);
				return null;
			}

			// create contact record
			DBA::insert('contact', [
				'uid'      => $importer['uid'],
				'created'  => DateTimeFormat::utcNow(),
				'url'      => $url,
				'nurl'     => Strings::normaliseLink($url),
				'name'     => $name,
				'nick'     => $nick,
				'photo'    => $photo,
				'network'  => $network,
				'rel'      => self::FOLLOWER,
				'blocked'  => 0,
				'readonly' => 0,
				'pending'  => 1,
				'writable' => 1,
			]);

			$contact_id = DBA::lastInsertId();

			// Ensure to always have the correct network type, independent from the connection request method
			self::updateFromProbe($contact_id, '', true);

			Contact::updateAvatar($photo, $importer["uid"], $contact_id, true);

			$contact_record = DBA::selectFirst('contact', ['id', 'network', 'name', 'url', 'photo'], ['id' => $contact_id]);

			/// @TODO Encapsulate this into a function/method
			$fields = ['uid', 'username', 'email', 'page-flags', 'notify-flags', 'language'];
			$user = DBA::selectFirst('user', $fields, ['uid' => $importer['uid']]);
			if (DBA::isResult($user) && !in_array($user['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE, User::PAGE_FLAGS_COMMUNITY])) {
				// create notification
				$hash = Strings::getRandomHex();

				if (is_array($contact_record)) {
					DBA::insert('intro', ['uid' => $importer['uid'], 'contact-id' => $contact_record['id'],
								'blocked' => false, 'knowyou' => false, 'note' => $note,
								'hash' => $hash, 'datetime' => DateTimeFormat::utcNow()]);
				}

				Group::addMember(User::getDefaultGroup($importer['uid'], $contact_record["network"]), $contact_record['id']);

				if (($user['notify-flags'] & Type::INTRO) &&
					in_array($user['page-flags'], [User::PAGE_FLAGS_NORMAL])) {

					notification([
						'type'         => Type::INTRO,
						'notify_flags' => $user['notify-flags'],
						'language'     => $user['language'],
						'to_name'      => $user['username'],
						'to_email'     => $user['email'],
						'uid'          => $user['uid'],
						'link'         => DI::baseUrl() . '/notifications/intros',
						'source_name'  => ((strlen(stripslashes($contact_record['name']))) ? stripslashes($contact_record['name']) : DI::l10n()->t('[Name Withheld]')),
						'source_link'  => $contact_record['url'],
						'source_photo' => $contact_record['photo'],
						'verb'         => ($sharing ? Activity::FRIEND : Activity::FOLLOW),
						'otype'        => 'intro'
					]);
				}
			} elseif (DBA::isResult($user) && in_array($user['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE, User::PAGE_FLAGS_COMMUNITY])) {
				if (($user['page-flags'] == User::PAGE_FLAGS_FREELOVE) && ($network != Protocol::DIASPORA)) {
					self::createFromProbe($importer, $url, false, $network);
				}

				$condition = ['uid' => $importer['uid'], 'url' => $url, 'pending' => true];
				$fields = ['pending' => false];
				if ($user['page-flags'] == User::PAGE_FLAGS_FREELOVE) {
					$fields['rel'] = Contact::FRIEND;
				}

				DBA::update('contact', $fields, $condition);

				return true;
			}
		}

		return null;
	}

	public static function removeFollower($importer, $contact, array $datarray = [], $item = "")
	{
		if (($contact['rel'] == self::FRIEND) || ($contact['rel'] == self::SHARING)) {
			DBA::update('contact', ['rel' => self::SHARING], ['id' => $contact['id']]);
		} else {
			Contact::remove($contact['id']);
		}
	}

	public static function removeSharer($importer, $contact, array $datarray = [], $item = "")
	{
		if (($contact['rel'] == self::FRIEND) || ($contact['rel'] == self::FOLLOWER)) {
			DBA::update('contact', ['rel' => self::FOLLOWER], ['id' => $contact['id']]);
		} else {
			Contact::remove($contact['id']);
		}
	}

	/**
	 * Create a birthday event.
	 *
	 * Update the year and the birthday.
	 */
	public static function updateBirthdays()
	{
		$condition = [
			'`bd` != ""
			AND `bd` > "0001-01-01"
			AND SUBSTRING(`bd`, 1, 4) != `bdyear`
			AND (`contact`.`rel` = ? OR `contact`.`rel` = ?)
			AND NOT `contact`.`pending`
			AND NOT `contact`.`hidden`
			AND NOT `contact`.`blocked`
			AND NOT `contact`.`archive`
			AND NOT `contact`.`deleted`',
			Contact::SHARING,
			Contact::FRIEND
		];

		$contacts = DBA::select('contact', ['id', 'uid', 'name', 'url', 'bd'], $condition);

		while ($contact = DBA::fetch($contacts)) {
			Logger::log('update_contact_birthday: ' . $contact['bd']);

			$nextbd = DateTimeFormat::utcNow('Y') . substr($contact['bd'], 4);

			if (Event::createBirthday($contact, $nextbd)) {
				// update bdyear
				DBA::update(
					'contact',
					['bdyear' => substr($nextbd, 0, 4), 'bd' => $nextbd],
					['id' => $contact['id']]
				);
			}
		}
		DBA::close($contacts);
	}

	/**
	 * Remove the unavailable contact ids from the provided list
	 *
	 * @param array $contact_ids Contact id list
	 * @return array
	 * @throws \Exception
	 */
	public static function pruneUnavailable(array $contact_ids)
	{
		if (empty($contact_ids)) {
			return [];
		}

		$contacts = Contact::selectToArray(['id'], [
			'id'      => $contact_ids,
			'blocked' => false,
			'pending' => false,
			'archive' => false,
		]);

		return array_column($contacts, 'id');
	}

	/**
	 * Returns a magic link to authenticate remote visitors
	 *
	 * @todo  check if the return is either a fully qualified URL or a relative path to Friendica basedir
	 *
	 * @param string $contact_url The address of the target contact profile
	 * @param string $url         An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLink($contact_url, $url = '')
	{
		if (!Session::isAuthenticated()) {
			return $url ?: $contact_url; // Equivalent to: ($url != '') ? $url : $contact_url;
		}

		$data = self::getProbeDataFromDatabase($contact_url);
		if (empty($data)) {
			return $url ?: $contact_url; // Equivalent to: ($url != '') ? $url : $contact_url;
		}

		// Prevents endless loop in case only a non-public contact exists for the contact URL
		unset($data['uid']);

		return self::magicLinkByContact($data, $url ?: $contact_url);
	}

	/**
	 * Returns a magic link to authenticate remote visitors
	 *
	 * @param integer $cid The contact id of the target contact profile
	 * @param string  $url An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLinkbyId($cid, $url = '')
	{
		$contact = DBA::selectFirst('contact', ['id', 'network', 'url', 'uid'], ['id' => $cid]);

		return self::magicLinkByContact($contact, $url);
	}

	/**
	 * Returns a magic link to authenticate remote visitors
	 *
	 * @param array  $contact The contact array with "uid", "network" and "url"
	 * @param string $url     An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLinkByContact($contact, $url = '')
	{
		$destination = $url ?: $contact['url']; // Equivalent to ($url != '') ? $url : $contact['url'];

		if (!Session::isAuthenticated() || ($contact['network'] != Protocol::DFRN)) {
			return $destination;
		}

		// Only redirections to the same host do make sense
		if (($url != '') && (parse_url($url, PHP_URL_HOST) != parse_url($contact['url'], PHP_URL_HOST))) {
			return $url;
		}

		if (!empty($contact['uid'])) {
			return self::magicLink($contact['url'], $url);
		}

		if (empty($contact['id'])) {
			return $destination;
		}

		$redirect = 'redir/' . $contact['id'];

		if (($url != '') && !Strings::compareLink($contact['url'], $url)) {
			$redirect .= '?url=' . $url;
		}

		return $redirect;
	}

	/**
	 * Remove a contact from all groups
	 *
	 * @param integer $contact_id
	 *
	 * @return boolean Success
	 */
	public static function removeFromGroups($contact_id)
	{
		return DBA::delete('group_member', ['contact-id' => $contact_id]);
	}

	/**
	 * Is the contact a forum?
	 *
	 * @param integer $contactid ID of the contact
	 *
	 * @return boolean "true" if it is a forum
	 */
	public static function isForum($contactid)
	{
		$fields = ['forum', 'prv'];
		$condition = ['id' => $contactid];
		$contact = DBA::selectFirst('contact', $fields, $condition);
		if (!DBA::isResult($contact)) {
			return false;
		}

		// Is it a forum?
		return ($contact['forum'] || $contact['prv']);
	}

	/**
	 * Can the remote contact receive private messages?
	 *
	 * @param array $contact
	 * @return bool
	 */
	public static function canReceivePrivateMessages(array $contact)
	{
		$protocol = $contact['network'] ?? $contact['protocol'] ?? Protocol::PHANTOM;
		$self = $contact['self'] ?? false;

		return in_array($protocol, [Protocol::DFRN, Protocol::DIASPORA, Protocol::ACTIVITYPUB]) && !$self;
	}
}
