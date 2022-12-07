<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Model\Contact;

use Exception;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * This class provides relationship information based on the `contact-relation` table.
 * This table is directional (cid = source, relation-cid = target), references public contacts (with uid=0) and records both
 * follows and the last interaction (likes/comments) on public posts.
 */
class Relation
{
	/**
	 * No discovery of followers/followings
	 */
	const DISCOVERY_NONE = 0;
	/**
	 * Discover followers/followings of local contacts
	 */
	const DISCOVERY_LOCAL = 1;
	/**
	 * Discover followers/followings of local contacts and contacts that visibly interacted on the system
	 */
	const DISCOVERY_INTERACTOR = 2;
	/**
	 * Discover followers/followings of all contacts
	 */
	const DISCOVERY_ALL = 3;

	public static function store(int $target, int $actor, string $interaction_date)
	{
		if ($actor == $target) {
			return;
		}

		DBA::insert('contact-relation', ['last-interaction' => $interaction_date, 'cid' => $target, 'relation-cid' => $actor], Database::INSERT_UPDATE);
	}

	/**
	 * Fetch the followers of a given user
	 *
	 * @param integer $uid User ID
	 * @return void
	 */
	public static function discoverByUser(int $uid)
	{
		$contact = Contact::selectFirst(['id', 'url', 'network'], ['uid' => $uid, 'self' => true]);
		if (empty($contact)) {
			Logger::warning('Self contact for user not found', ['uid' => $uid]);
			return;
		}

		$followers = self::getContacts($uid, [Contact::FOLLOWER, Contact::FRIEND]);
		$followings = self::getContacts($uid, [Contact::SHARING, Contact::FRIEND]);

		self::updateFollowersFollowings($contact, $followers, $followings);
	}

	/**
	 * Fetches the followers of a given profile and adds them
	 *
	 * @param string $url URL of a profile
	 * @return void
	 */
	public static function discoverByUrl(string $url)
	{
		$contact = Contact::getByURL($url);
		if (empty($contact)) {
			Logger::info('Contact not found', ['url' => $url]);
			return;
		}

		if (!self::isDiscoverable($url, $contact)) {
			Logger::info('Contact is not discoverable', ['url' => $url]);
			return;
		}

		$uid = User::getIdForURL($url);
		if (!empty($uid)) {
			Logger::info('Fetch the followers/followings locally', ['url' => $url]);
			$followers = self::getContacts($uid, [Contact::FOLLOWER, Contact::FRIEND]);
			$followings = self::getContacts($uid, [Contact::SHARING, Contact::FRIEND]);
		} elseif (!Contact::isLocal($url)) {
			Logger::info('Fetch the followers/followings by polling the endpoints', ['url' => $url]);
			$apcontact = APContact::getByURL($url, false);

			if (!empty($apcontact['followers']) && is_string($apcontact['followers'])) {
				$followers = ActivityPub::fetchItems($apcontact['followers']);
			} else {
				$followers = [];
			}

			if (!empty($apcontact['following']) && is_string($apcontact['following'])) {
				$followings = ActivityPub::fetchItems($apcontact['following']);
			} else {
				$followings = [];
			}
		} else {
			Logger::warning('Contact seems to be local but could not be found here', ['url' => $url]);
			$followers = [];
			$followings = [];
		}

		self::updateFollowersFollowings($contact, $followers, $followings);
	}

