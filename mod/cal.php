<?php
/**
 * @file mod/cal.php
 * @brief The calendar module
 *	This calendar is for profile visitors and contains only the events
 *	of the profile owner
 */

require_once('include/event.php');
require_once('include/redir.php');

function cal_init(App $a) {
	if($a->argc > 1)
		auto_redir($a, $a->argv[1]);

	if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	nav_set_selected('events');

	$o = '';

	if($a->argc > 1) {
		$nick = $a->argv[1];
		$user = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
			dbesc($nick)
		);

		if(! count($user))
			return;

		$a->data['user'] = $user[0];
		$a->profile_uid = $user[0]['uid'];

		// if it's a json request abort here becaus we don't
		// need the widget data
		if ($a->argv[2] === 'json')
			return;

		$profile = get_profiledata_by_nick($nick, $a->profile_uid);

		$account_type = account_type($profile);

		$tpl = get_markup_template("vcard-widget.tpl");

		$vcard_widget .= replace_macros($tpl, array(
			'$name' => $profile['name'],
			'$photo' => $profile['photo'],
			'$addr' => (($profile['addr'] != "") ? $profile['addr'] : ""),
			'$account_type' => $account_type,
			'$pdesc' => (($profile['pdesc'] != "") ? $profile['pdesc'] : ""),
		));

		$cal_widget = widget_events();

		if(! x($a->page,'aside'))
			$a->page['aside'] = '';

		$a->page['aside'] .= $vcard_widget;
		$a->page['aside'] .= $cal_widget;
	}

	return;
}

