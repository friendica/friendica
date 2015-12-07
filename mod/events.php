<?php

require_once('include/bbcode.php');
require_once('include/datetime.php');
require_once('include/event.php');
require_once('include/items.php');

function events_post(&$a) {

	logger('post: ' . print_r($_REQUEST,true));

	if(! local_user())
		return;

	$event_id = ((x($_POST,'event_id')) ? intval($_POST['event_id']) : 0);
	$cid = ((x($_POST,'cid')) ? intval($_POST['cid']) : 0);
	$uid      = local_user();

	$start_text = escape_tags($_REQUEST['start_text']);
	$finish_text = escape_tags($_REQUEST['finish_text']);

	$adjust   = intval($_POST['adjust']);
	$nofinish = intval($_POST['nofinish']);

	// The default setting for the `private` field in event_store() is false, so mirror that
	$private_event = false;

	if($start_text) {
		$start = $start_text;
	}
	else {
		$start    = sprintf('%d-%d-%d %d:%d:0',$startyear,$startmonth,$startday,$starthour,$startminute);
	}

	if($nofinish) {
		$finish = '0000-00-00 00:00:00';
	}

	if($finish_text) {
		$finish = $finish_text;
	}
	else {
		$finish    = sprintf('%d-%d-%d %d:%d:0',$finishyear,$finishmonth,$finishday,$finishhour,$finishminute);
	}

	if($adjust) {
		$start = datetime_convert(date_default_timezone_get(),'UTC',$start);
		if(! $nofinish)
			$finish = datetime_convert(date_default_timezone_get(),'UTC',$finish);
	}
	else {
		$start = datetime_convert('UTC','UTC',$start);
		if(! $nofinish)
			$finish = datetime_convert('UTC','UTC',$finish);
	}

	// Don't allow the event to finish before it begins.
	// It won't hurt anything, but somebody will file a bug report
	// and we'll waste a bunch of time responding to it. Time that
	// could've been spent doing something else.

	$summary  = escape_tags(trim($_POST['summary']));
	$desc     = escape_tags(trim($_POST['desc']));
	$location = escape_tags(trim($_POST['location']));
	$type     = 'event';

	$action = ($event_id == '') ? 'new' : "event/" . $event_id;
	$onerror_url = $a->get_baseurl() . "/events/" . $action . "?summary=$summary&description=$desc&location=$location&start=$start_text&finish=$finish_text&adjust=$adjust&nofinish=$nofinish";

        if(strcmp($finish,$start) < 0 && !$nofinish) {
		notice( t('Event can not end before it has started.') . EOL);
                if(intval($_REQUEST['preview'])) {
			echo( t('Event can not end before it has started.'));
			killme();
		}
		goaway($onerror_url);
	}

	if((! $summary) || (! $start)) {
		notice( t('Event title and start time are required.') . EOL);
		if(intval($_REQUEST['preview'])) {
			echo( t('Event title and start time are required.'));
			killme();
		}
		goaway($onerror_url);
	}

	$share = ((intval($_POST['share'])) ? intval($_POST['share']) : 0);

	$c = q("select id from contact where uid = %d and self = 1 limit 1",
		intval(local_user())
	);
	if(count($c))
		$self = $c[0]['id'];
	else
		$self = 0;


	if($share) {
		$str_group_allow   = perms2str($_POST['group_allow']);
		$str_contact_allow = perms2str($_POST['contact_allow']);
		$str_group_deny    = perms2str($_POST['group_deny']);
		$str_contact_deny  = perms2str($_POST['contact_deny']);

		// Undo the pseudo-contact of self, since there are real contacts now
		if( strpos($str_contact_allow, '<' . $self . '>') !== false )
		{
			$str_contact_allow = str_replace('<' . $self . '>', '', $str_contact_allow);
		}
		// Make sure to set the `private` field as true. This is necessary to
		// have the posts show up correctly in Diaspora if an event is created
		// as visible only to self at first, but then edited to display to others.
		if( strlen($str_group_allow) or strlen($str_contact_allow) or strlen($str_group_deny) or strlen($str_contact_deny) )
		{
			$private_event = true;
		}
	}
	else {
		// Note: do not set `private` field for self-only events. It will
		// keep even you from seeing them!
		$str_contact_allow = '<' . $self . '>';
		$str_group_allow = $str_contact_deny = $str_group_deny = '';
	}


	$datarray = array();
	$datarray['start'] = $start;
	$datarray['finish'] = $finish;
	$datarray['summary'] = $summary;
	$datarray['desc'] = $desc;
	$datarray['location'] = $location;
	$datarray['type'] = $type;
	$datarray['adjust'] = $adjust;
	$datarray['nofinish'] = $nofinish;
	$datarray['uid'] = $uid;
	$datarray['cid'] = $cid;
	$datarray['allow_cid'] = $str_contact_allow;
	$datarray['allow_gid'] = $str_group_allow;
	$datarray['deny_cid'] = $str_contact_deny;
	$datarray['deny_gid'] = $str_group_deny;
	$datarray['private'] = (($private_event) ? 1 : 0);
	$datarray['id'] = $event_id;
	$datarray['created'] = $created;
	$datarray['edited'] = $edited;

	if(intval($_REQUEST['preview'])) {
		$html = format_event_html($datarray);
		echo $html;
			killme();
	}

	$item_id = event_store($datarray);

	if(! $cid)
		proc_run('php',"include/notifier.php","event","$item_id");

	goaway($_SESSION['return_url']);
}



