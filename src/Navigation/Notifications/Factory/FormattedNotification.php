<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license   GNU AGPL version 3 or any later version
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

use Exception;
use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Module\BaseNotifications;
use Friendica\Navigation\Notifications\Collection\FormattedNotifications;
use Friendica\Navigation\Notifications\Repository;
use Friendica\Navigation\Notifications\ValueObject;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;
use Friendica\Util\Temporal;
use Friendica\Util\XML;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating notification objects based on items
 * Currently, there are the following types of item based notifications:
 * - network
 * - system
 * - home
 * - personal
 */
class FormattedNotification extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var Repository\Notify */
	private $notify;
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;

	public function __construct(LoggerInterface $logger, Database $dba, Repository\Notify $notify, BaseURL $baseUrl, L10n $l10n)
	{
		parent::__construct($logger);

		$this->dba     = $dba;
		$this->notify  = $notify;
		$this->baseUrl = $baseUrl;
		$this->l10n    = $l10n;
	}

	/**
	 * @param array $formattedItem The return of $this->formatItem
	 *
	 * @return ValueObject\FormattedNotification
	 */
	private function createFromFormattedItem(array $formattedItem): ValueObject\FormattedNotification
	{
		// Transform the different types of notification in a usable array
		switch ($formattedItem['verb'] ?? '') {
			case Activity::LIKE:
				return new ValueObject\FormattedNotification(
					'like',
					$this->baseUrl->get(true) . '/display/' . $formattedItem['parent-guid'],
					$formattedItem['author-avatar'],
					$formattedItem['author-link'],
					$this->l10n->t("%s liked %s's post", $formattedItem['author-name'], $formattedItem['parent-author-name']),
					$formattedItem['when'],
					$formattedItem['ago'],
					$formattedItem['seen']
				);

			case Activity::DISLIKE:
				return new ValueObject\FormattedNotification(
					'dislike',
					$this->baseUrl->get(true) . '/display/' . $formattedItem['parent-guid'],
					$formattedItem['author-avatar'],
					$formattedItem['author-link'],
					$this->l10n->t("%s disliked %s's post", $formattedItem['author-name'], $formattedItem['parent-author-name']),
					$formattedItem['when'],
					$formattedItem['ago'],
					$formattedItem['seen']
				);

			case Activity::ATTEND:
				return new ValueObject\FormattedNotification(
					'attend',
					$this->baseUrl->get(true) . '/display/' . $formattedItem['parent-guid'],
					$formattedItem['author-avatar'],
					$formattedItem['author-link'],
					$this->l10n->t("%s is attending %s's event", $formattedItem['author-name'], $formattedItem['parent-author-name']),
					$formattedItem['when'],
					$formattedItem['ago'],
					$formattedItem['seen']
				);

			case Activity::ATTENDNO:
				return new ValueObject\FormattedNotification(
					'attendno',
					$this->baseUrl->get(true) . '/display/' . $formattedItem['parent-guid'],
					$formattedItem['author-avatar'],
					$formattedItem['author-link'],
					$this->l10n->t("%s is not attending %s's event", $formattedItem['author-name'], $formattedItem['parent-author-name']),
					$formattedItem['when'],
					$formattedItem['ago'],
					$formattedItem['seen']
				);

			case Activity::ATTENDMAYBE:
				return new ValueObject\FormattedNotification(
					'attendmaybe',
					$this->baseUrl->get(true) . '/display/' . $formattedItem['parent-guid'],
					$formattedItem['author-avatar'],
					$formattedItem['author-link'],
					$this->l10n->t("%s may attending %s's event", $formattedItem['author-name'], $formattedItem['parent-author-name']),
					$formattedItem['when'],
					$formattedItem['ago'],
					$formattedItem['seen']
				);

			case Activity::FRIEND:
				if (!isset($formattedItem['object'])) {
					return new ValueObject\FormattedNotification(
						'friend',
						$formattedItem['link'],
						$formattedItem['image'],
						$formattedItem['url'],
						$formattedItem['text'],
						$formattedItem['when'],
						$formattedItem['ago'],
						$formattedItem['seen']
					);
				}

				$xmlHead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
				$obj     = XML::parseString($xmlHead . $formattedItem['object']);

				$formattedItem['fname'] = $obj->title;

				return new ValueObject\FormattedNotification(
					'friend',
					$this->baseUrl->get(true) . '/display/' . $formattedItem['parent-guid'],
					$formattedItem['author-avatar'],
					$formattedItem['author-link'],
					$this->l10n->t("%s is now friends with %s", $formattedItem['author-name'], $formattedItem['fname']),
					$formattedItem['when'],
					$formattedItem['ago'],
					$formattedItem['seen']
				);

			default:
				return new ValueObject\FormattedNotification(
					$formattedItem['label'] ?? '',
					$formattedItem['link'] ?? '',
					$formattedItem['image'] ?? '',
					$formattedItem['url'] ?? '',
					$formattedItem['text'] ?? '',
					$formattedItem['when'] ?? '',
					$formattedItem['ago'] ?? '',
					$formattedItem['seen'] ?? false
				);
		}
	}

	/**
	 * Get system notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return FormattedNotifications
	 */
	public function getSystemList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT): FormattedNotifications
	{
		$conditions = [];
		if (!$seen) {
			$conditions['seen'] = false;
		}

		$params          = [];
		$params['order'] = ['date' => 'DESC'];
		$params['limit'] = [$start, $limit];

		$formattedNotifications = new FormattedNotifications();
		try {
			$Notifies = $this->notify->selectForUser(local_user(), $conditions, $params);

			foreach ($Notifies as $Notify) {
				$formattedNotifications[] = new ValueObject\FormattedNotification(
					'notification',
					$this->baseUrl->get(true) . '/notification/' . $Notify->id,
					Contact::getAvatarUrlForUrl($Notify->url, $Notify->uid, Proxy::SIZE_MICRO),
					$Notify->url,
					strip_tags(BBCode::toPlaintext($Notify->msg)),
					DateTimeFormat::local($Notify->date->format(DateTimeFormat::MYSQL), 'r'),
					Temporal::getRelativeDate($Notify->date->format(DateTimeFormat::MYSQL)),
					$Notify->seen
				);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $conditions, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Get network notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return FormattedNotifications
	 */
	public function getNetworkList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT): FormattedNotifications
	{
		$condition = ['wall' => false, 'uid' => local_user()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid', 'gravity'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = new FormattedNotifications();

		try {
			$userPosts = Post::selectForUser(local_user(), $fields, $condition, $params);
			while ($userPost = $this->dba->fetch($userPosts)) {
				$formattedNotifications[] = $this->createFromFormattedItem($this->formatItem($userPost));
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['condition' => $condition, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Get personal notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return FormattedNotifications
	 */
	public function getPersonalList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT): FormattedNotifications
	{
		$condition = ['wall' => false, 'uid' => local_user(), 'author-id' => public_contact()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid', 'gravity'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = new FormattedNotifications();

		try {
			$userPosts = Post::selectForUser(local_user(), $fields, $condition, $params);
			while ($userPost = $this->dba->fetch($userPosts)) {
				$formattedNotifications[] = $this->createFromFormattedItem($this->formatItem($userPost));
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $condition, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Get home notifications
	 *
	 * @param bool $seen          False => only include notifications into the query
	 *                            which aren't marked as "seen"
	 * @param int  $start         Start the query at this point
	 * @param int  $limit         Maximum number of query results
	 *
	 * @return FormattedNotifications
	 */
	public function getHomeList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT): FormattedNotifications
	{
		$condition = ['wall' => true, 'uid' => local_user()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid', 'gravity'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = new FormattedNotifications();

		try {
			$userPosts = Post::selectForUser(local_user(), $fields, $condition, $params);
			while ($userPost = $this->dba->fetch($userPosts)) {
				$formattedItem = $this->formatItem($userPost);

				// Overwrite specific fields, not default item format
				$formattedItem['label'] = 'comment';
				$formattedItem['text']  = $this->l10n->t("%s commented on %s's post", $formattedItem['author-name'], $formattedItem['parent-author-name']);

				$formattedNotifications[] = $this->createFromFormattedItem($formattedItem);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $condition, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * Format the item query in a usable array
	 *
	 * @param array $item The item from the db query
	 *
	 * @return array The item, extended with the notification-specific information
	 *
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	private function formatItem(array $item): array
	{
		$item['seen'] = !($item['unseen'] > 0);

		// For feed items we use the user's contact, since the avatar is mostly self choosen.
		if (!empty($item['network']) && $item['network'] == Protocol::FEED) {
			$item['author-avatar'] = $item['contact-avatar'];
		}

		$item['label'] = (($item['gravity'] == GRAVITY_PARENT) ? 'post' : 'comment');
		$item['link']  = $this->baseUrl->get(true) . '/display/' . $item['parent-guid'];
		$item['image'] = $item['author-avatar'];
		$item['url']   = $item['author-link'];
		$item['when']  = DateTimeFormat::local($item['created'], 'r');
		$item['ago']   = Temporal::getRelativeDate($item['created']);
		$item['text']  = (($item['gravity'] == GRAVITY_PARENT)
			? $this->l10n->t("%s created a new post", $item['author-name'])
			: $this->l10n->t("%s commented on %s's post", $item['author-name'], $item['parent-author-name']));

		return $item;
	}
}
