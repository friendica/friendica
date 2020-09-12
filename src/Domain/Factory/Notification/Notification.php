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

namespace Friendica\Domain\Factory\Notification;

use Exception;
use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Domain\BaseFactory;
use Friendica\Domain\Collection\Api\Notifications as ApiNotifications;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\IPConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Session\ISession;
use Friendica\Database\Database;
use Friendica\Domain\Repository\Notify;
use Friendica\Model\Item;
use Friendica\Module\BaseNotifications;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Domain\Entity\Api\Friendica\Notification as ApiNotification;
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
class Notification extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var Notify */
	private $notification;
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var string */
	private $nurl;

	public function __construct(LoggerInterface $logger, Database $dba, Notify $notification, BaseURL $baseUrl, L10n $l10n, App $app, IPConfig $pConfig, ISession $session)
	{
		parent::__construct($logger);

		$this->dba          = $dba;
		$this->notification = $notification;
		$this->baseUrl      = $baseUrl;
		$this->l10n         = $l10n;
		$this->nurl         = $app->contact['nurl'] ?? '';
	}

	/**
	 * Format the item query in an usable array
	 *
	 * @param array $item The item from the db query
	 *
	 * @return array The item, extended with the notification-specific information
	 *
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	private function formatItem(array $item)
	{
		$item['seen'] = ($item['unseen'] > 0 ? false : true);

		// For feed items we use the user's contact, since the avatar is mostly self choosen.
		if (!empty($item['network']) && $item['network'] == Protocol::FEED) {
			$item['author-avatar'] = $item['contact-avatar'];
		}

		$item['label'] = (($item['gravity'] == GRAVITY_PARENT) ? 'post' : 'comment');
		$item['link']  = $this->baseUrl->get(true) . '/display/' . $item['parent-guid'];
		$item['image'] = $item['author-avatar'];
		$item['url']   = $item['author-link'];
		$item['text']  = (($item['gravity'] == GRAVITY_PARENT)
			? $this->l10n->t("%s created a new post", $item['author-name'])
			: $this->l10n->t("%s commented on %s's post", $item['author-name'], $item['parent-author-name']));
		$item['when']  = DateTimeFormat::local($item['created'], 'r');
		$item['ago']   = Temporal::getRelativeDate($item['created']);

		return $item;
	}

	/**
	 * @param array $item
	 *
	 * @return \Friendica\Object\Notification\Notification
	 *
	 * @throws InternalServerErrorException
	 */
	private function createFromItem(array $item)
	{
		$item = $this->formatItem($item);

		// Transform the different types of notification in an usable array
		switch ($item['verb'] ?? '') {
			case Activity::LIKE:
				return new \Friendica\Object\Notification\Notification([
					'label' => 'like',
					'link'  => $this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					'image' => $item['author-avatar'],
					'url'   => $item['author-link'],
					'text'  => $this->l10n->t("%s liked %s's post", $item['author-name'], $item['parent-author-name']),
					'when'  => $item['when'],
					'ago'   => $item['ago'],
					'seen'  => $item['seen']]);

			case Activity::DISLIKE:
				return new \Friendica\Object\Notification\Notification([
					'label' => 'dislike',
					'link'  => $this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					'image' => $item['author-avatar'],
					'url'   => $item['author-link'],
					'text'  => $this->l10n->t("%s disliked %s's post", $item['author-name'], $item['parent-author-name']),
					'when'  => $item['when'],
					'ago'   => $item['ago'],
					'seen'  => $item['seen']]);

			case Activity::ATTEND:
				return new \Friendica\Object\Notification\Notification([
					'label' => 'attend',
					'link'  => $this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					'image' => $item['author-avatar'],
					'url'   => $item['author-link'],
					'text'  => $this->l10n->t("%s is attending %s's event", $item['author-name'], $item['parent-author-name']),
					'when'  => $item['when'],
					'ago'   => $item['ago'],
					'seen'  => $item['seen']]);

			case Activity::ATTENDNO:
				return new \Friendica\Object\Notification\Notification([
					'label' => 'attendno',
					'link'  => $this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					'image' => $item['author-avatar'],
					'url'   => $item['author-link'],
					'text'  => $this->l10n->t("%s is not attending %s's event", $item['author-name'], $item['parent-author-name']),
					'when'  => $item['when'],
					'ago'   => $item['ago'],
					'seen'  => $item['seen']]);

			case Activity::ATTENDMAYBE:
				return new \Friendica\Object\Notification\Notification([
					'label' => 'attendmaybe',
					'link'  => $this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					'image' => $item['author-avatar'],
					'url'   => $item['author-link'],
					'text'  => $this->l10n->t("%s may attending %s's event", $item['author-name'], $item['parent-author-name']),
					'when'  => $item['when'],
					'ago'   => $item['ago'],
					'seen'  => $item['seen']]);

			case Activity::FRIEND:
				if (!isset($item['object'])) {
					return new \Friendica\Object\Notification\Notification([
						'label' => 'friend',
						'link'  => $item['link'],
						'image' => $item['image'],
						'url'   => $item['url'],
						'text'  => $item['text'],
						'when'  => $item['when'],
						'ago'   => $item['ago'],
						'seen'  => $item['seen']]);
				}

				$xmlHead       = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
				$obj           = XML::parseString($xmlHead . $item['object']);
				$item['fname'] = $obj->title;

				return new \Friendica\Object\Notification\Notification([
					'label' => 'friend',
					'link'  => $this->baseUrl->get(true) . '/display/' . $item['parent-guid'],
					'image' => $item['author-avatar'],
					'url'   => $item['author-link'],
					'text'  => $this->l10n->t("%s is now friends with %s", $item['author-name'], $item['fname']),
					'when'  => $item['when'],
					'ago'   => $item['ago'],
					'seen'  => $item['seen']]);

			default:
				return new \Friendica\Object\Notification\Notification($item);
				break;
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
	 * @return \Friendica\Module\Notifications\Notification[]
	 */
	public function getSystemList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$conditions = ['uid' => local_user()];

		if (!$seen) {
			$conditions['seen'] = false;
		}

		$params          = [];
		$params['order'] = ['date' => 'DESC'];
		$params['limit'] = [$start, $limit];

		$formattedNotifications = [];
		try {
			$notifications = $this->notification->select($conditions, $params);

			foreach ($notifications as $notification) {
				$formattedNotifications[] = new \Friendica\Object\Notification\Notification([
					'label' => 'notification',
					'link'  => $this->baseUrl->get(true) . '/notification/' . $notification->id,
					'image' => Proxy::proxifyUrl($notification->photo, false, Proxy::SIZE_MICRO),
					'url'   => $notification->url,
					'text'  => strip_tags(BBCode::convert($notification->msg)),
					'when'  => DateTimeFormat::local($notification->date, 'r'),
					'ago'   => Temporal::getRelativeDate($notification->date),
					'seen'  => $notification->seen]);
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
	 * @return \Friendica\Object\Notification\Notification[]
	 */
	public function getNetworkList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$conditions = ['wall' => false, 'uid' => local_user()];

		if (!$seen) {
			$conditions['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid', 'gravity'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = [];

		try {
			$items = Item::selectForUser(local_user(), $fields, $conditions, $params);

			while ($item = $this->dba->fetch($items)) {
				$formattedNotifications[] = $this->createFromItem($item);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $conditions, 'exception' => $e]);
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
	 * @return \Friendica\Object\Notification\Notification[]
	 */
	public function getPersonalList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$myUrl    = str_replace('http://', '', $this->nurl);
		$diaspUrl = str_replace('/profile/', '/u/', $myUrl);

		$condition = ["NOT `wall` AND `uid` = ? AND (`item`.`author-id` = ? OR `item`.`tag` REGEXP ? OR `item`.`tag` REGEXP ?)",
			local_user(), public_contact(), $myUrl . '\\]', $diaspUrl . '\\]'];

		if (!$seen) {
			$condition[0] .= " AND `unseen`";
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid', 'gravity'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = [];

		try {
			$items = Item::selectForUser(local_user(), $fields, $condition, $params);

			while ($item = $this->dba->fetch($items)) {
				$formattedNotifications[] = $this->createFromItem($item);
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
	 * @return \Friendica\Object\Notification\Notification[]
	 */
	public function getHomeList(bool $seen = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT)
	{
		$condition = ['wall' => true, 'uid' => local_user()];

		if (!$seen) {
			$condition['unseen'] = true;
		}

		$fields = ['id', 'parent', 'verb', 'author-name', 'unseen', 'author-link', 'author-avatar', 'contact-avatar',
			'network', 'created', 'object', 'parent-author-name', 'parent-author-link', 'parent-guid', 'gravity'];
		$params = ['order' => ['received' => true], 'limit' => [$start, $limit]];

		$formattedNotifications = [];

		try {
			$items = Item::selectForUser(local_user(), $fields, $condition, $params);

			while ($item = $this->dba->fetch($items)) {
				$item = $this->formatItem($item);

				// Overwrite specific fields, not default item format
				$item['label'] = 'comment';
				$item['text']  = $this->l10n->t("%s commented on %s's post", $item['author-name'], $item['parent-author-name']);

				$formattedNotifications[] = $this->createFromItem($item);
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['conditions' => $condition, 'exception' => $e]);
		}

		return $formattedNotifications;
	}

	/**
	 * @param int   $uid    The user id of the API call
	 * @param array $params Additional parameters
	 *
	 * @return ApiNotifications
	 *
	 * @throws Exception
	 */
	public function getApiList(int $uid, array $params = ['order' => ['seen' => 'ASC', 'date' => 'DESC'], 'limit' => 50])
	{
		$notifies = $this->notification->select(['uid' => $uid], $params);

		/** @var ApiNotification[] $notifications */
		$notifications = [];

		foreach ($notifies as $notify) {
			$notifications[] = new ApiNotification($notify);
		}

		return new ApiNotifications($notifications);
	}
}
