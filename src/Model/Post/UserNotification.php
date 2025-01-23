<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use BadMethodCallException;
use Exception;
use Friendica\Core\Hook;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Subscription;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\Strings;

class UserNotification
{
	// Notification types
	const TYPE_NONE                   = 0;
	const TYPE_EXPLICIT_TAGGED        = 1;
	const TYPE_IMPLICIT_TAGGED        = 2;
	const TYPE_THREAD_COMMENT         = 4;
	const TYPE_DIRECT_COMMENT         = 8;
	const TYPE_COMMENT_PARTICIPATION  = 16;
	const TYPE_ACTIVITY_PARTICIPATION = 32;
	const TYPE_DIRECT_THREAD_COMMENT  = 64;
	const TYPE_SHARED                 = 128;
	const TYPE_FOLLOW                 = 256;
	const TYPE_QUOTED                 = 512;

	/**
	 * Insert a new user notification entry
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param array   $data
	 * @return bool   success
	 * @throws Exception
	 */
	public static function insert(int $uri_id, int $uid, array $data = []): bool
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-user-notification', $data);

		$fields['uri-id'] = $uri_id;
		$fields['uid']    = $uid;

		return DBA::insert('post-user-notification', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Update a user notification entry
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws Exception
	 */
	public static function update(int $uri_id, int $uid, array $data = [], bool $insert_if_missing = false): bool
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-user-notification', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['uid']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-user-notification', $fields, ['uri-id' => $uri_id, 'uid' => $uid], $insert_if_missing ? true : []);
	}

	/**
	 * Delete a row from the post-user-notification table
	 *
	 * @param array $conditions  Field condition(s)
	 *
	 * @return boolean was the deletion successful?
	 * @throws Exception
	 */
	public static function delete(array $conditions): bool
	{
		return DBA::delete('post-user-notification', $conditions);
	}

	/**
	 * Checks an item for notifications and sets the "notification-type" field
	 *
	 * @ToDo:
	 * - Check for mentions in posts with "uid=0" where the user hadn't interacted before
	 *
	 * @param int $uri_id URI ID
	 * @param int $uid    user ID
	 * @throws Exception
	 */
	public static function setNotification(int $uri_id, int $uid)
	{
		$fields = ['id', 'uri-id', 'parent-uri-id', 'uid', 'body', 'parent', 'gravity', 'vid', 'gravity',
			'contact-id', 'author-id', 'author-gsid', 'owner-id', 'owner-gsid', 'causer-id', 'causer-gsid',
			'private', 'thr-parent', 'thr-parent-id', 'parent-uri-id', 'parent-uri', 'verb'];
		$item = Post::selectFirst($fields, ['uri-id' => $uri_id, 'uid' => $uid, 'origin' => false]);
		if (!DBA::isResult($item)) {
			return;
		}

		$parent = Post::selectFirstPost(['author-id', 'author-gsid', 'owner-id', 'owner-gsid', 'causer-id', 'causer-gsid',], ['uri-id' => $item['parent-uri-id']]);
		if (!DBA::isResult($parent)) {
			return;
		}

		// "Activity::FOLLOW" is an automated activity, so we ignore it here
		if ($item['verb'] == Activity::FOLLOW) {
			return;
		}

		if ($item['uid'] == 0) {
			$uids = [];
		} else {
			// Always include the item user
			$uids = [$item['uid']];
		}

		// Add every user who participated so far in this thread
		// This can only happen with participations on global items. (means: uid = 0)
		$users = DBA::p("SELECT DISTINCT(`contact-uid`) AS `uid` FROM `post-user-view`
			WHERE `contact-uid` != 0 AND `parent-uri-id` = ? AND `uid` = ?", $item['parent-uri-id'], $uid);
		while ($user = DBA::fetch($users)) {
			$uids[] = $user['uid'];
		}
		DBA::close($users);

		foreach (array_unique($uids) as $uid) {
			self::setNotificationForUser($item, $parent, $uid);
		}
	}

	/**
	 * Checks an item for notifications for the given user and sets the "notification-type" field
	 *
	 * @param array $item   Item array
	 * @param array $parent Parent item array
	 * @param int   $uid    User ID
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function setNotificationForUser(array $item, array $parent, int $uid)
	{
		if (Post\ThreadUser::getIgnored($item['parent-uri-id'], $uid)) {
			return;
		}

		foreach (array_unique([$parent['author-id'], $parent['owner-id'], $parent['causer-id'], $item['author-id'], $item['owner-id'], $item['causer-id']]) as $author_id) {
			if (empty($author_id)) {
				continue;
			}
			if (Contact\User::isBlocked($author_id, $uid) || Contact\User::isIgnored($author_id, $uid) || Contact\User::isCollapsed($author_id, $uid)) {
				DI::logger()->debug('Author is blocked/ignored/collapsed by user', ['uid' => $uid, 'author' => $author_id, 'uri-id' => $item['uri-id']]);
				return;
			}
		}

		foreach (array_unique([$parent['author-gsid'], $parent['owner-gsid'], $parent['causer-gsid'], $item['author-gsid'], $item['owner-gsid'], $item['causer-gsid']]) as $gsid) {
			if ($gsid && DI::userGServer()->isIgnoredByUser($uid, $gsid)) {
				DI::logger()->debug('Server is ignored by user', ['uid' => $uid, 'gsid' => $gsid, 'uri-id' => $item['uri-id']]);
				return;
			}
		}

		$user = User::getById($uid, ['account-type', 'account_removed', 'account_expired']);
		if (in_array($user['account-type'], [User::ACCOUNT_TYPE_COMMUNITY, User::ACCOUNT_TYPE_RELAY])) {
			return;
		}

		if ($user['account_removed'] || $user['account_expired']) {
			return;
		}

		$author = Contact::getById($item['author-id'], ['contact-type']);
		if (empty($author)) {
			return;
		}

		$notification_type = self::TYPE_NONE;

		if (self::checkShared($item, $uid)) {
			$notification_type = $notification_type | self::TYPE_SHARED;
			self::insertNotificationByItem(self::TYPE_SHARED, $uid, $item);
			$notified = true;
		} elseif ($author['contact-type'] == Contact::TYPE_COMMUNITY) {
			return;
		} else {
			$notified = false;
		}

		$profiles = self::getProfileForUser($uid);

		// Fetch all contacts for the given profiles
		$contacts    = [];
		$iscommunity = false;

		$ret = DBA::select('contact', ['id', 'contact-type'], ['uid' => 0, 'nurl' => $profiles]);
		while ($contact = DBA::fetch($ret)) {
			$contacts[] = $contact['id'];

			if ($contact['contact-type'] == Contact::TYPE_COMMUNITY) {
				$iscommunity = true;
			}
		}
		DBA::close($ret);

		// Don't create notifications for user's posts
		if (in_array($item['author-id'], $contacts)) {
			return;
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkExplicitMention($item, $profiles)) {
			$notification_type = $notification_type | self::TYPE_EXPLICIT_TAGGED;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_EXPLICIT_TAGGED, $uid, $item);
				$notified = true;
			}
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkImplicitMention($item, $profiles)) {
			$notification_type = $notification_type | self::TYPE_IMPLICIT_TAGGED;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_IMPLICIT_TAGGED, $uid, $item);
				$notified = true;
			}
		}

		if (self::checkDirectComment($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_DIRECT_COMMENT;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_DIRECT_COMMENT, $uid, $item);
				$notified = true;
			}
		}

		if (!$iscommunity && self::checkDirectCommentedThread($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_DIRECT_THREAD_COMMENT;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_DIRECT_THREAD_COMMENT, $uid, $item);
				$notified = true;
			}
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkCommentedThread($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_THREAD_COMMENT;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_THREAD_COMMENT, $uid, $item);
				$notified = true;
			}
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkCommentedParticipation($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_COMMENT_PARTICIPATION;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_COMMENT_PARTICIPATION, $uid, $item);
				$notified = true;
			}
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkQuoted($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_QUOTED;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_QUOTED, $uid, $item);
				$notified = true;
			}
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkFollowParticipation($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_FOLLOW;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_FOLLOW, $uid, $item);
				$notified = true;
			}
		}

		if (($item['verb'] != Activity::ANNOUNCE) && self::checkActivityParticipation($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_ACTIVITY_PARTICIPATION;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_ACTIVITY_PARTICIPATION, $uid, $item);
			}
		}

		if (empty($notification_type)) {
			return;
		}

		// Only create notifications for posts and comments, not for activities
		if (($item['gravity'] == Item::GRAVITY_ACTIVITY) && ($item['verb'] != Activity::ANNOUNCE)) {
			return;
		}

		DI::logger()->info('Set notification', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'notification-type' => $notification_type]);

		$fields = ['notification-type' => $notification_type];
		Post\User::update($item['uri-id'], $uid, $fields);
		self::update($item['uri-id'], $uid, $fields, true);
	}

	/**
	 * Add a notification entry for a given item array
	 *
	 * @param int   $type User notification type
	 * @param int   $uid  User ID
	 * @param array $item Item array
	 * @return void
	 * @throws Exception
	 */
	private static function insertNotificationByItem(int $type, int $uid, array $item): void
	{
		if (($item['verb'] != Activity::ANNOUNCE) && ($item['gravity'] == Item::GRAVITY_ACTIVITY) &&
			!in_array($type, [self::TYPE_DIRECT_COMMENT, self::TYPE_DIRECT_THREAD_COMMENT])) {
			// Activities are only stored when performed on the user's post or comment
			return;
		}

		$notification = DI::notificationFactory()->createForUser(
			$uid,
			$item['vid'],
			$type,
			$item['author-id'],
			$item['gravity'] == Item::GRAVITY_ACTIVITY ? $item['thr-parent-id'] : $item['uri-id'],
			$item['parent-uri-id']
		);

		try {
			$notification = DI::notification()->save($notification);
			Subscription::pushByNotification($notification);
		} catch (Exception $e) {

		}
	}

	/**
	 * Add a notification entry
	 *
	 * @param int    $actor Public contact ID of the actor
	 * @param string $verb  One of the Activity verb constant values
	 * @param int    $uid   User ID
	 * @return boolean
	 * @throws Exception
	 */
	public static function insertNotification(int $actor, string $verb, int $uid): bool
	{
		$notification = DI::notificationFactory()->createForRelationship(
			$uid,
			$actor,
			$verb
		);
		try {
			$notification = DI::notification()->save($notification);
			Subscription::pushByNotification($notification);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Fetch all profiles (contact URL) of a given user
	 *
	 * @param int $uid User ID
	 *
	 * @return array Profile links
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function getProfileForUser(int $uid): array
	{
		$owner = User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			return [];
		}

		$profiles = [$owner['nurl']];

		$notification_data = ['uid' => $uid, 'profiles' => []];
		Hook::callAll('check_item_notification', $notification_data);

		// Normalize the connector profiles
		foreach ($notification_data['profiles'] as $profile) {
			if (empty(parse_url($profile, PHP_URL_SCHEME)) || empty(parse_url($profile, PHP_URL_HOST)) || empty(parse_url($profile, PHP_URL_PATH))) {
				$profiles[] = $profile;
			} else {
				$profiles[] = Strings::normaliseLink($profile);
			}
		}

		return array_unique($profiles);
	}

	/**
	 * Check for a "shared" notification for every new post of contacts from the given user
	 *
	 * @param array $item
	 * @param int   $uid User ID
	 * @return bool A contact had shared something
	 * @throws Exception
	 */
	private static function checkShared(array $item, int $uid): bool
	{
		// Only check on original posts and reshare ("announce") activities, otherwise return
		if (($item['gravity'] != Item::GRAVITY_PARENT) && ($item['verb'] != Activity::ANNOUNCE)) {
			return false;
		}

		// Don't notify about reshares by communities of our own posts or each time someone comments
		if (($item['verb'] == Activity::ANNOUNCE) && DBA::exists('contact', ['id' => $item['contact-id'], 'contact-type' => Contact::TYPE_COMMUNITY])) {
			$post = Post::selectFirst(['origin', 'gravity'], ['uri-id' => $item['thr-parent-id'], 'uid' => $uid]);
			if (!$post || $post['origin'] || ($post['gravity'] != Item::GRAVITY_PARENT)) {
				return false;
			}
		}

		// Only check on posts by the user itself
		$cdata = Contact::getPublicAndUserContactID($item['contact-id'], $item['uid']);
		if (empty($cdata['user']) || ($item['author-id'] != $cdata['public'])) {
			return false;
		}

		// Check if the contact posted or shared something directly
		if (DBA::exists('contact', ['id' => $item['contact-id'], 'notify_new_posts' => true])) {
			return true;
		}

		return false;
	}

	/**
	 * Check for an implicit mention (only in tags, not in body) of the given user
	 *
	 * @param array $item
	 * @param array $profiles Profile links
	 * @return bool The user is mentioned
	 * @throws Exception
	 */
	private static function checkImplicitMention(array $item, array $profiles): bool
	{
		$mentions = Tag::getByURIId($item['uri-id'], [Tag::IMPLICIT_MENTION]);
		foreach ($mentions as $mention) {
			foreach ($profiles as $profile) {
				if (Strings::compareLink($profile, $mention['url'])) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check for an explicit mention (tag and body) of the given user
	 *
	 * @param array $item
	 * @param array $profiles Profile links
	 * @return bool The user is mentioned
	 * @throws Exception
	 */
	private static function checkExplicitMention(array $item, array $profiles): bool
	{
		$mentions = Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::EXCLUSIVE_MENTION]);
		foreach ($mentions as $mention) {
			foreach ($profiles as $profile) {
				if (Strings::compareLink($profile, $mention['url'])) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the given user had created this thread
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had created this thread
	 * @throws Exception
	 */
	private static function checkCommentedThread(array $item, array $contacts): bool
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => Item::GRAVITY_PARENT];
		return Post::exists($condition);
	}

	/**
	 * Check for a direct comment to a post of the given user
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The item is a direct comment to a user comment
	 * @throws Exception
	 */
	private static function checkDirectComment(array $item, array $contacts): bool
	{
		$condition = ['uri' => $item['thr-parent'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => Item::GRAVITY_COMMENT];
		return Post::exists($condition);
	}

	/**
	 * Check for a direct comment to the starting post of the given user
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had created this thread
	 * @throws Exception
	 */
	private static function checkDirectCommentedThread(array $item, array $contacts): bool
	{
		$condition = ['uri' => $item['thr-parent'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => Item::GRAVITY_PARENT];
		return Post::exists($condition);
	}

	/**
	 *  Check if the user had commented in this thread
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had commented in the thread
	 * @throws Exception
	 */
	private static function checkCommentedParticipation(array $item, array $contacts): bool
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => Item::GRAVITY_COMMENT];
		return Post::exists($condition);
	}

	/**
	 * Check if the user follows this thread
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user follows the thread
	 * @throws Exception
	 */
	private static function checkFollowParticipation(array $item, array $contacts): bool
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => Item::GRAVITY_ACTIVITY, 'verb' => Activity::FOLLOW];
		return Post::exists($condition);
	}

	/**
	 * Check if the user had interacted in this thread (Like, Dislike, ...)
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had interacted in the thread
	 * @throws Exception
	 */
	private static function checkActivityParticipation(array $item, array $contacts): bool
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => Item::GRAVITY_ACTIVITY];
		return Post::exists($condition);
	}

	/**
	 * Check for a quoted post of a post of the given user
	 *
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The item is a quoted post of a user's post or comment
	 * @throws Exception
	 */
	private static function checkQuoted(array $item, array $contacts): bool
	{
		if (empty($item['quote-uri-id']) || ($item['quote-uri-id'] == $item['uri-id'])) {
			return false;
		}
		$condition = ['uri-id' => $item['quote-uri-id'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]];
		return Post::exists($condition);
	}


}
