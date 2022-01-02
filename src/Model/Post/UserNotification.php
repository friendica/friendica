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

namespace Friendica\Model\Post;

use BadMethodCallException;
use Exception;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\Subscription;
use Friendica\Model\Tag;
use Friendica\Navigation\Notifications;
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

		$fields = DBStructure::getFieldsForTable('post-user-notification', $data);

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

		$fields = DBStructure::getFieldsForTable('post-user-notification', $data);

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
	 * @param array $options     - cascade: If true we delete records in other tables that depend on the one we're deleting through
	 *                           relations (default: true)
	 *
	 * @return boolean was the deletion successful?
	 * @throws Exception
	 */
	public static function delete(array $conditions, array $options = []): bool
	{
		return DBA::delete('post-user-notification', $conditions, $options);
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
		           'private', 'contact-id', 'thr-parent', 'thr-parent-id', 'parent-uri-id', 'parent-uri', 'author-id', 'verb'];
		$item   = Post::selectFirst($fields, ['uri-id' => $uri_id, 'uid' => $uid, 'origin' => false]);
		if (!DBA::isResult($item)) {
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
			self::setNotificationForUser($item, $uid);
		}
	}

	/**
	 * Checks an item for notifications for the given user and sets the "notification-type" field
	 *
	 * @param array $item Item array
	 * @param int   $uid  User ID
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function setNotificationForUser(array $item, int $uid)
	{
		if (Post\ThreadUser::getIgnored($item['parent-uri-id'], $uid)) {
			return;
		}

		$notification_type = self::TYPE_NONE;

		if (self::checkShared($item, $uid)) {
			$notification_type = $notification_type | self::TYPE_SHARED;
			self::insertNotificationByItem(self::TYPE_SHARED, $uid, $item);
			$notified = true;
		} else {
			$notified = false;
		}

		$profiles = self::getProfileForUser($uid);

		// Fetch all contacts for the given profiles
		$contacts = [];

		$ret = DBA::select('contact', ['id'], ['uid' => 0, 'nurl' => $profiles]);
		while ($contact = DBA::fetch($ret)) {
			$contacts[] = $contact['id'];
		}
		DBA::close($ret);

		// Don't create notifications for user's posts
		if (in_array($item['author-id'], $contacts)) {
			return;
		}

		if (self::checkExplicitMention($item, $profiles)) {
			$notification_type = $notification_type | self::TYPE_EXPLICIT_TAGGED;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_EXPLICIT_TAGGED, $uid, $item);
				$notified = true;
			}
		}

		if (self::checkImplicitMention($item, $profiles)) {
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

		if (self::checkDirectCommentedThread($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_DIRECT_THREAD_COMMENT;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_DIRECT_THREAD_COMMENT, $uid, $item);
				$notified = true;
			}
		}

		if (self::checkCommentedThread($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_THREAD_COMMENT;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_THREAD_COMMENT, $uid, $item);
				$notified = true;
			}
		}

		if (self::checkCommentedParticipation($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_COMMENT_PARTICIPATION;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_COMMENT_PARTICIPATION, $uid, $item);
				$notified = true;
			}
		}

		if (self::checkActivityParticipation($item, $contacts)) {
			$notification_type = $notification_type | self::TYPE_ACTIVITY_PARTICIPATION;
			if (!$notified) {
				self::insertNotificationByItem(self::TYPE_ACTIVITY_PARTICIPATION, $uid, $item);
			}
		}

		// Only create notifications for posts and comments, not for activities
		if (empty($notification_type) || !in_array($item['gravity'], [GRAVITY_PARENT, GRAVITY_COMMENT])) {
			return;
		}

		Logger::info('Set notification', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'notification-type' => $notification_type]);

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
		if (($item['gravity'] == GRAVITY_ACTIVITY) &&
			!in_array($type, [self::TYPE_DIRECT_COMMENT, self::TYPE_DIRECT_THREAD_COMMENT])) {
			// Activities are only stored when performed on the user's post or comment
			return;
		}

		$notification = (new Notifications\Factory\Notification(DI::logger()))->createForUser(
			$uid,
			$item['vid'],
			$type,
			$item['author-id'],
			$item['gravity'] == GRAVITY_ACTIVITY ? $item['thr-parent-id'] : $item['uri-id'],
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
	 * @param int    $actor Contact ID of the actor
	 * @param string $verb  One of the Activity verb constant values
	 * @param int    $uid   User ID
	 * @return boolean
	 * @throws Exception
	 */
	public static function insertNotification(int $actor, string $verb, int $uid): bool
	{
		$notification = (new Notifications\Factory\Notification(DI::logger()))->createForRelationship(
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
		$notification_data = ['uid' => $uid, 'profiles' => []];
		Hook::callAll('check_item_notification', $notification_data);

		$profiles = $notification_data['profiles'];

		$user = DBA::selectFirst('user', ['nickname'], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return [];
		}

		$owner = DBA::selectFirst('contact', ['url', 'alias'], ['self' => true, 'uid' => $uid]);
		if (!DBA::isResult($owner)) {
			return [];
		}

		// This is our regular URL format
		$profiles[] = $owner['url'];

		// Now the alias
		$profiles[] = $owner['alias'];

		// Notifications from Diaspora often have a URL in the Diaspora format
		$profiles[] = DI::baseUrl() . '/u/' . $user['nickname'];

		// Validate and add profile links
		foreach ($profiles as $key => $profile) {
			// Check for invalid profile urls (without scheme, host or path) and remove them
			if (empty(parse_url($profile, PHP_URL_SCHEME)) || empty(parse_url($profile, PHP_URL_HOST)) || empty(parse_url($profile, PHP_URL_PATH))) {
				unset($profiles[$key]);
				continue;
			}

			// Add the normalized form
			$profile    = Strings::normaliseLink($profile);
			$profiles[] = $profile;

			// Add the SSL form
			$profile    = str_replace('http://', 'https://', $profile);
			$profiles[] = $profile;
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
		if (($item['gravity'] != GRAVITY_PARENT) && ($item['verb'] != Activity::ANNOUNCE)) {
			return false;
		}

		// Check if the contact posted or shared something directly
		if (DBA::exists('contact', ['id' => $item['contact-id'], 'notify_new_posts' => true])) {
			return true;
		}

		// The following check doesn't make sense on activities, so quit here
		if ($item['verb'] == Activity::ANNOUNCE) {
			return false;
		}

		// Check if the contact is a mentioned forum
		$tags = DBA::select('tag-view', ['url'], ['uri-id' => $item['uri-id'], 'type' => [Tag::MENTION, Tag::EXCLUSIVE_MENTION]]);
		while ($tag = DBA::fetch($tags)) {
			$condition = ['nurl' => Strings::normaliseLink($tag['url']), 'uid' => $uid, 'notify_new_posts' => true, 'contact-type' => Contact::TYPE_COMMUNITY];
			if (DBA::exists('contact', $condition)) {
				return true;
			}
		}
		DBA::close($tags);

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
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_PARENT];
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
		$condition = ['uri' => $item['thr-parent'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_COMMENT];
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
		$condition = ['uri' => $item['thr-parent'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_PARENT];
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
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_COMMENT];
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
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_ACTIVITY];
		return Post::exists($condition);
	}
}
