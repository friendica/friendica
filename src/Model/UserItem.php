<?php

/**
 * @file src/Model/UserItem.php
 */

namespace Friendica\Model;

use Friendica\Core\Logger;
use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\Registry\App;
use Friendica\Util\Strings;

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
		$fields = ['id', 'uid', 'body', 'parent', 'gravity', 'tag', 'contact-id', 'thr-parent', 'parent-uri', 'author-id'];
		$item = Item::selectFirst($fields, ['id' => $iid, 'origin' => false]);
		if (!DBA::isResult($item)) {
			return;
		}

		// fetch all users in the thread
		$users = DBA::p("SELECT DISTINCT(`contact`.`uid`) FROM `item`
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id` AND `contact`.`uid` != 0
			WHERE `parent` IN (SELECT `parent` FROM `item` WHERE `id`=?)", $iid);
		while ($user = DBA::fetch($users)) {
			self::setNotificationForUser($item, $user['uid']);
		}
		DBA::close($users);
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
		if ($thread['ignored']) {
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

		if (empty($notification_type)) {
			return;
		}

		Logger::info('Set notification', ['iid' => $item['id'], 'uid' => $uid, 'notification-type' => $notification_type]);

		DBA::update('user-item', ['notification-type' => $notification_type], ['iid' => $item['id'], 'uid' => $uid], true);
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
		$profiles[] = App::baseUrl() . '/u/' . $user['nickname'];

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
		if ($item['gravity'] != GRAVITY_PARENT) {
			return false;
		}

		// Either the contact had posted something directly
		if (DBA::exists('contact', ['id' => $item['contact-id'], 'notify_new_posts' => true])) {
			return true;
		}

		// Or the contact is a mentioned forum
		$tags = DBA::select('term', ['url'], ['otype' => TERM_OBJ_POST, 'oid' => $item['id'], 'type' => TERM_MENTION, 'uid' => $uid]);
		while ($tag = DBA::fetch($tags)) {
			$condition = ['nurl' => Strings::normaliseLink($tag['url']), 'uid' => $uid, 'notify_new_posts' => true, 'contact-type' => Contact::TYPE_COMMUNITY];
			if (DBA::exists('contact', $condition)) {
				return true;
			}
		}

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
		foreach ($profiles AS $profile) {
			if (strpos($item['tag'], '=' . $profile.']') || strpos($item['body'], '=' . $profile . ']')) {
				if (strpos($item['body'], $profile) === false) {
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
		foreach ($profiles AS $profile) {
			if (strpos($item['tag'], '=' . $profile.']') || strpos($item['body'], '=' . $profile . ']')) {
				if (!(strpos($item['body'], $profile) === false)) {
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
		return Item::exists($condition);
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
		return Item::exists($condition);
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
		return Item::exists($condition);
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
		return Item::exists($condition);
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
		return Item::exists($condition);
	}
}
