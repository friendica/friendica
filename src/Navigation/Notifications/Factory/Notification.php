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

namespace Friendica\Navigation\Notifications\Factory;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Contact\LocalRelationship\Repository\LocalRelationship;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\L10n;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Psr\Log\LoggerInterface;

class Notification extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var LocalRelationship */
	private $localRelationshipRepo;
	/** @var ICanCache */
	private $cache;

	public function __construct(\Friendica\App\BaseURL $baseUrl, \Friendica\Core\L10n $l10n, \Friendica\Contact\LocalRelationship\Repository\LocalRelationship $localRelationshipRepo, LoggerInterface $logger, ICanCache $cache)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseUrl;
		$this->l10n = $l10n;
		$this->localRelationshipRepo = $localRelationshipRepo;
		$this->cache = $cache;
	}

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
			$row['dismissed'],
			$row['id'],
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

	/**
	 * @param int    $uid
	 * @param int    $contactId Public contact id
	 * @param string $verb
	 * @return Entity\Notification
	 */
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
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 */
	public function getMessageFromNotification(Entity\Notification $Notification): array
	{
		$message = [];

		$cachekey = 'Notification:' . $Notification->id;
		$result = $this->cache->get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$user = User::getById($Notification->uid, ['language']);
		$l10n = $this->l10n->withLang($user['language']);

		$causer = $author = Contact::getById($Notification->actorId, ['id', 'name', 'url', 'contact-type', 'pending']);
		if (empty($causer)) {
			$this->logger->info('Causer not found', ['contact' => $Notification->actorId]);
			return $message;
		}

		if ($Notification->type === Post\UserNotification::TYPE_NONE) {
			if ($causer['pending']) {
				$msg = $l10n->t('%1$s wants to follow you');
			} else {
				$msg = $l10n->t('%1$s has started following you');
			}

			$title = $causer['name'];
			$link  = $this->baseUrl . '/contact/' . $causer['id'];
		} else {
			if (!$Notification->targetUriId) {
				return $message;
			}

			if (Post\ThreadUser::getIgnored($Notification->parentUriId, $Notification->uid)) {
				$this->logger->info('Thread is ignored', ['parent-uri-id' => $Notification->parentUriId, 'type' => $Notification->type]);
				return $message;
			}

			if (in_array($Notification->type, [Post\UserNotification::TYPE_THREAD_COMMENT, Post\UserNotification::TYPE_COMMENT_PARTICIPATION, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION, Post\UserNotification::TYPE_FOLLOW, Post\UserNotification::TYPE_EXPLICIT_TAGGED])) {
				$item = Post::selectFirst([], ['uri-id' => $Notification->parentUriId, 'uid' => [0, $Notification->uid]], ['order' => ['uid' => true]]);
				if (empty($item)) {
					$this->logger->info('Parent post not found', ['uri-id' => $Notification->parentUriId]);
					return $message;
				}
				$link_item = Post::selectFirstPost(['guid'], ['uri-id' => $Notification->targetUriId]);
			} else {
				$link_item = $item = Post::selectFirst([], ['uri-id' => $Notification->targetUriId, 'uid' => [0, $Notification->uid]], ['order' => ['uid' => true]]);
				if (empty($item)) {
					$this->logger->info('Post not found', ['uri-id' => $Notification->targetUriId]);
					return $message;
				}

				if (($Notification->verb == Activity::POST) || ($Notification->type === Post\UserNotification::TYPE_SHARED)) {
					$thrparentid = $item['thr-parent-id'];
					$item = Post::selectFirst([], ['uri-id' => $thrparentid, 'uid' => [0, $Notification->uid]], ['order' => ['uid' => true]]);
					if (empty($item)) {
						$this->logger->info('Thread parent post not found', ['uri-id' => $thrparentid]);
						return $message;
					}
				}

				if (($Notification->verb != Activity::POST) || !in_array($Notification->type, [Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT, Post\UserNotification::TYPE_IMPLICIT_TAGGED])) {
					$link_item = $item;
				}
			}

			if (in_array($Notification->type, [Post\UserNotification::TYPE_COMMENT_PARTICIPATION, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION, Post\UserNotification::TYPE_FOLLOW, Post\UserNotification::TYPE_SHARED])) {
				$author = Contact::getById($item['author-id'], ['id', 'name', 'url', 'contact-type']);
				if (empty($author)) {
					$this->logger->info('Author not found', ['author' => $item['author-id']]);
					return $message;
				}
			}

			// Final check on $link_item
			// @see https://github.com/friendica/friendica/issues/11632#issuecomment-1183365937
			if (empty($link_item)) {
				$this->logger->info('Link item is still empty. Dumping whole Notification object:', [$Notification]);
				return $message;
			}

			$link = $this->baseUrl . '/display/' . urlencode($link_item['guid']);

			$body = BBCode::toPlaintext($item['body'], false);
			$title = Plaintext::shorten($body, 70);
			if (!empty($title)) {
				$title = '"' . trim(str_replace("\n", " ", $title)) . '"';
			}

			$this->logger->debug('Got verb and type', ['verb' => $Notification->verb, 'type' => $Notification->type, 'causer' => $causer['id'], 'author' => $author['id'], 'item' => $item['id'], 'uid' => $Notification->uid]);

			switch ($Notification->verb) {
				case Activity::LIKE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s liked your comment on %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s liked your post %2$s');
							break;
					}
					break;
				case Activity::DISLIKE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s disliked your comment on %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s disliked your post %2$s');
							break;
					}
					break;
				case Activity::ANNOUNCE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s shared your comment %2$s');
							break;
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s shared your post %2$s');
							break;
						case Post\UserNotification::TYPE_SHARED:
							if (($causer['id'] != $author['id']) && ($title != '')) {
								$msg = $l10n->t('%1$s shared the post %2$s from %3$s');
							} elseif ($causer['id'] != $author['id']) {
								$msg = $l10n->t('%1$s shared a post from %3$s');
							} elseif ($title != '') {
								$msg = $l10n->t('%1$s shared the post %2$s');
							} else {
								$msg = $l10n->t('%1$s shared a post');
							}
							break;
					}
					break;
				case Activity::ATTEND:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s wants to attend your event %2$s');
							break;
					}
					break;
				case Activity::ATTENDNO:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s does not want to attend your event %2$s');
							break;
					}
					break;
				case Activity::ATTENDMAYBE:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s maybe wants to attend your event %2$s');
							break;
					}
					break;
				case Activity::POST:
					switch ($Notification->type) {
						case Post\UserNotification::TYPE_EXPLICIT_TAGGED:
							$msg = $l10n->t('%1$s tagged you on %2$s');
							break;

						case Post\UserNotification::TYPE_IMPLICIT_TAGGED:
							$msg = $l10n->t('%1$s replied to you on %2$s');
							break;

						case Post\UserNotification::TYPE_THREAD_COMMENT:
							$msg = $l10n->t('%1$s commented in your thread %2$s');
							break;

						case Post\UserNotification::TYPE_DIRECT_COMMENT:
							$msg = $l10n->t('%1$s commented on your comment %2$s');
							break;

						case Post\UserNotification::TYPE_COMMENT_PARTICIPATION:
						case Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION:
						case Post\UserNotification::TYPE_FOLLOW;
							if (($causer['id'] == $author['id']) && ($title != '')) {
								$msg = $l10n->t('%1$s commented in their thread %2$s');
							} elseif ($causer['id'] == $author['id']) {
								$msg = $l10n->t('%1$s commented in their thread');
							} elseif ($title != '') {
								$msg = $l10n->t('%1$s commented in the thread %2$s from %3$s');
							} else {
								$msg = $l10n->t('%1$s commented in the thread from %3$s');
							}
							break;

						case Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
							$msg = $l10n->t('%1$s commented on your thread %2$s');
							break;

						case Post\UserNotification::TYPE_SHARED:
							if (($causer['id'] != $author['id']) && ($title != '')) {
								$msg = $l10n->t('%1$s shared the post %2$s from %3$s');
							} elseif ($causer['id'] != $author['id']) {
								$msg = $l10n->t('%1$s shared a post from %3$s');
							} elseif ($title != '') {
								$msg = $l10n->t('%1$s shared the post %2$s');
							} else {
								$msg = $l10n->t('%1$s shared a post');
							}
							break;

						case Post\UserNotification::TYPE_QUOTED:
							$msg = $l10n->t('%1$s shared your post %2$s');
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
			$message['link'] = $link;
			$this->cache->set($cachekey, $message, Duration::HOUR);
		} else {
			$this->logger->debug('Unhandled notification', ['notification' => $Notification]);
		}

		return $message;
	}
}
