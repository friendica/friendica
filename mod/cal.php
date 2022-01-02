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
 * The calendar module
 *
 * This calendar is for profile visitors and contains only the events
 * of the profile owner
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

function cal_init(App $a)
{
	if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
		throw new HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
	}

	if (DI::args()->getArgc() < 2) {
		throw new HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
	}

	Nav::setSelected('events');

	// if it's a json request abort here becaus we don't
	// need the widget data
	if (!empty(DI::args()->getArgv()[2]) && (DI::args()->getArgv()[2] === 'json')) {
		return;
	}

	$owner = User::getOwnerDataByNick(DI::args()->getArgv()[1]);
	if (empty($owner)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
	}

	if (empty(DI::page()['aside'])) {
		DI::page()['aside'] = '';
	}

	DI::page()['aside'] .= Widget\VCard::getHTML($owner);
	DI::page()['aside'] .= Widget\CalendarExport::getHTML($owner['uid']);

	return;
}

function cal_content(App $a)
{
	$owner = User::getOwnerDataByNick(DI::args()->getArgv()[1]);
	if (empty($owner)) {
		throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
	}

	Nav::setSelected('events');

	// get the translation strings for the callendar
	$i18n = Event::getStrings();

	DI::page()->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.min.css');
	DI::page()->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.print.min.css', 'print');
	DI::page()->registerFooterScript('view/asset/moment/min/moment-with-locales.min.js');
	DI::page()->registerFooterScript('view/asset/fullcalendar/dist/fullcalendar.min.js');

	$htpl = Renderer::getMarkupTemplate('event_head.tpl');
	DI::page()['htmlhead'] .= Renderer::replaceMacros($htpl, [
		'$module_url' => '/cal/' . $owner['nickname'],
		'$modparams' => 2,
		'$i18n' => $i18n,
	]);

	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = (!empty($_REQUEST['ignored']) ? intval($_REQUEST['ignored']) : 0);

	$format = 'ical';
	if (DI::args()->getArgc() == 4 && DI::args()->getArgv()[2] == 'export') {
		$mode = 'export';
		$format = DI::args()->getArgv()[3];
	}

	// Setup permissions structures
	$owner_uid = intval($owner['uid']);
	$nick = $owner['nickname'];

	$contact_id = Session::getRemoteContactID($owner['uid']);

	$remote_contact = $contact_id && DBA::exists('contact', ['id' => $contact_id, 'uid' => $owner['uid']]);

	$is_owner = local_user() == $owner['uid'];

	if ($owner['hidewall'] && !$is_owner && !$remote_contact) {
		notice(DI::l10n()->t('Access to this profile has been restricted.'));
		return;
	}

	// get the permissions
	$sql_perms = Item::getPermissionsSQLByUserId($owner_uid);
	// we only want to have the events of the profile owner
	$sql_extra = " AND `event`.`cid` = 0 " . $sql_perms;

	// get the tab navigation bar
	$tabs = BaseProfile::getTabsHTML($a, 'cal', false, $owner['nickname'], $owner['hide-friends']);

	// The view mode part is similiar to /mod/events.php
	if ($mode == 'view') {
		$thisyear = DateTimeFormat::localNow('Y');
		$thismonth = DateTimeFormat::localNow('m');
		if (!$y) {
			$y = intval($thisyear);
		}

		if (!$m) {
			$m = intval($thismonth);
		}

		// Put some limits on dates. The PHP date functions don't seem to do so well before 1900.
		// An upper limit was chosen to keep search engines from exploring links millions of years in the future.

		if ($y < 1901) {
			$y = 1900;
		}

		if ($y > 2099) {
			$y = 2100;
		}

		$nextyear = $y;
		$nextmonth = $m + 1;
		if ($nextmonth > 12) {
			$nextmonth = 1;
			$nextyear ++;
		}

		$prevyear = $y;
		if ($m > 1) {
			$prevmonth = $m - 1;
		} else {
			$prevmonth = 12;
			$prevyear --;
		}

		$dim = Temporal::getDaysInMonth($y, $m);
		$start = sprintf('%d-%d-%d %d:%d:%d', $y, $m, 1, 0, 0, 0);
		$finish = sprintf('%d-%d-%d %d:%d:%d', $y, $m, $dim, 23, 59, 59);


		if (!empty(DI::args()->getArgv()[2]) && (DI::args()->getArgv()[2] === 'json')) {
			if (!empty($_GET['start'])) {
				$start = $_GET['start'];
			}

			if (!empty($_GET['end'])) {
				$finish = $_GET['end'];
			}
		}

		$start = DateTimeFormat::utc($start);
		$finish = DateTimeFormat::utc($finish);

		// put the event parametes in an array so we can better transmit them
		$event_params = [
			'event_id'      => intval($_GET['id'] ?? 0),
			'start'         => $start,
			'finish'        => $finish,
			'ignore'        => $ignored,
		];

		// get events by id or by date
		if ($event_params['event_id']) {
			$r = Event::getListById($owner_uid, $event_params['event_id'], $sql_extra);
		} else {
			$r = Event::getListByDate($owner_uid, $event_params, $sql_extra);
		}

		$links = [];

		if (DBA::isResult($r)) {
			$r = Event::sortByDate($r);
			foreach ($r as $rr) {
				$j = DateTimeFormat::local($rr['start'], 'j');
				if (empty($links[$j])) {
					$links[$j] = DI::baseUrl() . '/' . DI::args()->getCommand() . '#link-' . $j;
				}
			}
		}

		// transform the event in a usable array
		$events = Event::prepareListForTemplate($r);

		if (!empty(DI::args()->getArgv()[2]) && (DI::args()->getArgv()[2] === 'json')) {
			echo json_encode($events);
			exit();
		}

		// links: array('href', 'text', 'extra css classes', 'title')
		if (!empty($_GET['id'])) {
			$tpl = Renderer::getMarkupTemplate("event.tpl");
		} else {
			$tpl = Renderer::getMarkupTemplate("events_js.tpl");
		}

		// Get rid of dashes in key names, Smarty3 can't handle them
		foreach ($events as $key => $event) {
			$event_item = [];
			foreach ($event['item'] as $k => $v) {
				$k = str_replace('-', '_', $k);
				$event_item[$k] = $v;
			}
			$events[$key]['item'] = $event_item;
		}

		$o = Renderer::replaceMacros($tpl, [
			'$tabs' => $tabs,
			'$title' => DI::l10n()->t('Events'),
			'$view' => DI::l10n()->t('View'),
			'$previous' => [DI::baseUrl() . "/events/$prevyear/$prevmonth", DI::l10n()->t('Previous'), '', ''],
			'$next' => [DI::baseUrl() . "/events/$nextyear/$nextmonth", DI::l10n()->t('Next'), '', ''],
			'$calendar' => Temporal::getCalendarTable($y, $m, $links, ' eventcal'),
			'$events' => $events,
			"today" => DI::l10n()->t("today"),
			"month" => DI::l10n()->t("month"),
			"week" => DI::l10n()->t("week"),
			"day" => DI::l10n()->t("day"),
			"list" => DI::l10n()->t("list"),
		]);

		if (!empty($_GET['id'])) {
			echo $o;
			exit();
		}

		return $o;
	}

	if ($mode == 'export') {
		if (!$owner_uid) {
			notice(DI::l10n()->t('User not found'));
			return;
		}

		// Get the export data by uid
		$evexport = Event::exportListByUserId($owner_uid, $format);

		if (!$evexport["success"]) {
			if ($evexport["content"]) {
				notice(DI::l10n()->t('This calendar format is not supported'));
			} else {
				notice(DI::l10n()->t('No exportable data found'));
			}

			// If it the own calendar return to the events page
			// otherwise to the profile calendar page
			if (local_user() === $owner_uid) {
				$return_path = "events";
			} else {
				$return_path = "cal/" . $nick;
			}

			DI::baseUrl()->redirect($return_path);
		}

		// If nothing went wrong we can echo the export content
		if ($evexport["success"]) {
			header('Content-type: text/calendar');
			header('content-disposition: attachment; filename="' . DI::l10n()->t('calendar') . '-' . $nick . '.' . $evexport["extension"] . '"');
			echo $evexport["content"];
			exit();
		}

		return;
	}
}
