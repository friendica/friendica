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

namespace Friendica\Navigation\Notifications\Factory;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\L10n;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Protocol\Activity;

class Notification extends BaseFactory implements ICanCreateFromTableRow
{
	public function createFromTableRow(array $row): Entity\Notification
	{
		return new Entity\Notification(
			$row['uid'] ?? 0,
			Verb::getByID($row['vid']),
			$row['type'],
			$row['actor-id'],
			$row['target-uri-id'],
			$row['parent-uri-id'],
			new \DateTime($row['created'], new \DateTimeZone('UTC')),
			$row['seen'],
			$row['id']
		);
	}

	public function createForUser(int $uid, int $vid, int $type, int $actorId, int $targetUriId, int $parentUriId): Entity\Notification
	{
		return new Entity\Notification(
			$uid,
			Verb::getByID($vid),
			$type,
			$actorId,
			$targetUriId,
			$parentUriId
		);
	}

	public function createForRelationship(int $uid, int $contactId, string $verb): Entity\Notification
	{
		return new Entity\Notification(
			$uid,
			$verb,
			Post\UserNotification::TYPE_NONE,
			$contactId
		);
	}

	/**
	 * @param Entity\Notification $Notification
	 * @param BaseURL             $baseUrl
	 * @param L10n                $userL10n Seeded with the language of the user we mean the notification for
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getMessageFromNotification(Entity\Notification $Notification, BaseURL $baseUrl, L10n $userL10n)
	{
		$message = [];

		$causer = $author = Contact::getById($Notification->actorId, ['id', 'name', 'url', 'pending']);
		if (empty($causer)) {
			$this->logger->info('Causer not found', ['contact' => $Notification->actorId]);
			return $message;
		}

		if ($Notification->type === Post\UserNotification::TYPE_NONE) {
			if ($causer['pending']) {
				$msg = $userL10n->t('%1$s wants to follow you');
			} else {
				$msg = $userL10n->t('%1$s had started following you');
			}
			$title = $causer['name'];
			$link  = $baseUrl . '/contact/' . $causer['id'];
		} else {
			if (!$Notification->targetUriId) {
				return $message;
			}

			if (in_array($Notification->type, [Post\UserNotification::TYPE_THREAD_COMMENT, Post\UserNotification::TYPE_COMMENT_PARTICIPATION, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION, Post\UserNotification::TYPE_EXPLICIT_TAGGED])) {
				$item = Post::selectFirst([], ['uri-id' => $Notification->parentUriId, 'uid' => [0, $Notification->uid]], ['order' => ['uid' => true]]);
				if (empty($item)) {
					$this->logger->info('Parent post not found', ['uri-id' => $Notification->parentUriId]);
					return $message;
				}
			} else {
				$item = Post::selectFirst([], ['uri-id' => $Notification->targetUriId, 'uid' => [0, $Notification->uid]], ['order' => ['uid' => true]]);
				if (empty($item)) {
					$this->logger->info('Post not found', ['uri-id' => $Notification->targetUriId]);
					return $message;
				}

				if ($Notification->verb == Activity::POST) {
					$item = Post::selectFirst([], ['uri-id' => $item['thr-parent-id'], 'uid' => [0, $Notification->uid]], ['order' => ['uid' => true]]);
					if (empty($item)) {
						$this->logger->info('Thread parent post not found', ['uri-id' => $item['thr-parent-id']]);
						return $message;
					}
				}
			}

			if ($item['owner-id'] != $item['author-id']) {
				$cid = $item['owner-id'];
			}
			if (!empty($item['causer-id']) && ($item['causer-id'] != $item['author-id'])) {
				$cid = $item['causer-id'];
			}

			if (($Notification->type === Post\UserNotification::TYPE_SHARED) && !empty($cid)) {
				$causer = Contact::getById($cid, ['id', 'name', 'url']);
				if (empty($causer)) {
					$this->logger->info('Causer not found', ['causer' => $cid]);
					return $message;
				}
			} elseif (in_array($Notification->type, [Post\UserNotification::TYPE_COMMENT_PARTICIPATION, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION])) {
				$author = Contact::getById($item['author-id'], ['id', 'name', 'url']);
				if (empty($author)) {
					$this->logger->info('Author not found', ['author' => $item['author-id']]);
					return $message;
				}
			}

			$link = $baseUrl . '/display/' . urlencode($item['guid']);

			$content = Plaintext::getPost($item, 70);
			if (!empty($content['text'])) {
				$title = '"' . trim(str_replace("\n", " ", $content['text'])) . '"';
			} else {
				$title = '';
			}

			switch ($Notification->verb) {
				case Activity::LIKE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $userL10n->t('%1$s liked your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $userL10n->t('%1$s liked your post %2$s');
							break;
					}
					break;
				case Activity::DISLIKE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $userL10n->t('%1$s disliked your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $userL10n->t('%1$s disliked your post %2$s');
							break;
					}
					break;
				case Activity::ANNOUNCE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $userL10n->t('%1$s shared your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $userL10n->t('%1$s shared your post %2$s');
							break;
					}
					break;
				case Activity::POST:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_EXPLICIT_TAGGED:
							$msg = $userL10n->t('%1$s tagged you on %2$s');
							break;

						case Post\UserNotification::TYPE_IMPLICIT_TAGGED:
							$msg = $userL10n->t('%1$s replied to you on %2$s');
							break;

						case Post\UserNotification::TYPE_THREAD_COMMENT:
							$msg = $userL10n->t('%1$s commented in your thread %2$s');
							break;

						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $userL10n->t('%1$s commented on your comment %2$s');
							break;

						case Post\UserNotification::TYPE_COMMENT_PARTICIPATION:
						case Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION:
							if (($causer['id'] == $author['id']) && ($title != '')) {
								$msg = $userL10n->t('%1$s commented in their thread %2$s');
							} elseif ($causer['id'] == $author['id']) {
								$msg = $userL10n->t('%1$s commented in their thread');
							} elseif ($title != '') {
								$msg = $userL10n->t('%1$s commented in the thread %2$s from %3$s');
							} else {
								$msg = $userL10n->t('%1$s commented in the thread from %3$s');
							}
							break;

						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $userL10n->t('%1$s commented on your thread %2$s');
							break;

						case Post\UserNotification::TYPE_SHARED:
							if (($causer['id'] != $author['id']) && ($title != '')) {
								$msg = $userL10n->t('%1$s shared the post %2$s from %3$s');
							} elseif ($causer['id'] != $author['id']) {
								$msg = $userL10n->t('%1$s shared a post from %3$s');
							} elseif ($title != '') {
								$msg = $userL10n->t('%1$s shared the post %2$s');
							} else {
								$msg = $userL10n->t('%1$s shared a post');
							}
							break;
					}
					break;
			}
		}

		if (!empty($msg)) {
			// Name of the notification's causer
			$message['causer'] = $causer['name'];
			// Format for the "ping" mechanism
			$message['notification'] = sprintf($msg, '{0}', $title, $author['name']);
			// Plain text for the web push api
			$message['plain'] = sprintf($msg, $causer['name'], $title, $author['name']);
			// Rich text for other purposes
			$message['rich'] = sprintf($msg,
				'[url=' . $causer['url'] . ']' . $causer['name'] . '[/url]',
				'[url=' . $link . ']' . $title . '[/url]',
				'[url=' . $author['url'] . ']' . $author['name'] . '[/url]');
		}

		return $message;
	}
}
