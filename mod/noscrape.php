<?php

function noscrape_init(App &$a) {

	if($a->argc > 1)
		$which = $a->argv[1];
	else
		killme();

	$profile = 0;
	if((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];
	}

	profile_load($a,$which,$profile);

	if (!$a->profile['net-publish'] OR $a->profile['hidewall']) {
		header('Content-type: application/json; charset=utf-8');
		$json_info = array("hide" => true);
		echo json_encode($json_info);
		exit;
	}

	$keywords = ((x($a->profile,'pub_keywords')) ? $a->profile['pub_keywords'] : '');
	$keywords = str_replace(array('#',',',' ',',,'),array('',' ',',',','),$keywords);
	$keywords = explode(',', $keywords);

	/// @TODO This query's result is not being used (see below), maybe old-lost code?
	$r = q("SELECT `photo` FROM `contact` WHERE `self` AND `uid` = %d",
		intval($a->profile['uid']));

	$json_info = array(
		'fn' => $a->profile['name'],
		'addr' => $a->profile['addr'],
		'nick' => $which,
		'key' => $a->profile['pubkey'],
		'homepage' => App::get_baseurl()."/profile/{$which}",
		'comm' => (x($a->profile,'page-flags')) && ($a->profile['page-flags'] == PAGE_COMMUNITY),
		'photo' => $r[0]["photo"],
		'tags' => $keywords
	);

	if(is_array($a->profile) AND !$a->profile['hide-friends']) {
		$r = q("SELECT `gcontact`.`updated` FROM `contact` INNER JOIN `gcontact` WHERE `gcontact`.`nurl` = `contact`.`nurl` AND `self` AND `uid` = %d LIMIT 1",
			intval($a->profile['uid']));
		if (dbm::is_result($r))
			$json_info["updated"] =  date("c", strtotime($r[0]['updated']));

		$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 AND `hidden` = 0 AND `archive` = 0
				AND `network` IN ('%s', '%s', '%s', '')",
			intval($a->profile['uid']),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS)
		);
		if (dbm::is_result($r))
			$json_info["contacts"] = intval($r[0]['total']);
	}

	//These are optional fields.
	$profile_fields = array('pdesc', 'locality', 'region', 'postal-code', 'country-name', 'gender', 'marital', 'about');
	foreach($profile_fields as $field) {
		if(!empty($a->profile[$field])) {
			$json_info["$field"] = $a->profile[$field];
		}
	}

	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach($dfrn_pages as $dfrn) {
		$json_info["dfrn-{$dfrn}"] = App::get_baseurl()."/dfrn_{$dfrn}/{$which}";
	}

	//Output all the JSON!
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($json_info);
	exit;

}
