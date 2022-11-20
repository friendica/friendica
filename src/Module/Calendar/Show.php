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

namespace Friendica\Module\Calendar;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Theme;
use Friendica\Model\Event;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Show extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var App\Page */
	protected $page;
	/** @var App */
	protected $app;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, App\Page $page, App $app, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->sysMessages = $sysMessages;
		$this->page        = $page;
		$this->app         = $app;
	}

	protected function content(array $request = []): string
	{
		// check if the user is authenticated or the calendar is public
		$calenderOwner= User::getOwnerDataByNick(!empty($this->parameters['nickname']) ? $this->parameters['nickname'] : '') ;
		if (!$this->session->getLocalUserId() && !Feature::isEnabled($calenderOwner['uid'], 'public_calendar')) {
			$this->sysMessages->addNotice($this->t('Permission denied.'));
			return Login::form();
		}

		// get the translation strings for the calendar
		$i18n = Event::getStrings();

		$this->page->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.min.css');
		$this->page->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.print.min.css', 'print');
		$this->page->registerFooterScript('view/asset/moment/min/moment-with-locales.min.js');
		$this->page->registerFooterScript('view/asset/fullcalendar/dist/fullcalendar.min.js');

		$htpl = Renderer::getMarkupTemplate('calendar/calendar_head.tpl');

		$this->page['htmlhead'] .= Renderer::replaceMacros($htpl, [
			'$calendar_api' => 'calendar/api/get' . (!empty($this->parameters['nickname']) ? '/' . $this->parameters['nickname'] : ''),
			'$event_api'    => 'calendar/event/show' . (!empty($this->parameters['nickname']) ? '/' . $this->parameters['nickname'] : ''),
			'$modparams'    => 2,
			'$i18n'         => $i18n,
		]);

		$tabs = '';

		if (empty($this->parameters['nickname'])) {
			if ($this->app->getThemeInfoValue('events_in_profile')) {
				Nav::setSelected('home');
			} else {
				Nav::setSelected('calendar');
			}

			// tabs
			if ($this->app->getThemeInfoValue('events_in_profile')) {
				$tabs = BaseProfile::getTabsHTML($this->app, 'calendar', true, $this->app->getLoggedInUserNickname(), false);
			}

			$this->page['aside'] .= Widget\CalendarExport::getHTML($this->session->getLocalUserId());
		} else {
			$owner = Event::getOwnerForNickname($this->parameters['nickname'], true);

			Nav::setSelected('calendar');

			// get the tab navigation bar
			$tabs = BaseProfile::getTabsHTML($this->app, 'calendar', false, $owner['nickname'], $owner['hide-friends']);

			$this->page['aside'] .= Widget\VCard::getHTML($owner);
			$this->page['aside'] .= Widget\CalendarExport::getHTML($owner['uid']);
		}

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
		]);

		return $o;
	}
}
