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
 * The events module
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Widget\CalendarExport;
use Friendica\Core\ACL;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Conversation;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Module\Security\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Worker\Delivery;

function events_init(App $a)
{
	if (!local_user()) {
		return;
	}

	if (empty(DI::page()['aside'])) {
		DI::page()['aside'] = '';
	}

	$cal_widget = CalendarExport::getHTML(local_user());

	DI::page()['aside'] .= $cal_widget;

	return;
}

function events_post(App $a)
{
	Logger::debug('post', ['request' => $_REQUEST]);
	if (!local_user()) {
		return;
	}

	$event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$cid = !empty($_POST['cid']) ? intval($_POST['cid']) : 0;
	$uid = local_user();

	$start_text  = Strings::escapeHtml($_REQUEST['start_text'] ?? '');
	$finish_text = Strings::escapeHtml($_REQUEST['finish_text'] ?? '');

	$nofinish = intval($_POST['nofinish'] ?? 0);

	$share = intval($_POST['share'] ?? 0);

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

	$start = DateTimeFormat::convert($start, 'UTC', $a->getTimeZone());
	if (!$nofinish) {
		$finish = DateTimeFormat::convert($finish, 'UTC', $a->getTimeZone());
	}

	// Don't allow the event to finish before it begins.
	// It won't hurt anything, but somebody will file a bug report
	// and we'll waste a bunch of time responding to it. Time that
	// could've been spent doing something else.

	$summary  = trim($_POST['summary']  ?? '');
	$desc     = trim($_POST['desc']     ?? '');
	$location = trim($_POST['location'] ?? '');
	$type     = 'event';

	$params = [
		'summary'     => $summary,
		'description' => $desc,
		'location'    => $location,
		'start'       => $start_text,
		'finish'      => $finish_text,
		'nofinish'    => $nofinish,
	];

	$action = ($event_id == '') ? 'new' : 'event/' . $event_id;
	$onerror_path = 'events/' . $action . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

	if (strcmp($finish, $start) < 0 && !$nofinish) {
		notice(DI::l10n()->t('Event can not end before it has started.'));
		if (intval($_REQUEST['preview'])) {
			echo DI::l10n()->t('Event can not end before it has started.');
			exit();
		}
		DI::baseUrl()->redirect($onerror_path);
	}

	if (!$summary || ($start === DBA::NULL_DATETIME)) {
		notice(DI::l10n()->t('Event title and start time are required.'));
		if (intval($_REQUEST['preview'])) {
			echo DI::l10n()->t('Event title and start time are required.');
			exit();
		}
		DI::baseUrl()->redirect($onerror_path);
	}

	$self = \Friendica\Model\Contact::getPublicIdByUserId($uid);

	$aclFormatter = DI::aclFormatter();

	if ($share) {
		$user = User::getById($uid, ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
		if (!DBA::isResult($user)) {
			return;
		}

		$str_contact_allow = isset($_REQUEST['contact_allow']) ? $aclFormatter->toString($_REQUEST['contact_allow']) : $user['allow_cid'] ?? '';
		$str_group_allow   = isset($_REQUEST['group_allow'])   ? $aclFormatter->toString($_REQUEST['group_allow'])   : $user['allow_gid'] ?? '';
		$str_contact_deny  = isset($_REQUEST['contact_deny'])  ? $aclFormatter->toString($_REQUEST['contact_deny'])  : $user['deny_cid']  ?? '';
		$str_group_deny    = isset($_REQUEST['group_deny'])    ? $aclFormatter->toString($_REQUEST['group_deny'])    : $user['deny_gid']  ?? '';

		$visibility = $_REQUEST['visibility'] ?? '';
		if ($visibility === 'public') {
			// The ACL selector introduced in version 2019.12 sends ACL input data even when the Public visibility is selected
			$str_contact_allow = $str_group_allow = $str_contact_deny = $str_group_deny = '';
		} else if ($visibility === 'custom') {
			// Since we know from the visibility parameter the item should be private, we have to prevent the empty ACL
			// case that would make it public. So we always append the author's contact id to the allowed contacts.
			// See https://github.com/friendica/friendica/issues/9672
			$str_contact_allow .= $aclFormatter->toString($self);
		}
	} else {
		$str_contact_allow = $aclFormatter->toString($self);
		$str_group_allow = $str_contact_deny = $str_group_deny = '';
	}

	// Make sure to set the `private` field as true. This is necessary to
	// have the posts show up correctly in Diaspora if an event is created
	// as visible only to self at first, but then edited to display to others.
	if (strlen($str_group_allow) || strlen($str_contact_allow) || strlen($str_group_deny) || strlen($str_contact_deny)) {
		$private_event = true;
	}

	$datarray = [];
	$datarray['start']     = $start;
	$datarray['finish']    = $finish;
	$datarray['summary']   = $summary;
	$datarray['desc']      = $desc;
	$datarray['location']  = $location;
	$datarray['type']      = $type;
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
		exit();
	}

	$event_id = Event::store($datarray);

	$item = ['network' => Protocol::DFRN, 'protocol' => Conversation::PARCEL_DIRECT, 'direction' => Conversation::PUSH];	
	$item = Event::getItemArrayForId($event_id, $item);
	if (Item::insert($item)) {
		$uri_id = $item['uri-id'];
	} else {
		$uri_id = 0;
	}

	if (!$cid && $uri_id) {
		Worker::add(PRIORITY_HIGH, "Notifier", Delivery::POST, (int)$uri_id, (int)$uid);
	}

	DI::baseUrl()->redirect('events');
}

