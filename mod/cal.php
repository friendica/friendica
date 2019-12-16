<?php
/**
 * @file mod/cal.php
 * @brief The calendar module
 * 	This calendar is for profile visitors and contains only the events
 * 	of the profile owner
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

function cal_init(App $a)
{
	if (Config::get('system', 'block_public') && !Session::isAuthenticated()) {
		throw new \Friendica\Network\HTTPException\ForbiddenException(L10n::t('Access denied.'));
	}

	if ($a->argc < 2) {
		throw new \Friendica\Network\HTTPException\ForbiddenException(L10n::t('Access denied.'));
	}

	Nav::setSelected('events');

	$nick = $a->argv[1];
	$user = DBA::selectFirst('user', [], ['nickname' => $nick, 'blocked' => false]);
	if (!DBA::isResult($user)) {
		throw new \Friendica\Network\HTTPException\NotFoundException();
	}

	$a->data['user'] = $user;
	$a->profile_uid = $user['uid'];

	// if it's a json request abort here becaus we don't
	// need the widget data
	if (!empty($a->argv[2]) && ($a->argv[2] === 'json')) {
		return;
	}

	$profile = Profile::getByNickname($nick, $a->profile_uid);

	$account_type = Contact::getAccountType($profile);

	$tpl = Renderer::getMarkupTemplate("widget/vcard.tpl");

	$vcard_widget = Renderer::replaceMacros($tpl, [
		'$name' => $profile['name'],
		'$photo' => $profile['photo'],
		'$addr' => (($profile['addr'] != "") ? $profile['addr'] : ""),
		'$account_type' => $account_type,
		'$pdesc' => (($profile['pdesc'] != "") ? $profile['pdesc'] : ""),
	]);

	$cal_widget = Widget\CalendarExport::getHTML();

	if (empty($a->page['aside'])) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= $vcard_widget;
	$a->page['aside'] .= $cal_widget;

	return;
}

function cal_content(App $a)
{
	Nav::setSelected('events');

	// get the translation strings for the callendar
	$i18n = Event::getStrings();

	$htpl = Renderer::getMarkupTemplate('event_head.tpl');
	$a->page['htmlhead'] .= Renderer::replaceMacros($htpl, [
		'$module_url' => '/cal/' . $a->data['user']['nickname'],
		'$modparams' => 2,
		'$i18n' => $i18n,
	]);

	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = (!empty($_REQUEST['ignored']) ? intval($_REQUEST['ignored']) : 0);

	$format = 'ical';
	if ($a->argc == 4 && $a->argv[2] == 'export') {
		$mode = 'export';
		$format = $a->argv[3];
	}

	// Setup permissions structures
	$remote_contact = false;
	$contact_id = 0;

	$owner_uid = intval($a->data['user']['uid']);
	$nick = $a->data['user']['nickname'];

	if (!empty(Session::getRemoteContactID($a->profile['profile_uid']))) {
		$contact_id = Session::getRemoteContactID($a->profile['profile_uid']);
	}

	if ($contact_id) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($a->profile['profile_uid'])
		);
		if (DBA::isResult($r)) {
			$remote_contact = true;
		}
	}

	$is_owner = local_user() == $a->profile['profile_uid'];

	if ($a->profile['hidewall'] && !$is_owner && !$remote_contact) {
		notice(L10n::t('Access to this profile has been restricted.') . EOL);
		return;
	}

	// get the permissions
	$sql_perms = Item::getPermissionsSQLByUserId($owner_uid);
	// we only want to have the events of the profile owner
	$sql_extra = " AND `event`.`cid` = 0 " . $sql_perms;

	// get the tab navigation bar
	$tabs = Profile::getTabs($a, 'cal', false, $a->data['user']['nickname']);

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


		if (!empty($a->argv[2]) && ($a->argv[2] === 'json')) {
			if (!empty($_GET['start'])) {
				$start = $_GET['start'];
			}

			if (!empty($_GET['end'])) {
				$finish = $_GET['end'];
			}
		}

		$start = DateTimeFormat::utc($start);
		$finish = DateTimeFormat::utc($finish);

		$adjust_start = DateTimeFormat::local($start);
		$adjust_finish = DateTimeFormat::local($finish);

		// put the event parametes in an array so we can better transmit them
		$event_params = [
			'event_id'      => intval($_GET['id'] ?? 0),
			'start'         => $start,
			'finish'        => $finish,
			'adjust_start'  => $adjust_start,
			'adjust_finish' => $adjust_finish,
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
				$j = $rr['adjust'] ? DateTimeFormat::local($rr['start'], 'j') : DateTimeFormat::utc($rr['start'], 'j');
				if (empty($links[$j])) {
					$links[$j] = System::baseUrl() . '/' . DI::args()->getCommand() . '#link-' . $j;
				}
			}
		}

		// transform the event in a usable array
		$events = Event::prepareListForTemplate($r);

		if (!empty($a->argv[2]) && ($a->argv[2] === 'json')) {
			echo json_encode($events);
			exit();
		}

		// links: array('href', 'text', 'extra css classes', 'title')
		if (!empty($_GET['id'])) {
			$tpl = Renderer::getMarkupTemplate("event.tpl");
		} else {
//			if (Config::get('experimentals','new_calendar')==1){
			$tpl = Renderer::getMarkupTemplate("events_js.tpl");
//			} else {
//				$tpl = Renderer::getMarkupTemplate("events.tpl");
//			}
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
			'$title' => L10n::t('Events'),
			'$view' => L10n::t('View'),
			'$previous' => [System::baseUrl() . "/events/$prevyear/$prevmonth", L10n::t('Previous'), '', ''],
			'$next' => [System::baseUrl() . "/events/$nextyear/$nextmonth", L10n::t('Next'), '', ''],
			'$calendar' => Temporal::getCalendarTable($y, $m, $links, ' eventcal'),
			'$events' => $events,
			"today" => L10n::t("today"),
			"month" => L10n::t("month"),
			"week" => L10n::t("week"),
			"day" => L10n::t("day"),
			"list" => L10n::t("list"),
		]);

		if (!empty($_GET['id'])) {
			echo $o;
			exit();
		}

		return $o;
	}

	if ($mode == 'export') {
		if (!$owner_uid) {
			notice(L10n::t('User not found'));
			return;
		}

		// Test permissions
		// Respect the export feature setting for all other /cal pages if it's not the own profile
		if ((local_user() !== $owner_uid) && !Feature::isEnabled($owner_uid, "export_calendar")) {
			notice(L10n::t('Permission denied.') . EOL);
			DI::baseUrl()->redirect('cal/' . $nick);
		}

		// Get the export data by uid
		$evexport = Event::exportListByUserId($owner_uid, $format);

		if (!$evexport["success"]) {
			if ($evexport["content"]) {
				notice(L10n::t('This calendar format is not supported'));
			} else {
				notice(L10n::t('No exportable data found'));
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
			header('content-disposition: attachment; filename="' . L10n::t('calendar') . '-' . $nick . '.' . $evexport["extension"] . '"');
			echo $evexport["content"];
			exit();
		}

		return;
	}
}
