<?php
/**
 * @file mod/events.php
 * @brief The events module
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Widget\CalendarExport;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Module\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

require_once 'include/items.php';

function events_init(App $a)
{
	if (!Session::user()->isLocal()) {
		return;
	}

	// If it's a json request abort here because we don't
	// need the widget data
	if ($a->argc > 1 && $a->argv[1] === 'json') {
		return;
	}

	if (empty($a->page['aside'])) {
		$a->page['aside'] = '';
	}

	$cal_widget = CalendarExport::getHTML();

	$a->page['aside'] .= $cal_widget;

	return;
}

function events_post(App $a)
{

	Logger::log('post: ' . print_r($_REQUEST, true), Logger::DATA);

	if (!Session::user()->isLocal()) {
		return;
	}

	$event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$cid = !empty($_POST['cid']) ? intval($_POST['cid']) : 0;
	$uid = Session::user()->getUid();

	$start_text  = escape_tags(defaults($_REQUEST, 'start_text', ''));
	$finish_text = escape_tags(defaults($_REQUEST, 'finish_text', ''));

	$adjust   = intval(defaults($_POST, 'adjust', 0));
	$nofinish = intval(defaults($_POST, 'nofinish', 0));

	// The default setting for the `private` field in event_store() is false, so mirror that
	$private_event = false;

	$start  = DBA::NULL_DATETIME;
	$finish = DBA::NULL_DATETIME;

	if ($start_text) {
		$start = $start_text;
	}

	if ($finish_text) {
		$finish = $finish_text;
	}

	if ($adjust) {
		$start = DateTimeFormat::convert($start, 'UTC', date_default_timezone_get());
		if (!$nofinish) {
			$finish = DateTimeFormat::convert($finish, 'UTC', date_default_timezone_get());
		}
	} else {
		$start = DateTimeFormat::utc($start);
		if (!$nofinish) {
			$finish = DateTimeFormat::utc($finish);
		}
	}

	// Don't allow the event to finish before it begins.
	// It won't hurt anything, but somebody will file a bug report
	// and we'll waste a bunch of time responding to it. Time that
	// could've been spent doing something else.

	$summary  = escape_tags(trim(defaults($_POST, 'summary', '')));
	$desc     = escape_tags(trim(defaults($_POST, 'desc', '')));
	$location = escape_tags(trim(defaults($_POST, 'location', '')));
	$type     = 'event';

	$action = ($event_id == '') ? 'new' : "event/" . $event_id;
	$onerror_path = "events/" . $action . "?summary=$summary&description=$desc&location=$location&start=$start_text&finish=$finish_text&adjust=$adjust&nofinish=$nofinish";

	if (strcmp($finish, $start) < 0 && !$nofinish) {
		notice(L10n::t('Event can not end before it has started.') . EOL);
		if (intval($_REQUEST['preview'])) {
			echo L10n::t('Event can not end before it has started.');
			killme();
		}
		$a->internalRedirect($onerror_path);
	}

	if (!$summary || ($start === DBA::NULL_DATETIME)) {
		notice(L10n::t('Event title and start time are required.') . EOL);
		if (intval($_REQUEST['preview'])) {
			echo L10n::t('Event title and start time are required.');
			killme();
		}
		$a->internalRedirect($onerror_path);
	}

	$share = intval(defaults($_POST, 'share', 0));

	$c = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1",
		$uid
	);

	if (DBA::isResult($c)) {
		$self = $c[0]['id'];
	} else {
		$self = 0;
	}


	if ($share) {
		$str_group_allow   = !empty($_POST['group_allow'])   ? perms2str($_POST['group_allow'])   : '';
		$str_contact_allow = !empty($_POST['contact_allow']) ? perms2str($_POST['contact_allow']) : '';
		$str_group_deny    = !empty($_POST['group_deny'])    ? perms2str($_POST['group_deny'])    : '';
		$str_contact_deny  = !empty($_POST['contact_deny'])  ? perms2str($_POST['contact_deny'])  : '';

		// Undo the pseudo-contact of self, since there are real contacts now
		if (strpos($str_contact_allow, '<' . $self . '>') !== false) {
			$str_contact_allow = str_replace('<' . $self . '>', '', $str_contact_allow);
		}
		// Make sure to set the `private` field as true. This is necessary to
		// have the posts show up correctly in Diaspora if an event is created
		// as visible only to self at first, but then edited to display to others.
		if (strlen($str_group_allow) || strlen($str_contact_allow) || strlen($str_group_deny) || strlen($str_contact_deny)) {
			$private_event = true;
		}
	} else {
		// Note: do not set `private` field for self-only events. It will
		// keep even you from seeing them!
		$str_contact_allow = '<' . $self . '>';
		$str_group_allow = $str_contact_deny = $str_group_deny = '';
	}


	$datarray = [];
	$datarray['start']     = $start;
	$datarray['finish']    = $finish;
	$datarray['summary']   = $summary;
	$datarray['desc']      = $desc;
	$datarray['location']  = $location;
	$datarray['type']      = $type;
	$datarray['adjust']    = $adjust;
	$datarray['nofinish']  = $nofinish;
	$datarray['uid']       = $uid;
	$datarray['cid']       = $cid;
	$datarray['allow_cid'] = $str_contact_allow;
	$datarray['allow_gid'] = $str_group_allow;
	$datarray['deny_cid']  = $str_contact_deny;
	$datarray['deny_gid']  = $str_group_deny;
	$datarray['private']   = $private_event;
	$datarray['id']        = $event_id;

	if (intval($_REQUEST['preview'])) {
		$html = Event::getHTML($datarray);
		echo $html;
		killme();
	}

	$item_id = Event::store($datarray);

	if (!$cid) {
		Worker::add(PRIORITY_HIGH, "Notifier", "event", $item_id);
	}

	$a->internalRedirect('events');
}

function events_content(App $a)
{
	if (!Session::user()->isLocal()) {
		notice(L10n::t('Permission denied.') . EOL);
		return Login::form();
	}

	if ($a->argc == 1) {
		$_SESSION['return_path'] = $a->cmd;
	}

	if (($a->argc > 2) && ($a->argv[1] === 'ignore') && intval($a->argv[2])) {
		$r = q("UPDATE `event` SET `ignore` = 1 WHERE `id` = %d AND `uid` = %d",
			intval($a->argv[2]),
			Session::user()->getUid()
		);
	}

	if (($a->argc > 2) && ($a->argv[1] === 'unignore') && intval($a->argv[2])) {
		$r = q("UPDATE `event` SET `ignore` = 0 WHERE `id` = %d AND `uid` = %d",
			intval($a->argv[2]),
			Session::user()->getUid()
		);
	}

	if ($a->theme_events_in_profile) {
		Nav::setSelected('home');
	} else {
		Nav::setSelected('events');
	}

	// get the translation strings for the callendar
	$i18n = Event::getStrings();

	$htpl = Renderer::getMarkupTemplate('event_head.tpl');
	$a->page['htmlhead'] .= Renderer::replaceMacros($htpl, [
		'$baseurl' => $a->getBaseURL(),
		'$module_url' => '/events',
		'$modparams' => 1,
		'$i18n' => $i18n,
	]);

	$o = '';
	$tabs = '';
	// tabs
	if ($a->theme_events_in_profile) {
		$tabs = Profile::getTabs($a, true);
	}

	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = !empty($_REQUEST['ignored']) ? intval($_REQUEST['ignored']) : 0;

	if ($a->argc > 1) {
		if ($a->argc > 2 && $a->argv[1] == 'event') {
			$mode = 'edit';
			$event_id = intval($a->argv[2]);
		}
		if ($a->argc > 2 && $a->argv[1] == 'drop') {
			$mode = 'drop';
			$event_id = intval($a->argv[2]);
		}
		if ($a->argc > 2 && $a->argv[1] == 'copy') {
			$mode = 'copy';
			$event_id = intval($a->argv[2]);
		}
		if ($a->argv[1] === 'new') {
			$mode = 'new';
			$event_id = 0;
		}
		if ($a->argc > 2 && intval($a->argv[1]) && intval($a->argv[2])) {
			$mode = 'view';
			$y = intval($a->argv[1]);
			$m = intval($a->argv[2]);
		}
	}

	// The view mode part is similiar to /mod/cal.php
	if ($mode == 'view') {
		$thisyear  = DateTimeFormat::localNow('Y');
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

		$dim    = Temporal::getDaysInMonth($y, $m);
		$start  = sprintf('%d-%d-%d %d:%d:%d', $y, $m, 1, 0, 0, 0);
		$finish = sprintf('%d-%d-%d %d:%d:%d', $y, $m, $dim, 23, 59, 59);

		if ($a->argc > 1 && $a->argv[1] === 'json') {
			if (!empty($_GET['start'])) {
				$start = $_GET['start'];
			}
			if (!empty($_GET['end'])) {
				$finish = $_GET['end'];
			}
		}

		$start  = DateTimeFormat::utc($start);
		$finish = DateTimeFormat::utc($finish);

		$adjust_start  = DateTimeFormat::local($start);
		$adjust_finish = DateTimeFormat::local($finish);

		// put the event parametes in an array so we can better transmit them
		$event_params = [
			'event_id'      => intval(defaults($_GET, 'id', 0)),
			'start'         => $start,
			'finish'        => $finish,
			'adjust_start'  => $adjust_start,
			'adjust_finish' => $adjust_finish,
			'ignore'        => $ignored,
		];

		// get events by id or by date
		if ($event_params['event_id']) {
			$r = Event::getListById(local_user(), $event_params['event_id']);
		} else {
			$r = Event::getListByDate(local_user(), $event_params);
		}

		$links = [];

		if (DBA::isResult($r)) {
			$r = Event::sortByDate($r);
			foreach ($r as $rr) {
				$j = $rr['adjust'] ? DateTimeFormat::local($rr['start'], 'j') : DateTimeFormat::utc($rr['start'], 'j');
				if (empty($links[$j])) {
					$links[$j] = $a->getBaseURL() . '/' . $a->cmd . '#link-' . $j;
				}
			}
		}

		$events = [];

		// transform the event in a usable array
		if (DBA::isResult($r)) {
			$r = Event::sortByDate($r);
			$events = Event::prepareListForTemplate($r);
		}

		if ($a->argc > 1 && $a->argv[1] === 'json') {
			echo json_encode($events);
			killme();
		}

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
			'$baseurl'   => System::baseUrl(),
			'$tabs'      => $tabs,
			'$title'     => L10n::t('Events'),
			'$view'      => L10n::t('View'),
			'$new_event' => [System::baseUrl() . '/events/new', L10n::t('Create New Event'), '', ''],
			'$previous'  => [System::baseUrl() . '/events/$prevyear/$prevmonth', L10n::t('Previous'), '', ''],
			'$next'      => [System::baseUrl() . '/events/$nextyear/$nextmonth', L10n::t('Next'), '', ''],
			'$calendar'  => Temporal::getCalendarTable($y, $m, $links, ' eventcal'),

			'$events'    => $events,

			'$today' => L10n::t('today'),
			'$month' => L10n::t('month'),
			'$week'  => L10n::t('week'),
			'$day'   => L10n::t('day'),
			'$list'  => L10n::t('list'),
		]);

		if (!empty($_GET['id'])) {
			echo $o;
			killme();
		}

		return $o;
	}

	if (($mode === 'edit' || $mode === 'copy') && $event_id) {
		$r = q("SELECT * FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($event_id),
			Session::user()->getUid()
		);
		if (DBA::isResult($r)) {
			$orig_event = $r[0];
		}
	}

	// Passed parameters overrides anything found in the DB
	if (in_array($mode, ['edit', 'new', 'copy'])) {
		if (empty($orig_event)) {
			$orig_event = [];
		}

		// In case of an error the browser is redirected back here, with these parameters filled in with the previous values
		if (!empty($_REQUEST['nofinish']))    {$orig_event['nofinish']    = $_REQUEST['nofinish'];}
		if (!empty($_REQUEST['adjust']))      {$orig_event['adjust']      = $_REQUEST['adjust'];}
		if (!empty($_REQUEST['summary']))     {$orig_event['summary']     = $_REQUEST['summary'];}
		if (!empty($_REQUEST['description'])) {$orig_event['description'] = $_REQUEST['description'];}
		if (!empty($_REQUEST['location']))    {$orig_event['location']    = $_REQUEST['location'];}
		if (!empty($_REQUEST['start']))       {$orig_event['start']       = $_REQUEST['start'];}
		if (!empty($_REQUEST['finish']))      {$orig_event['finish']      = $_REQUEST['finish'];}

		$n_checked = (!empty($orig_event['nofinish']) ? ' checked="checked" ' : '');
		$a_checked = (!empty($orig_event['adjust'])   ? ' checked="checked" ' : '');

		$t_orig = !empty($orig_event) ? $orig_event['summary']  : '';
		$d_orig = !empty($orig_event) ? $orig_event['desc']     : '';
		$l_orig = !empty($orig_event) ? $orig_event['location'] : '';
		$eid = !empty($orig_event) ? $orig_event['id']  : 0;
		$cid = !empty($orig_event) ? $orig_event['cid'] : 0;
		$uri = !empty($orig_event) ? $orig_event['uri'] : '';

		$sh_disabled = '';
		$sh_checked = '';

		if (!empty($orig_event)
			&& ($orig_event['allow_cid'] !== '<' . local_user() . '>'
			|| $orig_event['allow_gid']
			|| $orig_event['deny_cid']
			|| $orig_event['deny_gid']))
		{
			$sh_checked = ' checked="checked" ';
		}

		if ($cid || $mode === 'edit') {
			$sh_disabled = 'disabled="disabled"';
		}

		$sdt = !empty($orig_event) ? $orig_event['start']  : 'now';
		$fdt = !empty($orig_event) ? $orig_event['finish'] : 'now';

		$tz = date_default_timezone_get();
		if (!empty($orig_event)) {
			$tz = ($orig_event['adjust'] ? date_default_timezone_get() : 'UTC');
		}

		$syear  = DateTimeFormat::convert($sdt, $tz, 'UTC', 'Y');
		$smonth = DateTimeFormat::convert($sdt, $tz, 'UTC', 'm');
		$sday   = DateTimeFormat::convert($sdt, $tz, 'UTC', 'd');

		$shour   = !empty($orig_event) ? DateTimeFormat::convert($sdt, $tz, 'UTC', 'H') : '00';
		$sminute = !empty($orig_event) ? DateTimeFormat::convert($sdt, $tz, 'UTC', 'i') : '00';

		$fyear  = DateTimeFormat::convert($fdt, $tz, 'UTC', 'Y');
		$fmonth = DateTimeFormat::convert($fdt, $tz, 'UTC', 'm');
		$fday   = DateTimeFormat::convert($fdt, $tz, 'UTC', 'd');

		$fhour   = !empty($orig_event) ? DateTimeFormat::convert($fdt, $tz, 'UTC', 'H') : '00';
		$fminute = !empty($orig_event) ? DateTimeFormat::convert($fdt, $tz, 'UTC', 'i') : '00';

		$perms = ACL::getDefaultUserPermissions($orig_event);

		if (!$cid && in_array($mode, ['new', 'copy'])) {
			$acl = ACL::getFullSelectorHTML($a->user, false, $orig_event);
		} else {
			$acl = '';
		}

		// If we copy an old event, we need to remove the ID and URI
		// from the original event.
		if ($mode === 'copy') {
			$eid = 0;
			$uri = '';
		}

		$tpl = Renderer::getMarkupTemplate('event_form.tpl');

		$o .= Renderer::replaceMacros($tpl, [
			'$post' => $a->getBaseURL() . '/events',
			'$eid'  => $eid,
			'$cid'  => $cid,
			'$uri'  => $uri,

			'$allow_cid' => json_encode($perms['allow_cid']),
			'$allow_gid' => json_encode($perms['allow_gid']),
			'$deny_cid'  => json_encode($perms['deny_cid']),
			'$deny_gid'  => json_encode($perms['deny_gid']),

			'$title' => L10n::t('Event details'),
			'$desc' => L10n::t('Starting date and Title are required.'),
			'$s_text' => L10n::t('Event Starts:') . ' <span class="required" title="' . L10n::t('Required') . '">*</span>',
			'$s_dsel' => Temporal::getDateTimeField(
				new DateTime(),
				DateTime::createFromFormat('Y', $syear+5),
				DateTime::createFromFormat('Y-m-d H:i', "$syear-$smonth-$sday $shour:$sminute"),
				L10n::t('Event Starts:'),
				'start_text',
				true,
				true,
				'',
				'',
				true
			),
			'$n_text' => L10n::t('Finish date/time is not known or not relevant'),
			'$n_checked' => $n_checked,
			'$f_text' => L10n::t('Event Finishes:'),
			'$f_dsel' => Temporal::getDateTimeField(
				new DateTime(),
				DateTime::createFromFormat('Y', $fyear+5),
				DateTime::createFromFormat('Y-m-d H:i', "$fyear-$fmonth-$fday $fhour:$fminute"),
				L10n::t('Event Finishes:'),
				'finish_text',
				true,
				true,
				'start_text'
			),
			'$a_text' => L10n::t('Adjust for viewer timezone'),
			'$a_checked' => $a_checked,
			'$d_text' => L10n::t('Description:'),
			'$d_orig' => $d_orig,
			'$l_text' => L10n::t('Location:'),
			'$l_orig' => $l_orig,
			'$t_text' => L10n::t('Title:') . ' <span class="required" title="' . L10n::t('Required') . '">*</span>',
			'$t_orig' => $t_orig,
			'$summary' => ['summary', L10n::t('Title:'), $t_orig, '', '*'],
			'$sh_text' => L10n::t('Share this event'),
			'$share' => ['share', L10n::t('Share this event'), $sh_checked, '', $sh_disabled],
			'$sh_checked' => $sh_checked,
			'$nofinish' => ['nofinish', L10n::t('Finish date/time is not known or not relevant'), $n_checked],
			'$adjust' => ['adjust', L10n::t('Adjust for viewer timezone'), $a_checked],
			'$preview' => L10n::t('Preview'),
			'$acl' => $acl,
			'$submit' => L10n::t('Submit'),
			'$basic' => L10n::t('Basic'),
			'$advanced' => L10n::t('Advanced'),
			'$permissions' => L10n::t('Permissions'),
		]);

		return $o;
	}

	// Remove an event from the calendar and its related items
	if ($mode === 'drop' && $event_id) {
		$ev = Event::getListById(Session::user()->getUid(), $event_id);

		// Delete only real events (no birthdays)
		if (DBA::isResult($ev) && $ev[0]['type'] == 'event') {
			Item::deleteForUser(['id' => $ev[0]['itemid']], Session::user()->getUid());
		}

		if (Item::exists(['id' => $ev[0]['itemid']])) {
			notice(L10n::t('Failed to remove event') . EOL);
		} else {
			info(L10n::t('Event removed') . EOL);
		}

		$a->internalRedirect('events');
	}
}
