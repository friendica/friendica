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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Module\BaseApi;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Object\Api\Mastodon\Notification;
use Friendica\Protocol\Activity;

/**
 * @see https://docs.joinmastodon.org/methods/notifications/
 */
class Notifications extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (!empty($this->parameters['id'])) {
			$id = $this->parameters['id'];
			try {
				$notification = DI::notification()->selectOneForUser($uid, ['id' => $id]);
				System::jsonExit(DI::mstdnNotification()->createFromNotification($notification));
			} catch (\Exception $e) {
				DI::mstdnError()->RecordNotFound();
			}
		}

		$request = $this->getRequest([
			'max_id'        => 0,     // Return results older than this ID
			'since_id'      => 0,     // Return results newer than this ID
			'min_id'        => 0,     // Return results immediately newer than this ID
			'limit'         => 20,    // Maximum number of results to return (default 20)
			'exclude_types' => [],    // Array of types to exclude (follow, favourite, reblog, mention, poll, follow_request)
			'account_id'    => 0,     // Return only notifications received from this account
			'with_muted'    => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'count'         => 0,
			'include_all'   => false  // Include dismissed and undismissed
		], $request);

		$params = ['order' => ['id' => true]];

		$condition = ['uid' => $uid, 'dismissed' => false];
		if ($request['include_all']) {
			$condition = ['uid' => $uid];
		}

		if (!empty($request['account_id'])) {
			$contact = Contact::getById($request['account_id'], ['url']);
			if (!empty($contact['url'])) {
				$condition['url'] = $contact['url'];
			}
		}

		if (in_array(Notification::TYPE_INTRODUCTION, $request['exclude_types'])) {
			$condition = DBA::mergeConditions($condition,
				["(`vid` != ? OR `type` != ? OR NOT `actor-id` IN (SELECT `id` FROM `contact` WHERE `pending`))",
				Verb::getID(Activity::FOLLOW), Post\UserNotification::TYPE_NONE]);
		}

		if (in_array(Notification::TYPE_FOLLOW, $request['exclude_types'])) {
			$condition = DBA::mergeConditions($condition,
				["(`vid` != ? OR `type` != ? OR NOT `actor-id` IN (SELECT `id` FROM `contact` WHERE NOT `pending`))",
				Verb::getID(Activity::FOLLOW), Post\UserNotification::TYPE_NONE]);
		}

		if (in_array(Notification::TYPE_LIKE, $request['exclude_types'])) {
			$condition = DBA::mergeConditions($condition, [
				"(NOT `vid` IN (?, ?) OR NOT `type` IN (?, ?))",
				Verb::getID(Activity::LIKE), Verb::getID(Activity::DISLIKE),
				Post\UserNotification::TYPE_DIRECT_COMMENT, Post\UserNotification::TYPE_THREAD_COMMENT
			]);
		}

		if (in_array(Notification::TYPE_RESHARE, $request['exclude_types'])) {
			$condition = DBA::mergeConditions($condition, [
				"(NOT `vid` IN (?) OR NOT `type` IN (?, ?))",
				Verb::getID(Activity::ANNOUNCE),
				Post\UserNotification::TYPE_DIRECT_COMMENT, Post\UserNotification::TYPE_THREAD_COMMENT
			]);
		}

		if (in_array(Notification::TYPE_MENTION, $request['exclude_types'])) {
			$condition = DBA::mergeConditions($condition, [
				"(NOT `vid` IN (?) OR NOT `type` IN (?, ?, ?, ?, ?))",
				Verb::getID(Activity::POST), Post\UserNotification::TYPE_EXPLICIT_TAGGED,
				Post\UserNotification::TYPE_IMPLICIT_TAGGED, Post\UserNotification::TYPE_DIRECT_COMMENT,
				Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT, Post\UserNotification::TYPE_THREAD_COMMENT]);
		}

		if (in_array(Notification::TYPE_POST, $request['exclude_types'])) {
			$condition = DBA::mergeConditions($condition, ["(NOT `vid` IN (?) OR NOT `type` IN (?))",
				Verb::getID(Activity::POST), Post\UserNotification::TYPE_SHARED]);
		}

		$mstdnNotifications = [];

		$Notifications = DI::notification()->selectByBoundaries(
			$condition,
			$params,
			$request['min_id'] ?: $request['since_id'],
			$request['max_id'],
			$request['limit']
		);

		foreach($Notifications as $Notification) {
			try {
				$mstdnNotifications[] = DI::mstdnNotification()->createFromNotification($Notification);
				self::setBoundaries($Notification->id);
			} catch (\Exception $e) {
				// Skip this notification
			}
		}

		self::setLinkHeader();
		System::jsonExit($mstdnNotifications);
	}
}
