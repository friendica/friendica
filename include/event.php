<?php
/**
 * @file include/event.php
 * @brief functions specific to event handling
 */

require_once('include/bbcode.php');
require_once('include/map.php');
require_once('include/datetime.php');

function format_event_html($ev, $simple = false) {

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	$event_start = (($ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(),
			$ev['start'] , $bd_format ))
			:  day_translate(datetime_convert('UTC', 'UTC',
			$ev['start'] , $bd_format)));

	$event_end = (($ev['adjust']) ? day_translate(datetime_convert('UTC', date_default_timezone_get(),
				$ev['finish'] , $bd_format ))
				:  day_translate(datetime_convert('UTC', 'UTC',
				$ev['finish'] , $bd_format )));

	if ($simple) {
		$o = "<h3>".bbcode($ev['summary'])."</h3>";

		$o .= "<p>".bbcode($ev['desc'])."</p>";

		$o .= "<h4>".t('Starts:')."</h4><p>".$event_start."</p>";

		if(! $ev['nofinish'])
			$o .= "<h4>".t('Finishes:')."</h4><p>".$event_end."</p>";

		if(strlen($ev['location']))
			$o .= "<h4>".t('Location:')."</h4><p>".$ev['location']."</p>";

		return $o;
	}

	$o = '<div class="vevent">' . "\r\n";


	$o .= '<p class="summary event-summary">' . bbcode($ev['summary']) .  '</p>' . "\r\n";

	$o .= '<p class="description event-description">' . bbcode($ev['desc']) .  '</p>' . "\r\n";

	$o .= '<p class="event-start">' . t('Starts:') . ' <abbr class="dtstart" title="'
		. datetime_convert('UTC','UTC',$ev['start'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
		. '" >'.$event_start
		. '</abbr></p>' . "\r\n";

	if(! $ev['nofinish'])
		$o .= '<p class="event-end" >' . t('Finishes:') . ' <abbr class="dtend" title="'
			. datetime_convert('UTC','UTC',$ev['finish'], (($ev['adjust']) ? ATOM_TIME : 'Y-m-d\TH:i:s' ))
			. '" >'.$event_end
			. '</abbr></p>'  . "\r\n";

	if(strlen($ev['location'])){
		$o .= '<p class="event-location"> ' . t('Location:') . ' <span class="location">'
			. bbcode($ev['location'])
			. '</span></p>' . "\r\n";

		if (strpos($ev['location'], "[map") !== False) {
			$map = generate_named_map($ev['location']);
			if ($map!==$ev['location']) $o.=$map;
		}

	}

	$o .= '</div>' . "\r\n";
	return $o;
}

/*
function parse_event($h) {

	require_once('include/Scrape.php');
	require_once('include/html2bbcode');

	$h = '<html><body>' . $h . '</body></html>';

	$ret = array();


	try {
		$dom = HTML5_Parser::parse($h);
	} catch (DOMException $e) {
		logger('parse_event: parse error: ' . $e);
	}

	if(! $dom)
 		return $ret;

	$items = $dom->getElementsByTagName('*');

	foreach($items as $item) {
		if(attribute_contains($item->getAttribute('class'), 'vevent')) {
			$level2 = $item->getElementsByTagName('*');
			foreach($level2 as $x) {
				if(attribute_contains($x->getAttribute('class'),'dtstart') && $x->getAttribute('title')) {
					$ret['start'] = $x->getAttribute('title');
					if(! strpos($ret['start'],'Z'))
						$ret['adjust'] = true;
				}
				if(attribute_contains($x->getAttribute('class'),'dtend') && $x->getAttribute('title'))
					$ret['finish'] = $x->getAttribute('title');

				if(attribute_contains($x->getAttribute('class'),'description'))
					$ret['desc'] = $x->textContent;
				if(attribute_contains($x->getAttribute('class'),'location'))
					$ret['location'] = $x->textContent;
			}
		}
	}

	// sanitise

	if((x($ret,'desc')) && ((strpos($ret['desc'],'<') !== false) || (strpos($ret['desc'],'>') !== false))) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$ret['desc'] = html2bbcode($purifier->purify($ret['desc']));
	}

	if((x($ret,'location')) && ((strpos($ret['location'],'<') !== false) || (strpos($ret['location'],'>') !== false))) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new HTMLPurifier($config);
		$ret['location'] = html2bbcode($purifier->purify($ret['location']));
	}

	if(x($ret,'start'))
		$ret['start'] = datetime_convert('UTC','UTC',$ret['start']);
	if(x($ret,'finish'))
		$ret['finish'] = datetime_convert('UTC','UTC',$ret['finish']);

	return $ret;
}
*/

function format_event_bbcode($ev) {

	$o = '';

	if($ev['summary'])
		$o .= '[event-summary]' . $ev['summary'] . '[/event-summary]';

	if($ev['desc'])
		$o .= '[event-description]' . $ev['desc'] . '[/event-description]';

	if($ev['start'])
		$o .= '[event-start]' . $ev['start'] . '[/event-start]';

	if(($ev['finish']) && (! $ev['nofinish']))
		$o .= '[event-finish]' . $ev['finish'] . '[/event-finish]';

	if($ev['location'])
		$o .= '[event-location]' . $ev['location'] . '[/event-location]';

	if($ev['adjust'])
		$o .= '[event-adjust]' . $ev['adjust'] . '[/event-adjust]';


	return $o;

}

function bbtovcal($s) {
	$o = '';
	$ev = bbtoevent($s);
	if($ev['desc'])
		$o = format_event_html($ev);
	return $o;
}


function bbtoevent($s) {

	$ev = array();

	$match = '';
	if(preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is",$s,$match))
		$ev['summary'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is",$s,$match))
		$ev['desc'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is",$s,$match))
		$ev['start'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is",$s,$match))
		$ev['finish'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is",$s,$match))
		$ev['location'] = $match[1];
	$match = '';
	if(preg_match("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is",$s,$match))
		$ev['adjust'] = $match[1];
	$ev['nofinish'] = (((x($ev, 'start') && $ev['start']) && (!x($ev, 'finish') || !$ev['finish'])) ? 1 : 0);
	return $ev;

}


function sort_by_date(App &$a) {

	usort($a,'ev_compare');
	return $a;
}


function ev_compare($a,$b) {

	$date_a = (($a['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$a['start']) : $a['start']);
	$date_b = (($b['adjust']) ? datetime_convert('UTC',date_default_timezone_get(),$b['start']) : $b['start']);

	if($date_a === $date_b)
		return strcasecmp($a['desc'],$b['desc']);

	return strcmp($date_a,$date_b);
}

function event_delete($event_id) {
	if ($event_id == 0)
		return;

	q("DELETE FROM `event` WHERE `id` = %d", intval($event_id));
	logger("Deleted event ".$event_id, LOGGER_DEBUG);
}

function event_store($arr) {

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/bbcode.php');

	$a = get_app();

	$arr['created'] = (($arr['created']) ? $arr['created'] : datetime_convert());
	$arr['edited']  = (($arr['edited']) ? $arr['edited'] : datetime_convert());
	$arr['type']    = (($arr['type']) ? $arr['type'] : 'event' );
	$arr['cid']     = ((intval($arr['cid'])) ? intval($arr['cid']) : 0);
	$arr['uri']     = (x($arr,'uri') ? $arr['uri'] : item_new_uri($a->get_hostname(),$arr['uid']));
	$arr['private'] = ((x($arr,'private')) ? intval($arr['private']) : 0);
	$arr['guid']    = get_guid(32);

	if($arr['cid'])
		$c = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['cid']),
			intval($arr['uid'])
		);
	else
		$c = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
			intval($arr['uid'])
		);

	if(count($c))
		$contact = $c[0];


	// Existing event being modified

	if($arr['id']) {

		// has the event actually changed?

		$r = q("SELECT * FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if((! dbm::is_result($r)) || ($r[0]['edited'] === $arr['edited'])) {

			// Nothing has changed. Grab the item id to return.

			$r = q("SELECT * FROM `item` WHERE `event-id` = %d AND `uid` = %d LIMIT 1",
				intval($arr['id']),
				intval($arr['uid'])
			);
			return((dbm::is_result($r)) ? $r[0]['id'] : 0);
		}

		// The event changed. Update it.

		$r = q("UPDATE `event` SET
			`edited` = '%s',
			`start` = '%s',
			`finish` = '%s',
			`summary` = '%s',
			`desc` = '%s',
			`location` = '%s',
			`type` = '%s',
			`adjust` = %d,
			`nofinish` = %d
			WHERE `id` = %d AND `uid` = %d",

			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['desc']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			intval($arr['id']),
			intval($arr['uid'])
		);
		$r = q("SELECT * FROM `item` WHERE `event-id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if (dbm::is_result($r)) {
			$object = '<object><type>' . xmlify(ACTIVITY_OBJ_EVENT) . '</type><title></title><id>' . xmlify($arr['uri']) . '</id>';
			$object .= '<content>' . xmlify(format_event_bbcode($arr)) . '</content>';
			$object .= '</object>' . "\n";


			q("UPDATE `item` SET `body` = '%s', `object` = '%s', `edited` = '%s' WHERE `id` = %d AND `uid` = %d",
				dbesc(format_event_bbcode($arr)),
				dbesc($object),
				dbesc($arr['edited']),
				intval($r[0]['id']),
				intval($arr['uid'])
			);

			$item_id = $r[0]['id'];
		} else
			$item_id = 0;

		call_hooks("event_updated", $arr['id']);

		return $item_id;
	} else {

		// New event. Store it.

		$r = q("INSERT INTO `event` (`uid`,`cid`,`guid`,`uri`,`created`,`edited`,`start`,`finish`,`summary`, `desc`,`location`,`type`,
			`adjust`,`nofinish`,`allow_cid`,`allow_gid`,`deny_cid`,`deny_gid`)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' ) ",
			intval($arr['uid']),
			intval($arr['cid']),
			dbesc($arr['guid']),
			dbesc($arr['uri']),
			dbesc($arr['created']),
			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['desc']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid'])

		);

		$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($arr['uri']),
			intval($arr['uid'])
		);
		if (dbm::is_result($r))
			$event = $r[0];

		$item_arr = array();

		$item_arr['uid']           = $arr['uid'];
		$item_arr['contact-id']    = $arr['cid'];
		$item_arr['uri']           = $arr['uri'];
		$item_arr['parent-uri']    = $arr['uri'];
		$item_arr['guid']          = $arr['guid'];
		$item_arr['type']          = 'activity';
		$item_arr['wall']          = (($arr['cid']) ? 0 : 1);
		$item_arr['contact-id']    = $contact['id'];
		$item_arr['owner-name']    = $contact['name'];
		$item_arr['owner-link']    = $contact['url'];
		$item_arr['owner-avatar']  = $contact['thumb'];
		$item_arr['author-name']   = $contact['name'];
		$item_arr['author-link']   = $contact['url'];
		$item_arr['author-avatar'] = $contact['thumb'];
		$item_arr['title']         = '';
		$item_arr['allow_cid']     = $arr['allow_cid'];
		$item_arr['allow_gid']     = $arr['allow_gid'];
		$item_arr['deny_cid']      = $arr['deny_cid'];
		$item_arr['deny_gid']      = $arr['deny_gid'];
		$item_arr['private']       = $arr['private'];
		$item_arr['last-child']    = 1;
		$item_arr['visible']       = 1;
		$item_arr['verb']          = ACTIVITY_POST;
		$item_arr['object-type']   = ACTIVITY_OBJ_EVENT;
		$item_arr['origin']        = ((intval($arr['cid']) == 0) ? 1 : 0);
		$item_arr['body']          = format_event_bbcode($event);


		$item_arr['object'] = '<object><type>' . xmlify(ACTIVITY_OBJ_EVENT) . '</type><title></title><id>' . xmlify($arr['uri']) . '</id>';
		$item_arr['object'] .= '<content>' . xmlify(format_event_bbcode($event)) . '</content>';
		$item_arr['object'] .= '</object>' . "\n";

		$item_id = item_store($item_arr);

		$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($arr['uid'])
		);
		//if (dbm::is_result($r))
		//	$plink = App::get_baseurl() . '/display/' . $r[0]['nickname'] . '/' . $item_id;


		if($item_id) {
			//q("UPDATE `item` SET `plink` = '%s', `event-id` = %d  WHERE `uid` = %d AND `id` = %d",
			//	dbesc($plink),
			//	intval($event['id']),
			//	intval($arr['uid']),
			//	intval($item_id)
			//);
			q("UPDATE `item` SET `event-id` = %d  WHERE `uid` = %d AND `id` = %d",
				intval($event['id']),
				intval($arr['uid']),
				intval($item_id)
			);
		}

		call_hooks("event_created", $event['id']);

		return $item_id;
	}
}

function get_event_strings() {
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
			"allday" => t("all-day"),

			"noevent" => t("No events to display"),

			"dtstart_label" => t("Starts:"),
			"dtend_label" => t("Finishes:"),
			"location_label" => t("Location:")
		);

	return $i18n;
}

/**
 * @brief Get an event by its event ID
 * 
 * @param type $owner_uid The User ID of the owner of the event
 * @param type $event_params An assoziative array with
 *	int 'event_id' => The ID of the event in the event table
 * @param type $sql_extra
 * @return array Query result
 */
function event_by_id($owner_uid = 0, $event_params, $sql_extra = '') {
	// ownly allow events if there is a valid owner_id
	if($owner_uid == 0)
		return;

	// query for the event by event id
	$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
			`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event`
		STRAIGHT_JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
		WHERE `event`.`uid` = %d AND `event`.`id` = %d $sql_extra",
		intval($owner_uid),
		intval($event_params["event_id"])
	);

	if (dbm::is_result($r))
		return $r;

}

/**
 * @brief Get all events in a specific timeframe
 * 
 * @param int $owner_uid The User ID of the owner of the events
 * @param array $event_params An assoziative array with
 *	int 'ignored' => 
 *	string 'start' => Start time of the timeframe
 *	string 'finish' => Finish time of the timeframe
 *	string 'adjust_start' => 
 *	string 'adjust_start' =>
 *	
 * @param string $sql_extra Additional sql conditions (e.g. permission request)
 * @return array Query results
 */
function events_by_date($owner_uid = 0, $event_params, $sql_extra = '') {
	// ownly allow events if there is a valid owner_id
	if($owner_uid == 0)
		return;

	// query for the event by date
	$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
				`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event`
			STRAIGHT_JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
			WHERE `event`.`uid` = %d AND event.ignore = %d
			AND ((`adjust` = 0 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s')
			OR  (`adjust` = 1 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s'))
			$sql_extra ",
			intval($owner_uid),
			intval($event_params["ignored"]),
			dbesc($event_params["start"]),
			dbesc($event_params["start"]),
			dbesc($event_params["finish"]),
			dbesc($event_params["adjust_start"]),
			dbesc($event_params["adjust_start"]),
			dbesc($event_params["adjust_finish"])
	);

	if (dbm::is_result($r))
		return $r;
}

/**
 * @brief Convert an array query results in an arry which could be used by the events template
 * 
 * @param array $arr Event query array
 * @return array Event array for the template
 */
function process_events ($arr) {
	$events=array();

	$last_date = '';
	$fmt = t('l, F j');
	if (count($arr)) {
		foreach($arr as $rr) {

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
			$edit = ((! $rr['cid']) ? array(App::get_baseurl().'/events/event/'.$rr['id'],t('Edit event'),'','') : null);
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
				'is_first'=>$is_first,
				'item'=>$rr,
				'html'=>$html,
				'plink' => array($rr['plink'],t('link to source'),'',''),
			);
		}
	}

	return $events;
}

/**
 * @brief Format event to export format (ical/csv)
 * 
 * @param array $events Query result for events
 * @param string $format The output format (ical/csv)
 * @param string $timezone The timezone of the user (not implemented yet)
 * 
 * @return string Content according to selected export format
 */
function event_format_export ($events, $format = 'ical', $timezone) {
	if(! ((is_array($events)) && count($events)))
		return;

	switch ($format) {
		// format the exported data as a CSV file
		case "csv":
			header("Content-type: text/csv");
			$o = '"Subject", "Start Date", "Start Time", "Description", "End Date", "End Time", "Location"' . PHP_EOL;

			foreach ($events as $event) {
			/// @todo the time / date entries don't include any information about the 
			// timezone the event is scheduled in :-/
				$tmp1 = strtotime($event['start']);
				$tmp2 = strtotime($event['finish']);
				$time_format = "%H:%M:%S";
				$date_format = "%Y-%m-%d";
				$o .= '"'.$event['summary'].'", "'.strftime($date_format, $tmp1) .
					'", "'.strftime($time_format, $tmp1).'", "'.$event['desc'] .
					'", "'.strftime($date_format, $tmp2) .
					'", "'.strftime($time_format, $tmp2) . 
					'", "'.$event['location'].'"' . PHP_EOL;
			}
			break;

		// format the exported data as a ics file
		case "ical":
			header("Content-type: text/ics");
			$o = 'BEGIN:VCALENDAR'. PHP_EOL
				. 'VERSION:2.0' . PHP_EOL
				. 'PRODID:-//friendica calendar export//0.1//EN' . PHP_EOL;
			///  @todo include timezone informations in cases were the time is not in UTC
			//  see http://tools.ietf.org/html/rfc2445#section-4.8.3
			//		. 'BEGIN:VTIMEZONE' . PHP_EOL
			//		. 'TZID:' . $timezone . PHP_EOL
			//		. 'END:VTIMEZONE' . PHP_EOL;
			//  TODO instead of PHP_EOL CRLF should be used for long entries
			//       but test your solution against http://icalvalid.cloudapp.net/
			//       also long lines SHOULD be split at 75 characters length
			foreach ($events as $event) {
				if ($event['adjust'] == 1) {
					$UTC = 'Z';
				} else { 
					$UTC = '';
				}
				$o .= 'BEGIN:VEVENT' . PHP_EOL;
				if ($event[start]) {
					$tmp = strtotime($event['start']);
					$dtformat = "%Y%m%dT%H%M%S".$UTC;
					$o .= 'DTSTART:'.strftime($dtformat, $tmp).PHP_EOL;
				}
				if (!$event['nofinish']) {
					$tmp = strtotime($event['finish']);
					$dtformat = "%Y%m%dT%H%M%S".$UTC;
					$o .= 'DTEND:'.strftime($dtformat, $tmp).PHP_EOL;
				}
				if ($event['summary'])
					$tmp = $event['summary'];
					$tmp = str_replace(PHP_EOL, PHP_EOL.' ',$tmp);
					$tmp = addcslashes($tmp, ',;');
					$o .= 'SUMMARY:' . $tmp . PHP_EOL;
				if ($event['desc'])
					$tmp = $event['desc'];
					$tmp = str_replace(PHP_EOL, PHP_EOL.' ',$tmp);
					$tmp = addcslashes($tmp, ',;');
					$o .= 'DESCRIPTION:' . $tmp . PHP_EOL;
				if ($event['location']) {
					$tmp = $event['location'];
					$tmp = str_replace(PHP_EOL, PHP_EOL.' ',$tmp);
					$tmp = addcslashes($tmp, ',;');
					$o .= 'LOCATION:' . $tmp . PHP_EOL;
				}

				$o .= 'END:VEVENT' . PHP_EOL;
				$o .= PHP_EOL;
			}

			$o .= 'END:VCALENDAR' . PHP_EOL;
			break;
	}

	return $o;
}

/**
 * @brief Get all events for a user ID
 * 
 *    The query for events is done permission sensitive
 *    If the user is the owner of the calendar he/she
 *    will get all of his/her available events.
 *    If the user is only a visitor only the public events will
 *    be available
 * 
 * @param int $uid The user ID
 * @param int $sql_extra Additional sql conditions for permission
 * 
 * @return array Query results
 */
function events_by_uid($uid = 0, $sql_extra = '') {
	if($uid == 0)
		return;

	// The permission condition if no condition was transmitted
	if($sql_extra == '')
		$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' ";

	//  does the user who requests happen to be the owner of the events 
	//  requested? then show all of your events, otherwise only those that 
	//  don't have limitations set in allow_cid and allow_gid
	if (local_user() == $uid) {
		$r = q("SELECT `start`, `finish`, `adjust`, `summary`, `desc`, `location`, `nofinish`
			FROM `event` WHERE `uid`= %d AND `cid` = 0 ",
			intval($uid)
		);
	} else {
		$r = q("SELECT `start`, `finish`, `adjust`, `summary`, `desc`, `location`, `nofinish`
			FROM `event` WHERE `uid`= %d AND `cid` = 0 $sql_extra ",
			intval($uid)
		);
	}

	if (dbm::is_result($r))
		return $r;
}

/**
 * 
 * @param int $uid The user ID
 * @param string $format Output format (ical/csv)
 * @return array With the results
 *	bool 'success' => True if the processing was successful
 *	string 'format' => The output format
 *	string 'extension' => The file extension of the output format
 *	string 'content' => The formatted output content
 * 
 * @todo Respect authenticated users with events_by_uid()
 */
function event_export($uid, $format = 'ical') {

	$process = false;

	// we are allowed to show events
	// get the timezone the user is in
	$r = q("SELECT `timezone` FROM `user` WHERE `uid` = %d LIMIT 1", intval($uid));
	if (dbm::is_result($r))
		$timezone = $r[0]['timezone'];

	// get all events which are owned by a uid (respects permissions);
	$events = events_by_uid($uid);

	//  we have the events that are available for the requestor
	//  now format the output according to the requested format
	if(count($events))
		$res = event_format_export($events, $format, $timezone);

	// If there are results the precess was successfull
	if(x($res))
		$process = true;

	// get the file extension for the format
	switch ($format) {
		case "ical":
			$file_ext = "ics";
			break;

		case "csv":
			$file_ext = "csv";
			break;

		default:
			$file_ext = "";
	}

	$arr = array(
		'success' => $process,
		'format' => $format,
		'extension' => $file_ext,
		'content' => $res,
	);

	return $arr;
}

/**
 * @brief Get the events widget
 * 
 * @return string Formated html of the evens widget
 */
function widget_events() {
	$a = get_app();

	$owner_uid = $a->data['user']['uid'];
	// $a->data is only available if the profile page is visited. If the visited page is not part
	// of the profile page it should be the personal /events page. So we can use $a->user
	$user = ($a->data['user']['nickname'] ? $a->data['user']['nickname'] : $a->user['nickname']);


	// The permission testing is a little bit tricky because we have to respect many cases

	// It's not the private events page (we don't get the $owner_uid for /events)
	if(! local_user() && ! $owner_uid)
		return;

	// Cal logged in user (test permission at foreign profile page)
	// If the $owner uid is available we know it is part of one of the profile pages (like /cal)
	// So we have to test if if it's the own profile page of the logged in user 
	// or a foreign one. For foreign profile pages we need to check if the feature
	// for exporting the cal is enabled (otherwise the widget would appear for logged in users
	// on foreigen profile pages even if the widget is disabled)
	if(intval($owner_uid) && local_user() !== $owner_uid && ! feature_enabled($owner_uid, "export_calendar")) 
		return;

	// If it's a kind of profile page (intval($owner_uid)) return if the user not logged in and
	// export feature isn't enabled
	if(intval($owner_uid) && ! local_user() && ! feature_enabled($owner_uid, "export_calendar"))
		return;

	return replace_macros(get_markup_template("events_aside.tpl"), array(
		'$etitle' => t("Export"),
		'$export_ical' => t("Export calendar as ical"),
		'$export_csv' => t("Export calendar as csv"),
		'$user' => $user
	));

}
