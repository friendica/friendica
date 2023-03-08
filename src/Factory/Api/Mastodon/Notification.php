<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Model\Contact;
use Friendica\Navigation\Notifications;
use Friendica\Navigation\Notifications\Exception\UnexpectedNotificationTypeException;
use Friendica\Object\Api\Mastodon\Notification as MstdnNotification;
use Friendica\Protocol\Activity;
use Psr\Log\LoggerInterface;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Model\Post;

class Notification extends BaseFactory
{
	/** @var Account */
	private $mstdnAccountFactory;
	/** @var Status */
	private $mstdnStatusFactory;

	public function __construct(LoggerInterface $logger, Account $mstdnAccountFactory, Status $mstdnStatusFactoryFactory)
	{
		parent::__construct($logger);
		$this->mstdnAccountFactory = $mstdnAccountFactory;
		$this->mstdnStatusFactory  = $mstdnStatusFactoryFactory;
	}

	/**
	 * @param Notifications\Entity\Notification $Notification
	 * @param bool $display_quote Display quoted posts
	 *
	 * @return MstdnNotification
	 * @throws UnexpectedNotificationTypeException
	 */
	public function createFromNotification(Notifications\Entity\Notification $Notification, bool $display_quotes): MstdnNotification
	{
		$type = self::getType($Notification);
		if (empty($type)) {
			throw new UnexpectedNotificationTypeException();
		}

		$account = $this->mstdnAccountFactory->createFromContactId($Notification->actorId, $Notification->uid);

		if ($Notification->targetUriId) {
			try {
				$status = $this->mstdnStatusFactory->createFromUriId($Notification->targetUriId, $Notification->uid, $display_quotes);
			} catch (\Exception $exception) {
				$status = null;
			}
		} else {
			$status = null;
		}

		return new MstdnNotification($Notification->id, $type, $Notification->created, $account, $status, $Notification->dismissed);
	}

	/**
	 * Computes the Mastodon notification type from the given local notification
	 *
	 * @param Entity\Notification $Notification
	 * @return string
	 * @throws \Exception
	 */
	public static function getType(Entity\Notification $Notification): string
	{
		if (($Notification->verb == Activity::FOLLOW) && ($Notification->type === Post\UserNotification::TYPE_NONE)) {
			$contact = Contact::getById($Notification->actorId, ['pending', 'uri-id', 'uid']);
			if (($contact['uid'] == 0) && !empty($contact['uri-id'])) {
				$contact = Contact::selectFirst(['pending'], ['uri-id' => $contact['uri-id'], 'uid' => $Notification->uid]);
			}

			if (!isset($contact['pending'])) {
				return '';
			}

			$type = $contact['pending'] ? MstdnNotification::TYPE_INTRODUCTION : MstdnNotification::TYPE_FOLLOW;
		} elseif (($Notification->verb == Activity::ANNOUNCE) &&
			in_array($Notification->type, [Post\UserNotification::TYPE_DIRECT_COMMENT, Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT])) {
			$type = MstdnNotification::TYPE_RESHARE;
		} elseif (in_array($Notification->verb, [Activity::LIKE, Activity::DISLIKE]) &&
			in_array($Notification->type, [Post\UserNotification::TYPE_DIRECT_COMMENT, Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT])) {
			$type = MstdnNotification::TYPE_LIKE;
		} elseif ($Notification->type === Post\UserNotification::TYPE_SHARED) {
			$type = MstdnNotification::TYPE_POST;
		} elseif (in_array($Notification->type, [
			Post\UserNotification::TYPE_EXPLICIT_TAGGED,
			Post\UserNotification::TYPE_IMPLICIT_TAGGED,
			Post\UserNotification::TYPE_DIRECT_COMMENT,
			Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT,
			Post\UserNotification::TYPE_THREAD_COMMENT
		])) {
			$type = MstdnNotification::TYPE_MENTION;
		} else {
			return '';
		}

		return $type;
	}
}
