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

use Friendica\Core\Logger;
use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;
use Friendica\Model\Tag;
use Friendica\Protocol\Activity;

class UserItem
{
	// Notification types
	const NOTIF_NONE = 0;
	const NOTIF_EXPLICIT_TAGGED = 1;
	const NOTIF_IMPLICIT_TAGGED = 2;
	const NOTIF_THREAD_COMMENT = 4;
	const NOTIF_DIRECT_COMMENT = 8;
	const NOTIF_COMMENT_PARTICIPATION = 16;
	const NOTIF_ACTIVITY_PARTICIPATION = 32;
	const NOTIF_DIRECT_THREAD_COMMENT = 64;
	const NOTIF_SHARED = 128;

	/**
	 * Checks an item for notifications and sets the "notification-type" field
	 * @ToDo:
	 * - Check for mentions in posts with "uid=0" where the user hadn't interacted before
	 *
	 * @param int $iid Item ID
	 */
	public static function setNotification(int $iid)
	{
		$fields = ['id', 'uri-id', 'parent-uri-id', 'uid', 'body', 'parent', 'gravity',
			'private', 'contact-id', 'thr-parent', 'parent-uri', 'author-id', 'verb'];
		$item = Post::selectFirst($fields, ['id' => $iid, 'origin' => false]);
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
		$users = DBA::p("SELECT DISTINCT(`contact`.`uid`) FROM `item`
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id` AND `contact`.`uid` != 0
			WHERE `parent` IN (SELECT `parent` FROM `item` WHERE `id`=?)", $iid);
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
	 */
	private static function setNotificationForUser(array $item, int $uid)
	{
		$thread = Item::selectFirstThreadForUser($uid, ['ignored'], ['iid' => $item['parent'], 'deleted' => false]);
		if (!empty($thread['ignored'])) {
			return;
		}

		$notification_type = self::NOTIF_NONE;

		if (self::checkShared($item, $uid)) {
			$notification_type = $notification_type | self::NOTIF_SHARED;
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

		// Only create notifications for posts and comments, not for activities
		if (in_array($item['gravity'], [GRAVITY_PARENT, GRAVITY_COMMENT])) {
			if (self::checkImplicitMention($item, $profiles)) {
				$notification_type = $notification_type | self::NOTIF_IMPLICIT_TAGGED;
			}

			if (self::checkExplicitMention($item, $profiles)) {
				$notification_type = $notification_type | self::NOTIF_EXPLICIT_TAGGED;
			}

			if (self::checkCommentedThread($item, $contacts)) {
				$notification_type = $notification_type | self::NOTIF_THREAD_COMMENT;
			}

			if (self::checkDirectComment($item, $contacts)) {
				$notification_type = $notification_type | self::NOTIF_DIRECT_COMMENT;
			}

			if (self::checkDirectCommentedThread($item, $contacts)) {
				$notification_type = $notification_type | self::NOTIF_DIRECT_THREAD_COMMENT;
			}

			if (self::checkCommentedParticipation($item, $contacts)) {
				$notification_type = $notification_type | self::NOTIF_COMMENT_PARTICIPATION;
			}

			if (self::checkActivityParticipation($item, $contacts)) {
				$notification_type = $notification_type | self::NOTIF_ACTIVITY_PARTICIPATION;
			}
		}

		if (empty($notification_type)) {
			return;
		}

		Logger::info('Set notification', ['iid' => $item['id'], 'uid' => $uid, 'notification-type' => $notification_type]);

		$fields = ['notification-type' => $notification_type];
		Post\User::update($item['uri-id'], $uid, $fields);
		DBA::update('user-item', $fields, ['iid' => $item['id'], 'uid' => $uid], true);
	}

	/**
	 * Fetch all profiles (contact URL) of a given user
	 * @param int $uid User ID
	 *
	 * @return array Profile links
	 */
	private static function getProfileForUser(int $uid)
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

		// Notifications from Diaspora are often with an URL in the Diaspora format
		$profiles[] = DI::baseUrl() . '/u/' . $user['nickname'];

		// Validate and add profile links
		foreach ($profiles AS $key => $profile) {
			// Check for invalid profile urls (without scheme, host or path) and remove them
			if (empty(parse_url($profile, PHP_URL_SCHEME)) || empty(parse_url($profile, PHP_URL_HOST)) || empty(parse_url($profile, PHP_URL_PATH))) {
				unset($profiles[$key]);
				continue;
			}

			// Add the normalized form
			$profile = Strings::normaliseLink($profile);
			$profiles[] = $profile;

			// Add the SSL form
			$profile = str_replace('http://', 'https://', $profile);
			$profiles[] = $profile;
		}

		return array_unique($profiles);
	}

	/**
	 * Check for a "shared" notification for every new post of contacts from the given user
	 * @param array $item
	 * @param int   $uid  User ID
	 * @return bool A contact had shared something
	 */
	private static function checkShared(array $item, int $uid)
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
	 * Check for an implicit mention (only tag, no body) of the given user
	 * @param array $item
	 * @param array $profiles Profile links
	 * @return bool The user is mentioned
	 */
	private static function checkImplicitMention(array $item, array $profiles)
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
	 * @param array $item
	 * @param array $profiles Profile links
	 * @return bool The user is mentioned
	 */
	private static function checkExplicitMention(array $item, array $profiles)
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
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had created this thread
	 */
	private static function checkCommentedThread(array $item, array $contacts)
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_PARENT];
		return Post::exists($condition);
	}

	/**
	 * Check for a direct comment to a post of the given user
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The item is a direct comment to a user comment
	 */
	private static function checkDirectComment(array $item, array $contacts)
	{
		$condition = ['uri' => $item['thr-parent'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_COMMENT];
		return Post::exists($condition);
	}

	/**
	 * Check for a direct comment to the starting post of the given user
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had created this thread
	 */
	private static function checkDirectCommentedThread(array $item, array $contacts)
	{
		$condition = ['uri' => $item['thr-parent'], 'uid' => $item['uid'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_PARENT];
		return Post::exists($condition);
	}

	/**
	 *  Check if the user had commented in this thread
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had commented in the thread
	 */
	private static function checkCommentedParticipation(array $item, array $contacts)
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_COMMENT];
		return Post::exists($condition);
	}

	/**
	 * Check if the user had interacted in this thread (Like, Dislike, ...)
	 * @param array $item
	 * @param array $contacts Array of contact IDs
	 * @return bool The user had interacted in the thread
	 */
	private static function checkActivityParticipation(array $item, array $contacts)
	{
		$condition = ['parent' => $item['parent'], 'author-id' => $contacts, 'deleted' => false, 'gravity' => GRAVITY_ACTIVITY];
		return Post::exists($condition);
	}
}