function events_content(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		return Login::form();
	}

	if (DI::args()->getArgc() == 1) {
		$_SESSION['return_path'] = DI::args()->getCommand();
	}

	if ((DI::args()->getArgc() > 2) && (DI::args()->getArgv()[1] === 'ignore') && intval(DI::args()->getArgv()[2])) {
		DBA::update('event', ['ignore' => true], ['id' => DI::args()->getArgv()[2], 'uid' => local_user()]);
	}

	if ((DI::args()->getArgc() > 2) && (DI::args()->getArgv()[1] === 'unignore') && intval(DI::args()->getArgv()[2])) {
		DBA::update('event', ['ignore' => false], ['id' => DI::args()->getArgv()[2], 'uid' => local_user()]);
	}

	if ($a->getThemeInfoValue('events_in_profile')) {
		Nav::setSelected('home');
	} else {
		Nav::setSelected('events');
	}

	// get the translation strings for the callendar
	$i18n = Event::getStrings();

	DI::page()->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.min.css');
	DI::page()->registerStylesheet('view/asset/fullcalendar/dist/fullcalendar.print.min.css', 'print');
	DI::page()->registerFooterScript('view/asset/moment/min/moment-with-locales.min.js');
	DI::page()->registerFooterScript('view/asset/fullcalendar/dist/fullcalendar.min.js');

	$htpl = Renderer::getMarkupTemplate('event_head.tpl');
	DI::page()['htmlhead'] .= Renderer::replaceMacros($htpl, [
		'$module_url' => '/events',
		'$modparams' => 1,
		'$i18n' => $i18n,
	]);

	$o = '';
	$tabs = '';
	// tabs
	if ($a->getThemeInfoValue('events_in_profile')) {
		$tabs = BaseProfile::getTabsHTML($a, 'events', true, $a->getLoggedInUserNickname(), false);
	}

	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = !empty($_REQUEST['ignored']) ? intval($_REQUEST['ignored']) : 0;

	if (DI::args()->getArgc() > 1) {
		if (DI::args()->getArgc() > 2 && DI::args()->getArgv()[1] == 'event') {
			$mode = 'edit';
			$event_id = intval(DI::args()->getArgv()[2]);
		}
		if (DI::args()->getArgc() > 2 && DI::args()->getArgv()[1] == 'drop') {
			$mode = 'drop';
			$event_id = intval(DI::args()->getArgv()[2]);
		}
		if (DI::args()->getArgc() > 2 && DI::args()->getArgv()[1] == 'copy') {
			$mode = 'copy';
			$event_id = intval(DI::args()->getArgv()[2]);
		}
		if (DI::args()->getArgv()[1] === 'new') {
			$mode = 'new';
			$event_id = 0;
		}
		if (DI::args()->getArgc() > 2 && intval(DI::args()->getArgv()[1]) && intval(DI::args()->getArgv()[2])) {
			$mode = 'view';
			$y = intval(DI::args()->getArgv()[1]);
			$m = intval(DI::args()->getArgv()[2]);
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

		$dim    = Temporal::getDaysInMonth($y, $m);
		$start  = sprintf('%d-%d-%d %d:%d:%d', $y, $m, 1, 0, 0, 0);
		$finish = sprintf('%d-%d-%d %d:%d:%d', $y, $m, $dim, 23, 59, 59);

		// put the event parametes in an array so we can better transmit them
		$event_params = [
			'event_id'      => intval($_GET['id'] ?? 0),
			'start'         => $start,
			'finish'        => $finish,
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
				$j = DateTimeFormat::local($rr['start'], 'j');
				if (empty($links[$j])) {
					$links[$j] = DI::baseUrl() . '/' . DI::args()->getCommand() . '#link-' . $j;
				}
			}
		}

		$events = [];

		// transform the event in a usable array
		if (DBA::isResult($r)) {
			$r = Event::sortByDate($r);
			$events = Event::prepareListForTemplate($r);
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

		// ACL blocks are loaded in modals in frio
		DI::page()->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		DI::page()->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		DI::page()->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		DI::page()->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$o = Renderer::replaceMacros($tpl, [
			'$tabs'      => $tabs,
			'$title'     => DI::l10n()->t('Events'),
			'$view'      => DI::l10n()->t('View'),
			'$new_event' => [DI::baseUrl() . '/events/new', DI::l10n()->t('Create New Event'), '', ''],
			'$previous'  => [DI::baseUrl() . '/events/$prevyear/$prevmonth', DI::l10n()->t('Previous'), '', ''],
			'$next'      => [DI::baseUrl() . '/events/$nextyear/$nextmonth', DI::l10n()->t('Next'), '', ''],
			'$calendar'  => Temporal::getCalendarTable($y, $m, $links, ' eventcal'),

			'$events'    => $events,

			'$today' => DI::l10n()->t('today'),
			'$month' => DI::l10n()->t('month'),
			'$week'  => DI::l10n()->t('week'),
			'$day'   => DI::l10n()->t('day'),
			'$list'  => DI::l10n()->t('list'),
		]);

		if (!empty($_GET['id'])) {
			echo $o;
			exit();
		}

		return $o;
	}

	if (($mode === 'edit' || $mode === 'copy') && $event_id) {
		$orig_event = DBA::selectFirst('event', [], ['id' => $event_id, 'uid' => local_user()]);
	}

	// Passed parameters overrides anything found in the DB
	if (in_array($mode, ['edit', 'new', 'copy'])) {
		$share_checked = '';
		$share_disabled = '';

		if (empty($orig_event)) {
			$orig_event = User::getById(local_user(), ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);;
		} elseif ($orig_event['allow_cid'] !== '<' . local_user() . '>'
			|| $orig_event['allow_gid']
			|| $orig_event['deny_cid']
			|| $orig_event['deny_gid']) {
			$share_checked = ' checked="checked" ';
		}

		// In case of an error the browser is redirected back here, with these parameters filled in with the previous values
		if (!empty($_REQUEST['nofinish']))    {$orig_event['nofinish']    = $_REQUEST['nofinish'];}
		if (!empty($_REQUEST['summary']))     {$orig_event['summary']     = $_REQUEST['summary'];}
		if (!empty($_REQUEST['desc']))        {$orig_event['desc']        = $_REQUEST['desc'];}
		if (!empty($_REQUEST['location']))    {$orig_event['location']    = $_REQUEST['location'];}
		if (!empty($_REQUEST['start']))       {$orig_event['start']       = $_REQUEST['start'];}
		if (!empty($_REQUEST['finish']))      {$orig_event['finish']      = $_REQUEST['finish'];}

		$n_checked = (!empty($orig_event['nofinish']) ? ' checked="checked" ' : '');

		$t_orig = $orig_event['summary']  ?? '';
		$d_orig = $orig_event['desc']     ?? '';
		$l_orig = $orig_event['location'] ?? '';
		$eid = $orig_event['id'] ?? 0;
		$cid = $orig_event['cid'] ?? 0;
		$uri = $orig_event['uri'] ?? '';

		if ($cid || $mode === 'edit') {
			$share_disabled = 'disabled="disabled"';
		}

		$sdt = $orig_event['start'] ?? 'now';
		$fdt = $orig_event['finish'] ?? 'now';

		$syear  = DateTimeFormat::local($sdt, 'Y');
		$smonth = DateTimeFormat::local($sdt, 'm');
		$sday   = DateTimeFormat::local($sdt, 'd');

		$shour   = !empty($orig_event) ? DateTimeFormat::local($sdt, 'H') : '00';
		$sminute = !empty($orig_event) ? DateTimeFormat::local($sdt, 'i') : '00';

		$fyear  = DateTimeFormat::local($fdt, 'Y');
		$fmonth = DateTimeFormat::local($fdt, 'm');
		$fday   = DateTimeFormat::local($fdt, 'd');

		$fhour   = !empty($orig_event) ? DateTimeFormat::local($fdt, 'H') : '00';
		$fminute = !empty($orig_event) ? DateTimeFormat::local($fdt, 'i') : '00';

		if (!$cid && in_array($mode, ['new', 'copy'])) {
			$acl = ACL::getFullSelectorHTML(DI::page(), $a->getLoggedInUserId(), false, ACL::getDefaultUserPermissions($orig_event));
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
			'$post' => DI::baseUrl() . '/events',
			'$eid'  => $eid,
			'$cid'  => $cid,
			'$uri'  => $uri,

			'$title' => DI::l10n()->t('Event details'),
			'$desc' => DI::l10n()->t('Starting date and Title are required.'),
			'$s_text' => DI::l10n()->t('Event Starts:') . ' <span class="required" title="' . DI::l10n()->t('Required') . '">*</span>',
			'$s_dsel' => Temporal::getDateTimeField(
				new DateTime(),
				DateTime::createFromFormat('Y', intval($syear) + 5),
				DateTime::createFromFormat('Y-m-d H:i', "$syear-$smonth-$sday $shour:$sminute"),
				DI::l10n()->t('Event Starts:'),
				'start_text',
				true,
				true,
				'',
				'',
				true
			),
			'$n_text' => DI::l10n()->t('Finish date/time is not known or not relevant'),
			'$n_checked' => $n_checked,
			'$f_text' => DI::l10n()->t('Event Finishes:'),
			'$f_dsel' => Temporal::getDateTimeField(
				new DateTime(),
				DateTime::createFromFormat('Y', intval($fyear) + 5),
				DateTime::createFromFormat('Y-m-d H:i', "$fyear-$fmonth-$fday $fhour:$fminute"),
				DI::l10n()->t('Event Finishes:'),
				'finish_text',
				true,
				true,
				'start_text'
			),
			'$d_text' => DI::l10n()->t('Description:'),
			'$d_orig' => $d_orig,
			'$l_text' => DI::l10n()->t('Location:'),
			'$l_orig' => $l_orig,
			'$t_text' => DI::l10n()->t('Title:') . ' <span class="required" title="' . DI::l10n()->t('Required') . '">*</span>',
			'$t_orig' => $t_orig,
			'$summary' => ['summary', DI::l10n()->t('Title:'), $t_orig, '', '*'],
			'$sh_text' => DI::l10n()->t('Share this event'),
			'$share' => ['share', DI::l10n()->t('Share this event'), $share_checked, '', $share_disabled],
			'$sh_checked' => $share_checked,
			'$nofinish' => ['nofinish', DI::l10n()->t('Finish date/time is not known or not relevant'), $n_checked],
			'$preview' => DI::l10n()->t('Preview'),
			'$acl' => $acl,
			'$submit' => DI::l10n()->t('Submit'),
			'$basic' => DI::l10n()->t('Basic'),
			'$advanced' => DI::l10n()->t('Advanced'),
			'$permissions' => DI::l10n()->t('Permissions'),
		]);

		return $o;
	}

	// Remove an event from the calendar and its related items
	if ($mode === 'drop' && $event_id) {
		$ev = Event::getListById(local_user(), $event_id);

		// Delete only real events (no birthdays)
		if (DBA::isResult($ev) && $ev[0]['type'] == 'event') {
			Item::deleteForUser(['id' => $ev[0]['itemid']], local_user());
		}

		if (Post::exists(['id' => $ev[0]['itemid']])) {
			notice(DI::l10n()->t('Failed to remove event'));
		}

		DI::baseUrl()->redirect('events');
	}
}