function cal_content(App $a) {
	nav_set_selected('events');

	// First day of the week (0 = Sunday)
	$firstDay = get_pconfig(local_user(),'system','first_day_of_week');
	if ($firstDay === false) $firstDay=0;

	// get the translation strings for the callendar
	$i18n = get_event_strings();

	$htpl = get_markup_template('event_head.tpl');
	$a->page['htmlhead'] .= replace_macros($htpl,array(
		'$baseurl' => App::get_baseurl(),
		'$module_url' => '/cal/' . $a->data['user']['nickname'],
		'$modparams' => 2,
		'$i18n' => $i18n,
	));

	$etpl = get_markup_template('event_end.tpl');
	$a->page['end'] .= replace_macros($etpl,array(
		'$baseurl' => App::get_baseurl(),
	));

	$o ="";

	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = ((x($_REQUEST,'ignored')) ? intval($_REQUEST['ignored']) : 0);

	if($a->argc == 4) {
		if($a->argv[2] == 'export') {
			$mode = 'export';
			$format = $a->argv[3];
		}
	}

	//
	// Setup permissions structures
	//

	$contact = null;
	$remote_contact = false;
	$contact_id = 0;

	$owner_uid = $a->data['user']['uid'];
	$nick = $a->data['user']['nickname'];

	if(is_array($_SESSION['remote'])) {
		foreach($_SESSION['remote'] as $v) {
			if($v['uid'] == $a->profile['profile_uid']) {
				$contact_id = $v['cid'];
				break;
			}
		}
	}
	if($contact_id) {
		$groups = init_groups_visitor($contact_id);
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($contact_id),
			intval($a->profile['profile_uid'])
		);
		if (dbm::is_result($r)) {
			$contact = $r[0];
			$remote_contact = true;
		}
	}
	if(! $remote_contact) {
		if(local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}
	$is_owner = ((local_user()) && (local_user() == $a->profile['profile_uid']) ? true : false);

	if($a->profile['hidewall'] && (! $is_owner) && (! $remote_contact)) {
		notice( t('Access to this profile has been restricted.') . EOL);
		return;
	}

	// get the permissions
	$sql_perms = item_permissions_sql($owner_uid,$remote_contact,$groups);
	// we only want to have the events of the profile owner
	$sql_extra = " AND `event`.`cid` = 0 " . $sql_perms;

	// get the tab navigation bar
	$tabs .= profile_tabs($a,false, $a->data['user']['nickname']);

	// The view mode part is similiar to /mod/events.php
	if($mode == 'view') {


		$thisyear = datetime_convert('UTC',date_default_timezone_get(),'now','Y');
		$thismonth = datetime_convert('UTC',date_default_timezone_get(),'now','m');
		if(! $y)
			$y = intval($thisyear);
		if(! $m)
			$m = intval($thismonth);

		// Put some limits on dates. The PHP date functions don't seem to do so well before 1900.
		// An upper limit was chosen to keep search engines from exploring links millions of years in the future.

		if($y < 1901)
			$y = 1900;
		if($y > 2099)
			$y = 2100;

		$nextyear = $y;
		$nextmonth = $m + 1;
		if($nextmonth > 12) {
				$nextmonth = 1;
			$nextyear ++;
		}

		$prevyear = $y;
		if($m > 1)
			$prevmonth = $m - 1;
		else {
			$prevmonth = 12;
			$prevyear --;
		}

		$dim    = get_dim($y,$m);
		$start  = sprintf('%d-%d-%d %d:%d:%d',$y,$m,1,0,0,0);
		$finish = sprintf('%d-%d-%d %d:%d:%d',$y,$m,$dim,23,59,59);


		if ($a->argv[2] === 'json'){
			if (x($_GET,'start'))	$start = $_GET['start'];
			if (x($_GET,'end'))	$finish = $_GET['end'];
		}

		$start  = datetime_convert('UTC','UTC',$start);
		$finish = datetime_convert('UTC','UTC',$finish);

		$adjust_start = datetime_convert('UTC', date_default_timezone_get(), $start);
		$adjust_finish = datetime_convert('UTC', date_default_timezone_get(), $finish);

		// put the event parametes in an array so we can better transmit them
		$event_params = array(
			'event_id' => (x($_GET,'id') ? $_GET["id"] : 0),
			'start' => $start,
			'finish' => $finish,
			'adjust_start' => $adjust_start,
			'adjust_finish' => $adjust_finish,
			'ignored' => $ignored,
		);

		// get events by id or by date
		if (x($_GET,'id')){
			$r = event_by_id($owner_uid, $event_params, $sql_extra);
		} else {
			$r = events_by_date($owner_uid, $event_params, $sql_extra);
		}

		$links = array();

		if (dbm::is_result($r)) {
			$r = sort_by_date($r);
			foreach ($r as $rr) {
				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				if (! x($links,$j)) {
					$links[$j] = App::get_baseurl() . '/' . $a->cmd . '#link-' . $j;
				}
			}
		}


		$events=array();

		// transform the event in a usable array
		if (dbm::is_result($r))
			$r = sort_by_date($r);
			$events = process_events($r);

		if ($a->argv[2] === 'json'){
			echo json_encode($events); killme();
		}

		// links: array('href', 'text', 'extra css classes', 'title')
		if (x($_GET,'id')){
			$tpl =  get_markup_template("event.tpl");
		} else {
//			if (get_config('experimentals','new_calendar')==1){
				$tpl = get_markup_template("events_js.tpl");
//			} else {
//				$tpl = get_markup_template("events.tpl");
//			}
		}

		// Get rid of dashes in key names, Smarty3 can't handle them
		foreach($events as $key => $event) {
			$event_item = array();
			foreach($event['item'] as $k => $v) {
				$k = str_replace('-','_',$k);
				$event_item[$k] = $v;
			}
			$events[$key]['item'] = $event_item;
		}

		$o = replace_macros($tpl, array(
			'$baseurl'	=> App::get_baseurl(),
			'$tabs'		=> $tabs,
			'$title'	=> t('Events'),
			'$view'		=> t('View'),
			'$previous'	=> array(App::get_baseurl()."/events/$prevyear/$prevmonth", t('Previous'),'',''),
			'$next'		=> array(App::get_baseurl()."/events/$nextyear/$nextmonth", t('Next'),'',''),
			'$calendar' => cal($y,$m,$links, ' eventcal'),

			'$events'	=> $events,

			"today" => t("today"),
			"month" => t("month"),
			"week" => t("week"),
			"day" => t("day"),
			"list" => t("list"),
		));

		if (x($_GET,'id')){ echo $o; killme(); }

		return $o;
	}

	if($mode == 'export') {
		if(! (intval($owner_uid))) {
			notice( t('User not found'));
			return;
		}

		// Test permissions
		// Respect the export feature setting for all other /cal pages if it's not the own profile
		if( ((local_user() !== intval($owner_uid))) && ! feature_enabled($owner_uid, "export_calendar")) {
			notice( t('Permission denied.') . EOL);
			goaway('cal/' . $nick);
		}

		// Get the export data by uid
		$evexport = event_export($owner_uid, $format);

		if (!$evexport["success"]) {
			if($evexport["content"])
				notice( t('This calendar format is not supported') );
			else
				notice( t('No exportable data found'));

			// If it the own calendar return to the events page
			// otherwise to the profile calendar page
			if (local_user() === intval($owner_uid))
				$return_path = "events";
			else
				$returnpath = "cal/".$nick;

			goaway($return_path);
		}

		// If nothing went wrong we can echo the export content
		if ($evexport["success"]) {
			header('Content-type: text/calendar');
			header('content-disposition: attachment; filename="' . t('calendar') . '-' . $nick . '.' . $evexport["extension"] . '"' );
			echo $evexport["content"];
			killme();
		}

		return;
	}
}
