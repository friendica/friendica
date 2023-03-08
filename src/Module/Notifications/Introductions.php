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
use Friendica\App\Arguments;
use Friendica\App\Mode;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseNotifications;
use Friendica\Module\Response;
use Friendica\Navigation\Notifications\Factory\Introduction as IntroductionFactory;
use Friendica\Navigation\Notifications\ValueObject\Introduction;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Prints notifications about introduction
 */
class Introductions extends BaseNotifications
{
	/** @var IntroductionFactory */
	protected $notificationIntro;
	/** @var Mode */
	protected $mode;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Mode $mode, IntroductionFactory $notificationIntro, IHandleUserSessions $userSession, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $userSession, $server, $parameters);

		$this->notificationIntro = $notificationIntro;
		$this->mode              = $mode;
	}

	/**
	 * @inheritDoc
	 */
	public function getNotifications()
	{
		$id  = (int)$this->args->get(2, 0);
		$all = $this->args->get(2) == 'all';

		$notifications = [
			'ident'         => 'introductions',
			'notifications' => $this->notificationIntro->getList($all, $this->firstItemNum, self::ITEMS_PER_PAGE, $id),
		];

		return [
			'header'        => $this->t('Notifications'),
			'notifications' => $notifications,
		];
	}

	protected function content(array $request = []): string
	{
		Nav::setSelected('introductions');

		$all = $this->args->get(2) == 'all';

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = $this->getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header'] ?? '';

		$notificationSuggestions = Renderer::getMarkupTemplate('notifications/suggestions.tpl');
		$notificationTemplate    = Renderer::getMarkupTemplate('notifications/intros.tpl');

		// The link to switch between ignored and normal connection requests
		$notificationShowLink = [
			'href' => (!$all ? 'notifications/intros/all' : 'notifications/intros'),
			'text' => (!$all ? $this->t('Show Ignored Requests') : $this->t('Hide Ignored Requests')),
		];

		$owner = User::getOwnerDataById(DI::userSession()->getLocalUserId());

		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		/** @var Introduction $Introduction */
		foreach ($notifications['notifications'] as $Introduction) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($Introduction->getLabel()) {
				case 'friend_suggestion':
					$notificationContent[] = Renderer::replaceMacros($notificationSuggestions, [
						'$type'                  => $Introduction->getLabel(),
						'$str_notification_type' => $this->t('Notification type:'),
						'$str_type'              => $Introduction->getType(),
						'$intro_id'              => $Introduction->getIntroId(),
						'$lbl_madeby'            => $this->t('Suggested by:'),
						'$madeby'                => $Introduction->getMadeBy(),
						'$madeby_url'            => $Introduction->getMadeByUrl(),
						'$madeby_zrl'            => $Introduction->getMadeByZrl(),
						'$madeby_addr'           => $Introduction->getMadeByAddr(),
						'$contact_id'            => $Introduction->getContactId(),
						'$photo'                 => $Introduction->getPhoto(),
						'$fullname'              => $Introduction->getName(),
						'$dfrn_url'              => $owner['url'],
						'$url'                   => $Introduction->getUrl(),
						'$zrl'                   => $Introduction->getZrl(),
						'$lbl_url'               => $this->t('Profile URL'),
						'$addr'                  => $Introduction->getAddr(),
						'$action'                => 'contact/follow',
						'$approve'               => $this->t('Approve'),
						'$note'                  => $Introduction->getNote(),
						'$ignore'                => $this->t('Ignore'),
						'$discard'               => $this->t('Discard'),
						'$is_mobile'             => $this->mode->isMobile(),
					]);
					break;

				// Normal connection requests
				default:
					if ($Introduction->getNetwork() === Protocol::DFRN) {
						$lbl_knowyou = $this->t('Claims to be known to you: ');
						$knowyou     = ($Introduction->getKnowYou() ? $this->t('Yes') : $this->t('No'));
					} else {
						$lbl_knowyou = '';
						$knowyou = '';
					}

					$convertedName = BBCode::convert($Introduction->getName());

					$helptext  = $this->t('Shall your connection be bidirectional or not?');
					$helptext2 = $this->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $convertedName, $convertedName);
					$helptext3 = $this->t('Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $convertedName);

					$friend = ['duplex', $this->t('Friend'), '1', $helptext2, true];
					$follower = ['duplex', $this->t('Subscriber'), '0', $helptext3, false];

					$action = 'follow_confirm';

					$header = $Introduction->getName();

					if ($Introduction->getAddr() != '') {
						$header .= ' <' . $Introduction->getAddr() . '>';
					}

					$header .= ' (' . ContactSelector::networkToName($Introduction->getNetwork(), $Introduction->getUrl()) . ')';

					if ($Introduction->getNetwork() != Protocol::DIASPORA) {
						$discard = $this->t('Discard');
					} else {
						$discard = '';
					}

					$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
						'$type'                  => $Introduction->getLabel(),
						'$header'                => $header,
						'$str_notification_type' => $this->t('Notification type:'),
						'$str_type'              => $Introduction->getType(),
						'$dfrn_id'               => $Introduction->getDfrnId(),
						'$uid'                   => $Introduction->getUid(),
						'$intro_id'              => $Introduction->getIntroId(),
						'$contact_id'            => $Introduction->getContactId(),
						'$photo'                 => $Introduction->getPhoto(),
						'$fullname'              => $Introduction->getName(),
						'$location'              => $Introduction->getLocation(),
						'$lbl_location'          => $this->t('Location:'),
						'$about'                 => $Introduction->getAbout(),
						'$lbl_about'             => $this->t('About:'),
						'$keywords'              => $Introduction->getKeywords(),
						'$lbl_keywords'          => $this->t('Tags:'),
						'$hidden'                => ['hidden', $this->t('Hide this contact from others'), $Introduction->isHidden(), ''],
						'$lbl_connection_type'   => $helptext,
						'$friend'                => $friend,
						'$follower'              => $follower,
						'$url'                   => $Introduction->getUrl(),
						'$zrl'                   => $Introduction->getZrl(),
						'$lbl_url'               => $this->t('Profile URL'),
						'$addr'                  => $Introduction->getAddr(),
						'$lbl_knowyou'           => $lbl_knowyou,
						'$lbl_network'           => $this->t('Network:'),
						'$network'               => ContactSelector::networkToName($Introduction->getNetwork(), $Introduction->getUrl()),
						'$knowyou'               => $knowyou,
						'$approve'               => $this->t('Approve'),
						'$note'                  => $Introduction->getNote(),
						'$ignore'                => $this->t('Ignore'),
						'$discard'               => $discard,
						'$action'                => $action,
						'$is_mobile'             => $this->mode->isMobile(),
					]);
					break;
			}
		}

		if (count($notifications['notifications']) == 0) {
			DI::sysmsg()->addNotice($this->t('No introductions.'));
			$notificationNoContent = $this->t('No more %s notifications.', $notifications['ident']);
		}

		return $this->printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
