<?php

require_once('include/bbcode.php');
require_once('include/map.php');

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

		if (strpos($ev['location'], "[map")===False) {
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
	require_once('library/HTMLPurifier.auto.php');
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


function sort_by_date($a) {

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
		if((! count($r)) || ($r[0]['edited'] === $arr['edited'])) {

			// Nothing has changed. Grab the item id to return.

			$r = q("SELECT * FROM `item` WHERE `event-id` = %d AND `uid` = %d LIMIT 1",
				intval($arr['id']),
				intval($arr['uid'])
			);
			return((count($r)) ? $r[0]['id'] : 0);
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
			`nofinish` = %d,
			`allow_cid` = '%s',
			`allow_gid` = '%s',
			`deny_cid` = '%s',
			`deny_gid` = '%s'
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
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid']),
			intval($arr['id']),
			intval($arr['uid'])
		);
		$r = q("SELECT * FROM `item` WHERE `event-id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if(count($r)) {
			$object = '<object><type>' . xmlify(ACTIVITY_OBJ_EVENT) . '</type><title></title><id>' . xmlify($arr['uri']) . '</id>';
			$object .= '<content>' . xmlify(format_event_bbcode($arr)) . '</content>';
			$object .= '</object>' . "\n";


			q("UPDATE `item` SET `body` = '%s', `object` = '%s', `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s', `edited` = '%s', `private` = %d WHERE `id` = %d AND `uid` = %d",
				dbesc(format_event_bbcode($arr)),
				dbesc($object),
				dbesc($arr['allow_cid']),
				dbesc($arr['allow_gid']),
				dbesc($arr['deny_cid']),
				dbesc($arr['deny_gid']),
				dbesc($arr['edited']),
				intval($arr['private']),
				intval($r[0]['id']),
				intval($arr['uid'])
			);

			$item_id = $r[0]['id'];
		}
		else
			$item_id = 0;

		call_hooks("event_updated", $arr['id']);

		return $item_id;
	}
	else {

		// New event. Store it.

		$r = q("INSERT INTO `event` ( `uid`,`cid`,`uri`,`created`,`edited`,`start`,`finish`,`summary`, `desc`,`location`,`type`,
			`adjust`,`nofinish`,`allow_cid`,`allow_gid`,`deny_cid`,`deny_gid`)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' ) ",
			intval($arr['uid']),
			intval($arr['cid']),
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
		if(count($r))
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
		//if(count($r))
		//	$plink = $a->get_baseurl() . '/display/' . $r[0]['nickname'] . '/' . $item_id;


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
