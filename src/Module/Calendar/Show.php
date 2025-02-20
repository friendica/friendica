<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Calendar;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Theme;
use Friendica\Model\Event;
use Friendica\Model\Profile;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Show extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var Page */
	protected $page;
	/** @var AppHelper */
	protected $appHelper;

	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, Page $page, AppHelper $appHelper, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->sysMessages = $sysMessages;
		$this->page        = $page;
		$this->appHelper   = $appHelper;
	}

	protected function content(array $request = []): string
	{
		$nickname = $this->parameters['nickname'] ?? $this->session->getLocalUserNickname();
		if (!$nickname) {
			throw new HTTPException\UnauthorizedException();
		}

		$owner = Profile::load($this->appHelper, $nickname, false);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if (!$this->session->isAuthenticated() && $owner['hidewall']) {
			$this->baseUrl->redirect('profile/' . $nickname . '/restricted');
		}

		if (!$this->session->isAuthenticated() && !Feature::isEnabled($owner['uid'], Feature::PUBLIC_CALENDAR)) {
			$this->sysMessages->addNotice($this->t('Permission denied.'));
			return Login::form();
		}

		// get the translation strings for the calendar
		$i18n = Event::getStrings();

		$this->page->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.min.css');
		$this->page->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.print.min.css', 'print');
		$this->page->registerFooterScript('view/asset/moment/min/moment-with-locales.min.js');
		$this->page->registerFooterScript('view/asset/fullcalendar/dist/fullcalendar.min.js');

		$is_owner = $nickname == $this->session->getLocalUserNickname();

		$htpl = Renderer::getMarkupTemplate('calendar/calendar_head.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($htpl, [
			'$calendar_api' => 'calendar/api/get' . ($is_owner ? '' : '/' . $nickname),
			'$event_api'    => 'calendar/event/show' . ($is_owner ? '' : '/' . $nickname),
			'$modparams'    => 2,
			'$i18n'         => $i18n,
		]);

		Nav::setSelected($is_owner ? 'home' : 'calendar');

		if ($is_owner) {
			// Removing the vCard added by Profile::load for owners
			$this->page['aside'] = '';
		}

		$this->page['aside'] .= Widget\CalendarExport::getHTML($owner['uid']);

		$tabs = BaseProfile::getTabsHTML('calendar', $is_owner, $nickname, !$is_owner && $owner['hide-friends']);

		// ACL blocks are loaded in modals in frio
		$this->page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$tpl = Renderer::getMarkupTemplate("calendar/calendar.tpl");
		$o   = Renderer::replaceMacros($tpl, [
			'$tabs'      => $tabs,
			'$title'     => $this->t('Events'),
			'$view'      => $this->t('View'),
			'$new_event' => ['calendar/event/new', $this->t('Create New Event'), '', ''],

			'$today' => $this->t('today'),
			'$month' => $this->t('month'),
			'$week'  => $this->t('week'),
			'$day'   => $this->t('day'),
			'$list'  => $this->t('list'),
			'$prev'  => $this->t('prev'),
			'$next'  => $this->t('next'),
		]);

		return $o;
	}
}
