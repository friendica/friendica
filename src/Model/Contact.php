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
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
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
use Friendica\Util\Proxy;
use Friendica\Util\Strings;

/**
 * functions for interacting with a contact
 */
class Contact
{
	const DEFAULT_AVATAR_PHOTO = '/images/person-300.jpg';
	const DEFAULT_AVATAR_THUMB = '/images/person-80.jpg';
	const DEFAULT_AVATAR_MICRO = '/images/person-48.jpg';

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

	const LOCK_INSERT = 'contact-insert';

	/**
	 * Account types
	 *
	 * TYPE_UNKNOWN - unknown type
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
	const NOTHING  = 0;
	const FOLLOWER = 1;
	const SHARING  = 2;
	const FRIEND   = 3;
	/**
	 * @}
	 */

	 const MIRROR_DEACTIVATED = 0;
	 const MIRROR_FORWARDED = 1;
	 const MIRROR_OWN_POST = 2;
	 const MIRROR_NATIVE_RESHARE = 3;

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
	 * @param array $fields         field array
	 * @param int   $duplicate_mode Do an update on a duplicate entry
	 *
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public static function insert(array $fields, int $duplicate_mode = Database::INSERT_DEFAULT)
	{
		if (!empty($fields['baseurl']) && empty($fields['gsid'])) {
			$fields['gsid'] = GServer::getID($fields['baseurl'], true);
		}

		if (empty($fields['created'])) {
			$fields['created'] = DateTimeFormat::utcNow();
		}

		$ret = DBA::insert('contact', $fields, $duplicate_mode);
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
	 * Fetches a contact by a given url
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @param array   $fields Field list
	 * @param integer $uid    User ID of the contact
	 * @return array contact array
	 */
	public static function getByURL(string $url, $update = null, array $fields = [], int $uid = 0)
	{
		if ($update || is_null($update)) {
			$cid = self::getIdForURL($url, $uid, $update);
			if (empty($cid)) {
				return [];
			}

			$contact = self::getById($cid, $fields);
			if (empty($contact)) {
				return [];
			}
			return $contact;
		}

		// Add internal fields
		$removal = [];
		if (!empty($fields)) {
			foreach (['id', 'avatar', 'created', 'updated', 'last-update', 'success_update', 'failure_update', 'network'] as $internal) {
				if (!in_array($internal, $fields)) {
					$fields[] = $internal;
					$removal[] = $internal;
				}
			}
		}

		// We first try the nurl (http://server.tld/nick), most common case
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
		
		if (!DBA::isResult($contact)) {
			return [];
		}

		// Update the contact in the background if needed
		$updated = max($contact['success_update'], $contact['created'], $contact['updated'], $contact['last-update'], $contact['failure_update']);
		if (($updated < DateTimeFormat::utc('now -7 days')) && in_array($contact['network'], Protocol::FEDERATED)) {
			Worker::add(PRIORITY_LOW, "UpdateContact", $contact['id']);
		}

		// Remove the internal fields
		foreach ($removal as $internal) {
			unset($contact[$internal]);
		}

		return $contact;
	}

	/**
	 * Fetches a contact for a given user by a given url.
	 * In difference to "getByURL" the function will fetch a public contact when no user contact had been found.
	 *
	 * @param string  $url    profile url
	 * @param integer $uid    User ID of the contact
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @param array   $fields Field list
	 * @return array contact array
	 */
	public static function getByURLForUser(string $url, int $uid = 0, $update = false, array $fields = [])
	{
		if ($uid != 0) {
			$contact = self::getByURL($url, $update, $fields, $uid);
			if (!empty($contact)) {
				if (!empty($contact['id'])) {
					$contact['cid'] = $contact['id'];
					$contact['zid'] = 0;
				}
				return $contact;
			}
		}

		$contact = self::getByURL($url, $update, $fields);
		if (!empty($contact['id'])) {		
			$contact['cid'] = 0;
			$contact['zid'] = $contact['id'];
		}
		return $contact;
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
		if (Contact\User::isBlocked($cid, $uid)) {
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
		$cid = self::getIdForURL($url, $uid);

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
		if (Contact\User::isBlocked($cid, $uid)) {
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
		$cid = self::getIdForURL($url, $uid);

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
		self::updateFromProbe($contact['id']);

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
		return self::getIdForURL($self['url']);
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
			$pcid = self::getIdForURL($contact['url'], 0, false, ['url' => $contact['url']]);
			if (empty($pcid)) {
				return [];
			}
			$ucid = $contact['id'];
		} else {
			$pcid = $contact['id'];
			$ucid = self::getIdForURL($contact['url'], $uid);
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

		$user = DBA::selectFirst('user', ['uid', 'username', 'nickname', 'pubkey', 'prvkey'], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$return = DBA::insert('contact', [
			'uid'         => $user['uid'],
			'created'     => DateTimeFormat::utcNow(),
			'self'        => 1,
			'name'        => $user['username'],
			'nick'        => $user['nickname'],
			'pubkey'      => $user['pubkey'],
			'prvkey'      => $user['prvkey'],
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
		$fields = ['id', 'name', 'nick', 'location', 'about', 'keywords', 'avatar', 'prvkey', 'pubkey',
			'xmpp', 'contact-type', 'forum', 'prv', 'avatar-date', 'url', 'nurl', 'unsearchable',
			'photo', 'thumb', 'micro', 'addr', 'request', 'notify', 'poll', 'confirm', 'poco'];
		$self = DBA::selectFirst('contact', $fields, ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($self)) {
			return;
		}

		$fields = ['nickname', 'page-flags', 'account-type', 'prvkey', 'pubkey'];
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
			'contact-type' => $user['account-type'], 'prvkey' => $user['prvkey'],
			'pubkey' => $user['pubkey'], 'xmpp' => $profile['xmpp']];

		// it seems as if ported accounts can have wrong values, so we make sure that now everything is fine.
		$fields['url'] = DI::baseUrl() . '/profile/' . $user['nickname'];
		$fields['nurl'] = Strings::normaliseLink($fields['url']);
		$fields['addr'] = $user['nickname'] . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3);
		$fields['request'] = DI::baseUrl() . '/dfrn_request/' . $user['nickname'];
		$fields['notify'] = DI::baseUrl() . '/dfrn_notify/' . $user['nickname'];
		$fields['poll'] = DI::baseUrl() . '/dfrn_poll/'. $user['nickname'];
		$fields['confirm'] = DI::baseUrl() . '/dfrn_confirm/' . $user['nickname'];
		$fields['poco'] = DI::baseUrl() . '/poco/' . $user['nickname'];

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
			$fields['photo'] = self::getDefaultAvatar($fields, Proxy::SIZE_SMALL);
			$fields['thumb'] = self::getDefaultAvatar($fields, Proxy::SIZE_THUMB);
			$fields['micro'] = self::getDefaultAvatar($fields, Proxy::SIZE_MICRO);
		}

		$fields['avatar'] = DI::baseUrl() . '/photo/profile/' .$uid . '.' . $file_suffix;
		$fields['forum'] = $user['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$fields['prv'] = $user['page-flags'] == User::PAGE_FLAGS_PRVGROUP;
		$fields['unsearchable'] = !$profile['net-publish'];

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
		if (!DBA::isResult($contact)) {
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
			Logger::info('Empty contact', ['contact' => $contact, 'callstack' => System::callstack(20)]);
		}

		Logger::info('Contact is marked for archival', ['id' => $contact['id'], 'term-date' => $contact['term-date']]);

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
			$fields = ['failed' => false, 'term-date' => DBA::NULL_DATETIME, 'archive' => false];
			$condition = ['uid' => 0, 'network' => Protocol::FEDERATED, 'batch' => $contact['batch'], 'contact-type' => self::TYPE_RELAY];
			DBA::update('contact', $fields, $condition);
		}

		$condition = ['`id` = ? AND (`term-date` > ? OR `archive`)', $contact['id'], DBA::NULL_DATETIME];
		$exists = DBA::exists('contact', $condition);

		// We don't need to update, we never marked this contact for archival
		if (!$exists) {
			return;
		}

		Logger::info('Contact is marked as vital again', ['id' => $contact['id'], 'term-date' => $contact['term-date']]);

		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'batch'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		}

		// It's a miracle. Our dead contact has inexplicably come back to life.
		$fields = ['failed' => false, 'term-date' => DBA::NULL_DATETIME, 'archive' => false];
		DBA::update('contact', $fields, ['id' => $contact['id']]);
		DBA::update('contact', $fields, ['nurl' => Strings::normaliseLink($contact['url']), 'self' => false]);
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
				$unfollow_link = 'unfollow?url=' . urlencode($contact['url']) . '&auto=1';
			} elseif(!$contact['pending']) {
				$follow_link = 'follow?url=' . urlencode($contact['url']) . '&auto=1';
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
	 * @param boolean $update    true = always update, false = never update, null = update when not found
	 * @param array   $default   Default value for creating the contact when everything else fails
	 *
	 * @return integer Contact ID
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getIdForURL($url, $uid = 0, $update = null, $default = [])
	{
		$contact_id = 0;

		if ($url == '') {
			Logger::notice('Empty url, quitting', ['url' => $url, 'user' => $uid, 'default' => $default]);
			return 0;
		}

		$contact = self::getByURL($url, false, ['id', 'network'], $uid);

		if (!empty($contact)) {
			$contact_id = $contact["id"];

			if (empty($update)) {
				Logger::debug('Contact found', ['url' => $url, 'uid' => $uid, 'update' => $update, 'cid' => $contact_id]);
				return $contact_id;
			}
		} elseif ($uid != 0) {
			Logger::debug('Contact does not exist for the user', ['url' => $url, 'uid' => $uid, 'update' => $update]);
			return 0;
		} elseif (empty($default) && !is_null($update) && !$update) {
			Logger::info('Contact not found, update not desired', ['url' => $url, 'uid' => $uid, 'update' => $update]);
			return 0;
		}

		$data = [];

		if (empty($default['network']) || $update) {
			$data = Probe::uri($url, "", $uid);

			// Take the default values when probing failed
			if (!empty($default) && !in_array($data["network"], array_merge(Protocol::NATIVE_SUPPORT, [Protocol::PUMPIO]))) {
				$data = array_merge($data, $default);
			}
		} elseif (!empty($default['network'])) {
			$data = $default;
		}

		if (($uid == 0) && (empty($data['network']) || ($data['network'] == Protocol::PHANTOM))) {
			// Fetch data for the public contact via the first found personal contact
			/// @todo Check if this case can happen at all (possibly with mail accounts?)
			$fields = ['name', 'nick', 'url', 'addr', 'alias', 'avatar', 'contact-type',
				'keywords', 'location', 'about', 'unsearchable', 'batch', 'notify', 'poll',
				'request', 'confirm', 'poco', 'subscribe', 'network', 'baseurl', 'gsid'];

			$personal_contact = DBA::selectFirst('contact', $fields, ["`addr` = ? AND `uid` != 0", $url]);
			if (!DBA::isResult($personal_contact)) {
				$personal_contact = DBA::selectFirst('contact', $fields, ["`nurl` = ? AND `uid` != 0", Strings::normaliseLink($url)]);
			}

			if (DBA::isResult($personal_contact)) {
				Logger::info('Take contact data from personal contact', ['url' => $url, 'update' => $update, 'contact' => $personal_contact, 'callstack' => System::callstack(20)]);
				$data = $personal_contact;
				$data['photo'] = $personal_contact['avatar'];
				$data['account-type'] = $personal_contact['contact-type'];
				$data['hide'] = $personal_contact['unsearchable'];
				unset($data['avatar']);
				unset($data['contact-type']);
				unset($data['unsearchable']);
			}
		}

		if (empty($data['network']) || ($data['network'] == Protocol::PHANTOM)) {
			Logger::notice('No valid network found', ['url' => $url, 'uid' => $uid, 'default' => $default, 'update' => $update, 'callstack' => System::callstack(20)]);
			return 0;
		}

		if (!$contact_id) {
			$urls = [Strings::normaliseLink($url), Strings::normaliseLink($data['url'])];
			if (!empty($data['alias'])) {
				$urls[] = Strings::normaliseLink($data['alias']);
			}
			$contact = self::selectFirst(['id'], ['nurl' => $urls, 'uid' => $uid]);
			if (!empty($contact['id'])) {
				$contact_id = $contact['id'];
				Logger::info('Fetched id by url', ['cid' => $contact_id, 'uid' => $uid, 'url' => $url, 'data' => $data]);
			}
		}

		if (!$contact_id) {
			// We only insert the basic data. The rest will be done in "updateFromProbeArray"
			$fields = [
				'uid'       => $uid,
				'url'       => $data['url'],
				'nurl'      => Strings::normaliseLink($data['url']),
				'network'   => $data['network'],
				'created'   => DateTimeFormat::utcNow(),
				'rel'       => self::SHARING,
				'writable'  => 1,
				'blocked'   => 0,
				'readonly'  => 0,
				'pending'   => 0];

			$condition = ['nurl' => Strings::normaliseLink($data["url"]), 'uid' => $uid, 'deleted' => false];

			// Before inserting we do check if the entry does exist now.
			if (DI::lock()->acquire(self::LOCK_INSERT, 0)) {
				$contact = DBA::selectFirst('contact', ['id'], $condition, ['order' => ['id']]);
				if (DBA::isResult($contact)) {
					$contact_id = $contact['id'];
					Logger::notice('Contact had been created (shortly) before', ['id' => $contact_id, 'url' => $url, 'uid' => $uid]);
				} else {
					DBA::insert('contact', $fields);
					$contact_id = DBA::lastInsertId();
					if ($contact_id) {
						Logger::info('Contact inserted', ['id' => $contact_id, 'url' => $url, 'uid' => $uid]);
					}
				}
				DI::lock()->release(self::LOCK_INSERT);
			} else {
				Logger::warning('Contact lock had not been acquired');
			}

			if (!$contact_id) {
				Logger::info('Contact was not inserted', ['url' => $url, 'uid' => $uid]);
				return 0;
			}
		} else {
			Logger::info('Contact will be updated', ['url' => $url, 'uid' => $uid, 'update' => $update, 'cid' => $contact_id]);
		}

		self::updateFromProbeArray($contact_id, $data);

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
	 * @param int    $update      Update mode 
	 * @param int    $parent      Item parent ID for the update mode
	 * @return string posts in HTML
	 * @throws \Exception
	 */
	public static function getPostsFromUrl($contact_url, $thread_mode = false, $update = 0, $parent = 0)
	{
		return self::getPostsFromId(self::getIdForURL($contact_url), $thread_mode, $update, $parent);
	}

	/**
	 * Returns posts from a given contact id
	 *
	 * @param int  $cid         Contact ID
	 * @param bool $thread_mode
	 * @param int  $update      Update mode 
	 * @param int  $parent     Item parent ID for the update mode
	 * @return string posts in HTML
	 * @throws \Exception
	 */
	public static function getPostsFromId($cid, $thread_mode = false, $update = 0, $parent = 0)
	{
		$a = DI::app();

		$contact = DBA::selectFirst('contact', ['contact-type', 'network'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (empty($contact["network"]) || in_array($contact["network"], Protocol::FEDERATED)) {
			$sql = "(`uid` = 0 OR (`uid` = ? AND NOT `global`))";
		} else {
			$sql = "`uid` = ?";
		}

		$contact_field = ((($contact["contact-type"] == self::TYPE_COMMUNITY) || ($contact['network'] == Protocol::MAIL)) ? 'owner-id' : 'author-id');

		if ($thread_mode) {
			$condition = ["((`$contact_field` = ? AND `gravity` = ?) OR (`author-id` = ? AND `gravity` = ? AND `vid` = ?)) AND " . $sql,
				$cid, GRAVITY_PARENT, $cid, GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), local_user()];
		} else {
			$condition = ["`$contact_field` = ? AND `gravity` IN (?, ?) AND " . $sql,
				$cid, GRAVITY_PARENT, GRAVITY_COMMENT, local_user()];
		}

		if (!empty($parent)) {
			$condition = DBA::mergeConditions($condition, ['parent' => $parent]);
		} else {
			$last_received = isset($_GET['last_received']) ? DateTimeFormat::utc($_GET['last_received']) : '';
			if (!empty($last_received)) {
				$condition = DBA::mergeConditions($condition, ["`received` < ?", $last_received]);
			}
		}

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_mobile_network',
				DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = DI::pConfig()->get(local_user(), 'system', 'itemspage_network',
				DI::config()->get('system', 'itemspage_network'));
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		$params = ['order' => ['received' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o = Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		} else {
			$o = '';
		}

		if ($thread_mode) {		
			$items = Post::toArray(Post::selectForUser(local_user(), ['uri', 'gravity', 'parent-uri', 'thr-parent-id', 'author-id'], $condition, $params));

			$o .= conversation($a, $items, 'contacts', $update, false, 'commented', local_user());
		} else {
			$items = Post::toArray(Post::selectForUser(local_user(), [], $condition, $params));

			$o .= conversation($a, $items, 'contact-posts', $update);
		}

		if (!$update) {
			if (DI::pConfig()->get(local_user(), 'system', 'infinite_scroll')) {
				$o .= HTML::scrollLoader();
			} else {
				$o .= $pager->renderMinimal(count($items));
			}
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
	 * Ensure that cached avatar exist
	 *
	 * @param integer $cid
	 */
	public static function checkAvatarCache(int $cid)
	{
		$contact = DBA::selectFirst('contact', ['url', 'avatar', 'photo', 'thumb', 'micro'], ['id' => $cid, 'uid' => 0, 'self' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		if (empty($contact['avatar']) || (!empty($contact['photo']) && !empty($contact['thumb']) && !empty($contact['micro']))) {
			return;
		}

		Logger::info('Adding avatar cache', ['id' => $cid, 'contact' => $contact]);

		self::updateAvatar($cid, $contact['avatar'], true);
	}

	/**
	 * Return the photo path for a given contact array in the given size
	 *
	 * @param array $contact  contact array
	 * @param string $field   Fieldname of the photo in the contact array
	 * @param string $size    Size of the avatar picture
	 * @param string $avatar  Avatar path that is displayed when no photo had been found
	 * @return string photo path
	 */
	private static function getAvatarPath(array $contact, string $field, string $size, string $avatar)
	{
		if (!empty($contact)) {
			$contact = self::checkAvatarCacheByArray($contact);
			if (!empty($contact[$field])) {
				$avatar = $contact[$field];
			}
		}

		if (empty($avatar)) {
			$avatar = self::getDefaultAvatar([], $size);
		}

		if (Proxy::isLocalImage($avatar)) {
			return $avatar;
		} else {
			return Proxy::proxifyUrl($avatar, false, $size);
		}
	}

	/**
	 * Return the photo path for a given contact array
	 *
	 * @param array $contact Contact array
	 * @param string $avatar  Avatar path that is displayed when no photo had been found
	 * @return string photo path
	 */
	public static function getPhoto(array $contact, string $avatar = '')
	{
		return self::getAvatarPath($contact, 'photo', Proxy::SIZE_SMALL, $avatar);
	}

	/**
	 * Return the photo path (thumb size) for a given contact array
	 *
	 * @param array $contact Contact array
	 * @param string $avatar  Avatar path that is displayed when no photo had been found
	 * @return string photo path
	 */
	public static function getThumb(array $contact, string $avatar = '')
	{
		return self::getAvatarPath($contact, 'thumb', Proxy::SIZE_THUMB, $avatar);
	}

	/**
	 * Return the photo path (micro size) for a given contact array
	 *
	 * @param array $contact Contact array
	 * @param string $avatar  Avatar path that is displayed when no photo had been found
	 * @return string photo path
	 */
	public static function getMicro(array $contact, string $avatar = '')
	{
		return self::getAvatarPath($contact, 'micro', Proxy::SIZE_MICRO, $avatar);
	}

	/**
	 * Check the given contact array for avatar cache fields
	 *
	 * @param array $contact
	 * @return array contact array with avatar cache fields
	 */
	private static function checkAvatarCacheByArray(array $contact)
	{
		$update = false;
		$contact_fields = [];
		$fields = ['photo', 'thumb', 'micro'];
		foreach ($fields as $field) {
			if (isset($contact[$field])) {
				$contact_fields[] = $field;
			}
			if (isset($contact[$field]) && empty($contact[$field])) {
				$update = true;
			}
		}

		if (!$update) {
			return $contact;
		}

		if (!empty($contact['id']) && !empty($contact['avatar'])) {
			self::updateAvatar($contact['id'], $contact['avatar'], true);

			$new_contact = self::getById($contact['id'], $contact_fields);
			if (DBA::isResult($new_contact)) {
				// We only update the cache fields
				$contact = array_merge($contact, $new_contact);
			}
		}

		/// add the default avatars if the fields aren't filled
		if (isset($contact['photo']) && empty($contact['photo'])) {
			$contact['photo'] = self::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
		}
		if (isset($contact['thumb']) && empty($contact['thumb'])) {
			$contact['thumb'] = self::getDefaultAvatar($contact, Proxy::SIZE_THUMB);
		}
		if (isset($contact['micro']) && empty($contact['micro'])) {
			$contact['micro'] = self::getDefaultAvatar($contact, Proxy::SIZE_MICRO);
		}

		return $contact;
	}

	/**
	 * Fetch the default avatar for the given contact and size
	 *
	 * @param array $contact  contact array
	 * @param string $size    Size of the avatar picture
	 * @return void
	 */
	public static function getDefaultAvatar(array $contact, string $size)
	{
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$avatar['size'] = 48;
				$default = self::DEFAULT_AVATAR_MICRO;
				break;
	
			case Proxy::SIZE_THUMB:
				$avatar['size'] = 80;
				$default = self::DEFAULT_AVATAR_THUMB;
				break;
	
			case Proxy::SIZE_SMALL:
			default:
				$avatar['size'] = 300;
				$default = self::DEFAULT_AVATAR_PHOTO;
				break;
		}

		if (!DI::config()->get('system', 'remote_avatar_lookup')) {
			return DI::baseUrl() . $default;
		}

		if (!empty($contact['xmpp'])) {
			$avatar['email'] = $contact['xmpp'];
		} elseif (!empty($contact['addr'])) {
			$avatar['email'] = $contact['addr'];
		} elseif (!empty($contact['url'])) {
			$avatar['email'] = $contact['url'];
		} else {
			return DI::baseUrl() . $default;
		}

		$avatar['url'] = '';
		$avatar['success'] = false;

		Hook::callAll('avatar_lookup', $avatar);

		if ($avatar['success'] && !empty($avatar['url'])) {
			return $avatar['url'];
		}

		return DI::baseUrl() . $default;
	}

	/**
	 * Updates the avatar links in a contact only if needed
	 *
	 * @param int    $cid          Contact id
	 * @param string $avatar       Link to avatar picture
	 * @param bool   $force        force picture update
	 * @param bool   $create_cache Enforces the creation of cached avatar fields
	 *
	 * @return void
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public static function updateAvatar(int $cid, string $avatar, bool $force = false, bool $create_cache = false)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'avatar', 'photo', 'thumb', 'micro', 'xmpp', 'addr', 'nurl', 'url', 'network'],
			['id' => $cid, 'self' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		$uid = $contact['uid'];

		// Only update the cached photo links of public contacts when they already are cached
		if (($uid == 0) && !$force && empty($contact['thumb']) && empty($contact['micro']) && !$create_cache) {
			if ($contact['avatar'] != $avatar) {
				DBA::update('contact', ['avatar' => $avatar], ['id' => $cid]);
				Logger::info('Only update the avatar', ['id' => $cid, 'avatar' => $avatar, 'contact' => $contact]);
			}
			return;
		}

		// User contacts use are updated through the public contacts
		if (($uid != 0) && !in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			$pcid = self::getIdForURL($contact['url'], false);
			if (!empty($pcid)) {
				Logger::debug('Update the private contact via the public contact', ['id' => $cid, 'uid' => $uid, 'public' => $pcid]);
				self::updateAvatar($pcid, $avatar, $force, true);
				return;
			}
		}

		$default_avatar = empty($avatar) || strpos($avatar, self::DEFAULT_AVATAR_PHOTO);

		if ($default_avatar) {
			$avatar = self::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
		}

		if ($default_avatar && Proxy::isLocalImage($avatar)) {
			$fields = ['avatar' => $avatar, 'avatar-date' => DateTimeFormat::utcNow(),
				'photo' => $avatar,
				'thumb' => self::getDefaultAvatar($contact, Proxy::SIZE_THUMB),
				'micro' => self::getDefaultAvatar($contact, Proxy::SIZE_MICRO)];
			Logger::debug('Use default avatar', ['id' => $cid, 'uid' => $uid]);
		}

		// Use the data from the self account
		if (empty($fields)) {
			$local_uid = User::getIdForURL($contact['url']);
			if (!empty($local_uid)) {
				$fields = self::selectFirst(['avatar', 'avatar-date', 'photo', 'thumb', 'micro'], ['self' => true, 'uid' => $local_uid]);
				Logger::debug('Use owner data', ['id' => $cid, 'uid' => $uid, 'owner-uid' => $local_uid]);
			}
		}

		if (empty($fields)) {
			$update = ($contact['avatar'] != $avatar) || $force;

			if (!$update) {
				$data = [
					$contact['photo'] ?? '',
					$contact['thumb'] ?? '',
					$contact['micro'] ?? '',
				];
		
				foreach ($data as $image_uri) {
					$image_rid = Photo::ridFromURI($image_uri);
					if ($image_rid && !Photo::exists(['resource-id' => $image_rid, 'uid' => $uid])) {
						Logger::debug('Regenerating avatar', ['contact uid' => $uid, 'cid' => $cid, 'missing photo' => $image_rid, 'avatar' => $contact['avatar']]);
						$update = true;
					}
				}
			}

			if ($update) {
				$photos = Photo::importProfilePhoto($avatar, $uid, $cid, true);
				if ($photos) {
					$fields = ['avatar' => $avatar, 'photo' => $photos[0], 'thumb' => $photos[1], 'micro' => $photos[2], 'avatar-date' => DateTimeFormat::utcNow()];
					$update = !empty($fields);
					Logger::debug('Created new cached avatars', ['id' => $cid, 'uid' => $uid, 'owner-uid' => $local_uid]);
				} else {
					$update = false;
				}
			}
		} else {
			$update = ($fields['photo'] . $fields['thumb'] . $fields['micro'] != $contact['photo'] . $contact['thumb'] . $contact['micro']) || $force;
		}

		if (!$update) {
			return;
		}

		$cids = [];
		$uids = [];
		if (($uid == 0) && !in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			// Collect all user contacts of the given public contact
			$personal_contacts = DBA::select('contact', ['id', 'uid'],
				["`nurl` = ? AND `id` != ? AND NOT `self`", $contact['nurl'], $cid]);
			while ($personal_contact = DBA::fetch($personal_contacts)) {
				$cids[] = $personal_contact['id'];
				$uids[] = $personal_contact['uid'];
			}
			DBA::close($personal_contacts);

			if (!empty($cids)) {
				// Delete possibly existing cached user contact avatars
				Photo::delete(['uid' => $uids, 'contact-id' => $cids, 'album' => Photo::CONTACT_PHOTOS]);
			}
		}

		$cids[] = $cid;
		$uids[] = $uid;
		Logger::info('Updating cached contact avatars', ['cid' => $cids, 'uid' => $uids, 'fields' => $fields]);
		DBA::update('contact', $fields, ['id' => $cids]);
	}

	public static function deleteContactByUrl(string $url)
	{
		// Update contact data for all users
		$condition = ['self' => false, 'nurl' => Strings::normaliseLink($url)];
		$contacts = DBA::select('contact', ['id', 'uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			Logger::info('Deleting contact', ['id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $url]);
			self::remove($contact['id']);
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
		if (self::removeDuplicates(Strings::normaliseLink($url), $uid)) {
			return;
		}

		// Archive or unarchive the contact.
		$contact = DBA::selectFirst('contact', [], ['id' => $id]);
		if (!DBA::isResult($contact)) {
			Logger::info('Couldn\'t select contact for archival.', ['id' => $id]);
			return;
		}

		if (isset($fields['failed'])) {
			if ($fields['failed']) {
				self::markForArchival($contact);
			} else {
				self::unmarkForArchival($contact);
			}
		}

		if ($contact['uid'] != 0) {
			return;
		}

		// Update contact data for all users
		$condition = ['self' => false, 'nurl' => Strings::normaliseLink($url)];

		$condition['network'] = [Protocol::DFRN, Protocol::DIASPORA, Protocol::ACTIVITYPUB];
		DBA::update('contact', $fields, $condition);

		// We mustn't set the update fields for OStatus contacts since they are updated in OnePoll
		$condition['network'] = Protocol::OSTATUS;

		// If the contact failed, propagate the update fields to all contacts
		if (empty($fields['failed'])) {
			unset($fields['last-update']);
			unset($fields['success_update']);
			unset($fields['failure_update']);
		}

		if (empty($fields)) {
			return;
		}

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
	 * @return boolean
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbe(int $id, string $network = '')
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url'], ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$ret = Probe::uri($contact['url'], $network, $contact['uid']);
		return self::updateFromProbeArray($id, $ret);
	}

	/**
	 * @param integer $id      contact id
	 * @param array   $ret     Probed data
	 * @return boolean
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function updateFromProbeArray(int $id, array $ret)
	{
		/*
		  Warning: Never ever fetch the public key via Probe::uri and write it into the contacts.
		  This will reliably kill your communication with old Friendica contacts.
		 */

		// These fields aren't updated by this routine:
		// 'xmpp', 'sensitive'

		$fields = ['uid', 'avatar', 'name', 'nick', 'location', 'keywords', 'about', 'subscribe', 'manually-approve',
			'unsearchable', 'url', 'addr', 'batch', 'notify', 'poll', 'request', 'confirm', 'poco',
			'network', 'alias', 'baseurl', 'gsid', 'forum', 'prv', 'contact-type', 'pubkey', 'last-item'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		if (!empty($ret['account-type']) && $ret['account-type'] == User::ACCOUNT_TYPE_DELETED) {
			Logger::info('Deleted account', ['id' => $id, 'url' => $ret['url'], 'ret' => $ret]);
			self::remove($id);

			// Delete all contacts with the same URL
			self::deleteContactByUrl($ret['url']);
			return true;
		}

		$uid = $contact['uid'];
		unset($contact['uid']);

		$pubkey = $contact['pubkey'];
		unset($contact['pubkey']);

		$contact['photo'] = $contact['avatar'];
		unset($contact['avatar']);

		$updated = DateTimeFormat::utcNow();

		// We must not try to update relay contacts via probe. They are no real contacts.
		// We check after the probing to be able to correct falsely detected contact types.
		if (($contact['contact-type'] == self::TYPE_RELAY) &&
			(!Strings::compareLink($ret['url'], $contact['url']) || in_array($ret['network'], [Protocol::FEED, Protocol::PHANTOM]))) {
			self::updateContact($id, $uid, $contact['url'], ['failed' => false, 'last-update' => $updated, 'success_update' => $updated]);
			Logger::info('Not updating relais', ['id' => $id, 'url' => $contact['url']]);
			return true;
		}

		// If Probe::uri fails the network code will be different ("feed" or "unkn")
		if (in_array($ret['network'], [Protocol::FEED, Protocol::PHANTOM]) && ($ret['network'] != $contact['network'])) {
			self::updateContact($id, $uid, $ret['url'], ['failed' => true, 'last-update' => $updated, 'failure_update' => $updated]);
			return false;
		}

		if (isset($ret['hide']) && is_bool($ret['hide'])) {
			$ret['unsearchable'] = $ret['hide'];
		}

		if (isset($ret['account-type']) && is_int($ret['account-type'])) {
			$ret['forum'] = false;
			$ret['prv'] = false;
			$ret['contact-type'] = $ret['account-type'];
			if (($ret['contact-type'] == User::ACCOUNT_TYPE_COMMUNITY) && isset($ret['manually-approve'])) {
				$ret['forum'] = (bool)!$ret['manually-approve'];
				$ret['prv'] = (bool)!$ret['forum'];
			}
		}

		$new_pubkey = $ret['pubkey'] ?? '';

		if ($uid == 0) {
			$ret['last-item'] = Probe::getLastUpdate($ret);
			Logger::info('Fetched last item', ['id' => $id, 'probed_url' => $ret['url'], 'last-item' => $ret['last-item'], 'callstack' => System::callstack(20)]);
		}

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

		if (!empty($ret['last-item']) && ($contact['last-item'] < $ret['last-item'])) {
			$update = true;
		} else {
			unset($ret['last-item']);
		}

		if (!empty($ret['photo']) && ($ret['network'] != Protocol::FEED)) {
			self::updateAvatar($id, $ret['photo'], $update);
		}

		if (!$update) {
			self::updateContact($id, $uid, $ret['url'], ['failed' => false, 'last-update' => $updated, 'success_update' => $updated]);

			if (Contact\Relation::isDiscoverable($ret['url'])) {
				Worker::add(PRIORITY_LOW, 'ContactDiscovery', $ret['url']);
			}
	
			// Update the public contact
			if ($uid != 0) {
				$contact = self::getByURL($ret['url'], false, ['id']);
				if (!empty($contact['id'])) {
					self::updateFromProbeArray($contact['id'], $ret);
				}
			}

			return true;
		}

		$ret['nurl'] = Strings::normaliseLink($ret['url']);
		$ret['updated'] = $updated;
		$ret['failed'] = false;

		// Only fill the pubkey if it had been empty before. We have to prevent identity theft.
		if (empty($pubkey) && !empty($new_pubkey)) {
			$ret['pubkey'] = $new_pubkey;
		}

		if ((!empty($ret['addr']) && ($ret['addr'] != $contact['addr'])) || (!empty($ret['alias']) && ($ret['alias'] != $contact['alias']))) {
			$ret['uri-date'] = DateTimeFormat::utcNow();
		}

		if (($ret['name'] != $contact['name']) || ($ret['nick'] != $contact['nick'])) {
			$ret['name-date'] = $updated;
		}

		if (($uid == 0) || in_array($ret['network'], [Protocol::DFRN, Protocol::DIASPORA, Protocol::ACTIVITYPUB])) {
			$ret['last-update'] = $updated;
			$ret['success_update'] = $updated;
		}

		unset($ret['photo']);

		self::updateContact($id, $uid, $ret['url'], $ret);

		if (Contact\Relation::isDiscoverable($ret['url'])) {
			Worker::add(PRIORITY_LOW, 'ContactDiscovery', $ret['url']);
		}

		return true;
	}

	/**
	 * @param integer $url contact url
	 * @return integer Contact id
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbeByURL($url)
	{
		$id = self::getIdForURL($url);

		if (empty($id)) {
			return $id;
		}

		self::updateFromProbe($id);

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
			$ret = Probe::uri($url, $network, $user['uid']);
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
			$condition = ['uid' => $user['uid'], 'nurl' => Strings::normaliseLink($ret['url']), 'network' => $ret['network'], 'pending' => false];
			$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		}

		$protocol = self::getProtocol($ret['url'], $ret['network']);

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
		if (empty($protocol) || ($protocol == Protocol::PHANTOM) || (empty($ret['url']) && empty($ret['addr']))) {
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
			if (strpos($ret['url'], '@') !== false) {
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
		if (($protocol == Protocol::ACTIVITYPUB) && isset($ret['manually-approve'])) {
			$pending = (bool)$ret['manually-approve'];
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
		self::updateAvatar($contact_id, $ret['photo']);

		// pull feed and consume it, which should subscribe to the hub.
		if ($contact['network'] == Protocol::OSTATUS) {
			Worker::add(PRIORITY_HIGH, 'OnePoll', $contact_id, 'force');
		} else {
			Worker::add(PRIORITY_HIGH, 'UpdateContact', $contact_id);
		}

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
				Contact\User::isBlocked($contact['id'], $importer['id'])) {
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
			self::updateFromProbe($contact['id']);

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
				'network'  => $network,
				'rel'      => self::FOLLOWER,
				'blocked'  => 0,
				'readonly' => 0,
				'pending'  => 1,
				'writable' => 1,
			]);

			$contact_id = DBA::lastInsertId();

			// Ensure to always have the correct network type, independent from the connection request method
			self::updateFromProbe($contact_id);

			self::updateAvatar($contact_id, $photo, true);

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

				if (($user['notify-flags'] & Notification\Type::INTRO) &&
					in_array($user['page-flags'], [User::PAGE_FLAGS_NORMAL])) {

					notification([
						'type'  => Notification\Type::INTRO,
						'otype' => Notification\ObjectType::INTRO,
						'verb'  => ($sharing ? Activity::FRIEND : Activity::FOLLOW),
						'uid'   => $user['uid'],
						'cid'   => $contact_record['id'],
						'link'  => DI::baseUrl() . '/notifications/intros',
					]);
				}
			} elseif (DBA::isResult($user) && in_array($user['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE, User::PAGE_FLAGS_COMMUNITY])) {
				if (($user['page-flags'] == User::PAGE_FLAGS_FREELOVE) && ($network != Protocol::DIASPORA)) {
					self::createFromProbe($importer, $url, false, $network);
				}

				$condition = ['uid' => $importer['uid'], 'url' => $url, 'pending' => true];
				$fields = ['pending' => false];
				if ($user['page-flags'] == User::PAGE_FLAGS_FREELOVE) {
					$fields['rel'] = self::FRIEND;
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
			self::remove($contact['id']);
		}
	}

	public static function removeSharer($importer, $contact, array $datarray = [], $item = "")
	{
		if (($contact['rel'] == self::FRIEND) || ($contact['rel'] == self::FOLLOWER)) {
			DBA::update('contact', ['rel' => self::FOLLOWER], ['id' => $contact['id']]);
		} else {
			self::remove($contact['id']);
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
			self::SHARING,
			self::FRIEND
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

		$contacts = self::selectToArray(['id'], [
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

		$contact = self::getByURL($contact_url, false);
		if (empty($contact)) {
			return $url ?: $contact_url; // Equivalent to: ($url != '') ? $url : $contact_url;
		}

		// Prevents endless loop in case only a non-public contact exists for the contact URL
		unset($contact['uid']);

		return self::magicLinkByContact($contact, $url ?: $contact_url);
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
	public static function magicLinkById($cid, $url = '')
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

		if (!Session::isAuthenticated()) {
			return $destination;
		}

		// Only redirections to the same host do make sense
		if (($url != '') && (parse_url($url, PHP_URL_HOST) != parse_url($contact['url'], PHP_URL_HOST))) {
			return $url;
		}

		if (DI::pConfig()->get(local_user(), 'system', 'stay_local') && ($url == '')) {
			return 'contact/' . $contact['id'] . '/conversations';
		}

		if ($contact['network'] != Protocol::DFRN) {
			return $destination;
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

	/**
	 * Search contact table by nick or name
	 *
	 * @param string $search Name or nick
	 * @param string $mode   Search mode (e.g. "community")
	 *
	 * @return array with search results
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function searchByName($search, $mode = '')
	{
		if (empty($search)) {
			return [];
		}

		// check supported networks
		if (DI::config()->get('system', 'diaspora_enabled')) {
			$diaspora = Protocol::DIASPORA;
		} else {
			$diaspora = Protocol::DFRN;
		}

		if (!DI::config()->get('system', 'ostatus_disabled')) {
			$ostatus = Protocol::OSTATUS;
		} else {
			$ostatus = Protocol::DFRN;
		}

		// check if we search only communities or every contact
		if ($mode === 'community') {
			$extra_sql = sprintf(' AND `contact-type` = %d', self::TYPE_COMMUNITY);
		} else {
			$extra_sql = '';
		}

		$search .= '%';

		$results = DBA::p("SELECT * FROM `contact`
			WHERE NOT `unsearchable` AND `network` IN (?, ?, ?, ?) AND
				NOT `failed` AND `uid` = ? AND
				(`addr` LIKE ? OR `name` LIKE ? OR `nick` LIKE ?) $extra_sql
				ORDER BY `nurl` DESC LIMIT 1000",
			Protocol::DFRN, Protocol::ACTIVITYPUB, $ostatus, $diaspora, 0, $search, $search, $search
		);

		$contacts = DBA::toArray($results);
		return $contacts;
	}

	/**
	 * Add public contacts from an array
	 *
	 * @param array $urls
	 * @return array result "count", "added" and "updated"
	 */
	public static function addByUrls(array $urls)
	{
		$added = 0;
		$updated = 0;
		$unchanged = 0;
		$count = 0;

		foreach ($urls as $url) {
			$contact = self::getByURL($url, false, ['id', 'updated']);
			if (empty($contact['id'])) {
				Worker::add(PRIORITY_LOW, 'AddContact', 0, $url);
				++$added;
			} elseif ($contact['updated'] < DateTimeFormat::utc('now -7 days')) {
				Worker::add(PRIORITY_LOW, 'UpdateContact', $contact['id']);
				++$updated;
			} else {
				++$unchanged;
			}
			++$count;
		}

		return ['count' => $count, 'added' => $added, 'updated' => $updated, 'unchanged' => $unchanged];
	}

	/**
	 * Returns a random, global contact of the current node
	 *
	 * @return string The profile URL
	 * @throws Exception
	 */
	public static function getRandomUrl()
	{
		$r = DBA::selectFirst('contact', ['url'], [
			"`uid` = ? AND `network` = ? AND NOT `failed` AND `last-item` > ?",
			0, Protocol::DFRN, DateTimeFormat::utc('now - 1 month'),
		], ['order' => ['RAND()']]);

		if (DBA::isResult($r)) {
			return $r['url'];
		}

		return '';
	}
}