	/**
	 * Update followers and followings for the given contact
	 *
	 * @param array $contact
	 * @param array $followers
	 * @param array $followings
	 * @return void
	 */
	private static function updateFollowersFollowings(array $contact, array $followers, array $followings)
	{
		if (empty($followers) && empty($followings)) {
			Contact::update(['last-discovery' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			Logger::info('The contact does not offer discoverable data', ['id' => $contact['id'], 'url' => $contact['url'], 'network' => $contact['network']]);
			return;
		}

		$target = $contact['id'];
		$url    = $contact['url'];

		if (!empty($followers)) {
			// Clear the follower list, since it will be recreated in the next step
			DBA::update('contact-relation', ['follows' => false], ['cid' => $target]);
		}

		$contacts = [];
		foreach (array_merge($followers, $followings) as $contact) {
			if (is_string($contact)) {
				$contacts[] = $contact;
			} elseif (!empty($contact['url']) && is_string($contact['url'])) {
				$contacts[] = $contact['url'];
			}
		}
		$contacts = array_unique($contacts);

		$follower_counter = 0;
		$following_counter = 0;

		Logger::info('Discover contacts', ['id' => $target, 'url' => $url, 'contacts' => count($contacts)]);
		foreach ($contacts as $contact) {
			$actor = Contact::getIdForURL($contact);
			if (!empty($actor)) {
				if (in_array($contact, $followers)) {
					$fields = ['cid' => $target, 'relation-cid' => $actor, 'follows' => true, 'follow-updated' => DateTimeFormat::utcNow()];
					DBA::insert('contact-relation', $fields, Database::INSERT_UPDATE);
					$follower_counter++;
				}

				if (in_array($contact, $followings)) {
					$fields = ['cid' => $actor, 'relation-cid' => $target, 'follows' => true, 'follow-updated' => DateTimeFormat::utcNow()];
					DBA::insert('contact-relation', $fields, Database::INSERT_UPDATE);
					$following_counter++;
				}
			}
		}

		if (!empty($followers)) {
			// Delete all followers that aren't followers anymore (and aren't interacting)
			DBA::delete('contact-relation', ['cid' => $target, 'follows' => false, 'last-interaction' => DBA::NULL_DATETIME]);
		}

		Contact::update(['last-discovery' => DateTimeFormat::utcNow()], ['id' => $target]);
		Logger::info('Contacts discovery finished', ['id' => $target, 'url' => $url, 'follower' => $follower_counter, 'following' => $following_counter]);
		return;
	}

	/**
	 * Fetch contact url list from the given local user
	 *
	 * @param integer $uid
	 * @param array $rel
	 * @return array contact list
	 */
	private static function getContacts(int $uid, array $rel): array
	{
		$list = [];
		$profile = Profile::getByUID($uid);
		if (!empty($profile['hide-friends'])) {
			return $list;
		}

		$condition = [
			'rel' => $rel,
			'uid' => $uid,
			'self' => false,
			'deleted' => false,
			'hidden' => false,
			'archive' => false,
			'pending' => false,
		];
		$condition = DBA::mergeConditions($condition, ["`url` IN (SELECT `url` FROM `apcontact`)"]);
		$contacts = DBA::select('contact', ['url'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			$list[] = $contact['url'];
		}
		DBA::close($contacts);

		return $list;
	}

	/**
	 * Tests if a given contact url is discoverable
	 *
	 * @param string $url     Contact url
	 * @param array  $contact Contact array
	 * @return boolean True if contact is discoverable
	 */
	public static function isDiscoverable(string $url, array $contact = []): bool
	{
		$contact_discovery = DI::config()->get('system', 'contact_discovery');

		if ($contact_discovery == self::DISCOVERY_NONE) {
			return false;
		}

		if (empty($contact)) {
			$contact = Contact::getByURL($url, false);
		}

		if (empty($contact)) {
			return false;
		}

		if ($contact['last-discovery'] > DateTimeFormat::utc('now - 1 month')) {
			Logger::info('No discovery - Last was less than a month ago.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['last-discovery']]);
			return false;
		}

		if ($contact_discovery != self::DISCOVERY_ALL) {
			$local = DBA::exists('contact', ["`nurl` = ? AND `uid` != ?", Strings::normaliseLink($url), 0]);
			if (($contact_discovery == self::DISCOVERY_LOCAL) && !$local) {
				Logger::info('No discovery - This contact is not followed/following locally.', ['id' => $contact['id'], 'url' => $url]);
				return false;
			}

			if ($contact_discovery == self::DISCOVERY_INTERACTOR) {
				$interactor = DBA::exists('contact-relation', ["`relation-cid` = ? AND `last-interaction` > ?", $contact['id'], DBA::NULL_DATETIME]);
				if (!$local && !$interactor) {
					Logger::info('No discovery - This contact is not interacting locally.', ['id' => $contact['id'], 'url' => $url]);
					return false;
				}
			}
		} elseif ($contact['created'] > DateTimeFormat::utc('now - 1 day')) {
			// Newly created contacts are not discovered to avoid DDoS attacks
			Logger::info('No discovery - Contact record is less than a day old.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['created']]);
			return false;
		}

		if (!in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS])) {
			$apcontact = APContact::getByURL($url, false);
			if (empty($apcontact)) {
				Logger::info('No discovery - The contact does not seem to speak ActivityPub.', ['id' => $contact['id'], 'url' => $url, 'network' => $contact['network']]);
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the cached suggestion is outdated
	 *
	 * @param integer $uid
	 * @return boolean
	 */
	static public function areSuggestionsOutdated(int $uid): bool
	{
		return DI::pConfig()->get($uid, 'suggestion', 'last_update') + 3600 < time();
	}

	/**
	 * Update contact suggestions for a given user
	 *
	 * @param integer $uid
	 * @return void
	 */
	static public function updateCachedSuggestions(int $uid)
	{
		if (!self::areSuggestionsOutdated($uid)) {
			return;
		}

		DBA::delete('account-suggestion', ['uid' => $uid, 'ignore' => false]);

		foreach (self::getSuggestions($uid) as $contact) {
			DBA::insert('account-suggestion', ['uri-id' => $contact['uri-id'], 'uid' => $uid, 'level' => 1], Database::INSERT_IGNORE);
		}

		DI::pConfig()->set($uid, 'suggestion', 'last_update', time());
	}

	/**
	 * Returns a cached array of suggested contacts for given user id
	 *
	 * @param int $uid   User id
	 * @param int $start optional, default 0
	 * @param int $limit optional, default 80
	 * @return array
	 */
	static public function getCachedSuggestions(int $uid, int $start = 0, int $limit = 80): array
	{
		$condition = ["`uid` = ? AND `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE NOT `ignore` AND `uid` = ?)", 0, $uid];
		$params = ['limit' => [$start, $limit]];
		$cached = DBA::selectToArray('contact', [], $condition, $params);

		if (!empty($cached)) {
			return $cached;
		} else {
			return self::getSuggestions($uid, $start, $limit);
		}
	}

	/**
	 * Returns an array of suggested contacts for given user id
	 *
	 * @param int $uid   User id
	 * @param int $start optional, default 0
	 * @param int $limit optional, default 80
	 * @return array
	 */
	static public function getSuggestions(int $uid, int $start = 0, int $limit = 80): array
	{
		if ($uid == 0) {
			return [];
		}

		$cid = Contact::getPublicIdByUserId($uid);
		$totallimit = $start + $limit;
		$contacts = [];

		Logger::info('Collecting suggestions', ['uid' => $uid, 'cid' => $cid, 'start' => $start, 'limit' => $limit]);

		$diaspora = DI::config()->get('system', 'diaspora_enabled') ? Protocol::DIASPORA : Protocol::ACTIVITYPUB;
		$ostatus = !DI::config()->get('system', 'ostatus_disabled') ? Protocol::OSTATUS : Protocol::ACTIVITYPUB;

		// The query returns contacts where contacts interacted with whom the given user follows.
		// Contacts who already are in the user's contact table are ignored.
		$results = DBA::select('contact', [], ["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` IN
				(SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ?)
					AND NOT `cid` IN (SELECT `id` FROM `contact` WHERE `uid` = ? AND `nurl` IN
						(SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?))) AND `id` = `cid`)
			AND NOT `hidden` AND `network` IN (?, ?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
			$cid,
			0,
			$uid, Contact::FRIEND, Contact::SHARING,
			Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $ostatus, $uid
			], [
				'order' => ['last-item' => true],
				'limit' => $totallimit,
			]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}

		DBA::close($results);

		Logger::info('Contacts of contacts who are followed by the given user', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		if (count($contacts) >= $totallimit) {
			return array_slice($contacts, $start, $limit);
		}

		// The query returns contacts where contacts interacted with whom also interacted with the given user.
		// Contacts who already are in the user's contact table are ignored.
		$results = DBA::select('contact', [],
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` IN
				(SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?)
					AND NOT `cid` IN (SELECT `id` FROM `contact` WHERE `uid` = ? AND `nurl` IN
						(SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?))) AND `id` = `cid`)
			AND NOT `hidden` AND `network` IN (?, ?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
			$cid, 0, $uid, Contact::FRIEND, Contact::SHARING,
			Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $ostatus, $uid],
			['order' => ['last-item' => true], 'limit' => $totallimit]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}
		DBA::close($results);

		Logger::info('Contacts of contacts who are following the given user', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		if (count($contacts) >= $totallimit) {
			return array_slice($contacts, $start, $limit);
		}

		// The query returns contacts that follow the given user but aren't followed by that user.
		$results = DBA::select('contact', [],
			["`nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` = ?)
			AND NOT `hidden` AND `uid` = ? AND `network` IN (?, ?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
			$uid, Contact::FOLLOWER, 0, 
			Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $ostatus, $uid],
			['order' => ['last-item' => true], 'limit' => $totallimit]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}
		DBA::close($results);

		Logger::info('Followers that are not followed by the given user', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		if (count($contacts) >= $totallimit) {
			return array_slice($contacts, $start, $limit);
		}

		// The query returns any contact that isn't followed by that user.
		$results = DBA::select('contact', [],
			["NOT `nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?) AND `nurl` = `nurl`)
			AND NOT `hidden` AND `uid` = ? AND `network` IN (?, ?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
			$uid, Contact::FRIEND, Contact::SHARING, 0, 
			Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $ostatus, $uid],
			['order' => ['last-item' => true], 'limit' => $totallimit]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}
		DBA::close($results);

		Logger::info('Any contact', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		return array_slice($contacts, $start, $limit);
	}

	/**
	 * Counts all the known follows of the provided public contact
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countFollows(int $cid, array $condition = []): int
	{
		$condition = DBA::mergeConditions($condition, [
			'`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)', 
			$cid,
		]);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that are followed the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listFollows(int $cid, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)', 
			$cid]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Counts all the known followers of the provided public contact
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countFollowers(int $cid, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)',
			$cid]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that follow the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listFollowers(int $cid, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)', $cid]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Counts the number of contacts that are known mutuals with the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countMutuals(int $cid, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)',
			$cid, $cid]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that are known mutuals with the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listMutuals(int $cid, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)',
			$cid, $cid]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}


	/**
	 * Counts the number of contacts with any relationship with the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countAll(int $cid, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['(`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			OR `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`))',
				$cid, $cid]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts with any relationship with the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listAll(int $cid, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			['(`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			OR `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`))',
				$cid, $cid]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Counts the number of contacts that both provided public contacts have interacted with at least once.
	 * Interactions include follows and likes and comments on public posts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countCommon(int $sourceId, int $targetId, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?)',
			$sourceId, $targetId]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that both provided public contacts have interacted with at least once.
	 * Interactions include follows and likes and comments on public posts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array|bool Array on success, false on failure
	 * @throws Exception
	 */
	public static function listCommon(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?)",
			$sourceId, $targetId]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Counts the number of contacts that are followed by both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countCommonFollows(int $sourceId, int $targetId, array $condition = []): int
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)',
			$sourceId, $targetId]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that are followed by both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array|bool Array on success, false on failure
	 * @throws Exception
	 */
	public static function listCommonFollows(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Counts the number of contacts that follow both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countCommonFollowers(int $sourceId, int $targetId, array $condition = []): int
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`) 
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that follow both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array|bool Array on success, false on failure
	 * @throws Exception
	 */
	public static function listCommonFollowers(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`) 
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 	'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}
}
