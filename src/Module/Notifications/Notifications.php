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

namespace Friendica\Module\Notifications;

use Friendica\App;
use Friendica\App\Arguments;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Module\BaseNotifications;
use Friendica\Module\Response;
use Friendica\Navigation\Notifications\ValueObject\FormattedNotification;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Prints all notification types except introduction:
 * - Network
 * - System
 * - Personal
 * - Home
 */
class Notifications extends BaseNotifications
{
	/** @var \Friendica\Navigation\Notifications\Factory\FormattedNotification */
	protected $formattedNotificationFactory;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, \Friendica\Navigation\Notifications\Factory\FormattedNotification $formattedNotificationFactory, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->formattedNotificationFactory = $formattedNotificationFactory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getNotifications()
	{
		$notificationHeader = '';
		$notifications = [];

		$factory = $this->formattedNotificationFactory;

		if (($this->args->get(1) == 'network')) {
			$notificationHeader = $this->t('Network Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::NETWORK,
				'notifications' => $factory->getNetworkList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif (($this->args->get(1) == 'system')) {
			$notificationHeader = $this->t('System Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::SYSTEM,
				'notifications' => $factory->getSystemList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif (($this->args->get(1) == 'personal')) {
			$notificationHeader = $this->t('Personal Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::PERSONAL,
				'notifications' => $factory->getPersonalList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} elseif (($this->args->get(1) == 'home')) {
			$notificationHeader = $this->t('Home Notifications');
			$notifications      = [
				'ident'        => FormattedNotification::HOME,
				'notifications' => $factory->getHomeList($this->showAll, $this->firstItemNum, self::ITEMS_PER_PAGE),
			];
		} else {
			$this->baseUrl->redirect('notifications');
		}

		return [
			'header'        => $notificationHeader,
			'notifications' => $notifications,
		];
	}

	protected function content(array $request = []): string
	{
		Nav::setSelected('notifications');

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = $this->getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header'] ?? '';

		if (!empty($notifications['notifications'])) {
			$notificationTemplates = [
				'like'         => 'notifications/likes_item.tpl',
				'dislike'      => 'notifications/dislikes_item.tpl',
				'attend'       => 'notifications/attend_item.tpl',
				'attendno'     => 'notifications/attend_item.tpl',
				'attendmaybe'  => 'notifications/attend_item.tpl',
				'friend'       => 'notifications/friends_item.tpl',
				'comment'      => 'notifications/comments_item.tpl',
				'post'         => 'notifications/posts_item.tpl',
				'notification' => 'notifications/notification.tpl',
			];
			// Loop trough ever notification This creates an array with the output html for each
			// notification and apply the correct template according to the notificationtype (label).
			/** @var FormattedNotification $Notification */
			foreach ($notifications['notifications'] as $Notification) {
				$notificationArray = $Notification->toArray();

				$notificationTemplate = Renderer::getMarkupTemplate($notificationTemplates[$notificationArray['label']]);

				$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
					'$notification' => $notificationArray
				]);
			}
		} else {
			$notificationNoContent = $this->t('No more %s notifications.', $notificationResult['ident']);
		}

		$notificationShowLink = [
			'href' => ($this->showAll ? 'notifications/' . $notifications['ident'] : 'notifications/' . $notifications['ident'] . '?show=all'),
			'text' => ($this->showAll ? $this->t('Show unread') : $this->t('Show all')),
		];

		return $this->printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
