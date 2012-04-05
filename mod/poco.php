<?php

function poco_init(&$a) {

	$system_mode = false;

	if(intval(get_config('system','block_public')))
		http_status_exit(401);


	if($a->argc > 1) {
		$user = notags(trim($a->argv[1]));
	}
	if(! x($user)) {
		$c = q("select * from pconfig where cat = 'system' and k = 'suggestme' and v = 1");
		if(! count($c))
			http_status_exit(401);
		$system_mode = true;
	}

	$format = (($_GET['format']) ? $_GET['format'] : 'json');

	$justme = false;

	if($a->argc > 2 && $a->argv[2] === '@me')
		$justme = true;
	if($a->argc > 3 && $a->argv[3] === '@all')
		$justme = false;
	if($a->argc > 3 && $a->argv[3] === '@self')
		$justme = true;
	if($a->argc > 4 && intval($a->argv[4]) && $justme == false)
		$cid = intval($a->argv[4]);
 		

	if(! $system_mode) {
		$r = q("SELECT `user`.*,`profile`.`hide-friends` from user left join profile on `user`.`uid` = `profile`.`uid`
			where `user`.`nickname` = '%s' and `profile`.`is-default` = 1 limit 1",
			dbesc($user)
		);
		if(! count($r) || $r[0]['hidewall'] || $r[0]['hide-friends'])
			http_status_exit(404);

		$user = $r[0];
	}

	if($justme)
		$sql_extra = " and `contact`.`self` = 1 ";
	else
		$sql_extra = " and `contact`.`self` = 0 ";

	if($cid)
		$sql_extra = sprintf(" and `contact`.`id` = %d ",intval($cid));

	if($system_mode) {
		$r = q("SELECT count(*) as `total` from `contact` where self = 1 
			and uid in (select uid from pconfig where cat = 'system' and k = 'suggestme' and v = 1) ");
	}
	else {
		$r = q("SELECT count(*) as `total` from `contact` where `uid` = %d and blocked = 0 and pending = 0 and hidden = 0
			$sql_extra ",
			intval($user['uid'])
		);
	}
	if(count($r))
		$totalResults = intval($r[0]['total']);
	else
		$totalResults = 0;

	$startIndex = intval($_GET['startIndex']);
	if(! $startIndex)
		$startIndex = 0;
	$itemsPerPage = ((x($_GET,'count') && intval($_GET['count'])) ? intval($_GET['count']) : $totalResults);


	if($system_mode) {
		$r = q("SELECT * from contact where self = 1 
			and uid in (select uid from pconfig where cat = 'system' and k = 'suggestme' and v = 1) limit %d, %d ",
			intval($startIndex),
			intval($itemsPerPage)
		);
	}
	else {

		$r = q("SELECT * from `contact` where `uid` = %d and blocked = 0 and pending = 0 and hidden = 0
			$sql_extra LIMIT %d, %d",
			intval($user['uid']),
			intval($startIndex),
			intval($itemsPerPage)
		);
	}
	$ret = array();
	if(x($_GET,'sorted'))
		$ret['sorted'] = 'false';
	if(x($_GET,'filtered'))
		$ret['filtered'] = 'false';
	if(x($_GET,'updatedSince'))
		$ret['updateSince'] = 'false';

	$ret['startIndex']   = (string) $startIndex;
	$ret['itemsPerPage'] = (string) $itemsPerPage;
	$ret['totalResults'] = (string) $totalResults;
	$ret['entry']        = array();


	$fields_ret = array(
		'id' => false,
		'displayName' => false,
		'urls' => false,
		'preferredUsername' => false,
		'photos' => false
	);

	if((! x($_GET,'fields')) || ($_GET['fields'] === '@all'))
		foreach($fields_ret as $k => $v)
			$fields_ret[$k] = true;
	else {
		$fields_req = explode(',',$_GET['fields']);
		foreach($fields_req as $f)
			$fields_ret[trim($f)] = true;
	}

	if(is_array($r)) {
		if(count($r)) {
			foreach($r as $rr) {
				$entry = array();
				if($fields_ret['id'])
					$entry['id'] = $rr['id'];
				if($fields_ret['displayName'])
					$entry['displayName'] = $rr['name'];
				if($fields_ret['urls']) {
					$entry['urls'] = array(array('value' => $rr['url'], 'type' => 'profile'));
					if($rr['addr'] && ($rr['network'] !== NETWORK_MAIL))
						$entry['urls'][] = array('value' => 'acct:' . $rr['addr'], 'type' => 'webfinger');  
				}
				if($fields_ret['preferredUsername'])
					$entry['preferredUsername'] = $rr['nick'];
				if($fields_ret['photos'])
					$entry['photos'] = array(array('value' => $rr['photo'], 'type' => 'profile'));
				$ret['entry'][] = $entry;
			}
		}
		else
			$ret['entry'][] = array();
	}
	else
		http_status_exit(500);

	if($format === 'xml') {
		header('Content-type: text/xml');
		echo replace_macros(get_markup_template('poco_xml.tpl'),array_xmlify(array('$response' => $ret)));
		http_status_exit(500);
	}
	if($format === 'json') {
		header('Content-type: application/json');
		echo json_encode($ret);
		killme();	
	}
	else
		http_status_exit(500);


}