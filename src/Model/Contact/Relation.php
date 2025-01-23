<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Contact;

use Exception;
use Friendica\Content\Widget;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
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
		$contact = Contact::selectFirst(['id', 'url', 'network'], ['id' => Contact::getPublicIdByUserId($uid)]);
		if (empty($contact)) {
			DI::logger()->warning('Self contact for user not found', ['uid' => $uid]);
			return;
		}

		$followers  = self::getContacts($uid, [Contact::FOLLOWER, Contact::FRIEND], false);
		$followings = self::getContacts($uid, [Contact::SHARING, Contact::FRIEND], false);

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
			DI::logger()->info('Contact not found', ['url' => $url]);
			return;
		}

		if (!self::isDiscoverable($url, $contact)) {
			DI::logger()->info('Contact is not discoverable', ['url' => $url]);
			return;
		}

		$uid = User::getIdForURL($url);
		if (!empty($uid)) {
			DI::logger()->info('Fetch the followers/followings locally', ['url' => $url]);
			$followers  = self::getContacts($uid, [Contact::FOLLOWER, Contact::FRIEND]);
			$followings = self::getContacts($uid, [Contact::SHARING, Contact::FRIEND]);
		} elseif (!Contact::isLocal($url)) {
			DI::logger()->info('Fetch the followers/followings by polling the endpoints', ['url' => $url]);
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
			DI::logger()->warning('Contact seems to be local but could not be found here', ['url' => $url]);
			$followers  = [];
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
			DI::logger()->info('The contact does not offer discoverable data', ['id' => $contact['id'], 'url' => $contact['url'], 'network' => $contact['network']]);
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

		$follower_counter  = 0;
		$following_counter = 0;

		DI::logger()->info('Discover contacts', ['id' => $target, 'url' => $url, 'contacts' => count($contacts)]);
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
		DI::logger()->info('Contacts discovery finished', ['id' => $target, 'url' => $url, 'follower' => $follower_counter, 'following' => $following_counter]);
		return;
	}

	/**
	 * Fetch contact url list from the given local user
	 *
	 * @param integer $uid
	 * @param array   $rel
	 * @param bool    $only_ap
	 * @return array contact list
	 */
	private static function getContacts(int $uid, array $rel, bool $only_ap = true): array
	{
		$list    = [];
		$profile = Profile::getByUID($uid);
		if (!empty($profile['hide-friends'])) {
			return $list;
		}

		$condition = [
			'rel'     => $rel,
			'uid'     => $uid,
			'self'    => false,
			'deleted' => false,
			'hidden'  => false,
			'archive' => false,
			'pending' => false,
			'blocked' => false,
			'failed'  => false,
		];
		if ($only_ap) {
			$condition = DBA::mergeConditions($condition, ["`url` IN (SELECT `url` FROM `apcontact`)"]);
		} else {
			$networks  = Widget::unavailableNetworks();
			$condition = DBA::mergeConditions($condition, array_merge(["NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")"], $networks));
		}
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

		if (Contact::isLocal($url)) {
			return true;
		}

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
			DI::logger()->info('No discovery - Last was less than a month ago.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['last-discovery']]);
			return false;
		}

		if ($contact_discovery != self::DISCOVERY_ALL) {
			$local = DBA::exists('contact', ["`nurl` = ? AND `uid` != ?", Strings::normaliseLink($url), 0]);
			if (($contact_discovery == self::DISCOVERY_LOCAL) && !$local) {
				DI::logger()->info('No discovery - This contact is not followed/following locally.', ['id' => $contact['id'], 'url' => $url]);
				return false;
			}

			if ($contact_discovery == self::DISCOVERY_INTERACTOR) {
				$interactor = DBA::exists('contact-relation', ["`relation-cid` = ? AND `last-interaction` > ?", $contact['id'], DBA::NULL_DATETIME]);
				if (!$local && !$interactor) {
					DI::logger()->info('No discovery - This contact is not interacting locally.', ['id' => $contact['id'], 'url' => $url]);
					return false;
				}
			}
		} elseif ($contact['created'] > DateTimeFormat::utc('now - 1 day')) {
			// Newly created contacts are not discovered to avoid DDoS attacks
			DI::logger()->info('No discovery - Contact record is less than a day old.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['created']]);
			return false;
		}

		if (!in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			$apcontact = APContact::getByURL($url, false);
			if (empty($apcontact)) {
				DI::logger()->info('No discovery - The contact does not seem to speak ActivityPub.', ['id' => $contact['id'], 'url' => $url, 'network' => $contact['network']]);
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
	public static function areSuggestionsOutdated(int $uid): bool
	{
		return DI::pConfig()->get($uid, 'suggestion', 'last_update') + 3600 < time();
	}

	/**
	 * Update contact suggestions for a given user
	 *
	 * @param integer $uid
	 * @return void
	 */
	public static function updateCachedSuggestions(int $uid)
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
	public static function getCachedSuggestions(int $uid, int $start = 0, int $limit = 80): array
	{
		$condition = ["`uid` = ? AND `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE NOT `ignore` AND `uid` = ?)", 0, $uid];
		$params    = ['limit' => [$start, $limit]];
		$cached    = DBA::selectToArray('contact', [], $condition, $params);

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
	public static function getSuggestions(int $uid, int $start = 0, int $limit = 80): array
	{
		if ($uid == 0) {
			return [];
		}

		$cid        = Contact::getPublicIdByUserId($uid);
		$totallimit = $start + $limit;
		$contacts   = [];

		DI::logger()->info('Collecting suggestions', ['uid' => $uid, 'cid' => $cid, 'start' => $start, 'limit' => $limit]);

		$diaspora = DI::config()->get('system', 'diaspora_enabled') ? Protocol::DIASPORA : Protocol::ACTIVITYPUB;

		// The query returns contacts where contacts interacted with whom the given user follows.
		// Contacts who already are in the user's contact table are ignored.
		$results = DBA::select(
			'contact',
			[],
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` IN
				(SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ?)
					AND NOT `cid` IN (SELECT `id` FROM `contact` WHERE `uid` = ? AND `nurl` IN
						(SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?))) AND `id` = `cid`)
			AND NOT `hidden` AND `network` IN (?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
				$cid,
				0,
				$uid, Contact::FRIEND, Contact::SHARING,
				Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $uid
			],
			[
				'order' => ['last-item' => true],
				'limit' => $totallimit,
			]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}

		DBA::close($results);

		DI::logger()->info('Contacts of contacts who are followed by the given user', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		if (count($contacts) >= $totallimit) {
			return array_slice($contacts, $start, $limit);
		}

		// The query returns contacts where contacts interacted with whom also interacted with the given user.
		// Contacts who already are in the user's contact table are ignored.
		$results = DBA::select(
			'contact',
			[],
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` IN
				(SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?)
					AND NOT `cid` IN (SELECT `id` FROM `contact` WHERE `uid` = ? AND `nurl` IN
						(SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?))) AND `id` = `cid`)
			AND NOT `hidden` AND `network` IN (?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
				$cid, 0, $uid, Contact::FRIEND, Contact::SHARING,
				Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $uid],
			['order' => ['last-item' => true], 'limit' => $totallimit]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}
		DBA::close($results);

		DI::logger()->info('Contacts of contacts who are following the given user', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		if (count($contacts) >= $totallimit) {
			return array_slice($contacts, $start, $limit);
		}

		// The query returns contacts that follow the given user but aren't followed by that user.
		$results = DBA::select(
			'contact',
			[],
			["`nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` = ?)
			AND NOT `hidden` AND `uid` = ? AND `network` IN (?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
				$uid, Contact::FOLLOWER, 0,
				Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $uid],
			['order' => ['last-item' => true], 'limit' => $totallimit]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}
		DBA::close($results);

		DI::logger()->info('Followers that are not followed by the given user', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

		if (count($contacts) >= $totallimit) {
			return array_slice($contacts, $start, $limit);
		}

		// The query returns any contact that isn't followed by that user.
		$results = DBA::select(
			'contact',
			[],
			["NOT `nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` = ? AND `rel` IN (?, ?) AND `nurl` = `nurl`)
			AND NOT `hidden` AND `uid` = ? AND `network` IN (?, ?, ?)
			AND NOT `uri-id` IN (SELECT `uri-id` FROM `account-suggestion` WHERE `uri-id` = `contact`.`uri-id` AND `uid` = ?)",
				$uid, Contact::FRIEND, Contact::SHARING, 0,
				Protocol::ACTIVITYPUB, Protocol::DFRN, $diaspora, $uid],
			['order' => ['last-item' => true], 'limit' => $totallimit]
		);

		while ($contact = DBA::fetch($results)) {
			$contacts[$contact['id']] = $contact;
		}
		DBA::close($results);

		DI::logger()->info('Any contact', ['uid' => $uid, 'cid' => $cid, 'count' => count($contacts)]);

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
		$condition = DBA::mergeConditions($condition, ["`cid` = ? and `follows`", $cid]);
		$sql       = "SELECT COUNT(*) AS `total` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `relation-cid` WHERE " . array_shift($condition);

		$result = DBA::fetchFirst($sql, $condition);
		return $result['total'] ?? 0;
	}

	/**
	 * Returns a paginated list of contacts that are followed the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @return array
	 * @throws Exception
	 */
	public static function listFollows(int $cid, array $condition = [], int $count = 30, int $offset = 0)
	{
		$condition = DBA::mergeConditions($condition, ["`cid` = ? and `follows`", $cid]);
		$sql       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `relation-cid` WHERE " . array_shift($condition);
		if ($count > 0) {
			$sql .= " LIMIT ?, ?";
			$condition = array_merge($condition, [$offset, $count]);
		}
		return DBA::toArray(DBA::p($sql, $condition));
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
		$condition = DBA::mergeConditions($condition, ["`relation-cid` = ? and `follows`", $cid]);
		$sql       = "SELECT COUNT(*) AS `total` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " . array_shift($condition);

		$result = DBA::fetchFirst($sql, $condition);
		return $result['total'] ?? 0;
	}

	/**
	 * Returns a paginated list of contacts that follow the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @return array
	 * @throws Exception
	 */
	public static function listFollowers(int $cid, array $condition = [], int $count = 30, int $offset = 0)
	{
		$condition = DBA::mergeConditions($condition, ["`relation-cid` = ? and `follows`", $cid]);
		$sql       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " . array_shift($condition);
		if ($count > 0) {
			$sql .= " LIMIT ?, ?";
			$condition = array_merge($condition, [$offset, $count]);
		}
		return DBA::toArray(DBA::p($sql, $condition));
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
		$condition1 = DBA::mergeConditions($condition, ["`cid` = ? and `follows`", $cid]);
		$condition2 = DBA::mergeConditions($condition, ["`relation-cid` = ? and `follows`", $cid]);
		$sql1       = "SELECT `contact`.`id` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `relation-cid` WHERE " . array_shift($condition1);
		$sql2       = "SELECT `contact`.`id` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " . array_shift($condition2);
		$union      = array_merge($condition1, $condition2);
		$sql        = $sql1 . " INTERSECT " . $sql2;

		$contacts = 0;
		$query    = DBA::p($sql, $union);
		while (DBA::fetch($query)) {
			$contacts++;
		}
		DBA::close($query);

		return $contacts;
	}

	/**
	 * Returns a paginated list of contacts that are known mutuals with the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @return array
	 * @throws Exception
	 */
	public static function listMutuals(int $cid, array $condition = [], int $count = 30, int $offset = 0)
	{
		$condition1 = DBA::mergeConditions($condition, ["`cid` = ? and `follows`", $cid]);
		$condition2 = DBA::mergeConditions($condition, ["`relation-cid` = ? and `follows`", $cid]);
		$sql1       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `relation-cid` WHERE " . array_shift($condition1);
		$sql2       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " . array_shift($condition2);
		$union      = array_merge($condition1, $condition2);
		$sql        = $sql1 . " INTERSECT " . $sql2;
		if ($count > 0) {
			$sql .= " LIMIT ?, ?";
			$union = array_merge($union, [$offset, $count]);
		}
		return DBA::toArray(DBA::p($sql, $union));
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
		$condition1 = DBA::mergeConditions($condition, ["`cid` = ? and `follows`", $cid]);
		$condition2 = DBA::mergeConditions($condition, ["`relation-cid` = ? and `follows`", $cid]);
		$sql1       = "SELECT `contact`.`id` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `relation-cid` WHERE " . array_shift($condition1);
		$sql2       = "SELECT `contact`.`id` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " .array_shift($condition2);
		$union      = array_merge($condition1, $condition2);
		$sql        = $sql1 . " UNION " . $sql2;

		$contacts = 0;
		$query    = DBA::p($sql, $union);
		while (DBA::fetch($query)) {
			$contacts++;
		}
		DBA::close($query);

		return $contacts;
	}

	/**
	 * Returns a paginated list of contacts with any relationship with the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @return array
	 * @throws Exception
	 */
	public static function listAll(int $cid, array $condition = [], int $count = 30, int $offset = 0)
	{
		$condition1 = DBA::mergeConditions($condition, ["`cid` = ? and `follows`", $cid]);
		$condition2 = DBA::mergeConditions($condition, ["`relation-cid` = ? and `follows`", $cid]);
		$sql1       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `relation-cid` WHERE " . array_shift($condition1);
		$sql2       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " .array_shift($condition2);
		$union      = array_merge($condition1, $condition2);
		$sql        = $sql1 . " UNION " . $sql2;
		if ($count > 0) {
			$sql .= " LIMIT ?, ?";
			$union = array_merge($union, [$offset, $count]);
		}
		return DBA::toArray(DBA::p($sql, $union));
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
		$condition1 = DBA::mergeConditions($condition, ["`relation-cid` = ?", $sourceId]);
		$condition2 = DBA::mergeConditions($condition, ["`relation-cid` = ?", $targetId]);
		$sql1       = "SELECT `contact`.`id` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " . array_shift($condition1);
		$sql2       = "SELECT `contact`.`id` FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " .array_shift($condition2);
		$union      = array_merge($condition1, $condition2);
		$sql        = $sql1 . " INTERSECT " . $sql2;

		$contacts = 0;
		$query    = DBA::p($sql, $union);
		while (DBA::fetch($query)) {
			$contacts++;
		}
		DBA::close($query);

		return $contacts;
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
	 * @return array|bool Array on success, false on failure
	 * @throws Exception
	 */
	public static function listCommon(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0)
	{
		$condition1 = DBA::mergeConditions($condition, ["`relation-cid` = ?", $sourceId]);
		$condition2 = DBA::mergeConditions($condition, ["`relation-cid` = ?", $targetId]);
		$sql1       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " . array_shift($condition1);
		$sql2       = "SELECT `contact`.* FROM `contact-relation` INNER JOIN `contact` ON `contact`.`id` = `cid` WHERE " .array_shift($condition2);
		$union      = array_merge($condition1, $condition2);
		$sql        = $sql1 . " INTERSECT " . $sql2;
		if ($count > 0) {
			$sql .= " LIMIT ?, ?";
			$union = array_merge($union, [$offset, $count]);
		}
		return DBA::toArray(DBA::p($sql, $union));
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
		$condition = DBA::mergeConditions(
			$condition,
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
		$condition = DBA::mergeConditions(
			$condition,
			["`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)",
				$sourceId, $targetId]
		);

		return DI::dba()->selectToArray(
			'contact',
			[],
			$condition,
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
		$condition = DBA::mergeConditions(
			$condition,
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
		$condition = DBA::mergeConditions(
			$condition,
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)",
				$sourceId, $targetId]
		);

		return DI::dba()->selectToArray(
			'contact',
			[],
			$condition,
			['limit' => [$offset, $count], 	'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Calculate the interaction scores for the given user
	 *
	 * @param integer $uid
	 * @return void
	 */
	public static function calculateInteractionScore(int $uid)
	{
		$days       = DI::config()->get('channel', 'interaction_score_days');
		$contact_id = Contact::getPublicIdByUserId($uid);

		DI::logger()->debug('Calculation - start', ['uid' => $uid, 'cid' => $contact_id, 'days' => $days]);

		$follow = Verb::getID(Activity::FOLLOW);
		$view   = Verb::getID(Activity::VIEW);
		$read   = Verb::getID(Activity::READ);

		DBA::update('contact-relation', ['score' => 0, 'relation-score' => 0, 'thread-score' => 0, 'relation-thread-score' => 0], ['relation-cid' => $contact_id]);

		$total = DBA::fetchFirst(
			"SELECT count(*) AS `activity` FROM `post-user` INNER JOIN `post` ON `post`.`uri-id` = `post-user`.`thr-parent-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?)",
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);

		DI::logger()->debug('Calculate relation-score', ['uid' => $uid, 'total' => $total['activity']]);

		$interactions = DBA::p(
			"SELECT `post`.`author-id`, count(*) AS `activity`, EXISTS(SELECT `pid` FROM `account-user-view` WHERE `pid` = `post`.`author-id` AND `uid` = ? AND `rel` IN (?, ?)) AS `follows`
			FROM `post-user` INNER JOIN `post` ON `post`.`uri-id` = `post-user`.`thr-parent-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?) GROUP BY `post`.`author-id`",
			$uid,
			Contact::SHARING,
			Contact::FRIEND,
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);
		while ($interaction = DBA::fetch($interactions)) {
			$score = min((int)(($interaction['activity'] / $total['activity']) * 65535), 65535);
			DBA::update('contact-relation', ['relation-score' => $score, 'follows' => $interaction['follows']], ['relation-cid' => $contact_id, 'cid' => $interaction['author-id']]);
		}
		DBA::close($interactions);

		$total = DBA::fetchFirst(
			"SELECT count(*) AS `activity` FROM `post-user` INNER JOIN `post` ON `post`.`uri-id` = `post-user`.`parent-uri-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?)",
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);

		DI::logger()->debug('Calculate relation-thread-score', ['uid' => $uid, 'total' => $total['activity']]);

		$interactions = DBA::p(
			"SELECT `post`.`author-id`, count(*) AS `activity`, EXISTS(SELECT `pid` FROM `account-user-view` WHERE `pid` = `post`.`author-id` AND `uid` = ? AND `rel` IN (?, ?)) AS `follows`
			FROM `post-user` INNER JOIN `post` ON `post`.`uri-id` = `post-user`.`parent-uri-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?) GROUP BY `post`.`author-id`",
			$uid,
			Contact::SHARING,
			Contact::FRIEND,
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);
		while ($interaction = DBA::fetch($interactions)) {
			$score = min((int)(($interaction['activity'] / $total['activity']) * 65535), 65535);
			DBA::update('contact-relation', ['relation-thread-score' => $score, 'follows' => !empty($interaction['follows'])], ['relation-cid' => $contact_id, 'cid' => $interaction['author-id']]);
		}
		DBA::close($interactions);

		$total = DBA::fetchFirst(
			"SELECT count(*) AS `activity` FROM `post-user` INNER JOIN `post` ON `post-user`.`uri-id` = `post`.`thr-parent-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?)",
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);

		DI::logger()->debug('Calculate score', ['uid' => $uid, 'total' => $total['activity']]);

		$interactions = DBA::p(
			"SELECT `post`.`author-id`, count(*) AS `activity` FROM `post-user` INNER JOIN `post` ON `post-user`.`uri-id` = `post`.`thr-parent-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?) GROUP BY `post`.`author-id`",
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);
		while ($interaction = DBA::fetch($interactions)) {
			$score = min((int)(($interaction['activity'] / $total['activity']) * 65535), 65535);
			DBA::update('contact-relation', ['score' => $score], ['relation-cid' => $contact_id, 'cid' => $interaction['author-id']]);
		}
		DBA::close($interactions);

		$total = DBA::fetchFirst(
			"SELECT count(*) AS `activity` FROM `post-user` INNER JOIN `post` ON `post-user`.`uri-id` = `post`.`parent-uri-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?)",
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);

		DI::logger()->debug('Calculate thread-score', ['uid' => $uid, 'total' => $total['activity']]);

		$interactions = DBA::p(
			"SELECT `post`.`author-id`, count(*) AS `activity` FROM `post-user` INNER JOIN `post` ON `post-user`.`uri-id` = `post`.`parent-uri-id` WHERE `post-user`.`author-id` = ? AND `post-user`.`received` >= ? AND `post-user`.`uid` = ? AND `post`.`author-id` != ? AND NOT `post`.`vid` IN (?, ?, ?) GROUP BY `post`.`author-id`",
			$contact_id,
			DateTimeFormat::utc('now - ' . $days . ' day'),
			$uid,
			$contact_id,
			$follow,
			$view,
			$read
		);
		while ($interaction = DBA::fetch($interactions)) {
			$score = min((int)(($interaction['activity'] / $total['activity']) * 65535), 65535);
			DBA::update('contact-relation', ['thread-score' => $score], ['relation-cid' => $contact_id, 'cid' => $interaction['author-id']]);
		}
		DBA::close($interactions);

		$total = DBA::fetchFirst(
			"SELECT count(*) AS `posts` FROM `post-thread-user` WHERE EXISTS(SELECT `cid` FROM `contact-relation` WHERE `cid` = `post-thread-user`.`author-id` AND `relation-cid` = ? AND `follows`) AND `uid` = ? AND `created` > ?",
			$contact_id,
			$uid,
			DateTimeFormat::utc('now - ' . $days . ' day')
		);

		DI::logger()->debug('Calculate post-score', ['uid' => $uid, 'total' => $total['posts']]);

		$posts = DBA::p(
			"SELECT `author-id`, count(*) AS `posts` FROM `post-thread-user` WHERE EXISTS(SELECT `cid` FROM `contact-relation` WHERE `cid` = `post-thread-user`.`author-id` AND `relation-cid` = ? AND `follows`) AND `uid` = ? AND `created` > ? GROUP BY `author-id`",
			$contact_id,
			$uid,
			DateTimeFormat::utc('now - ' . $days . ' day')
		);
		while ($post = DBA::fetch($posts)) {
			$score = min((int)(($post['posts'] / $total['posts']) * 65535), 65535);
			DBA::update('contact-relation', ['post-score' => $score], ['relation-cid' => $contact_id, 'cid' => $post['author-id']]);
		}
		DBA::close($posts);

		DI::logger()->debug('Calculation - end', ['uid' => $uid]);
	}
}
