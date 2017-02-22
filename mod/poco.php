<?php
// See here for a documentation for portable contacts:
// https://web.archive.org/web/20160405005550/http://portablecontacts.net/draft-spec.html

function poco_init(App $a) {
	require_once("include/bbcode.php");

	$system_mode = false;

	if(intval(get_config('system','block_public')) || (get_config('system','block_local_dir')))
		http_status_exit(401);


	if($a->argc > 1) {
		$user = notags(trim($a->argv[1]));
	}
	if(! x($user)) {
		$c = q("SELECT * FROM `pconfig` WHERE `cat` = 'system' AND `k` = 'suggestme' AND `v` = 1");
		if (! dbm::is_result($c)) {
			http_status_exit(401);
		}
		$system_mode = true;
	}

	$format = (($_GET['format']) ? $_GET['format'] : 'json');

	$justme = false;
	$global = false;

	if($a->argc > 1 && $a->argv[1] === '@global') {
		$global = true;
		$update_limit = date("Y-m-d H:i:s", time() - 30 * 86400);
	}
	if($a->argc > 2 && $a->argv[2] === '@me')
		$justme = true;
	if($a->argc > 3 && $a->argv[3] === '@all')
		$justme = false;
	if($a->argc > 3 && $a->argv[3] === '@self')
		$justme = true;
	if($a->argc > 4 && intval($a->argv[4]) && $justme == false)
		$cid = intval($a->argv[4]);


	if(!$system_mode AND !$global) {
		$r = q("SELECT `user`.*,`profile`.`hide-friends` from user left join profile on `user`.`uid` = `profile`.`uid`
			where `user`.`nickname` = '%s' and `profile`.`is-default` = 1 limit 1",
			dbesc($user)
		);
		if(! dbm::is_result($r) || $r[0]['hidewall'] || $r[0]['hide-friends'])
			http_status_exit(404);

		$user = $r[0];
	}

	if($justme)
		$sql_extra = " AND `contact`.`self` = 1 ";
//	else
//		$sql_extra = " AND `contact`.`self` = 0 ";

	if($cid)
		$sql_extra = sprintf(" AND `contact`.`id` = %d ",intval($cid));

	if(x($_GET,'updatedSince'))
		$update_limit =  date("Y-m-d H:i:s",strtotime($_GET['updatedSince']));

	if ($global) {
		$r = q("SELECT count(*) AS `total` FROM `gcontact` WHERE `updated` >= '%s' AND `updated` >= `last_failure` AND NOT `hide` AND `network` IN ('%s', '%s', '%s')",
			dbesc($update_limit),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS)
		);
	} elseif($system_mode) {
		$r = q("SELECT count(*) AS `total` FROM `contact` WHERE `self` = 1
			AND `uid` IN (SELECT `uid` FROM `pconfig` WHERE `cat` = 'system' AND `k` = 'suggestme' AND `v` = 1) ");
	} else {
		$r = q("SELECT count(*) AS `total` FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 AND `archive` = 0
			AND (`success_update` >= `failure_update` OR `last-item` >= `failure_update`)
			AND `network` IN ('%s', '%s', '%s', '%s') $sql_extra",
			intval($user['uid']),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS),
			dbesc(NETWORK_STATUSNET)
		);
	}
	if (dbm::is_result($r))
		$totalResults = intval($r[0]['total']);
	else
		$totalResults = 0;

	$startIndex = intval($_GET['startIndex']);
	if(! $startIndex)
		$startIndex = 0;
	$itemsPerPage = ((x($_GET,'count') && intval($_GET['count'])) ? intval($_GET['count']) : $totalResults);

	if ($global) {
		logger("Start global query", LOGGER_DEBUG);
		$r = q("SELECT * FROM `gcontact` WHERE `updated` > '%s' AND NOT `hide` AND `network` IN ('%s', '%s', '%s') AND `updated` > `last_failure`
			ORDER BY `updated` DESC LIMIT %d, %d",
			dbesc($update_limit),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS),
			intval($startIndex),
			intval($itemsPerPage)
		);
	} elseif($system_mode) {
		logger("Start system mode query", LOGGER_DEBUG);
		$r = q("SELECT `contact`.*, `profile`.`about` AS `pabout`, `profile`.`locality` AS `plocation`, `profile`.`pub_keywords`,
				`profile`.`gender` AS `pgender`, `profile`.`address` AS `paddress`, `profile`.`region` AS `pregion`,
				`profile`.`postal-code` AS `ppostalcode`, `profile`.`country-name` AS `pcountry`, `user`.`account-type`
			FROM `contact` INNER JOIN `profile` ON `profile`.`uid` = `contact`.`uid`
				INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `self` = 1 AND `profile`.`is-default`
			AND `contact`.`uid` IN (SELECT `uid` FROM `pconfig` WHERE `cat` = 'system' AND `k` = 'suggestme' AND `v` = 1) LIMIT %d, %d",
			intval($startIndex),
			intval($itemsPerPage)
		);
	} else {
		logger("Start query for user ".$user['nickname'], LOGGER_DEBUG);
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `hidden` = 0 AND `archive` = 0
			AND (`success_update` >= `failure_update` OR `last-item` >= `failure_update`)
			AND `network` IN ('%s', '%s', '%s', '%s') $sql_extra LIMIT %d, %d",
			intval($user['uid']),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS),
			dbesc(NETWORK_STATUSNET),
			intval($startIndex),
			intval($itemsPerPage)
		);
	}
	logger("Query done", LOGGER_DEBUG);

	$ret = array();
	if(x($_GET,'sorted'))
		$ret['sorted'] = false;
	if(x($_GET,'filtered'))
		$ret['filtered'] = false;
	if(x($_GET,'updatedSince') AND !$global)
		$ret['updatedSince'] = false;

	$ret['startIndex']   = (int) $startIndex;
	$ret['itemsPerPage'] = (int) $itemsPerPage;
	$ret['totalResults'] = (int) $totalResults;
	$ret['entry']        = array();


	$fields_ret = array(
		'id' => false,
		'displayName' => false,
		'urls' => false,
		'updated' => false,
		'preferredUsername' => false,
		'photos' => false,
		'aboutMe' => false,
		'currentLocation' => false,
		'network' => false,
		'gender' => false,
		'tags' => false,
		'address' => false,
		'contactType' => false,
		'generation' => false
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
		if (dbm::is_result($r)) {
			foreach ($r as $rr) {
				if (!isset($rr['generation'])) {
					if ($global)
						$rr['generation'] = 3;
					elseif ($system_mode)
						$rr['generation'] = 1;
					else
						$rr['generation'] = 2;
				}

				if (($rr['about'] == "") AND isset($rr['pabout']))
					$rr['about'] = $rr['pabout'];

				if ($rr['location'] == "") {
					if (isset($rr['plocation']))
						$rr['location'] = $rr['plocation'];

					if (isset($rr['pregion']) AND ($rr['pregion'] != "")) {
						if ($rr['location'] != "")
							$rr['location'] .= ", ";

						$rr['location'] .= $rr['pregion'];
					}

					if (isset($rr['pcountry']) AND ($rr['pcountry'] != "")) {
						if ($rr['location'] != "")
							$rr['location'] .= ", ";

						$rr['location'] .= $rr['pcountry'];
					}
				}

				if (($rr['gender'] == "") AND isset($rr['pgender']))
					$rr['gender'] = $rr['pgender'];

				if (($rr['keywords'] == "") AND isset($rr['pub_keywords']))
					$rr['keywords'] = $rr['pub_keywords'];

				if (isset($rr['account-type']))
					$rr['contact-type'] = $rr['account-type'];

				$about = Cache::get("about:".$rr['updated'].":".$rr['nurl']);
				if (is_null($about)) {
					$about = bbcode($rr['about'], false, false);
					Cache::set("about:".$rr['updated'].":".$rr['nurl'],$about);
				}

				// Non connected persons can only see the keywords of a Diaspora account
				if ($rr['network'] == NETWORK_DIASPORA) {
					$rr['location'] = "";
					$about = "";
					$rr['gender'] = "";
				}

				$entry = array();
				if($fields_ret['id'])
					$entry['id'] = (int)$rr['id'];
				if($fields_ret['displayName'])
					$entry['displayName'] = $rr['name'];
				if($fields_ret['aboutMe'])
					$entry['aboutMe'] = $about;
				if($fields_ret['currentLocation'])
					$entry['currentLocation'] = $rr['location'];
				if($fields_ret['gender'])
					$entry['gender'] = $rr['gender'];
				if($fields_ret['generation'])
					$entry['generation'] = (int)$rr['generation'];
				if($fields_ret['urls']) {
					$entry['urls'] = array(array('value' => $rr['url'], 'type' => 'profile'));
					if($rr['addr'] && ($rr['network'] !== NETWORK_MAIL))
						$entry['urls'][] = array('value' => 'acct:' . $rr['addr'], 'type' => 'webfinger');
				}
				if($fields_ret['preferredUsername'])
					$entry['preferredUsername'] = $rr['nick'];
				if($fields_ret['updated']) {
					if (!$global) {
						$entry['updated'] = $rr['success_update'];

						if ($rr['name-date'] > $entry['updated'])
							$entry['updated'] = $rr['name-date'];

						if ($rr['uri-date'] > $entry['updated'])
							$entry['updated'] = $rr['uri-date'];

						if ($rr['avatar-date'] > $entry['updated'])
							$entry['updated'] = $rr['avatar-date'];
					} else
						$entry['updated'] = $rr['updated'];

					$entry['updated'] = date("c", strtotime($entry['updated']));
				}
				if($fields_ret['photos'])
					$entry['photos'] = array(array('value' => $rr['photo'], 'type' => 'profile'));
				if($fields_ret['network']) {
					$entry['network'] = $rr['network'];
					if ($entry['network'] == NETWORK_STATUSNET)
						$entry['network'] = NETWORK_OSTATUS;
					if (($entry['network'] == "") AND ($rr['self']))
						$entry['network'] = NETWORK_DFRN;
				}
				if($fields_ret['tags']) {
					$tags = str_replace(","," ",$rr['keywords']);
					$tags = explode(" ", $tags);

					$cleaned = array();
					foreach ($tags as $tag) {
						$tag = trim(strtolower($tag));
						if ($tag != "")
							$cleaned[] = $tag;
					}

					$entry['tags'] = array($cleaned);
				}
				if($fields_ret['address']) {
					$entry['address'] = array();

					// Deactivated. It just reveals too much data. (Although its from the default profile)
					//if (isset($rr['paddress']))
					//	 $entry['address']['streetAddress'] = $rr['paddress'];

					if (isset($rr['plocation']))
						 $entry['address']['locality'] = $rr['plocation'];

					if (isset($rr['pregion']))
						 $entry['address']['region'] = $rr['pregion'];

					// See above
					//if (isset($rr['ppostalcode']))
					//	 $entry['address']['postalCode'] = $rr['ppostalcode'];

					if (isset($rr['pcountry']))
						 $entry['address']['country'] = $rr['pcountry'];
				}

				if($fields_ret['contactType'])
					$entry['contactType'] = intval($rr['contact-type']);

				$ret['entry'][] = $entry;
			}
		}
		else
			$ret['entry'][] = array();
	}
	else
		http_status_exit(500);

	logger("End of poco", LOGGER_DEBUG);

	if($format === 'xml') {
		header('Content-type: text/xml');
		echo replace_macros(get_markup_template('poco_xml.tpl'),array_xmlify(array('$response' => $ret)));
		killme();
	}
	if($format === 'json') {
		header('Content-type: application/json');
		echo json_encode($ret);
		killme();
	}
	else
		http_status_exit(500);


}
