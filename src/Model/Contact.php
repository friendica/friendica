<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Exception;
use Friendica\Contact\Avatar;
use Friendica\Contact\Header;
use Friendica\Contact\Introduction\Exception\IntroductionNotFoundException;
use Friendica\Content\Conversation as ConversationContent;
use Friendica\Content\Pager;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Images;
use Friendica\Util\Network;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Worker\UpdateContact;

/**
 * functions for interacting with a contact
 */
class Contact
{
	const DEFAULT_AVATAR_PHOTO = '/images/person-300.jpg';
	const DEFAULT_AVATAR_THUMB = '/images/person-80.jpg';
	const DEFAULT_AVATAR_MICRO = '/images/person-48.jpg';

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
	 * TYPE_COMMUNITY - the account is community group
	 *	Associated page types: PAGE_COMMUNITY, PAGE_PRVGROUP
	 *
	 * TYPE_RELAY - the account is a relay
	 *      This will only be assigned to contacts, not to user accounts
	 * @{
	 */
	const TYPE_UNKNOWN      = -1;
	const TYPE_PERSON       = User::ACCOUNT_TYPE_PERSON;
	const TYPE_ORGANISATION = User::ACCOUNT_TYPE_ORGANISATION;
	const TYPE_NEWS         = User::ACCOUNT_TYPE_NEWS;
	const TYPE_COMMUNITY    = User::ACCOUNT_TYPE_COMMUNITY;
	const TYPE_RELAY        = User::ACCOUNT_TYPE_RELAY;
	/**
	 * @}
	 */

	/**
	 * Contact_is
	 *
	 * Relationship types
	 * @{
	 */
	const NOTHING  = 0; // There is no relationship between the contact and the user
	const FOLLOWER = 1; // The contact is following this user (the contact is the subscriber)
	const SHARING  = 2; // The contact shares their content with this user (the user is the subscriber)
	const FRIEND   = 3; // There is a mutual relationship between the contact and the user
	const SELF     = 4; // This is the user theirself
	/**
	 * @}
	 */

	/**
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array
	 * @throws Exception
	 */
	public static function selectToArray(array $fields = [], array $condition = [], array $params = []): array
	{
		return DBA::selectToArray('contact', $fields, $condition, $params);
	}

	/**
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array|bool
	 * @throws Exception
	 */
	public static function selectFirst(array $fields = [], array $condition = [], array $params = [])
	{
		$contact = DBA::selectFirst('contact', $fields, $condition, $params);

		return $contact;
	}

	/**
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array
	 * @throws Exception
	 */
	public static function selectAccountToArray(array $fields = [], array $condition = [], array $params = []): array
	{
		return DBA::selectToArray('account-user-view', $fields, $condition, $params);
	}

	/**
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array|bool
	 * @throws Exception
	 */
	public static function selectFirstAccount(array $fields = [], array $condition = [], array $params = [])
	{
		return DBA::selectFirst('account-view', $fields, $condition, $params);
	}

	public static function selectFirstAccountUser(array $fields = [], array $condition = [], array $params = [])
	{
		return DBA::selectFirst('account-user-view', $fields, $condition, $params);
	}

	/**
	 * Insert a row into the contact table
	 * Important: You can't use DBA::lastInsertId() after this call since it will be set to 0.
	 *
	 * @param array $fields         field array
	 * @param int   $duplicate_mode Do an update on a duplicate entry
	 *
	 * @return int  id of the created contact
	 * @throws Exception
	 */
	public static function insert(array $fields, int $duplicate_mode = Database::INSERT_DEFAULT): int
	{
		if (!empty($fields['baseurl']) && empty($fields['gsid'])) {
			$fields['gsid'] = GServer::getID($fields['baseurl'], true);
		}

		$fields['uri-id'] = ItemURI::getIdByURI($fields['url']);

		if (empty($fields['created'])) {
			$fields['created'] = DateTimeFormat::utcNow();
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('contact', $fields);
		DBA::insert('contact', $fields, $duplicate_mode);
		$contact = DBA::selectFirst('contact', [], ['id' => DBA::lastInsertId()]);
		if (!DBA::isResult($contact)) {
			// Shouldn't happen
			DI::logger()->warning('Created contact could not be found', ['fields' => $fields]);
			return 0;
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('account-user', $contact);
		DBA::insert('account-user', $fields, Database::INSERT_IGNORE);
		$account_user = DBA::selectFirst('account-user', ['id'], ['uid' => $contact['uid'], 'uri-id' => $contact['uri-id']]);
		if (empty($account_user['id'])) {
			DI::logger()->warning('Account-user entry not found', ['cid' => $contact['id'], 'uid' => $contact['uid'], 'uri-id' => $contact['uri-id'], 'url' => $contact['url']]);
		} elseif ($account_user['id'] != $contact['id']) {
			$duplicate = DBA::selectFirst('contact', [], ['id' => $account_user['id'], 'deleted' => false]);
			if (!empty($duplicate['id'])) {
				$ret = Contact::deleteById($contact['id']);
				DI::logger()->notice('Deleted duplicated contact', ['ret' => $ret, 'account-user' => $account_user, 'cid' => $duplicate['id'], 'uid' => $duplicate['uid'], 'uri-id' => $duplicate['uri-id'], 'url' => $duplicate['url']]);
				$contact = $duplicate;
			} else {
				$ret = DBA::update('account-user', ['id' => $contact['id']], ['uid' => $contact['uid'], 'uri-id' => $contact['uri-id']]);
				DI::logger()->notice('Updated account-user', ['ret' => $ret, 'account-user' => $account_user, 'cid' => $contact['id'], 'uid' => $contact['uid'], 'uri-id' => $contact['uri-id'], 'url' => $contact['url']]);
			}
		}

		Contact\User::insertForContactArray($contact);

		if ((empty($contact['baseurl']) || empty($contact['gsid'])) && Probe::isProbable($contact['network'])) {
			DI::logger()->debug('Update missing baseurl', ['id' => $contact['id'], 'url' => $contact['url'], 'callstack' => System::callstack(4, 0, true)]);
			UpdateContact::add(['priority' => Worker::PRIORITY_MEDIUM, 'dont_fork' => true], $contact['id']);
		}

		return $contact['id'];
	}

	/**
	 * Delete contact by id
	 *
	 * @param integer $id
	 * @return boolean
	 */
	public static function deleteById(int $id): bool
	{
		DI::logger()->debug('Delete contact', ['id' => $id]);
		DBA::delete('account-user', ['id' => $id]);
		return DBA::delete('contact', ['id' => $id]);
	}

	/**
	 * Updates rows in the contact table
	 *
	 * @param array         $fields     contains the fields that are updated
	 * @param array         $condition  condition array with the key values
	 * @param array|boolean $old_fields array with the old field values that are about to be replaced (true = update on duplicate, false = don't update identical fields)
	 *
	 * @return boolean was the update successful?
	 * @throws Exception
	 * @todo Let's get rid of boolean type of $old_fields
	 */
	public static function update(array $fields, array $condition, $old_fields = []): bool
	{
		// Apply changes to the "user-contact" table on dedicated fields
		Contact\User::updateByContactUpdate($fields, $condition);

		$fields = DI::dbaDefinition()->truncateFieldsForTable('contact', $fields);
		return DBA::update('contact', $fields, $condition, $old_fields);
	}

	/**
	 * @param integer $id     Contact ID
	 * @param array   $fields Array of selected fields, empty for all
	 * @return array|boolean Contact record if it exists, false otherwise
	 * @throws Exception
	 */
	public static function getAccountById(int $id, array $fields = [])
	{
		return DBA::selectFirst('account-user-view', $fields, ['id' => $id]);
	}

	/**
	 * @param integer $id     Contact ID
	 * @param array   $fields Array of selected fields, empty for all
	 * @return array|boolean Contact record if it exists, false otherwise
	 * @throws Exception
	 */
	public static function getById(int $id, array $fields = [])
	{
		return DBA::selectFirst('contact', $fields, ['id' => $id]);
	}

	/**
	 * Fetch the first contact with the provided uri-id.
	 *
	 * @param integer $uri_id uri-id of the contact
	 * @param array   $fields Array of selected fields, empty for all
	 * @return array|boolean Contact record if it exists, false otherwise
	 * @throws Exception
	 */
	public static function getByUriId(int $uri_id, array $fields = [])
	{
		return DBA::selectFirst('contact', $fields, ['uri-id' => $uri_id], ['order' => ['uid']]);
	}

	/**
	 * Fetch all remote contacts for a given contact url
	 *
	 * @param string $url The URL of the contact
	 * @param array  $fields The wanted fields
	 *
	 * @return array all remote contacts
	 *
	 * @throws Exception
	 */
	public static function getVisitorByUrl(string $url, array $fields = ['id', 'uid']): array
	{
		$remote = [];

		$remote_contacts = DBA::select('contact', ['id', 'uid'], ['nurl' => Strings::normaliseLink($url), 'rel' => [Contact::FOLLOWER, Contact::FRIEND], 'self' => false]);
		while ($contact = DBA::fetch($remote_contacts)) {
			if (($contact['uid'] == 0) || Contact\User::isBlocked($contact['id'], $contact['uid'])) {
				continue;
			}
			$remote[$contact['uid']] = $contact['id'];
		}
		DBA::close($remote_contacts);

		return $remote;
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
	public static function getByURL(string $url, $update = null, array $fields = [], int $uid = 0): array
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
			foreach (['id', 'next-update', 'network', 'local-data'] as $internal) {
				if (!in_array($internal, $fields)) {
					$fields[]  = $internal;
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
			$ssl_url   = str_replace('http://', 'https://', $url);
			$condition = ['`alias` IN (?, ?, ?) AND `uid` = ? AND NOT `deleted`', $url, Strings::normaliseLink($url), $ssl_url, $uid];
			$contact   = DBA::selectFirst('contact', $fields, $condition, $options);
		}

		if (!DBA::isResult($contact)) {
			return [];
		}

		// Update the contact in the background if needed
		if (UpdateContact::isUpdatable($contact['id'])) {
			try {
				UpdateContact::add(['priority' => Worker::PRIORITY_LOW, 'dont_fork' => true], $contact['id']);
			} catch (\InvalidArgumentException $e) {
				DI::logger()->notice($e->getMessage(), ['contact' => $contact]);
			}
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
	public static function getByURLForUser(string $url, int $uid = 0, $update = false, array $fields = []): array
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
	 * Checks if a contact uses a specific platform
	 *
	 * @param string $url
	 * @param string $platform
	 * @return boolean
	 */
	public static function isPlatform(string $url, string $platform): bool
	{
		return DBA::exists('account-view', ['nurl' => Strings::normaliseLink($url), 'platform' => $platform]);
	}

	/**
	 * Tests if the given contact is a follower
	 *
	 * @param int  $cid    Either public contact id or user's contact id
	 * @param int  $uid    User ID
	 * @param bool $strict If "true" then contact mustn't be set to pending or readonly
	 *
	 * @return boolean is the contact id a follower?
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isFollower(int $cid, int $uid, bool $strict = false): bool
	{
		if (Contact\User::isBlocked($cid, $uid)) {
			return false;
		}

		$ucid = self::getUserContactId($cid, $uid);
		if (!$ucid) {
			return false;
		}

		$condition = ['id' => $ucid, 'rel' => [self::FOLLOWER, self::FRIEND]];
		if ($strict) {
			$condition = array_merge($condition, ['pending' => false, 'readonly' => false, 'blocked' => false]);
		}
		return DBA::exists('contact', $condition);
	}

	/**
	 * Tests if the given contact url is a follower
	 *
	 * @param string $url    Contact URL
	 * @param int    $uid    User ID
	 * @param bool   $strict If "true" then contact mustn't be set to pending or readonly
	 *
	 * @return boolean is the contact id a follower?
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isFollowerByURL(string $url, int $uid, bool $strict = false): bool
	{
		$cid = self::getIdForURL($url, $uid);

		if (empty($cid)) {
			return false;
		}

		return self::isFollower($cid, $uid, $strict);
	}

	/**
	 * Tests if the given user shares with the given contact
	 *
	 * @param int  $cid    Either public contact id or user's contact id
	 * @param int  $uid    User ID
	 * @param bool $strict If "true" then contact mustn't be set to pending or readonly
	 *
	 * @return boolean is the contact sharing with given user?
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSharing(int $cid, int $uid, bool $strict = false): bool
	{
		if (Contact\User::isBlocked($cid, $uid)) {
			return false;
		}

		$ucid = self::getUserContactId($cid, $uid);
		if (!$ucid) {
			return false;
		}

		$condition = ['id' => $ucid, 'rel' => [self::SHARING, self::FRIEND]];
		if ($strict) {
			$condition = array_merge($condition, ['pending' => false, 'readonly' => false, 'blocked' => false]);
		}
		return DBA::exists('contact', $condition);
	}

	/**
	 * Tests if the given user follow the given contact url
	 *
	 * @param string $url    Contact URL
	 * @param int    $uid    User ID
	 * @param bool   $strict If "true" then contact mustn't be set to pending or readonly
	 *
	 * @return boolean is the contact url being followed?
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSharingByURL(string $url, int $uid, bool $strict = false): bool
	{
		$cid = self::getIdForURL($url, $uid);

		if (empty($cid)) {
			return false;
		}

		return self::isSharing($cid, $uid, $strict);
	}

	/**
	 * Checks if the provided public contact id has got followers on this system
	 *
	 * @param integer $cid
	 * @return boolean
	 */
	public static function hasFollowers(int $cid): bool
	{
		return DBA::exists('account-user-view', ["`pid` = ? AND `uid` != ? AND `rel` IN (?, ?)", $cid, 0, self::SHARING, self::FRIEND]);
	}

	/**
	 * Checks if the provided public contact id has got relations with someone on this system
	 *
	 * @param integer $cid Public Contact Id
	 * @return boolean Contact has followers or sharers on this system
	 */
	public static function hasRelations(int $cid): bool
	{
		return DBA::exists('account-user-view', ["`pid` = ? AND `uid` != ? AND `rel` IN (?, ?, ?)", $cid, 0, self::FOLLOWER, self::SHARING, self::FRIEND]);
	}

	/**
	 * Get the basepath for a given contact link
	 *
	 * @param string $url The contact link
	 * @param boolean $dont_update Don't update the contact
	 *
	 * @return string basepath
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getBasepath(string $url, bool $dont_update = false): string
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
			DI::logger()->info('No baseurl for contact', ['url' => $url]);
			return '';
		}

		DI::logger()->info('Found baseurl for contact', ['url' => $url, 'baseurl' => $contact['baseurl']]);
		return $contact['baseurl'];
	}

	/**
	 * Check if the given contact url is on the same server
	 *
	 * @param string $url The contact link
	 *
	 * @return boolean Is it the same server?
	 */
	public static function isLocal(string $url): bool
	{
		if (!parse_url($url, PHP_URL_SCHEME)) {
			$addr_parts = explode('@', $url);
			return (count($addr_parts) == 2) && ($addr_parts[1] == DI::baseUrl()->getHost());
		}

		return Strings::compareLink(self::getBasepath($url, true), DI::baseUrl());
	}

	/**
	 * Check if the given contact ID is on the same server
	 *
	 * @param int $cid The contact link
	 * @return boolean Is it the same server?
	 */
	public static function isLocalById(int $cid): bool
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
	 * @throws Exception
	 */
	public static function getPublicIdByUserId(int $uid)
	{
		$self = self::selectFirstAccountUser(['pid'], ['self' => true, 'uid' => $uid]);
		return $self['pid'] ?? false;
	}

	/**
	 * Returns the contact id for the user and the public contact id for a given contact id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return array with public and user's contact id
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getPublicAndUserContactID(int $cid, int $uid): array
	{
		// We have to use the legacy function as long as the post update hasn't finished
		if (DI::keyValue()->get('post_update_version') < 1427) {
			return self::legacyGetPublicAndUserContactID($cid, $uid);
		}

		if (empty($uid) || empty($cid)) {
			return [];
		}

		$contact = DBA::selectFirst('account-user-view', ['id', 'uid', 'pid'], ['id' => $cid]);
		if (!DBA::isResult($contact) || !in_array($contact['uid'], [0, $uid])) {
			return [];
		}

		$pcid = $contact['pid'];
		if ($contact['uid'] == $uid) {
			$ucid = $contact['id'];
		} else {
			$contact = DBA::selectFirst('account-user-view', ['id', 'uid'], ['pid' => $cid, 'uid' => $uid]);
			if (DBA::isResult($contact)) {
				$ucid = $contact['id'];
			} else {
				$ucid = 0;
			}
		}

		return ['public' => $pcid, 'user' => $ucid];
	}

	/**
	 * Returns the public contact id of a provided contact id
	 *
	 * @param integer $cid
	 * @param integer $uid
	 * @return integer
	 */
	public static function getPublicContactId(int $cid, int $uid): int
	{
		$contact = DBA::selectFirst('account-user-view', ['pid'], ['id' => $cid, 'uid' => [0, $uid]]);
		return $contact['pid'] ?? 0;
	}

	/**
	 * Returns the user contact id of a provided contact id
	 *
	 * @param integer $cid
	 * @param integer $uid
	 * @return integer
	 */
	public static function getUserContactId(int $cid, int $uid): int
	{
		$data = self::getPublicAndUserContactID($cid, $uid);
		return $data['user'] ?? 0;
	}

	/**
	 * Helper function for "getPublicAndUserContactID"
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 * @return array with public and user's contact id
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function legacyGetPublicAndUserContactID(int $cid, int $uid): array
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
	 * @return array The contact details
	 *
	 * @throws Exception
	 */
	public static function getContactForUser(int $cid, int $uid, array $fields = []): array
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
	 * @throws InternalServerErrorException
	 */
	public static function createSelfFromUserId(int $uid): bool
	{
		$user = DBA::selectFirst(
			'user',
			['uid', 'username', 'nickname', 'pubkey', 'prvkey'],
			['uid' => $uid, 'account_removed' => false, 'account_expired' => false]
		);
		if (!DBA::isResult($user)) {
			return false;
		}

		$contact = [
			'uid'         => $user['uid'],
			'created'     => DateTimeFormat::utcNow(),
			'self'        => 1,
			'name'        => $user['username'],
			'nick'        => $user['nickname'],
			'pubkey'      => $user['pubkey'],
			'prvkey'      => $user['prvkey'],
			'photo'       => User::getAvatarUrl($user),
			'thumb'       => User::getAvatarUrl($user, Proxy::SIZE_THUMB),
			'micro'       => User::getAvatarUrl($user, Proxy::SIZE_MICRO),
			'blocked'     => 0,
			'pending'     => 0,
			'url'         => DI::baseUrl() . '/profile/' . $user['nickname'],
			'nurl'        => Strings::normaliseLink(DI::baseUrl() . '/profile/' . $user['nickname']),
			'addr'        => $user['nickname'] . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3),
			'notify'      => DI::baseUrl() . '/dfrn_notify/' . $user['nickname'],
			'poll'        => DI::baseUrl() . '/feed/' . $user['nickname'],
			'name-date'   => DateTimeFormat::utcNow(),
			'uri-date'    => DateTimeFormat::utcNow(),
			'avatar-date' => DateTimeFormat::utcNow(),
			'baseurl'     => DI::baseUrl(),
			'closeness'   => 0
		];

		$return = true;

		// Only create the entry if it doesn't exist yet
		if (!DBA::exists('contact', ['uid' => $uid, 'self' => true])) {
			$return = (bool)self::insert($contact);
		}

		// Create the public contact
		if (!DBA::exists('contact', ['nurl' => $contact['nurl'], 'uid' => 0])) {
			$contact['self']   = false;
			$contact['uid']    = 0;
			$contact['prvkey'] = null;

			self::insert($contact, Database::INSERT_IGNORE);
		}

		return $return;
	}

	/**
	 * Updates the self-contact for the provided user id
	 *
	 * @param int  $uid
	 * @param bool $update_avatar Force the avatar update
	 * @return bool "true" if updated
	 * @throws Exception
	 */
	public static function updateSelfFromUserID(int $uid, bool $update_avatar = false): bool
	{
		$fields = [
			'id', 'uri-id', 'name', 'nick', 'location', 'about', 'keywords', 'avatar', 'prvkey', 'pubkey', 'manually-approve',
			'xmpp', 'matrix', 'contact-type', 'forum', 'prv', 'avatar-date', 'url', 'nurl', 'unsearchable',
			'photo', 'thumb', 'micro', 'header', 'addr', 'request', 'notify', 'poll', 'confirm', 'poco', 'network', 'baseurl', 'gsid'
		];
		$self = DBA::selectFirst('contact', $fields, ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($self)) {
			return false;
		}

		$fields = ['uid', 'username', 'nickname', 'page-flags', 'account-type', 'prvkey', 'pubkey'];
		$user   = DBA::selectFirst('user', $fields, ['uid' => $uid, 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$fields = [
			'name', 'photo', 'thumb', 'about', 'address', 'locality', 'region',
			'country-name', 'pub_keywords', 'xmpp', 'matrix', 'net-publish'
		];
		$profile = DBA::selectFirst('profile', $fields, ['uid' => $uid]);
		if (!DBA::isResult($profile)) {
			return false;
		}

		$url = DI::baseUrl() . '/profile/' . $user['nickname'];

		$fields = [
			'name'         => $user['username'],
			'nick'         => $user['nickname'],
			'avatar-date'  => $self['avatar-date'],
			'location'     => Profile::formatLocation($profile),
			'about'        => $profile['about'],
			'keywords'     => $profile['pub_keywords'],
			'contact-type' => $user['account-type'],
			'prvkey'       => $user['prvkey'],
			'pubkey'       => $user['pubkey'],
			'xmpp'         => $profile['xmpp'],
			'matrix'       => $profile['matrix'],
			'network'      => Protocol::DFRN,
			'url'          => $url,
			// it seems as if ported accounts can have wrong values, so we make sure that now everything is fine.
			'nurl'   => Strings::normaliseLink($url),
			'uri-id' => ItemURI::getIdByURI($url),
			'addr'   => $user['nickname'] . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3),
			'notify' => DI::baseUrl() . '/dfrn_notify/' . $user['nickname'],
			'poll'   => DI::baseUrl() . '/feed/' . $user['nickname'],
		];

		$avatar = Photo::selectFirst(['resource-id', 'type'], ['uid' => $uid, 'profile' => true]);
		if (DBA::isResult($avatar)) {
			if ($update_avatar) {
				$fields['avatar-date'] = DateTimeFormat::utcNow();
			}

			// We are adding a timestamp value so that other systems won't use cached content
			$timestamp = strtotime($fields['avatar-date']);

			$prefix = DI::baseUrl() . '/photo/' . $avatar['resource-id'] . '-';
			$suffix = Images::getExtensionByMimeType($avatar['type']) . '?ts=' . $timestamp;

			$fields['photo'] = $prefix . '4' . $suffix;
			$fields['thumb'] = $prefix . '5' . $suffix;
			$fields['micro'] = $prefix . '6' . $suffix;
		} else {
			// We hadn't found a photo entry, so we use the default avatar
			$fields['photo'] = self::getDefaultAvatar($fields, Proxy::SIZE_SMALL);
			$fields['thumb'] = self::getDefaultAvatar($fields, Proxy::SIZE_THUMB);
			$fields['micro'] = self::getDefaultAvatar($fields, Proxy::SIZE_MICRO);
		}

		$fields['avatar']           = User::getAvatarUrl($user);
		$fields['header']           = User::getBannerUrl($user);
		$fields['forum']            = in_array($user['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_COMM_MAN]);
		$fields['prv']              = $user['page-flags'] == User::PAGE_FLAGS_PRVGROUP;
		$fields['unsearchable']     = !$profile['net-publish'];
		$fields['manually-approve'] = in_array($user['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP, User::PAGE_FLAGS_COMM_MAN]);
		$fields['baseurl']          = DI::baseUrl();
		$fields['gsid']             = GServer::getID($fields['baseurl'], true);

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
			self::update($fields, ['id' => $self['id']]);

			// Update the other contacts as well
			unset($fields['prvkey']);
			$fields['self'] = false;
			self::update($fields, ['uri-id' => $self['uri-id'], 'self' => false]);

			// Update the profile
			$fields = [
				'photo' => User::getAvatarUrl($user),
				'thumb' => User::getAvatarUrl($user, Proxy::SIZE_THUMB)
			];

			DBA::update('profile', $fields, ['uid' => $uid]);
		}

		return $update;
	}

	/**
	 * Marks a contact for removal
	 *
	 * @param int $id contact id
	 * @return void
	 * @throws InternalServerErrorException
	 */
	public static function remove(int $id)
	{
		// We want just to make sure that we don't delete our "self" contact
		$contact = DBA::selectFirst('contact', ['uri-id', 'photo', 'thumb', 'micro', 'uid'], ['id' => $id, 'self' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		DBA::delete('account-user', ['id' => $id]);

		self::clearFollowerFollowingEndpointCache($contact['uid']);

		// Archive the contact
		self::update(['archive' => true, 'network' => Protocol::PHANTOM, 'rel' => self::NOTHING, 'deleted' => true], ['id' => $id]);

		if (!DBA::exists('contact', ['uri-id' => $contact['uri-id'], 'deleted' => false])) {
			Avatar::deleteCache($contact);
		}

		// Delete it in the background
		Worker::add(Worker::PRIORITY_MEDIUM, 'Contact\Remove', $id);
	}

	/**
	 * Unfollow the remote contact
	 *
	 * @param array $contact Target user-specific contact (uid != 0) array
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function unfollow(array $contact): void
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Empty network in contact array');
		}

		if (empty($contact['uid'])) {
			throw new \InvalidArgumentException('Unexpected public contact record');
		}

		if (in_array($contact['rel'], [self::SHARING, self::FRIEND])) {
			$pcid = self::getPublicContactId($contact['id'], $contact['uid']);
			if ($pcid) {
				Worker::add(Worker::PRIORITY_HIGH, 'Contact\Unfollow', $pcid, $contact['uid']);
			}
		}

		self::removeSharer($contact, false);
	}

	/**
	 * Revoke follow privileges of the remote user contact
	 *
	 * The local relationship is updated immediately, the eventual remote server is messaged in the background.
	 *
	 * @param array $contact User-specific contact array (uid != 0) to revoke the follow from
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function revokeFollow(array $contact): void
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Empty network in contact array');
		}

		if (empty($contact['uid'])) {
			throw new \InvalidArgumentException('Unexpected public contact record');
		}

		if (in_array($contact['rel'], [self::FOLLOWER, self::FRIEND])) {
			$pcid = self::getPublicContactId($contact['id'], $contact['uid']);
			if ($pcid) {
				Worker::add(Worker::PRIORITY_HIGH, 'Contact\RevokeFollow', $pcid, $contact['uid']);
			}
		}

		self::removeFollower($contact, false);
	}

	/**
	 * Completely severs a relationship with a contact
	 *
	 * @param array $contact User-specific contact (uid != 0) array
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function terminateFriendship(array $contact)
	{
		if (empty($contact['network'])) {
			throw new \InvalidArgumentException('Empty network in contact array');
		}

		if (empty($contact['uid'])) {
			throw new \InvalidArgumentException('Unexpected public contact record');
		}

		$pcid = self::getPublicContactId($contact['id'], $contact['uid']);

		if (in_array($contact['rel'], [self::SHARING, self::FRIEND]) && $pcid) {
			Worker::add(Worker::PRIORITY_HIGH, 'Contact\Unfollow', $pcid, $contact['uid']);
		}

		if (in_array($contact['rel'], [self::FOLLOWER, self::FRIEND]) && $pcid) {
			Worker::add(Worker::PRIORITY_HIGH, 'Contact\RevokeFollow', $pcid, $contact['uid']);
		}

		self::remove($contact['id']);
	}

	private static function clearFollowerFollowingEndpointCache(int $uid)
	{
		if (empty($uid)) {
			return;
		}

		DI::cache()->delete(ActivityPub\Transmitter::CACHEKEY_CONTACTS . 'followers:' . $uid);
		DI::cache()->delete(ActivityPub\Transmitter::CACHEKEY_CONTACTS . 'following:' . $uid);
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
	 * @return void
	 * @throws InternalServerErrorException
	 */
	public static function markForArchival(array $contact)
	{
		if ((!isset($contact['uri-id']) || !isset($contact['url']) || !isset($contact['archive']) || !isset($contact['self']) || !isset($contact['term-date'])) && !empty($contact['id'])) {
			$fields  = ['id', 'uri-id', 'url', 'archive', 'self', 'term-date'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		} elseif (!isset($contact['url']) || !isset($contact['uri-id'])) {
			DI::logger()->info('Empty contact', ['contact' => $contact]);
		}

		DI::logger()->info('Contact is marked for archival', ['id' => $contact['id'], 'archive' => $contact['archive'], 'term-date' => $contact['term-date'], 'url' => $contact['url']]);

		// Contact already archived or "self" contact? => nothing to do
		if ($contact['archive'] || $contact['self']) {
			return;
		}

		if ($contact['term-date'] <= DBA::NULL_DATETIME) {
			self::update(['term-date' => DateTimeFormat::utcNow()], ['uri-id' => $contact['uri-id'], 'self' => false]);
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
				self::update(['archive' => true], ['uri-id' => $contact['uri-id'], 'self' => false]);
			}
		}
	}

	/**
	 * Cancels the archival countdown
	 *
	 * @see   Contact::markForArchival()
	 *
	 * @param array $contact contact to be unmarked for archival
	 * @return void
	 * @throws Exception
	 */
	public static function unmarkForArchival(array $contact)
	{
		// Always unarchive the relay contact entry
		if (!empty($contact['batch']) && !empty($contact['term-date']) && ($contact['term-date'] > DBA::NULL_DATETIME)) {
			$fields    = ['failed' => false, 'term-date' => DBA::NULL_DATETIME, 'archive' => false, 'unsearchable' => true];
			$condition = ['uid' => 0, 'network' => Protocol::FEDERATED, 'batch' => $contact['batch'], 'contact-type' => self::TYPE_RELAY];
			if (!DBA::exists('contact', array_merge($condition, $fields))) {
				self::update($fields, $condition);
			}
		}

		// We don't need to update, we never marked this contact for archival
		$condition = ['`id` = ? AND (`term-date` > ? OR `archive`)', $contact['id'], DBA::NULL_DATETIME];
		if (!DBA::exists('contact', $condition)) {
			return;
		}

		if ((!isset($contact['url']) || !isset($contact['uri-id'])) && !empty($contact['id'])) {
			$fields  = ['id', 'uri-id', 'url', 'batch', 'term-date'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		}

		DI::logger()->info('Contact is marked as vital again', ['id' => $contact['id'], 'term-date' => $contact['term-date'], 'url' => $contact['url']]);

		// It's a miracle. Our dead contact has inexplicably come back to life.
		$fields = ['failed' => false, 'term-date' => DBA::NULL_DATETIME, 'archive' => false];
		self::update($fields, ['uri-id' => $contact['uri-id'], 'self' => false]);
	}

	/**
	 * Returns the data array for the photo menu of a given contact
	 *
	 * @param array $contact contact
	 * @param int   $uid     Visitor user id
	 * @return array
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function photoMenu(array $contact, int $uid): array
	{
		// Anonymous visitor
		if (!$uid) {
			return ['profile' => [DI::l10n()->t('View Profile'), self::magicLinkByContact($contact), true]];
		}

		$pm_url      = '';
		$mention_url = '';
		$status_link = '';
		$photos_link = '';

		if (($contact['network'] === Protocol::DFRN) && !$contact['self'] && empty($contact['pending'])) {
			$profile_link = 'contact/redir/' . $contact['id'];
			$status_link  = $profile_link . '?' . http_build_query(['url' => $contact['url'] . '/status']);
			$photos_link  = $profile_link . '?' . http_build_query(['url' => $contact['url'] . '/photos']);
			$profile_link = $profile_link . '?' . http_build_query(['url' => $contact['url'] . '/profile']);
		} else {
			$profile_link = $contact['url'];
		}

		if ($profile_link === 'mailbox') {
			$profile_link = '';
		}

		if (self::canReceivePrivateMessages($contact) && empty($contact['pending'])) {
			$pm_url = 'message/new/' . $contact['id'];
		}

		$contact_url = 'contact/' . $contact['id'];

		if ($contact['contact-type'] == Contact::TYPE_COMMUNITY) {
			$mention_label = DI::l10n()->t('Post to group');
			$mention_url   = 'compose/0?body=!' . $contact['addr'];
			$network_label = DI::l10n()->t('View group');
		} else {
			$mention_label = DI::l10n()->t('Mention');
			$mention_url   = 'compose/0?body=@' . $contact['addr'];
			$network_label = DI::l10n()->t('Network Posts');
		}
		$network_url = 'contact/' . $contact['id'] . '/conversations';

		$follow_link   = '';
		$unfollow_link = '';
		if (!$contact['self'] && Protocol::supportsFollow($contact['network'])) {
			if ($contact['uid'] && in_array($contact['rel'], [self::SHARING, self::FRIEND])) {
				$unfollow_link = 'contact/unfollow?url=' . urlencode($contact['url']) . '&auto=1';
			} elseif (!$contact['pending']) {
				$follow_link = 'contact/follow?binurl=' . bin2hex($contact['url']) . '&auto=1';
			}
		}

		/**
		 * Menu array:
		 * "name" => [ "Label", "link", (bool)Should the link opened in a new tab? ]
		 */


		if (empty($contact['uid'])) {
			$menu = [
				'profile'  => [DI::l10n()->t('View Profile'), $profile_link, true],
				'network'  => [$network_label, $network_url, false],
				'edit'     => [DI::l10n()->t('View Contact'), $contact_url, false],
				'follow'   => [DI::l10n()->t('Connect/Follow'), $follow_link, true],
				'unfollow' => [DI::l10n()->t('Unfollow'), $unfollow_link, true],
				'mention'  => [$mention_label, $mention_url, false],
			];
		} else {
			$menu = [
				'status'   => [DI::l10n()->t('View Status'), $status_link, true],
				'profile'  => [DI::l10n()->t('View Profile'), $profile_link, true],
				'photos'   => [DI::l10n()->t('View Photos'), $photos_link, true],
				'network'  => [$network_label, $network_url, false],
				'edit'     => [DI::l10n()->t('View Contact'), $contact_url, false],
				'pm'       => [DI::l10n()->t('Send PM'), $pm_url, false],
				'follow'   => [DI::l10n()->t('Connect/Follow'), $follow_link, true],
				'unfollow' => [DI::l10n()->t('Unfollow'), $unfollow_link, true],
				'mention'  => [$mention_label, $mention_url, false],
			];

			if (!empty($contact['pending'])) {
				try {
					$intro          = DI::intro()->selectForContact($contact['id']);
					$menu['follow'] = [DI::l10n()->t('Approve'), 'notifications/intros/' . $intro->id, true];
				} catch (IntroductionNotFoundException $exception) {
					DI::logger()->error('Pending contact doesn\'t have an introduction.', ['exception' => $exception]);
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
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getIdForURL(string $url = null, int $uid = 0, $update = null, array $default = []): int
	{
		$contact_id = 0;

		if (empty($url)) {
			DI::logger()->notice('Empty url, quitting', ['url' => $url, 'user' => $uid, 'default' => $default]);
			return 0;
		}

		$contact = self::getByURL($url, false, ['id', 'network', 'uri-id', 'next-update', 'local-data'], $uid);

		if (!empty($contact)) {
			$contact_id = $contact['id'];

			if (UpdateContact::isUpdatable($contact['id'])) {
				try {
					UpdateContact::add(['priority' => Worker::PRIORITY_LOW, 'dont_fork' => true], $contact['id']);
				} catch (\InvalidArgumentException $e) {
					DI::logger()->notice($e->getMessage(), ['contact' => $contact]);
				}
			}

			if (empty($update) && (!empty($contact['uri-id']) || is_bool($update))) {
				DI::logger()->debug('Contact found', ['url' => $url, 'uid' => $uid, 'update' => $update, 'cid' => $contact_id]);
				return $contact_id;
			}
		} elseif ($uid != 0) {
			DI::logger()->debug('Contact does not exist for the user', ['url' => $url, 'uid' => $uid, 'update' => $update]);
			return 0;
		} elseif (empty($default) && !is_null($update) && !$update) {
			DI::logger()->info('Contact not found, update not desired', ['url' => $url, 'uid' => $uid, 'update' => $update]);
			return 0;
		}

		$data = [];

		if (empty($default['network']) || $update) {
			$data = Probe::uri($url, '', $uid);

			// Take the default values when probing failed
			if (!empty($default) && !in_array($data['network'], array_merge(Protocol::NATIVE_SUPPORT, [Protocol::PUMPIO]))) {
				$data = array_merge($data, $default);
			}
		} elseif (!empty($default['network'])) {
			$data = $default;
		}

		if (($uid == 0) && (empty($data['network']) || ($data['network'] == Protocol::PHANTOM))) {
			// Fetch data for the public contact via the first found personal contact
			/// @todo Check if this case can happen at all (possibly with mail accounts?)
			$fields = [
				'name', 'nick', 'url', 'addr', 'alias', 'avatar', 'header', 'contact-type',
				'keywords', 'location', 'about', 'unsearchable', 'batch', 'notify', 'poll',
				'request', 'confirm', 'poco', 'subscribe', 'network', 'baseurl', 'gsid'
			];

			$personal_contact = DBA::selectFirst('contact', $fields, ["`addr` = ? AND `uid` != 0", $url]);
			if (!DBA::isResult($personal_contact)) {
				$personal_contact = DBA::selectFirst('contact', $fields, ["`nurl` = ? AND `uid` != 0", Strings::normaliseLink($url)]);
			}

			if (DBA::isResult($personal_contact) && !Probe::isProbable($personal_contact['network'])) {
				DI::logger()->info('Take contact data from personal contact', ['url' => $url, 'update' => $update, 'contact' => $personal_contact]);
				$data                 = $personal_contact;
				$data['photo']        = $personal_contact['avatar'];
				$data['account-type'] = $personal_contact['contact-type'];
				$data['hide']         = $personal_contact['unsearchable'];
				unset($data['avatar']);
				unset($data['contact-type']);
				unset($data['unsearchable']);
			}
		}

		if (empty($data['network']) || ($data['network'] == Protocol::PHANTOM)) {
			DI::logger()->notice('No valid network found', ['url' => $url, 'uid' => $uid, 'default' => $default, 'update' => $update]);
			return 0;
		}

		if (!$contact_id && !empty($data['account-type']) && $data['account-type'] == User::ACCOUNT_TYPE_DELETED) {
			DI::logger()->info('Contact is a tombstone. It will not be inserted', ['url' => $url, 'uid' => $uid]);
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
				DI::logger()->info('Fetched id by url', ['cid' => $contact_id, 'uid' => $uid, 'url' => $url, 'data' => $data]);
			}
		}

		if (!$contact_id) {
			// We only insert the basic data. The rest will be done in "updateFromProbeArray"
			$fields = [
				'uid'      => $uid,
				'url'      => $data['url'],
				'baseurl'  => $data['baseurl'] ?? '',
				'nurl'     => Strings::normaliseLink($data['url']),
				'network'  => $data['network'],
				'created'  => DateTimeFormat::utcNow(),
				'rel'      => self::SHARING,
				'writable' => 1,
				'blocked'  => 0,
				'readonly' => 0,
				'pending'  => 0,
			];

			$condition = ['nurl' => Strings::normaliseLink($data['url']), 'uid' => $uid, 'deleted' => false];

			// Before inserting we do check if the entry does exist now.
			$contact = DBA::selectFirst('contact', ['id'], $condition, ['order' => ['id']]);
			if (DBA::isResult($contact)) {
				$contact_id = $contact['id'];
				DI::logger()->notice('Contact had been created (shortly) before', ['id' => $contact_id, 'url' => $url, 'uid' => $uid]);
			} else {
				$contact_id = self::insert($fields);
				if ($contact_id) {
					DI::logger()->info('Contact inserted', ['id' => $contact_id, 'url' => $url, 'uid' => $uid]);
				}
			}

			if (!$contact_id) {
				DI::logger()->warning('Contact was not inserted', ['url' => $url, 'uid' => $uid]);
				return 0;
			}
		} else {
			DI::logger()->info('Contact will be updated', ['url' => $url, 'uid' => $uid, 'update' => $update, 'cid' => $contact_id]);
		}

		if ($data['network'] == Protocol::DIASPORA) {
			try {
				DI::dsprContact()->updateFromProbeArray($data);
			} catch (NotFoundException $e) {
				DI::logger()->notice($e->getMessage(), ['url' => $url, 'data' => $data]);
			} catch (\InvalidArgumentException $e) {
				DI::logger()->notice($e->getMessage(), ['url' => $url, 'data' => $data]);
			}
		} elseif (!empty($data['networks'][Protocol::DIASPORA])) {
			try {
				DI::dsprContact()->updateFromProbeArray($data['networks'][Protocol::DIASPORA]);
			} catch (NotFoundException $e) {
				DI::logger()->notice($e->getMessage(), ['url' => $url, 'data' => $data['networks'][Protocol::DIASPORA]]);
			} catch (\InvalidArgumentException $e) {
				DI::logger()->notice($e->getMessage(), ['url' => $url, 'data' => $data['networks'][Protocol::DIASPORA]]);
			}
		}

		GServer::updateFromProbeArray($data);
		self::updateFromProbeArray($contact_id, $data);

		// Don't return a number for a deleted account
		if (!empty($data['account-type']) && $data['account-type'] == User::ACCOUNT_TYPE_DELETED) {
			DI::logger()->info('Contact is a tombstone', ['url' => $url, 'uid' => $uid]);
			return 0;
		}

		return $contact_id;
	}

	/**
	 * Checks if the contact is archived
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact archived?
	 * @throws InternalServerErrorException
	 */
	public static function isArchived(int $cid): bool
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
	 * @return boolean Is the contact blocked?
	 * @throws InternalServerErrorException
	 */
	public static function isBlocked(int $cid): bool
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
	 * @return boolean Is the contact hidden?
	 * @throws Exception
	 */
	public static function isHidden(int $cid): bool
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
	 * @param string $contact_url  Contact URL
	 * @param int    $uid          User ID
	 * @param bool   $only_media   Only display media content
	 * @param string $last_created Newest creation date, used for paging
	 * @return string posts in HTML
	 * @throws Exception
	 */
	public static function getPostsFromUrl(string $contact_url, int $uid, bool $only_media = false, string $last_created = null): string
	{
		return self::getPostsFromId(self::getIdForURL($contact_url), $uid, $only_media, $last_created);
	}

	/**
	 * Returns posts from a given contact id
	 *
	 * @param int    $cid          Contact ID
	 * @param int    $uid          User ID
	 * @param bool   $only_media   Only display media content
	 * @param string $last_created Newest creation date, used for paging
	 * @return string posts in HTML
	 * @throws Exception
	 */
	public static function getPostsFromId(int $cid, int $uid, bool $only_media = false, string $last_created = null): string
	{
		$contact = DBA::selectFirst('contact', ['contact-type', 'network', 'name', 'nick'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (Contact\User::isIsBlocked($cid, $uid)) {
			return DI::l10n()->t('%s has blocked you', $contact['name'] ?: $contact['nick']);
		}

		if (empty($contact["network"]) || in_array($contact["network"], Protocol::FEDERATED)) {
			$condition = ["(`uid` = 0 OR (`uid` = ? AND NOT `global`))", $uid];
		} else {
			$condition = ["`uid` = ?", $uid];
		}

		$contact_field = ((($contact["contact-type"] == self::TYPE_COMMUNITY) || ($contact['network'] == Protocol::MAIL)) ? 'owner-id' : 'author-id');

		$condition = DBA::mergeConditions($condition, ["`$contact_field` = ? AND `gravity` IN (?, ?)", $cid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]);

		if (!empty($last_created)) {
			$condition = DBA::mergeConditions($condition, ["`created` < ?", $last_created]);
		}

		if ($only_media) {
			$condition = DBA::mergeConditions($condition, [
				"`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO
			]);
		}

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get($uid, 'system', 'itemspage_mobile_network', DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = DI::pConfig()->get($uid, 'system', 'itemspage_network', DI::config()->get('system', 'itemspage_network'));
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		$params = ['order' => ['created' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		if (DI::pConfig()->get($uid, 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o   = Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		} else {
			$o = '';
		}

		$fields = array_merge(Item::DISPLAY_FIELDLIST, ['featured']);
		$items  = Post::toArray(Post::selectForUser($uid, $fields, $condition, $params));

		$o .= DI::conversation()->render($items, ConversationContent::MODE_CONTACT_POSTS);

		if (DI::pConfig()->get($uid, 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal(count($items));
		}

		return $o;
	}

	/**
	 * Returns threads from a given contact id
	 *
	 * @param int  $cid         Contact ID
	 * @param int  $update      Update mode
	 * @param int  $parent      Item parent ID for the update mode
	 * @return string posts in HTML
	 * @throws Exception
	 */
	public static function getThreadsFromId(int $cid, int $uid, int $update = 0, int $parent = 0, string $last_created = ''): string
	{
		$contact = DBA::selectFirst('contact', ['contact-type', 'network', 'name', 'nick'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (Contact\User::isIsBlocked($cid, $uid)) {
			return DI::l10n()->t('%s has blocked you', $contact['name'] ?: $contact['nick']);
		}

		if (empty($contact["network"]) || in_array($contact["network"], Protocol::FEDERATED)) {
			$condition = ["(`uid` = 0 OR (`uid` = ? AND NOT `global`))", $uid];
		} else {
			$condition = ["`uid` = ?", $uid];
		}

		if (!empty($parent)) {
			$condition = DBA::mergeConditions($condition, ['parent' => $parent]);
		} elseif (!empty($last_created)) {
			$condition = DBA::mergeConditions($condition, ["`created` < ?", $last_created]);
		}

		$contact_field = ((($contact["contact-type"] == self::TYPE_COMMUNITY) || ($contact['network'] == Protocol::MAIL)) ? 'owner-id' : 'author-id');

		if (DI::mode()->isMobile()) {
			$itemsPerPage = DI::pConfig()->get($uid, 'system', 'itemspage_mobile_network', DI::config()->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = DI::pConfig()->get($uid, 'system', 'itemspage_network', DI::config()->get('system', 'itemspage_network'));
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

		if (DI::pConfig()->get($uid, 'system', 'infinite_scroll')) {
			$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
			$o   = Renderer::replaceMacros($tpl, ['$reload_uri' => DI::args()->getQueryString()]);
		} else {
			$o = '';
		}

		$condition1 = DBA::mergeConditions($condition, ["`$contact_field` = ? AND `gravity` = ?", $cid, Item::GRAVITY_PARENT]);

		$condition2 = DBA::mergeConditions($condition, [
			"`author-id` = ? AND `gravity` = ? AND `vid` = ? AND `protocol` != ? AND `thr-parent-id` = `parent-uri-id`",
			$cid, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), Conversation::PARCEL_DIASPORA
		]);

		$sql1 = "SELECT `uri-id`, `created` FROM `post-thread-user-view` WHERE " . array_shift($condition1);
		$sql2 = "SELECT `thr-parent-id` AS `uri-id`, `created` FROM `post-user-view` WHERE " . array_shift($condition2);

		$union = array_merge($condition1, $condition2);
		$sql   = $sql1 . " UNION " . $sql2;

		$sql .= " ORDER BY `created` DESC LIMIT ?, ?";
		$union = array_merge($union, [$pager->getStart(), $pager->getItemsPerPage()]);
		$items = Post::toArray(DBA::p($sql, $union));

		if (empty($last_created) && ($pager->getStart() == 0)) {
			$fields = ['uri-id', 'thr-parent-id', 'gravity', 'author-id', 'created'];
			$pinned = Post\Collection::selectToArrayForContact($cid, Post\Collection::FEATURED, $fields);
			$items  = array_merge($items, $pinned);
		}

		$o .= DI::conversation()->render($items, ConversationContent::MODE_CONTACTS, $update, false, 'pinned_created', $uid);

		if (!$update) {
			if (DI::pConfig()->get($uid, 'system', 'infinite_scroll')) {
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
	 * @param int $type type of contact or account
	 * @return string
	 */
	public static function getAccountType(int $type): string
	{
		switch ($type) {
			case self::TYPE_ORGANISATION:
				$account_type = DI::l10n()->t("Organisation");
				break;

			case self::TYPE_NEWS:
				$account_type = DI::l10n()->t('News');
				break;

			case self::TYPE_COMMUNITY:
				$account_type = DI::l10n()->t("Group");
				break;

			case self::TYPE_RELAY:
				$account_type = DI::l10n()->t("Relay");
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
	 * @param int $cid Contact id to block
	 * @param string $reason Block reason
	 * @return bool Whether it was successful
	 */
	public static function block(int $cid, string $reason = null): bool
	{
		$return = self::update(['blocked' => true, 'block_reason' => $reason], ['id' => $cid]);

		return $return;
	}

	/**
	 * Unblocks a contact
	 *
	 * @param int $cid Contact id to unblock
	 * @return bool Whether it was successful
	 */
	public static function unblock(int $cid): bool
	{
		$return = self::update(['blocked' => false, 'block_reason' => null], ['id' => $cid]);

		return $return;
	}

	/**
	 * Ensure that cached avatar exist
	 *
	 * @param integer $cid Contact id
	 */
	public static function checkAvatarCache(int $cid)
	{
		$contact = DBA::selectFirst('contact', ['url', 'network', 'avatar', 'photo', 'thumb', 'micro'], ['id' => $cid, 'uid' => 0, 'self' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		if (DI::baseUrl()->isLocalUrl($contact['url'])) {
			return;
		}

		if (in_array($contact['network'], [Protocol::FEED, Protocol::MAIL]) || DI::config()->get('system', 'cache_contact_avatar')) {
			if (!empty($contact['avatar']) && (empty($contact['photo']) || empty($contact['thumb']) || empty($contact['micro']))) {
				DI::logger()->info('Adding avatar cache', ['id' => $cid, 'contact' => $contact]);
				self::updateAvatar($cid, $contact['avatar'], true);
				return;
			}
		} elseif (Photo::isPhotoURI($contact['photo']) || Photo::isPhotoURI($contact['thumb']) || Photo::isPhotoURI($contact['micro'])) {
			DI::logger()->info('Replacing legacy avatar cache', ['id' => $cid, 'contact' => $contact]);
			self::updateAvatar($cid, $contact['avatar'], true);
			return;
		} elseif (DI::config()->get('system', 'avatar_cache') && (empty($contact['photo']) || empty($contact['thumb']) || empty($contact['micro']))) {
			DI::logger()->info('Adding avatar cache file', ['id' => $cid, 'contact' => $contact]);
			self::updateAvatar($cid, $contact['avatar'], true);
			return;
		}
	}

	/**
	 * Return the photo path for a given contact array in the given size
	 *
	 * @param array  $contact   contact array
	 * @param string $size      Size of the avatar picture
	 * @param bool   $no_update Don't perform an update if no cached avatar was found
	 * @return string photo path
	 */
	private static function getAvatarPath(array $contact, string $size, bool $no_update = false): string
	{
		$contact = self::checkAvatarCacheByArray($contact, $no_update);

		if (DI::config()->get('system', 'avatar_cache')) {
			switch ($size) {
				case Proxy::SIZE_MICRO:
					if (!empty($contact['micro']) && !Photo::isPhotoURI($contact['micro'])) {
						return $contact['micro'];
					}
					break;
				case Proxy::SIZE_THUMB:
					if (!empty($contact['thumb']) && !Photo::isPhotoURI($contact['thumb'])) {
						return $contact['thumb'];
					}
					break;
				case Proxy::SIZE_SMALL:
					if (!empty($contact['photo']) && !Photo::isPhotoURI($contact['photo'])) {
						return $contact['photo'];
					}
					break;
			}
		}

		return self::getAvatarUrlForId($contact['id'] ?? 0, $size, $contact['updated'] ?? '');
	}

	/**
	 * Return the photo path for a given contact array
	 *
	 * @param array  $contact   Contact array
	 * @param bool   $no_update Don't perform an update if no cached avatar was found
	 * @return string photo path
	 */
	public static function getPhoto(array $contact, bool $no_update = false): string
	{
		return self::getAvatarPath($contact, Proxy::SIZE_SMALL, $no_update);
	}

	/**
	 * Return the photo path (thumb size) for a given contact array
	 *
	 * @param array  $contact   Contact array
	 * @param bool   $no_update Don't perform an update if no cached avatar was found
	 * @return string photo path
	 */
	public static function getThumb(array $contact, bool $no_update = false): string
	{
		return self::getAvatarPath($contact, Proxy::SIZE_THUMB, $no_update);
	}

	/**
	 * Return the photo path (micro size) for a given contact array
	 *
	 * @param array  $contact   Contact array
	 * @param bool   $no_update Don't perform an update if no cached avatar was found
	 * @return string photo path
	 */
	public static function getMicro(array $contact, bool $no_update = false): string
	{
		return self::getAvatarPath($contact, Proxy::SIZE_MICRO, $no_update);
	}

	/**
	 * Check the given contact array for avatar cache fields
	 *
	 * @param array $contact
	 * @param bool  $no_update Don't perform an update if no cached avatar was found
	 * @return array contact array with avatar cache fields
	 */
	private static function checkAvatarCacheByArray(array $contact, bool $no_update = false): array
	{
		$update         = false;
		$contact_fields = [];
		$fields         = ['photo', 'thumb', 'micro'];
		foreach ($fields as $field) {
			if (isset($contact[$field])) {
				$contact_fields[] = $field;
			}
			if (isset($contact[$field]) && empty($contact[$field])) {
				$update = true;
			}
		}

		if (!$update || $no_update) {
			return $contact;
		}

		$local = !empty($contact['url']) && DI::baseUrl()->isLocalUrl($contact['url']);

		if (!$local && !empty($contact['id']) && !empty($contact['avatar'])) {
			self::updateAvatar($contact['id'], $contact['avatar'], true);

			$new_contact = self::getById($contact['id'], $contact_fields);
			if (DBA::isResult($new_contact)) {
				// We only update the cache fields
				$contact = array_merge($contact, $new_contact);
			}
		} elseif ($local && !empty($contact['avatar'])) {
			return $contact;
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
	 * Fetch the default header for the given contact
	 *
	 * @param array $contact  contact array
	 * @return string avatar URL
	 */
	public static function getDefaultHeader(array $contact): string
	{
		if (!empty($contact['header'])) {
			return $contact['header'];
		}

		if (!empty($contact['gsid'])) {
			// Use default banners for certain platforms
			$gserver  = DBA::selectFirst('gserver', ['platform'], ['id' => $contact['gsid']]);
			$platform = strtolower($gserver['platform'] ?? '');
		} else {
			$platform = '';
		}

		switch ($platform) {
			case 'friendica':
			case 'friendika':
				$header = DI::baseUrl() . (new Header(DI::config()))->getMastodonBannerPath();
				break;
			case 'diaspora':
				/**
				 * Picture credits
				 * @author  John Liu <https://www.flickr.com/photos/8047705@N02/>
				 * @license CC BY 2.0 https://creativecommons.org/licenses/by/2.0/
				 * @link    https://www.flickr.com/photos/8047705@N02/5572197407
				 */
				$header = DI::baseUrl() . '/images/diaspora-banner.jpg';
				break;
			default:
				/**
				 * Use a random picture.
				 * The service provides random pictures from Unsplash.
				 * @license https://unsplash.com/license
				 */
				$header = 'https://picsum.photos/seed/' . hash('ripemd128', $contact['url']) . '/960/300';
				break;
		}

		return $header;
	}

	/**
	 * Fetch the default avatar for the given contact and size
	 *
	 * @param array $contact  contact array
	 * @param string $size    Size of the avatar picture
	 * @return string avatar URL
	 */
	public static function getDefaultAvatar(array $contact, string $size): string
	{
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$avatar['size'] = 48;
				$default        = self::DEFAULT_AVATAR_MICRO;
				break;

			case Proxy::SIZE_THUMB:
				$avatar['size'] = 80;
				$default        = self::DEFAULT_AVATAR_THUMB;
				break;

			case Proxy::SIZE_SMALL:
			default:
				$avatar['size'] = 300;
				$default        = self::DEFAULT_AVATAR_PHOTO;
				break;
		}

		if (!DI::config()->get('system', 'remote_avatar_lookup')) {
			$platform = '';
			$type     = Contact::TYPE_PERSON;

			if (!empty($contact['id'])) {
				$account  = DBA::selectFirst('account-user-view', ['platform', 'contact-type'], ['id' => $contact['id']]);
				$platform = $account['platform']     ?? '';
				$type     = $account['contact-type'] ?? Contact::TYPE_PERSON;
			}

			if (empty($platform) && !empty($contact['uri-id'])) {
				$account  = DBA::selectFirst('account-user-view', ['platform', 'contact-type'], ['uri-id' => $contact['uri-id']]);
				$platform = $account['platform']     ?? '';
				$type     = $account['contact-type'] ?? Contact::TYPE_PERSON;
			}

			switch ($platform) {
				case 'corgidon':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/msdos621/corgidon/blob/main/public/avatars/original/missing.png
					 */
					$default = '/images/default/corgidon.png';
					break;

				case 'diaspora':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/diaspora/diaspora/
					 */
					$default = '/images/default/diaspora.png';
					break;

				case 'gotosocial':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/superseriousbusiness/gotosocial/blob/main/web/assets/default_avatars/GoToSocial_icon1.svg
					 */
					$default = '/images/default/gotosocial.svg';
					break;

				case 'hometown':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/hometown-fork/hometown/blob/hometown-dev/public/avatars/original/missing.png
					 */
					$default = '/images/default/hometown.png';
					break;

				case 'koyuspace':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/koyuspace/mastodon/blob/main/public/avatars/original/missing.png
					 */
					$default = '/images/default/koyuspace.png';
					break;

				case 'ecko':
				case 'qoto':
				case 'mastodon':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/mastodon/mastodon/tree/main/public/avatars/original/missing.png
					 */
					$default = '/images/default/mastodon.png';
					break;

				case 'peertube':
					if ($type == Contact::TYPE_COMMUNITY) {
						/**
						 * Picture credits
						 * @license GNU Affero General Public License v3.0
						 * @link    https://github.com/Chocobozzz/PeerTube/blob/develop/client/src/assets/images/default-avatar-video-channel.png
						 */
						$default = '/images/default/peertube-channel.png';
					} else {
						/**
						 * Picture credits
						 * @license GNU Affero General Public License v3.0
						 * @link    https://github.com/Chocobozzz/PeerTube/blob/develop/client/src/assets/images/default-avatar-account.png
						 */
						$default = '/images/default/peertube-account.png';
					}
					break;

				case 'pleroma':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://git.pleroma.social/pleroma/pleroma/-/blob/develop/priv/static/images/avi.png
					 */
					$default = '/images/default/pleroma.png';
					break;

				case 'plume':
					/**
					 * Picture credits
					 * @license GNU Affero General Public License v3.0
					 * @link    https://github.com/Plume-org/Plume/blob/main/assets/images/default-avatar.png
					 */
					$default = '/images/default/plume.png';
					break;
			}
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

		$avatar['url']     = '';
		$avatar['success'] = false;

		Hook::callAll('avatar_lookup', $avatar);

		if ($avatar['success'] && !empty($avatar['url'])) {
			return $avatar['url'];
		}

		return DI::baseUrl() . $default;
	}

	/**
	 * Get avatar link for given contact id
	 *
	 * @param integer $cid     contact id
	 * @param string  $size    One of the Proxy::SIZE_* constants
	 * @param string  $updated Contact update date
	 * @param bool    $static  If "true" a parameter is added to convert the avatar to a static one
	 * @return string avatar link
	 */
	public static function getAvatarUrlForId(int $cid, string $size = '', string $updated = '', string $guid = '', bool $static = false): string
	{
		// We have to fetch the "updated" variable when it wasn't provided
		// The parameter can be provided to improve performance
		if (empty($updated)) {
			$account = DBA::selectFirst('account-user-view', ['updated', 'guid'], ['id' => $cid]);
			$updated = $account['updated'] ?? '';
			$guid    = $account['guid']    ?? '';
		}

		$guid = urlencode($guid);

		$url = DI::baseUrl() . '/photo/contact/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;
			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;
			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;
			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;
			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}
		$query_params = [];
		if ($updated) {
			$query_params['ts'] = strtotime($updated);
		}
		if ($static) {
			$query_params['static'] = true;
		}

		return $url . ($guid ?: $cid) . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
	}

	/**
	 * Get avatar link for given contact URL
	 *
	 * @param string  $url  contact url
	 * @param integer $uid  user id
	 * @param string  $size One of the Proxy::SIZE_* constants
	 * @return string avatar link
	 */
	public static function getAvatarUrlForUrl(string $url, int $uid, string $size = ''): string
	{
		$condition = [
			"`nurl` = ? AND ((`uid` = ? AND `network` IN (?, ?)) OR `uid` = ?)",
			Strings::normaliseLink($url), $uid, Protocol::FEED, Protocol::MAIL, 0
		];
		$contact = self::selectFirst(['id', 'updated'], $condition, ['order' => ['uid' => true]]);
		return self::getAvatarUrlForId($contact['id'] ?? 0, $size, $contact['updated'] ?? '');
	}

	/**
	 * Get header link for given contact id
	 *
	 * @param integer $cid     contact id
	 * @param string  $size    One of the Proxy::SIZE_* constants
	 * @param string  $updated Contact update date
	 * @param bool    $static  If "true" a parameter is added to convert the header to a static one
	 * @return string header link
	 */
	public static function getHeaderUrlForId(int $cid, string $size = '', string $updated = '', string $guid = '', bool $static = false): string
	{
		// We have to fetch the "updated" variable when it wasn't provided
		// The parameter can be provided to improve performance
		if (empty($updated) || empty($guid)) {
			$account = DBA::selectFirst('account-user-view', ['updated', 'guid'], ['id' => $cid]);
			$updated = $account['updated'] ?? '';
			$guid    = $account['guid']    ?? '';
		}

		$guid = urlencode($guid);

		$url = DI::baseUrl() . '/photo/header/';
		switch ($size) {
			case Proxy::SIZE_MICRO:
				$url .= Proxy::PIXEL_MICRO . '/';
				break;
			case Proxy::SIZE_THUMB:
				$url .= Proxy::PIXEL_THUMB . '/';
				break;
			case Proxy::SIZE_SMALL:
				$url .= Proxy::PIXEL_SMALL . '/';
				break;
			case Proxy::SIZE_MEDIUM:
				$url .= Proxy::PIXEL_MEDIUM . '/';
				break;
			case Proxy::SIZE_LARGE:
				$url .= Proxy::PIXEL_LARGE . '/';
				break;
		}

		$query_params = [];
		if ($updated) {
			$query_params['ts'] = strtotime($updated);
		}
		if ($static) {
			$query_params['static'] = true;
		}

		return $url . ($guid ?: $cid) . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
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
	 * @throws InternalServerErrorException
	 * @throws NotFoundException
	 * @throws \ImagickException
	 */
	public static function updateAvatar(int $cid, string $avatar, bool $force = false, bool $create_cache = false)
	{
		$contact = DBA::selectFirst(
			'contact',
			['uid', 'avatar', 'photo', 'thumb', 'micro', 'blurhash', 'xmpp', 'addr', 'nurl', 'url', 'network', 'uri-id'],
			['id' => $cid, 'self' => false]
		);
		if (!DBA::isResult($contact)) {
			return;
		}

		if (!empty($avatar) && !Network::isValidHttpUrl($avatar)) {
			DI::logger()->warning('Invalid avatar', ['cid' => $cid, 'avatar' => $avatar]);
			$avatar = '';
		}

		$uid = $contact['uid'];

		// Only update the cached photo links of public contacts when they already are cached
		if (($uid == 0) && !$force && empty($contact['thumb']) && empty($contact['micro']) && !$create_cache) {
			if (($contact['avatar'] != $avatar) || empty($contact['blurhash'])) {
				$update_fields = ['avatar' => $avatar];
				if (!DI::baseUrl()->isLocalUrl($avatar)) {
					try {
						$fetchResult = HTTPSignature::fetchRaw($avatar, 0, [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::IMAGE]]);

						$img_str = $fetchResult->getBodyString();
						if ($fetchResult->isSuccess() && !empty($img_str)) {
							$image = new Image($img_str, $fetchResult->getContentType(), $avatar);
							if ($image->isValid()) {
								$update_fields['blurhash'] = $image->getBlurHash($img_str);
							} else {
								return;
							}
						}
					} catch (\Exception $exception) {
						DI::logger()->notice('Error fetching avatar', ['avatar' => $avatar, 'exception' => $exception]);
						return;
					}
				} elseif (!empty($contact['blurhash'])) {
					$update_fields['blurhash'] = null;
				} else {
					return;
				}

				self::update($update_fields, ['id' => $cid]);
				DI::logger()->info('Only update the avatar', ['id' => $cid, 'avatar' => $avatar, 'contact' => $contact]);
			}
			return;
		}

		// User contacts use are updated through the public contacts
		if (($uid != 0) && !in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			$pcid = self::getIdForURL($contact['url'], 0, false);
			if (!empty($pcid)) {
				DI::logger()->debug('Update the private contact via the public contact', ['id' => $cid, 'uid' => $uid, 'public' => $pcid]);
				self::updateAvatar($pcid, $avatar, $force, true);
				return;
			}
		}

		$default_avatar = empty($avatar) || strpos($avatar, self::DEFAULT_AVATAR_PHOTO);

		if ($default_avatar) {
			$avatar = self::getDefaultAvatar($contact, Proxy::SIZE_SMALL);
		}

		$cache_avatar = DI::config()->get('system', 'cache_contact_avatar');

		// Local contact avatars don't need to be cached
		if ($cache_avatar && DI::baseUrl()->isLocalUrl($contact['url'])) {
			$cache_avatar = !DBA::exists('contact', ['nurl' => $contact['nurl'], 'self' => true]);
		}

		$fields = [];

		if (in_array($contact['network'], [Protocol::FEED, Protocol::MAIL]) || $cache_avatar) {
			if (Avatar::deleteCache($contact)) {
				$force = true;
			}

			if ($default_avatar && Proxy::isLocalImage($avatar)) {
				$fields = [
					'avatar'      => $avatar,
					'avatar-date' => DateTimeFormat::utcNow(),
					'photo'       => $avatar,
					'thumb'       => self::getDefaultAvatar($contact, Proxy::SIZE_THUMB),
					'micro'       => self::getDefaultAvatar($contact, Proxy::SIZE_MICRO)
				];
				DI::logger()->debug('Use default avatar', ['id' => $cid, 'uid' => $uid]);
			}

			$local_uid = 0;

			// Use the data from the self account
			if (empty($fields)) {
				$local_uid = User::getIdForURL($contact['url']);
				if (!empty($local_uid)) {
					$fields = self::selectFirst(['avatar', 'avatar-date', 'photo', 'thumb', 'micro'], ['self' => true, 'uid' => $local_uid]);
					DI::logger()->debug('Use owner data', ['id' => $cid, 'uid' => $uid, 'owner-uid' => $local_uid]);
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
							DI::logger()->debug('Regenerating avatar', ['contact uid' => $uid, 'cid' => $cid, 'missing photo' => $image_rid, 'avatar' => $contact['avatar']]);
							$update = true;
						}
					}
				}

				if ($update) {
					$photos = Photo::importProfilePhoto($avatar, $uid, $cid, true);
					if ($photos) {
						$fields = [
							'avatar'      => $avatar,
							'photo'       => $photos[0],
							'thumb'       => $photos[1],
							'micro'       => $photos[2],
							'blurhash'    => $photos[3],
							'avatar-date' => DateTimeFormat::utcNow(),
						];
						$update = true;
						DI::logger()->debug('Created new cached avatars', ['id' => $cid, 'uid' => $uid, 'owner-uid' => $local_uid]);
					} else {
						$update = false;
					}
				}
			} else {
				$update = ($fields['photo'] . $fields['thumb'] . $fields['micro'] != $contact['photo'] . $contact['thumb'] . $contact['micro']) || $force;
			}
		} else {
			Photo::delete(['uid' => $uid, 'contact-id' => $cid, 'photo-type' => Photo::CONTACT_AVATAR]);
			$fields = Avatar::fetchAvatarContact($contact, $avatar, $force);
			$update = ($avatar . $fields['photo'] . $fields['thumb'] . $fields['micro'] != $contact['avatar'] . $contact['photo'] . $contact['thumb'] . $contact['micro']) || $force;
		}

		if (!$update) {
			return;
		}

		$cids = [];
		$uids = [];
		if (($uid == 0) && !in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			// Collect all user contacts of the given public contact
			$personal_contacts = DBA::select(
				'contact',
				['id', 'uid'],
				["`nurl` = ? AND `id` != ? AND NOT `self`", $contact['nurl'], $cid]
			);
			while ($personal_contact = DBA::fetch($personal_contacts)) {
				$cids[] = $personal_contact['id'];
				$uids[] = $personal_contact['uid'];
			}
			DBA::close($personal_contacts);

			if (!empty($cids)) {
				// Delete possibly existing cached user contact avatars
				Photo::delete(['uid' => $uids, 'contact-id' => $cids, 'photo-type' => Photo::CONTACT_AVATAR]);
			}
		}

		$cids[] = $cid;
		$uids[] = $uid;
		DI::logger()->info('Updating cached contact avatars', ['cid' => $cids, 'uid' => $uids, 'fields' => $fields]);
		self::update($fields, ['id' => $cids]);
	}

	public static function deleteContactByUrl(string $url)
	{
		// Update contact data for all users
		$condition = ['self' => false, 'nurl' => Strings::normaliseLink($url)];
		$contacts  = DBA::select('contact', ['id', 'uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			DI::logger()->info('Deleting contact', ['id' => $contact['id'], 'uid' => $contact['uid'], 'url' => $url]);
			self::remove($contact['id']);
		}
	}

	/**
	 * Updates the poll URL of a contact. This is the right function to call if there is a redirect.
	 *
	 * @param integer $id  contact id
	 * @param string  $url The new URL to use for polling
	 *
	 * @throws Exception
	 */
	public static function updatePollUrl(int $id, string $url)
	{
		self::update(['poll', $url], ['id' => $id]);
	}

	/**
	 * Helper function for "updateFromProbe". Updates personal and public contact
	 *
	 * @param integer $id     contact id
	 * @param integer $uid    user id
	 * @param integer $uri_id Uri-Id
	 * @param string  $url    The profile URL of the contact
	 * @param array   $fields The fields that are updated
	 *
	 * @throws Exception
	 */
	private static function updateContact(int $id, int $uid, int $uri_id, string $url, array $fields)
	{
		if (!self::update($fields, ['id' => $id])) {
			DI::logger()->info('Couldn\'t update contact.', ['id' => $id, 'fields' => $fields]);
			return;
		}

		self::setAccountUser($id, $uid, $uri_id, $url);

		// Archive or unarchive the contact.
		$contact = DBA::selectFirst('contact', [], ['id' => $id]);
		if (!DBA::isResult($contact)) {
			DI::logger()->info('Couldn\'t select contact for archival.', ['id' => $id]);
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

		if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT) && Protocol::supportsProbe($contact['network'])) {
			$condition['network'][] = $contact['network'];
		}

		self::update($fields, $condition);
	}

	/**
	 * Create or update an "account-user" entry
	 *
	 * @param integer $id
	 * @param integer $uid
	 * @param integer $uri_id
	 * @param string $url
	 * @return void
	 */
	public static function setAccountUser(int $id, int $uid, int $uri_id, string $url)
	{
		if (empty($uri_id)) {
			return;
		}

		$account_user = DBA::selectFirst('account-user', ['id', 'uid', 'uri-id'], ['id' => $id]);
		if (!empty($account_user['uri-id']) && ($account_user['uri-id'] != $uri_id)) {
			if ($account_user['uid'] == $uid) {
				$ret = DBA::update('account-user', ['uri-id' => $uri_id], ['id' => $id]);
				DI::logger()->notice('Updated account-user uri-id', ['ret' => $ret, 'account-user' => $account_user, 'cid' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
			} else {
				// This should never happen
				DI::logger()->warning('account-user exists for a different uri-id and uid', ['account_user' => $account_user, 'id' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
			}
		}

		$account_user = DBA::selectFirst('account-user', ['id', 'uid', 'uri-id'], ['uid' => $uid, 'uri-id' => $uri_id]);
		if (!empty($account_user['id'])) {
			if ($account_user['id'] == $id) {
				DI::logger()->debug('account-user already exists', ['id' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
				return;
			} elseif (!DBA::exists('contact', ['id' => $account_user['id'], 'deleted' => false])) {
				$ret = DBA::update('account-user', ['id' => $id], ['uid' => $uid, 'uri-id' => $uri_id]);
				DI::logger()->notice('Updated account-user', ['ret' => $ret, 'account-user' => $account_user, 'cid' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
				return;
			}
			DI::logger()->warning('account-user exists for a different contact id', ['account_user' => $account_user, 'id' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
			Worker::add(Worker::PRIORITY_HIGH, 'MergeContact', $account_user['id'], $id, $uid);
		} elseif (DBA::insert('account-user', ['id' => $id, 'uri-id' => $uri_id, 'uid' => $uid], Database::INSERT_IGNORE)) {
			DI::logger()->notice('account-user was added', ['id' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
		} else {
			DI::logger()->warning('account-user was not added', ['id' => $id, 'uid' => $uid, 'uri-id' => $uri_id, 'url' => $url]);
		}
	}

	/**
	 * Remove duplicated contacts
	 *
	 * @param string  $nurl  Normalised contact url
	 * @param integer $uid   User id
	 * @return boolean
	 * @throws Exception
	 */
	public static function removeDuplicates(string $nurl, int $uid)
	{
		$condition = ['nurl' => $nurl, 'uid' => $uid, 'self' => false, 'deleted' => false, 'network' => Protocol::FEDERATED];
		$count     = DBA::count('contact', $condition);
		if ($count <= 1) {
			return false;
		}

		$first_contact = DBA::selectFirst('contact', ['id', 'network'], $condition, ['order' => ['id']]);
		if (!DBA::isResult($first_contact)) {
			// Shouldn't happen - so we handle it
			return false;
		}

		$first = $first_contact['id'];
		DI::logger()->info('Found duplicates', ['count' => $count, 'first' => $first, 'uid' => $uid, 'nurl' => $nurl]);

		// Find all duplicates
		$condition  = ["`nurl` = ? AND `uid` = ? AND `id` != ? AND NOT `self` AND NOT `deleted`", $nurl, $uid, $first];
		$duplicates = DBA::select('contact', ['id', 'network'], $condition);
		while ($duplicate = DBA::fetch($duplicates)) {
			if (!in_array($duplicate['network'], Protocol::FEDERATED)) {
				continue;
			}

			Worker::add(Worker::PRIORITY_HIGH, 'MergeContact', $first, $duplicate['id'], $uid);
		}
		DBA::close($duplicates);
		DI::logger()->info('Duplicates handled', ['uid' => $uid, 'nurl' => $nurl]);
		return true;
	}

	/**
	 * Perform a contact update if the contact is outdated
	 *
	 * @param integer $id contact id
	 * @return bool
	 */
	public static function updateByIdIfNeeded(int $id): bool
	{
		$contact = self::selectFirst(['url'], ["`id` = ? AND `next-update` < ?", $id, DateTimeFormat::utcNow()]);
		if (empty($contact['url'])) {
			return false;
		}

		if (self::isLocal($contact['url'])) {
			return true;
		}

		$stamp = (float)microtime(true);
		self::updateFromProbe($id);
		DI::logger()->debug('Contact data is updated.', ['duration' => round((float)microtime(true) - $stamp, 3), 'id' => $id, 'url' => $contact['url']]);
		return true;
	}

	/**
	 * Perform a contact update if the contact is outdated
	 *
	 * @param string $url contact url
	 * @return bool
	 */
	public static function updateByUrlIfNeeded(string $url): bool
	{
		$id = self::getIdForURL($url, 0, false);
		if (!empty($id)) {
			return self::updateByIdIfNeeded($id);
		}
		return (bool)self::getIdForURL($url);
	}

	/**
	 * Updates contact record by provided id and optional network
	 *
	 * @param integer $id      contact id
	 * @param string  $network Optional network we are probing for
	 * @return boolean
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbe(int $id, string $network = ''): bool
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url'], ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$data = Probe::uri($contact['url'], $network, $contact['uid']);

		if (in_array($data['network'], Protocol::FEDERATED) && (parse_url($data['url'], PHP_URL_SCHEME) == 'http')) {
			$ssl_url  = str_replace('http://', 'https://', $contact['url']);
			$ssl_data = Probe::uri($ssl_url, $network, $contact['uid']);
			if (($ssl_data['network'] == $data['network']) && (parse_url($ssl_data['url'], PHP_URL_SCHEME) != 'http')) {
				$data = $ssl_data;
			}
		}

		if ($data['network'] == Protocol::DIASPORA) {
			try {
				DI::dsprContact()->updateFromProbeArray($data);
			} catch (NotFoundException $e) {
				DI::logger()->notice($e->getMessage(), ['id' => $id, 'network' => $network, 'contact' => $contact, 'data' => $data]);
			} catch (\InvalidArgumentException $e) {
				DI::logger()->notice($e->getMessage(), ['id' => $id, 'network' => $network, 'contact' => $contact, 'data' => $data]);
			}
		} elseif (!empty($data['networks'][Protocol::DIASPORA])) {
			try {
				DI::dsprContact()->updateFromProbeArray($data['networks'][Protocol::DIASPORA]);
			} catch (NotFoundException $e) {
				DI::logger()->notice($e->getMessage(), ['id' => $id, 'network' => $network, 'contact' => $contact, 'data' => $data]);
			} catch (\InvalidArgumentException $e) {
				DI::logger()->notice($e->getMessage(), ['id' => $id, 'network' => $network, 'contact' => $contact, 'data' => $data]);
			}
		}

		GServer::updateFromProbeArray($data);
		return self::updateFromProbeArray($id, $data);
	}

	/**
	 * Checks if the given contact has got local data
	 *
	 * @param int   $id
	 * @param array $contact
	 */
	private static function hasLocalData(int $id, array $contact): bool
	{
		if (!empty($contact['uri-id']) && DBA::exists('contact', ["`uri-id` = ? AND `uid` != ?", $contact['uri-id'], 0])) {
			// User contacts with the same uri-id exist
			return true;
		} elseif (DBA::exists('contact', ["`nurl` = ? AND `uid` != ?", Strings::normaliseLink($contact['url']), 0])) {
			// User contacts with the same nurl exists (compatibility mode for systems with missing uri-id values)
			return true;
		}
		if (DBA::exists('post-tag', ['cid' => $id])) {
			// Is tagged in a post
			return true;
		}
		if (DBA::exists('user-contact', ['cid' => $id])) {
			// Has got user-contact data
			return true;
		}
		if (Post::exists(['author-id' => $id])) {
			// Posts with this author exist
			return true;
		}
		if (Post::exists(['owner-id' => $id])) {
			// Posts with this owner exist
			return true;
		}
		if (Post::exists(['causer-id' => $id])) {
			// Posts with this causer exist
			return true;
		}
		// We don't have got this contact locally
		return false;
	}

	/**
	 * Updates contact record by provided id and probed data
	 *
	 * @param integer $id      contact id
	 * @param array   $ret     Probed data
	 * @return boolean
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function updateFromProbeArray(int $id, array $ret): bool
	{
		/*
		  Warning: Never ever fetch the public key via Probe::uri and write it into the contacts.
		  This will reliably kill your communication with old Friendica contacts.
		 */

		// These fields aren't updated by this routine:
		// 'sensitive'

		$fields = [
			'uid', 'uri-id', 'avatar', 'header', 'name', 'nick', 'location', 'keywords', 'about', 'subscribe',
			'manually-approve', 'unsearchable', 'url', 'addr', 'batch', 'notify', 'poll', 'request', 'confirm', 'poco',
			'network', 'alias', 'baseurl', 'gsid', 'forum', 'prv', 'contact-type', 'pubkey', 'last-item', 'xmpp', 'matrix',
			'created', 'last-update'
		];

		/** @var array<string,mixed> */
		$contact = DBA::selectFirst('contact', $fields, ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		if (self::isLocal($ret['url'])) {
			if ($contact['uid'] == 0) {
				DI::logger()->info('Local contacts are not updated here.');
			} else {
				self::updateFromPublicContact($id, $contact);
			}
			return true;
		}

		if (!empty($ret['account-type']) && $ret['account-type'] == User::ACCOUNT_TYPE_DELETED) {
			DI::logger()->info('Deleted account', ['id' => $id, 'url' => $ret['url'], 'ret' => $ret]);
			self::remove($id);

			// Delete all contacts with the same URL
			self::deleteContactByUrl($ret['url']);
			return true;
		}

		$has_local_data = self::hasLocalData($id, $contact);

		$uid = $contact['uid'] ?? null;
		unset($contact['uid']);

		$uriid = $contact['uri-id'] ?? null;
		unset($contact['uri-id']);

		$pubkey = $contact['pubkey'] ?? null;
		unset($contact['pubkey']);

		$created = $contact['created'] ?? '';
		unset($contact['created']);

		$last_update = $contact['last-update'] ?? '';
		unset($contact['last-update']);

		$contact['photo'] = $contact['avatar'] ?? null;
		unset($contact['avatar']);

		$updated = DateTimeFormat::utcNow();

		if (!Protocol::supportsProbe($ret['network']) && !Protocol::supportsProbe($contact['network'])) {
			// Periodical checks are only done on federated contacts
			$failed_next_update  = null;
			$success_next_update = null;
		} elseif ($has_local_data) {
			$failed_next_update  = GServer::getNextUpdateDate(false, $created, $last_update, !in_array($contact['network'], Protocol::FEDERATED));
			$success_next_update = GServer::getNextUpdateDate(true, $created, $last_update, !in_array($contact['network'], Protocol::FEDERATED));
		} elseif (in_array($ret['network'], array_merge(Protocol::NATIVE_SUPPORT, [Protocol::ZOT, Protocol::PHANTOM]))) {
			$failed_next_update  = DateTimeFormat::utc('now +6 month');
			$success_next_update = DateTimeFormat::utc('now +1 month');
		} else {
			// We don't check connector networks very often to not run into API rate limits
			$failed_next_update  = DateTimeFormat::utc('now +12 month');
			$success_next_update = DateTimeFormat::utc('now +12 month');
		}

		if (Strings::normaliseLink($contact['url']) != Strings::normaliseLink($ret['url'])) {
			DI::logger()->notice('New URL differs from old URL', ['id' => $id, 'uid' => $uid, 'old' => $contact['url'], 'new' => $ret['url']]);
			self::updateContact($id, $uid, $uriid, $contact['url'], ['failed' => true, 'local-data' => $has_local_data, 'last-update' => $updated, 'next-update' => $failed_next_update, 'failure_update' => $updated]);
			return false;
		}

		// We must not try to update relay contacts via probe. They are no real contacts.
		// See Relay::updateContact() for more details.
		// We check after the probing to be able to correct falsely detected contact types.
		if (($contact['contact-type'] == self::TYPE_RELAY) && Strings::compareLink($contact['url'], $contact['baseurl'] ?? '') &&
			(!Strings::compareLink($ret['url'], $contact['url']) || in_array($ret['network'], [Protocol::FEED, Protocol::PHANTOM]))
		) {
			if (GServer::reachable($contact)) {
				self::updateContact($id, $uid, $uriid, $contact['url'], ['failed' => false, 'local-data' => $has_local_data, 'last-update' => $updated, 'next-update' => $success_next_update, 'success_update' => $updated, 'unsearchable' => true]);
				DI::logger()->info('Not updating relay', ['id' => $id, 'url' => $contact['url']]);
				return true;
			}
			DI::logger()->info('Relay server is not reachable', ['id' => $id, 'url' => $contact['url']]);
			self::updateContact($id, $uid, $uriid, $contact['url'], ['failed' => true, 'local-data' => $has_local_data, 'last-update' => $updated, 'next-update' => $failed_next_update, 'failure_update' => $updated, 'unsearchable' => true]);
			return false;
		}

		// If Probe::uri fails the network code will be different ("feed" or "unkn")
		if (($ret['network'] == Protocol::PHANTOM) || (($ret['network'] == Protocol::FEED) && ($ret['network'] != $contact['network']))) {
			self::updateContact($id, $uid, $uriid, $contact['url'], ['failed' => true, 'local-data' => $has_local_data, 'last-update' => $updated, 'next-update' => $failed_next_update, 'failure_update' => $updated]);
			return false;
		}

		if (Strings::normaliseLink($ret['url']) != Strings::normaliseLink($contact['url'])) {
			$cid = self::getIdForURL($ret['url'], 0, false);
			if (!empty($cid) && ($cid != $id)) {
				DI::logger()->notice('URL of contact changed.', ['id' => $id, 'new_id' => $cid, 'old' => $contact['url'], 'new' => $ret['url']]);
				return self::updateFromProbeArray($cid, $ret);
			}
		}

		if (isset($ret['hide']) && is_bool($ret['hide'])) {
			$ret['unsearchable'] = $ret['hide'];
		}

		if (isset($ret['account-type']) && is_int($ret['account-type'])) {
			$ret['forum']        = false;
			$ret['prv']          = false;
			$ret['contact-type'] = $ret['account-type'];
			if (($ret['contact-type'] == User::ACCOUNT_TYPE_COMMUNITY) && isset($ret['manually-approve'])) {
				$ret['forum'] = (bool)!$ret['manually-approve'];
				$ret['prv']   = (bool)!$ret['forum'];
			}
		}

		$new_pubkey = $ret['pubkey'] ?? '';

		if ($uid == 0 && DI::config()->get('system', 'fetch_featured_posts')) {
			if ($ret['network'] == Protocol::ACTIVITYPUB) {
				$apcontact = APContact::getByURL($ret['url'], false);
				if (!empty($apcontact['featured'])) {
					Worker::add(Worker::PRIORITY_LOW, 'FetchFeaturedPosts', $ret['url']);
				}
			}

			$ret['last-item'] = Probe::getLastUpdate($ret);
			DI::logger()->info('Fetched last item', ['id' => $id, 'probed_url' => $ret['url'], 'last-item' => $ret['last-item']]);
		}

		$update = false;
		$guid   = ($ret['guid'] ?? '') ?: Item::guidFromUri($ret['url'], $ret['baseurl'] ?? $ret['alias'] ?? '');

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

		if (empty($uriid)) {
			$update = true;
		}

		if (!empty($ret['photo']) && ($ret['network'] != Protocol::FEED)) {
			self::updateAvatar($id, $ret['photo'], $update);
		}

		if (!$update) {
			self::updateContact($id, $uid, $uriid, $contact['url'], ['failed' => false, 'local-data' => $has_local_data, 'last-update' => $updated, 'next-update' => $success_next_update, 'success_update' => $updated]);

			if (Contact\Relation::isDiscoverable($ret['url'])) {
				Worker::add(Worker::PRIORITY_LOW, 'ContactDiscovery', $ret['url']);
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

		$ret['uri-id']      = ItemURI::insert(['uri' => $ret['url'], 'guid' => $guid]);
		$ret['nurl']        = Strings::normaliseLink($ret['url']);
		$ret['updated']     = $updated;
		$ret['failed']      = false;
		$ret['next-update'] = $success_next_update;
		$ret['local-data']  = $has_local_data;

		// Only fill the pubkey if it had been empty before. We have to prevent identity theft.
		if (empty($pubkey) && !empty($new_pubkey)) {
			$ret['pubkey'] = $new_pubkey;
		}

		if ((!empty($ret['addr']) && ($ret['addr'] != $contact['addr'])) || (!empty($ret['alias']) && ($ret['alias'] != $contact['alias']))) {
			$ret['uri-date'] = $updated;
		}

		if ((!empty($ret['name']) && ($ret['name'] != $contact['name'])) || (!empty($ret['nick']) && ($ret['nick'] != $contact['nick']))) {
			$ret['name-date'] = $updated;
		}

		if (($uid == 0) || in_array($ret['network'], [Protocol::DFRN, Protocol::DIASPORA, Protocol::ACTIVITYPUB])) {
			$ret['last-update']    = $updated;
			$ret['success_update'] = $updated;
		}

		unset($ret['photo']);

		self::updateContact($id, $uid, $ret['uri-id'], $ret['url'], $ret);

		if (Contact\Relation::isDiscoverable($ret['url'])) {
			Worker::add(Worker::PRIORITY_LOW, 'ContactDiscovery', $ret['url']);
		}

		return true;
	}

	private static function updateFromPublicContact(int $id, array $contact)
	{
		$public = self::getByURL($contact['url'], false);

		$fields = [];

		foreach ($contact as $field => $value) {
			if ($field == 'uid') {
				continue;
			}
			if ($public[$field] != $value) {
				$fields[$field] = $public[$field];
			}
		}
		if (!empty($fields)) {
			self::update($fields, ['id' => $id, 'self' => false]);
			DI::logger()->info('Updating local contact', ['id' => $id]);
		}
	}

	/**
	 * Updates contact record by provided URL
	 *
	 * @param string $url contact url
	 * @return int Contact id
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbeByURL(string $url): int
	{
		$id = self::getIdForURL($url);

		if (empty($id)) {
			return $id;
		}

		self::updateFromProbe($id);

		return $id;
	}

	/**
	 * Detects the communication protocol for a given contact url.
	 * This is used to detect Friendica contacts that we can communicate via AP.
	 *
	 * @param string $url contact url
	 * @param string $network Network of that contact
	 * @return string with protocol
	 */
	public static function getProtocol(string $url, string $network): string
	{
		if (self::isLocal($url)) {
			return Protocol::ACTIVITYPUB;
		}

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
	 * Returns an array
	 * $return['success'] boolean true if successful
	 * $return['message'] error text if success is false.
	 *
	 * Takes a $uid and a url/handle and adds a new contact
	 *
	 * @param int    $uid         The user id the contact should be created for
	 * @param string $url         The profile URL of the contact
	 * @param string $network
	 * @return array
	 * @throws InternalServerErrorException
	 * @throws NotFoundException
	 * @throws \ImagickException
	 */
	public static function createFromProbeForUser(int $uid, string $url, string $network = ''): array
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

		$arr = ['url' => $url, 'uid' => $uid, 'contact' => []];

		Hook::callAll('follow', $arr);

		if (empty($arr)) {
			$result['message'] = DI::l10n()->t('The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.');
			return $result;
		}

		if (!empty($arr['contact']['name'])) {
			$probed = false;
			$ret    = $arr['contact'];
		} else {
			$probed = true;
			$ret    = Probe::uri($url, $network, $uid);

			// Ensure that the public contact exists
			if ($ret['network'] != Protocol::PHANTOM) {
				self::getIdForURL($url);
			}
		}

		if (($network != '') && ($ret['network'] != $network)) {
			$result['message'] = DI::l10n()->t('Expected network %s does not match actual network %s', $network, $ret['network']);
			return $result;
		}

		// check if we already have a contact
		$condition = ['uid' => $uid, 'nurl' => Strings::normaliseLink($ret['url']), 'deleted' => false];
		$contact   = DBA::selectFirst('contact', ['id', 'rel', 'url', 'pending', 'hub-verify'], $condition);

		$protocol = self::getProtocol($ret['url'], $ret['network']);

		// This extra param just confuses things, remove it
		if ($protocol === Protocol::DIASPORA) {
			$ret['url'] = str_replace('?absolute=true', '', $ret['url']);
		}

		if (($protocol == Protocol::ACTIVITYPUB) && ($uid != 0)) {
			if (APContact::isRelay(APContact::getByURL($ret['url']))) {
				$result['message'] = DI::l10n()->t('This seems to be a relay account. They can\'t be followed by users.');
				return $result;
			}
		}

		// do we have enough information?
		if (empty($protocol) || ($protocol == Protocol::PHANTOM) || (empty($ret['url']) && empty($ret['addr']))) {
			$result['message'] .= DI::l10n()->t('The profile address specified does not provide adequate information.') . '<br />';
			if (empty($ret['poll'])) {
				$result['message'] .= DI::l10n()->t('No compatible communication protocols or feeds were discovered.') . '<br />';
			}
			if (empty($ret['name'])) {
				$result['message'] .= DI::l10n()->t('An author or name was not found.') . '<br />';
			}
			if (empty($ret['url'])) {
				$result['message'] .= DI::l10n()->t('No browser URL could be matched to this address.') . '<br />';
			}
			if (strpos($ret['url'], '@') !== false) {
				$result['message'] .= DI::l10n()->t('Unable to match @-style Identity Address with a known protocol or email contact.') . '<br />';
				$result['message'] .= DI::l10n()->t('Use mailto: in front of address to force email check.') . '<br />';
			}
			return $result;
		}

		if (!$ret['notify']) {
			$result['message'] .= DI::l10n()->t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . '<br />';
		}

		$hidden = ($protocol === Protocol::MAIL);

		$pending = false;
		if (($protocol == Protocol::ACTIVITYPUB) && isset($ret['manually-approve'])) {
			$pending = (bool)$ret['manually-approve'];
		}

		$writeable = in_array($protocol, [Protocol::MAIL, Protocol::DIASPORA, Protocol::ACTIVITYPUB]);

		if (DBA::isResult($contact)) {
			// update contact
			$new_relation = (in_array($contact['rel'], [self::FOLLOWER, self::FRIEND]) ? self::FRIEND : self::SHARING);

			$fields = ['rel' => $new_relation, 'readonly' => false, 'network' => $ret['network']];

			if ($contact['pending'] && !empty($contact['hub-verify'])) {
				ActivityPub\Transmitter::sendContactAccept($contact['url'], $contact['hub-verify'], $uid);
				$fields['pending'] = false;
			}

			self::update($fields, ['id' => $contact['id']]);
		} else {
			$new_relation = (in_array($protocol, [Protocol::MAIL]) ? self::FRIEND : self::SHARING);

			// create contact record
			self::insert([
				'uid'          => $uid,
				'created'      => DateTimeFormat::utcNow(),
				'url'          => $ret['url'],
				'nurl'         => Strings::normaliseLink($ret['url']),
				'addr'         => $ret['addr'],
				'alias'        => $ret['alias'],
				'batch'        => $ret['batch'],
				'notify'       => $ret['notify'],
				'poll'         => $ret['poll'],
				'poco'         => $ret['poco'],
				'name'         => $ret['name'],
				'nick'         => $ret['nick'],
				'network'      => $ret['network'],
				'baseurl'      => $ret['baseurl'],
				'gsid'         => $ret['gsid']         ?? null,
				'contact-type' => $ret['account-type'] ?? self::TYPE_PERSON,
				'protocol'     => $protocol,
				'pubkey'       => $ret['pubkey'],
				'rel'          => $new_relation,
				'priority'     => $ret['priority'],
				'writable'     => $writeable,
				'hidden'       => $hidden,
				'blocked'      => 0,
				'readonly'     => 0,
				'pending'      => $pending,
			]);
		}

		$contact = DBA::selectFirst('contact', [], ['url' => $ret['url'], 'network' => $ret['network'], 'uid' => $uid]);
		if (!DBA::isResult($contact)) {
			$result['message'] .= DI::l10n()->t('Unable to retrieve contact information.') . '<br />';
			return $result;
		}

		$contact_id    = $contact['id'];
		$result['cid'] = $contact_id;

		if ($contact['contact-type'] == self::TYPE_COMMUNITY) {
			Circle::addMember(User::getDefaultGroupCircle($uid), $contact_id);
		} else {
			Circle::addMember(User::getDefaultCircle($uid), $contact_id);
		}

		// Update the avatar
		self::updateAvatar($contact_id, $ret['photo']);

		if ($probed) {
			GServer::updateFromProbeArray($ret);
			self::updateFromProbeArray($contact_id, $ret);
		} else {
			try {
				UpdateContact::add(Worker::PRIORITY_HIGH, $contact['id']);
			} catch (\InvalidArgumentException $e) {
				DI::logger()->notice($e->getMessage(), ['contact' => $contact]);
			}
		}

		$result['success'] = Protocol::follow($uid, $contact, $protocol);

		return $result;
	}

	/**
	 * @param array  $importer Owner (local user) data
	 * @param array  $contact  Existing owner-specific contact data we want to expand the relationship with. Optional.
	 * @param array  $datarray An item-like array with at least the 'author-id' and 'author-url' keys for the contact. Mandatory.
	 * @param bool   $sharing  True: Contact is now sharing with Owner; False: Contact is now following Owner (default)
	 * @param string $note     Introduction additional message
	 * @return bool|null True: follow request is accepted; False: relationship is rejected; Null: relationship is pending
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function addRelationship(array $importer, array $contact, array $datarray, bool $sharing = false, string $note = '')
	{
		// Should always be set
		if (empty($datarray['author-id'])) {
			return false;
		}

		$fields      = ['id', 'url', 'name', 'nick', 'avatar', 'photo', 'network', 'blocked', 'baseurl'];
		$pub_contact = DBA::selectFirst('contact', $fields, ['id' => $datarray['author-id']]);
		if (!DBA::isResult($pub_contact)) {
			// Should never happen
			return false;
		}

		// Contact is blocked at node-level
		if (self::isBlocked($datarray['author-id'])) {
			return false;
		}

		$url     = ($datarray['author-link'] ?? '') ?: $pub_contact['url'];
		$name    = $pub_contact['name'];
		$photo   = ($pub_contact['avatar'] ?? '') ?: $pub_contact["photo"];
		$nick    = $pub_contact['nick'];
		$network = $pub_contact['network'];

		// Ensure that we don't create a new contact when there already is one
		$cid = self::getIdForURL($url, $importer['uid']);
		if (!empty($cid)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		}

		self::clearFollowerFollowingEndpointCache($importer['uid']);

		if (!empty($contact)) {
			if (!empty($contact['pending'])) {
				DI::logger()->info('Pending contact request already exists.', ['url' => $url, 'uid' => $importer['uid']]);
				return null;
			}

			// Contact is blocked at user-level
			if (
				!empty($contact['id']) && !empty($importer['id']) &&
				Contact\User::isBlocked($contact['id'], $importer['id'])
			) {
				return false;
			}

			// Make sure that the existing contact isn't archived
			self::unmarkForArchival($contact);

			if (($contact['rel'] == self::SHARING)
				|| ($sharing && $contact['rel'] == self::FOLLOWER)
			) {
				self::update(
					['rel' => self::FRIEND, 'writable' => true, 'pending' => false],
					['id' => $contact['id'], 'uid' => $importer['uid']]
				);
			}

			// Ensure to always have the correct network type, independent from the connection request method
			self::updateFromProbe($contact['id']);

			Post\UserNotification::insertNotification($pub_contact['id'], Activity::FOLLOW, $importer['uid']);

			return true;
		} else {
			// send email notification to owner?
			if (DBA::exists('contact', ['nurl' => Strings::normaliseLink($url), 'uid' => $importer['uid'], 'pending' => true])) {
				DI::logger()->notice('ignoring duplicated connection request from pending contact ' . $url);
				return null;
			}

			// create contact record
			$contact_id = self::insert([
				'uid'      => $importer['uid'],
				'created'  => DateTimeFormat::utcNow(),
				'url'      => $url,
				'nurl'     => Strings::normaliseLink($url),
				'baseurl'  => $pub_contact['baseurl'] ?? '',
				'name'     => $name,
				'nick'     => $nick,
				'network'  => $network,
				'rel'      => self::FOLLOWER,
				'blocked'  => 0,
				'readonly' => 0,
				'pending'  => 1,
				'writable' => 1,
			]);

			// Ensure to always have the correct network type, independent from the connection request method
			self::updateFromProbe($contact_id);

			self::updateAvatar($contact_id, $photo, true);

			Post\UserNotification::insertNotification($pub_contact['id'], Activity::FOLLOW, $importer['uid']);

			$contact_record = DBA::selectFirst('contact', ['id', 'network', 'name', 'url', 'photo', 'contact-type'], ['id' => $contact_id]);

			/// @TODO Encapsulate this into a function/method
			$fields = ['uid', 'username', 'email', 'page-flags', 'notify-flags', 'language'];
			$user   = DBA::selectFirst('user', $fields, ['uid' => $importer['uid']]);
			if (DBA::isResult($user) && !in_array($user['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE, User::PAGE_FLAGS_COMMUNITY])) {
				// create notification
				if (is_array($contact_record)) {
					$intro = DI::introFactory()->createNew(
						$importer['uid'],
						$contact_record['id'],
						$note
					);
					DI::intro()->save($intro);
				}

				if ($contact_record['contact-type'] == self::TYPE_COMMUNITY) {
					Circle::addMember(User::getDefaultGroupCircle($importer['uid']), $contact_record['id']);
				} else {
					Circle::addMember(User::getDefaultCircle($importer['uid']), $contact_record['id']);
				}

				if (($user['notify-flags'] & Notification\Type::INTRO) && $user['page-flags'] == User::PAGE_FLAGS_NORMAL) {
					DI::notify()->createFromArray([
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
					self::createFromProbeForUser($importer['uid'], $url, $network);
				}

				$condition = ['uid' => $importer['uid'], 'url' => $url, 'pending' => true];
				$fields    = ['pending' => false];
				if ($user['page-flags'] == User::PAGE_FLAGS_FREELOVE) {
					$fields['rel'] = self::FRIEND;
				}

				self::update($fields, $condition);

				return true;
			}
		}

		return null;
	}

	/**
	 * Update the local relationship when a local user loses a follower
	 *
	 * @param array $contact User-specific contact (uid != 0) array
	 * @param bool  $delete  Delete if set, otherwise set relation to "nothing" when contact had been a follower
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function removeFollower(array $contact, bool $delete = true)
	{
		if (in_array($contact['rel'] ?? [], [self::FRIEND, self::SHARING])) {
			self::update(['rel' => self::SHARING], ['id' => $contact['id']]);
		} elseif (!empty($contact['id'])) {
			if ($delete) {
				self::remove($contact['id']);
			} else {
				self::update(['rel' => self::NOTHING, 'pending' => false], ['id' => $contact['id']]);
			}
		} else {
			DI::logger()->info('Couldn\'t remove follower because of invalid contact array', ['contact' => $contact]);
			return;
		}

		Worker::add(Worker::PRIORITY_LOW, 'ContactDiscoveryForUser', $contact['uid']);

		self::clearFollowerFollowingEndpointCache($contact['uid']);

		$pcid = self::getPublicContactId($contact['id'], $contact['uid']);
		if ($pcid) {
			DI::notification()->deleteForUserByVerb($contact['uid'], Activity::FOLLOW, ['actor-id' => $pcid]);
		}
	}

	/**
	 * Update the local relationship when a local user unfollow a contact.
	 * Removes the contact for sharing-only protocols (feed and mail).
	 *
	 * @param array $contact User-specific contact (uid != 0) array
	 * @param bool  $delete  Delete if set, otherwise set relation to "nothing" when contact had been a sharer
	 * @throws InternalServerErrorException
	 */
	public static function removeSharer(array $contact, bool $delete = true)
	{
		self::clearFollowerFollowingEndpointCache($contact['uid']);

		if (in_array($contact['rel'], [self::SHARING, self::NOTHING]) || in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			if ($delete) {
				self::remove($contact['id']);
			} else {
				self::update(['rel' => self::NOTHING, 'pending' => false], ['id' => $contact['id']]);
			}
		} else {
			self::update(['rel' => self::FOLLOWER, 'pending' => false], ['id' => $contact['id']]);
		}

		Worker::add(Worker::PRIORITY_LOW, 'ContactDiscoveryForUser', $contact['uid']);
	}

	/**
	 * Create a birthday event.
	 *
	 * Update the year and the birthday.
	 */
	public static function updateBirthdays()
	{
		$condition = [
			'`bd` > ?
			AND (`contact`.`rel` = ? OR `contact`.`rel` = ?)
			AND NOT `contact`.`pending`
			AND NOT `contact`.`hidden`
			AND NOT `contact`.`blocked`
			AND NOT `contact`.`archive`
			AND NOT `contact`.`deleted`',
			DBA::NULL_DATE,
			self::SHARING,
			self::FRIEND
		];

		$contacts = DBA::select('contact', ['id', 'uid', 'name', 'url', 'bd'], $condition);

		while ($contact = DBA::fetch($contacts)) {
			DI::logger()->notice('update_contact_birthday: ' . $contact['bd']);

			$nextbd = DateTimeFormat::utcNow('Y') . substr($contact['bd'], 4);

			if (Event::createBirthday($contact, $nextbd)) {
				// update bdyear
				DBA::update(
					'contact',
					['bdyear' => substr($nextbd, 0, 4), 'bd' => $nextbd],
					['id'     => $contact['id']]
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
	 * @throws Exception
	 */
	public static function pruneUnavailable(array $contact_ids): array
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
	 * Return the link to the profile
	 *
	 * @param array $contact
	 * @return string
	 */
	public static function getProfileLink(array $contact): string
	{
		if (!empty($contact['alias']) && Network::isValidHttpUrl($contact['alias']) && (($contact['network'] ?? '') != Protocol::DFRN)) {
			return $contact['alias'];
		} else {
			return $contact['url'];
		}
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
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLink(string $contact_url, string $url = ''): string
	{
		if (!DI::userSession()->isAuthenticated()) {
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
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLinkById(int $cid, string $url = ''): string
	{
		if (($url == '') && DI::userSession()->isAuthenticated() && DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'stay_local')) {
			return 'contact/' . $cid . '/conversations';
		}

		$contact = DBA::selectFirst('contact', ['id', 'network', 'url', 'alias', 'uid'], ['id' => $cid]);
		if (empty($contact)) {
			return $url;
		}

		return self::magicLinkByContact($contact, $url);
	}

	/**
	 * Returns a magic link to authenticate remote visitors
	 *
	 * @param array  $contact The contact array with "uid", "network" and "url"
	 * @param string $url     An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLinkByContact(array $contact, string $url = ''): string
	{
		$destination = $url ?: self::getProfileLink($contact);

		if (!DI::userSession()->isAuthenticated()) {
			return $destination;
		}

		if (DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'stay_local') && ($url == '')) {
			return 'contact/' . $contact['id'] . '/conversations';
		}

		if (Strings::compareLink($contact['url'], $url) || Strings::compareLink($contact['alias'] ?? '', $url)) {
			$url = '';
		}

		// Only redirections to the same host do make sense
		if (($url != '') && (parse_url($url, PHP_URL_HOST) != parse_url($contact['url'], PHP_URL_HOST))) {
			return $url;
		}

		if (!empty($contact['network']) && $contact['network'] != Protocol::DFRN) {
			return $destination;
		}

		if (empty($contact['id'])) {
			return $destination;
		}

		$redirect = 'contact/redir/' . $contact['id'];

		if ($url != '') {
			$redirect .= '?url=' . $url;
		}

		return $redirect;
	}

	/**
	 * Is the contact a group?
	 *
	 * @param integer $contactid ID of the contact
	 *
	 * @return boolean "true" if it is a group
	 */
	public static function isGroup(int $contactid): bool
	{
		$fields    = ['contact-type'];
		$condition = ['id' => $contactid];
		$contact   = DBA::selectFirst('contact', $fields, $condition);
		if (!DBA::isResult($contact)) {
			return false;
		}

		// Is it a group?
		return ($contact['contact-type'] == self::TYPE_COMMUNITY);
	}

	/**
	 * Can the remote contact receive private messages?
	 *
	 * @param array $contact
	 * @return bool
	 */
	public static function canReceivePrivateMessages(array $contact): bool
	{
		$protocol = $contact['network'] ?? $contact['protocol'] ?? Protocol::PHANTOM;
		$self     = $contact['self']    ?? false;

		return in_array($protocol, [Protocol::DFRN, Protocol::DIASPORA, Protocol::ACTIVITYPUB]) && !$self;
	}

	/**
	 * Search contact table by nick or name
	 *
	 * @param string $search       Name or nick
	 * @param string $mode         Search mode (e.g. "community")
	 * @param bool   $show_blocked Show users from blocked servers. Default is false
	 * @param int    $uid          User ID
	 * @param int    $limit        Maximum amount of returned values
	 * @param int    $offset       Limit offset
	 *
	 * @return array with search results
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function searchByName(string $search, string $mode = '', bool $show_blocked = false, int $uid = 0, int $limit = 0, int $offset = 0): array
	{
		if (empty($search)) {
			return [];
		}

		// check supported networks
		$networks = [Protocol::DFRN, Protocol::ACTIVITYPUB];
		if (DI::config()->get('system', 'diaspora_enabled')) {
			$networks[] = Protocol::DIASPORA;
		}

		$condition = [
			'network'       => $networks,
			'server-failed' => false,
			'failed'        => false,
			'deleted'       => false,
			'unsearchable'  => false,
			'uid'           => $uid
		];

		if (!$show_blocked) {
			$condition['server-blocked'] = false;
		}

		if ($uid == 0) {
			$condition['blocked'] = false;
		} else {
			$condition['rel'] = [Contact::SHARING, Contact::FRIEND];
		}

		// check if we search only communities or every contact
		if ($mode === 'community') {
			$condition['contact-type'] = self::TYPE_COMMUNITY;
		}

		$search .= '%';

		$params = [];

		if (!empty($limit) && !empty($offset)) {
			$params['limit'] = [$offset, $limit];
		} elseif (!empty($limit)) {
			$params['limit'] = $limit;
		}

		$condition = DBA::mergeConditions(
			$condition,
			["(`addr` LIKE ? OR `name` LIKE ? OR `nick` LIKE ?)", $search, $search, $search]
		);

		return DBA::selectToArray('account-user-view', [], $condition, $params);
	}

	/**
	 * Add public contacts from an array
	 *
	 * @param array $urls
	 * @return array result "count", "added" and "updated"
	 */
	public static function addByUrls(array $urls): array
	{
		$added     = 0;
		$updated   = 0;
		$unchanged = 0;
		$count     = 0;

		foreach ($urls as $url) {
			if (empty($url) || !is_string($url)) {
				continue;
			}
			$contact = self::getByURL($url, false, ['id', 'network', 'next-update']);
			if (empty($contact['id']) && Network::isValidHttpUrl($url)) {
				Worker::add(Worker::PRIORITY_LOW, 'AddContact', 0, $url);
				++$added;
			} elseif (!empty($contact['network']) && Protocol::supportsProbe($contact['network']) && ($contact['next-update'] < DateTimeFormat::utcNow())) {
				try {
					UpdateContact::add(['priority' => Worker::PRIORITY_LOW, 'dont_fork' => true], $contact['id']);
					++$updated;
				} catch (\InvalidArgumentException $e) {
					DI::logger()->notice($e->getMessage(), ['contact' => $contact]);
				}
			} else {
				++$unchanged;
			}
			++$count;
		}

		return ['count' => $count, 'added' => $added, 'updated' => $updated, 'unchanged' => $unchanged];
	}

	/**
	 * Returns a random, global contact array of the current node
	 *
	 * @return array The profile array
	 * @throws Exception
	 */
	public static function getRandomContact(): array
	{
		$contact = DBA::selectFirst('contact', ['id', 'network', 'url', 'alias', 'uid'], [
			"`uid` = ? AND `network` = ? AND NOT `failed` AND `last-item` > ?",
			0, Protocol::DFRN, DateTimeFormat::utc('now - 1 month'),
		], ['order' => ['RAND()']]);

		if (DBA::isResult($contact)) {
			return $contact;
		}

		return [];
	}

	/**
	 * Checks, if contacts with the given condition exists
	 *
	 * @param array $condition
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function exists(array $condition): bool
	{
		return DBA::exists('contact', $condition);
	}
}
