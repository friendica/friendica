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

namespace Friendica\Module\Notifications;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Contact\Introduction\Repository\Introduction;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\Notifications\Factory;
use Friendica\Navigation\Notifications\Repository;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Notification extends BaseModule
{
	/** @var Introduction */
	private $introductionRepo;
	/** @var Repository\Notification */
	private $notificationRepo;
	/** @var Repository\Notify */
	private $notifyRepo;
	/** @var IManagePersonalConfigValues */
	private $pconfig;
	/** @var Factory\Notification */
	private $notificationFactory;

	public function __construct(Introduction $introductionRepo, Repository\Notification $notificationRepo, Factory\Notification $notificationFactory, Repository\Notify $notifyRepo, IManagePersonalConfigValues $pconfig, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->introductionRepo    = $introductionRepo;
		$this->notificationRepo    = $notificationRepo;
		$this->notificationFactory = $notificationFactory;
		$this->notifyRepo          = $notifyRepo;
		$this->pconfig             = $pconfig;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\UnauthorizedException
	 * @throws \ImagickException
	 * @throws \Exception
	 */
	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException($this->l10n->t('Permission denied.'));
		}

		$request_id = $this->parameters['id'] ?? false;

		if ($request_id) {
			$intro = $this->introductionRepo->selectOneById($request_id, DI::userSession()->getLocalUserId());

			switch ($_POST['submit']) {
				case $this->l10n->t('Discard'):
					Contact\Introduction::discard($intro);
					$this->introductionRepo->delete($intro);
					break;
				case $this->l10n->t('Ignore'):
					$intro->ignore();
					$this->introductionRepo->save($intro);
					break;
			}

			$this->baseUrl->redirect('notifications/intros');
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws HTTPException\UnauthorizedException
	 */
	protected function rawContent(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException($this->l10n->t('Permission denied.'));
		}

		if ($this->args->get(1) === 'mark' && $this->args->get(2) === 'all') {
			try {
				$this->notificationRepo->setAllSeenForUser(DI::userSession()->getLocalUserId());
				$success = $this->notifyRepo->setAllSeenForUser(DI::userSession()->getLocalUserId());
			} catch (\Exception $e) {
				$this->logger->warning('set all seen failed.', ['exception' => $e]);
				$success = false;
			}

			System::jsonExit(['result' => (($success) ? 'success' : 'fail')]);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * Redirect to the notifications main page or to the url for the chosen notifications
	 *
	 * @throws HTTPException\NotFoundException In case the notification is either not existing or is not for this user
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \Exception
	 */
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice($this->l10n->t('You must be logged in to show this page.'));
			return Login::form();
		}

		if (isset($this->parameters['notify_id'])) {
			$this->handleNotify($this->parameters['notify_id']);
		} elseif (isset($this->parameters['id'])) {
			$this->handleNotification($this->parameters['id']);
		}

		$this->baseUrl->redirect('notifications/system');

		return '';
	}

	private function handleNotify(int $notifyId)
	{
		$Notify = $this->notifyRepo->selectOneById($notifyId);
		if ($Notify->uid !== DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException();
		}

		if ($this->pconfig->get(DI::userSession()->getLocalUserId(), 'system', 'detailed_notif')) {
			$Notify->setSeen();
			$this->notifyRepo->save($Notify);
		} else {
			if ($Notify->uriId) {
				$this->notificationRepo->setAllSeenForUser($Notify->uid, ['target-uri-id' => $Notify->uriId]);
			}

			$this->notifyRepo->setAllSeenForRelatedNotify($Notify);
		}

		if ((string)$Notify->link) {
			System::externalRedirect($Notify->link);
		}

		$this->baseUrl->redirect();
	}

	private function handleNotification(int $notificationId)
	{
		$Notification = $this->notificationRepo->selectOneById($notificationId);
		if ($Notification->uid !== DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException();
		}

		if ($this->pconfig->get(DI::userSession()->getLocalUserId(), 'system', 'detailed_notif')) {
			$Notification->setSeen();
			$this->notificationRepo->save($Notification);
		} else {
			if ($Notification->parentUriId) {
				$this->notificationRepo->setAllSeenForUser($Notification->uid, ['parent-uri-id' => $Notification->parentUriId]);
			} else {
				$Notification->setSeen();
				$this->notificationRepo->save($Notification);
			}
		}

		$message = $this->notificationFactory->getMessageFromNotification($Notification);

		if ($message['link']) {
			System::externalRedirect($message['link']);
		}

		$this->baseUrl->redirect();
	}
}