function events_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc == 1)
		$_SESSION['return_url'] = $a->get_baseurl() . '/' . $a->cmd;

	if(($a->argc > 2) && ($a->argv[1] === 'ignore') && intval($a->argv[2])) {
		$r = q("update event set ignore = 1 where id = %d and uid = %d",
			intval($a->argv[2]),
			intval(local_user())
		);
	}

	if(($a->argc > 2) && ($a->argv[1] === 'unignore') && intval($a->argv[2])) {
		$r = q("update event set ignore = 0 where id = %d and uid = %d",
			intval($a->argv[2]),
			intval(local_user())
		);
	}

	if ($a->theme_events_in_profile)
		nav_set_selected('home');
	else
		nav_set_selected('events');

	$editselect = 'none';
	if( feature_enabled(local_user(), 'richtext') )
		$editselect = 'textareas';

	// First day of the week (0 = Sunday)
	$firstDay = get_pconfig(local_user(),'system','first_day_of_week');
	if ($firstDay === false) $firstDay=0;

	$i18n = array(
			"firstDay" => $firstDay,
			"Sun" => t("Sun"),
			"Mon" => t("Mon"),
			"Tue" => t("Tue"),
			"Wed" => t("Wed"),
			"Thu" => t("Thu"),
			"Fri" => t("Fri"),
			"Sat" => t("Sat"),
			"Sunday" => t("Sunday"),
			"Monday" => t("Monday"),
			"Tuesday" => t("Tuesday"),
			"Wednesday" => t("Wednesday"),
			"Thursday" => t("Thursday"),
			"Friday" => t("Friday"),
			"Saturday" => t("Saturday"),
			"Jan" => t("Jan"),
			"Feb" => t("Feb"),
			"Mar" => t("Mar"),
			"Apr" => t("Apr"),
			"May" => t("May"),
			"Jun" => t("Jun"),
			"Jul" => t("Jul"),
			"Aug" => t("Aug"),
			"Sep" => t("Sept"),
			"Oct" => t("Oct"),
			"Nov" => t("Nov"),
			"Dec" => t("Dec"),
			"January" => t("January"),
			"February" => t("February"),
			"March" => t("March"),
			"April" => t("April"),
			"May" => t("May"),
			"June" => t("June"),
			"July" => t("July"),
			"August" => t("August"),
			"September" => t("September"),
			"October" => t("October"),
			"November" => t("November"),
			"December" => t("December"),
			"today" => t("today"),
			"month" => t("month"),
			"week" => t("week"),
			"day" => t("day"),
		);

	$htpl = get_markup_template('event_head.tpl');
	$a->page['htmlhead'] .= replace_macros($htpl,array(
		'$baseurl' => $a->get_baseurl(),
		'$i18n' => $i18n,
		'$editselect' => $editselect
	));

	$etpl = get_markup_template('event_end.tpl');
	$a->page['end'] .= replace_macros($etpl,array(
		'$baseurl' => $a->get_baseurl(),
		'$editselect' => $editselect
	));

	$o ="";
	// tabs
	if ($a->theme_events_in_profile)
		$tabs = profile_tabs($a, True);



	$mode = 'view';
	$y = 0;
	$m = 0;
	$ignored = ((x($_REQUEST,'ignored')) ? intval($_REQUEST['ignored']) : 0);

	if($a->argc > 1) {
		if($a->argc > 2 && $a->argv[1] == 'event') {
			$mode = 'edit';
			$event_id = intval($a->argv[2]);
		}
		if($a->argv[1] === 'new') {
			$mode = 'new';
			$event_id = 0;
		}
		if($a->argc > 2 && intval($a->argv[1]) && intval($a->argv[2])) {
			$mode = 'view';
			$y = intval($a->argv[1]);
			$m = intval($a->argv[2]);
		}
	}

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


		if ($a->argv[1] === 'json'){
			if (x($_GET,'start'))	$start = date("Y-m-d h:i:s", $_GET['start']);
			if (x($_GET,'end'))	$finish = date("Y-m-d h:i:s", $_GET['end']);
		}

		$start  = datetime_convert('UTC','UTC',$start);
		$finish = datetime_convert('UTC','UTC',$finish);

		$adjust_start = datetime_convert('UTC', date_default_timezone_get(), $start);
		$adjust_finish = datetime_convert('UTC', date_default_timezone_get(), $finish);


		if (x($_GET,'id')){
			$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
				`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event`
				LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
				WHERE `event`.`uid` = %d AND `event`.`id` = %d",
				intval(local_user()),
				intval($_GET['id'])
			);
		} else {
			$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
				`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event`
				LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
				WHERE `event`.`uid` = %d and event.ignore = %d
				AND ((`adjust` = 0 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s')
				OR  (`adjust` = 1 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s')) ",
				intval(local_user()),
				intval($ignored),
				dbesc($start),
				dbesc($start),
				dbesc($finish),
				dbesc($adjust_start),
				dbesc($adjust_start),
				dbesc($adjust_finish)
			);
		}

		$links = array();

		if(count($r)) {
			$r = sort_by_date($r);
			foreach($r as $rr) {
				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				if(! x($links,$j))
					$links[$j] = $a->get_baseurl() . '/' . $a->cmd . '#link-' . $j;
			}
		}


		$events=array();

		$last_date = '';
		$fmt = t('l, F j');

		if(count($r)) {
			$r = sort_by_date($r);
			foreach($r as $rr) {


				$j = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'j') : datetime_convert('UTC','UTC',$rr['start'],'j'));
				$d = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], $fmt) : datetime_convert('UTC','UTC',$rr['start'],$fmt));
				$d = day_translate($d);

				$start = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['start'], 'c') : datetime_convert('UTC','UTC',$rr['start'],'c'));
				if ($rr['nofinish']){
					$end = null;
				} else {
					$end = (($rr['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$rr['finish'], 'c') : datetime_convert('UTC','UTC',$rr['finish'],'c'));
				}


				$is_first = ($d !== $last_date);

				$last_date = $d;
				$edit = ((! $rr['cid']) ? array($a->get_baseurl().'/events/event/'.$rr['id'],t('Edit event'),'','') : null);
				$title = strip_tags(html_entity_decode(bbcode($rr['summary']),ENT_QUOTES,'UTF-8'));
				if(! $title) {
					list($title, $_trash) = explode("<br",bbcode($rr['desc']),2);
					$title = strip_tags(html_entity_decode($title,ENT_QUOTES,'UTF-8'));
				}
				$html = format_event_html($rr);
				$rr['desc'] = bbcode($rr['desc']);
				$rr['location'] = bbcode($rr['location']);
				$events[] = array(
					'id'=>$rr['id'],
					'start'=> $start,
					'end' => $end,
					'allDay' => false,
					'title' => $title,

					'j' => $j,
					'd' => $d,
					'edit' => $edit,
					'is_first'=>$is_first,
					'item'=>$rr,
					'html'=>$html,
					'plink' => array($rr['plink'],t('link to source'),'',''),
				);


			}
		}

		if ($a->argv[1] === 'json'){
			echo json_encode($events); killme();
		}

		// links: array('href', 'text', 'extra css classes', 'title')
		if (x($_GET,'id')){
			$tpl =  get_markup_template("event.tpl");
		} else {
//			if (get_config('experimentals','new_calendar')==1){
				$tpl = get_markup_template("events-js.tpl");
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
			'$baseurl'	=> $a->get_baseurl(),
			'$tabs'		=> $tabs,
			'$title'	=> t('Events'),
			'$new_event'=> array($a->get_baseurl().'/events/new',t('Create New Event'),'',''),
			'$previus'	=> array($a->get_baseurl()."/events/$prevyear/$prevmonth",t('Previous'),'',''),
			'$next'		=> array($a->get_baseurl()."/events/$nextyear/$nextmonth",t('Next'),'',''),
			'$calendar' => cal($y,$m,$links, ' eventcal'),

			'$events'	=> $events,


		));

		if (x($_GET,'id')){ echo $o; killme(); }

		return $o;

	}

	if($mode === 'edit' && $event_id) {
		$r = q("SELECT * FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($event_id),
			intval(local_user())
		);
		if(count($r))
			$orig_event = $r[0];
	}

	// Passed parameters overrides anything found in the DB
	if($mode === 'edit' || $mode === 'new') {
		if(!x($orig_event)) $orig_event = array();
		// In case of an error the browser is redirected back here, with these parameters filled in with the previous values
		if(x($_REQUEST,'nofinish')) $orig_event['nofinish'] = $_REQUEST['nofinish'];
		if(x($_REQUEST,'adjust')) $orig_event['adjust'] = $_REQUEST['adjust'];
		if(x($_REQUEST,'summary')) $orig_event['summary'] = $_REQUEST['summary'];
		if(x($_REQUEST,'description')) $orig_event['description'] = $_REQUEST['description'];
		if(x($_REQUEST,'location')) $orig_event['location'] = $_REQUEST['location'];
		if(x($_REQUEST,'start')) $orig_event['start'] = $_REQUEST['start'];
		if(x($_REQUEST,'finish')) $orig_event['finish'] = $_REQUEST['finish'];
	}

	if($mode === 'edit' || $mode === 'new') {

		$n_checked = ((x($orig_event) && $orig_event['nofinish']) ? ' checked="checked" ' : '');
		$a_checked = ((x($orig_event) && $orig_event['adjust']) ? ' checked="checked" ' : '');
		$t_orig = ((x($orig_event)) ? $orig_event['summary'] : '');
		$d_orig = ((x($orig_event)) ? $orig_event['desc'] : '');
		$l_orig = ((x($orig_event)) ? $orig_event['location'] : '');
		$eid = ((x($orig_event)) ? $orig_event['id'] : 0);
		$cid = ((x($orig_event)) ? $orig_event['cid'] : 0);
		$uri = ((x($orig_event)) ? $orig_event['uri'] : '');


		if(! x($orig_event))
			$sh_checked = '';
		else
			$sh_checked = (($orig_event['allow_cid'] === '<' . local_user() . '>' && (! $orig_event['allow_gid']) && (! $orig_event['deny_cid']) && (! $orig_event['deny_gid'])) ? '' : ' checked="checked" ' );

		if($cid OR ($mode !== 'new'))
			$sh_checked .= ' disabled="disabled" ';


		$sdt = ((x($orig_event)) ? $orig_event['start'] : 'now');
		$fdt = ((x($orig_event)) ? $orig_event['finish'] : 'now');

		$tz = date_default_timezone_get();
		if(x($orig_event))
			$tz = (($orig_event['adjust']) ? date_default_timezone_get() : 'UTC');

		$syear = datetime_convert('UTC', $tz, $sdt, 'Y');
		$smonth = datetime_convert('UTC', $tz, $sdt, 'm');
		$sday = datetime_convert('UTC', $tz, $sdt, 'd');

		$shour = ((x($orig_event)) ? datetime_convert('UTC', $tz, $sdt, 'H') : 0);
		$sminute = ((x($orig_event)) ? datetime_convert('UTC', $tz, $sdt, 'i') : 0);

		$fyear = datetime_convert('UTC', $tz, $fdt, 'Y');
		$fmonth = datetime_convert('UTC', $tz, $fdt, 'm');
		$fday = datetime_convert('UTC', $tz, $fdt, 'd');

		$fhour = ((x($orig_event)) ? datetime_convert('UTC', $tz, $fdt, 'H') : 0);
		$fminute = ((x($orig_event)) ? datetime_convert('UTC', $tz, $fdt, 'i') : 0);

		$f = get_config('system','event_input_format');
		if(! $f)
			$f = 'ymd';

		require_once('include/acl_selectors.php');

		if ($mode === 'new')
			$acl = (($cid) ? '' : populate_acl(((x($orig_event)) ? $orig_event : $a->user)));

		$tpl = get_markup_template('event_form.tpl');

		$o .= replace_macros($tpl,array(
			'$post' => $a->get_baseurl() . '/events',
			'$eid' => $eid,
			'$cid' => $cid,
			'$uri' => $uri,

			'$title' => t('Event details'),
			'$desc' => t('Starting date and Title are required.'),
			'$s_text' => t('Event Starts:') . ' <span class="required" title="' . t('Required') . '">*</span>',
			'$s_dsel' => datetimesel($f,new DateTime(),DateTime::createFromFormat('Y',$syear+5),DateTime::createFromFormat('Y-m-d H:i',"$syear-$smonth-$sday $shour:$sminute"),'start_text',true,true,'','',true),
			'$n_text' => t('Finish date/time is not known or not relevant'),
			'$n_checked' => $n_checked,
			'$f_text' => t('Event Finishes:'),
			'$f_dsel' => datetimesel($f,new DateTime(),DateTime::createFromFormat('Y',$fyear+5),DateTime::createFromFormat('Y-m-d H:i',"$fyear-$fmonth-$fday $fhour:$fminute"),'finish_text',true,true,'start_text'),
			'$a_text' => t('Adjust for viewer timezone'),
			'$a_checked' => $a_checked,
			'$d_text' => t('Description:'),
			'$d_orig' => $d_orig,
			'$l_text' => t('Location:'),
			'$l_orig' => $l_orig,
			'$t_text' => t('Title:') . ' <span class="required" title="' . t('Required') . '">*</span>',
			'$t_orig' => $t_orig,
			'$sh_text' => t('Share this event'),
			'$sh_checked' => $sh_checked,
			'$preview' => t('Preview'),
			'$acl' => $acl,
			'$submit' => t('Submit')

		));

		return $o;
	}
}
