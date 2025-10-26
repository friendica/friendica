<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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

	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		Nav::setSelected('introductions');

		$all = $this->args->get(2) == 'all';

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = $this->getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header']        ?? '';

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
						$knowyou     = '';
					}

					$convertedName = BBCode::toPlaintext($Introduction->getName(), false);

					$helptext  = $this->t('Accept %s as a friend or follower?', $convertedName);
					$helptext2 = $this->t('Allows them to follow your posts.', '<strong>' . $convertedName . '</strong>');
					$helptext3 = $this->t('You will also follow them and receive their posts.');
					$helptext4 = $this->t('You won\'t follow them and won\'t receive their posts.');

					$friend   = ['duplex', $this->t('Friend (Follow them back)'), '1', $helptext2 . '<br/>' . $helptext3, true];
					$follower = ['duplex', $this->t('Follower'), '0', $helptext2 . '<br/>' . $helptext4, false];

					$action = 'follow_confirm';

					$header = $Introduction->getName();

					if ($Introduction->getAddr() != '') {
						$header .= ' <' . $Introduction->getAddr() . '>';
					}

					$gsid = ContactSelector::getServerIdForProfile($Introduction->getUrl());
					$header .= ' (' . ContactSelector::networkToName($Introduction->getNetwork(), '', $gsid) . ')';

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
						'$network'               => ContactSelector::networkToName($Introduction->getNetwork(), '', $gsid),
						'$knowyou'               => $knowyou,
						'$approve'               => $this->t('Accept request'),
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
			$notificationNoContent = $this->t('No more %s notifications.', $notifications['ident']);
		}

		return $this->printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
