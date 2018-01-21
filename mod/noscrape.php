<?php
/**
 * @file mod/noscrape.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Profile;

function noscrape_init(App $a)
{
	if ($a->argc > 1) {
		$which = $a->argv[1];
	} else {
		killme();
	}

	$profile = 0;
	if ((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];
	}

	Profile::load($a, $which, $profile);

	if (!$a->profile['net-publish'] || $a->profile['hidewall']) {
		header('Content-type: application/json; charset=utf-8');
		$json_info = ["hide" => true];
		echo json_encode($json_info);
		exit;
	}

	$keywords = ((x($a->profile, 'pub_keywords')) ? $a->profile['pub_keywords'] : '');
	$keywords = str_replace(['#',',',' ',',,'], ['',' ',',',','], $keywords);
	$keywords = explode(',', $keywords);

	$contactPhoto = dba::selectFirst('contact', ['photo'], ['self' => true, 'uid' => $a->profile['uid']]);

	$json_info = [
		'fn'       => $a->profile['name'],
		'addr'     => $a->profile['addr'],
		'nick'     => $which,
		'guid'     => $a->profile['guid'],
		'key'      => $a->profile['pubkey'],
		'homepage' => System::baseUrl()."/profile/{$which}",
		'comm'     => (x($a->profile, 'page-flags')) && ($a->profile['page-flags'] == PAGE_COMMUNITY),
		'photo'    => $contactPhoto["photo"],
		'tags'     => $keywords
	];

	if (is_array($a->profile) && !$a->profile['hide-friends']) {
		/// @todo What should this value tell us?
		$r = q("SELECT `gcontact`.`updated` FROM `contact` INNER JOIN `gcontact` WHERE `gcontact`.`nurl` = `contact`.`nurl` AND `self` AND `uid` = %d LIMIT 1",
			intval($a->profile['uid']));
		if (DBM::isResult($r)) {
			$json_info["updated"] =  date("c", strtotime($r[0]['updated']));
		}

		$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 AND `hidden` = 0 AND `archive` = 0
				AND `network` IN ('%s', '%s', '%s', '')",
			intval($a->profile['uid']),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS)
		);
		if (DBM::isResult($r)) {
			$json_info["contacts"] = intval($r[0]['total']);
		}
	}

	// We display the last activity (post or login), reduced to year and week number
	$last_active = 0;
	$condition = ['uid' => $a->profile['uid'], 'self' => true];
	$contact = dba::selectFirst('contact', ['last-item'], $condition);
	if (DBM::isResult($contact)) {
		$last_active = strtotime($contact['last-item']);
	}

	$condition = ['uid' => $a->profile['uid']];
	$user = dba::selectFirst('user', ['login_date'], $condition);
	if (DBM::isResult($user)) {
		if ($last_active < strtotime($user['login_date'])) {
			$last_active = strtotime($user['login_date']);
		}
	}
	$json_info["last-activity"] = date("o-W", $last_active);

	//These are optional fields.
	$profile_fields = ['pdesc', 'locality', 'region', 'postal-code', 'country-name', 'gender', 'marital', 'about'];
	foreach ($profile_fields as $field) {
		if (!empty($a->profile[$field])) {
			$json_info["$field"] = $a->profile[$field];
		}
	}

	$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
	foreach ($dfrn_pages as $dfrn) {
		$json_info["dfrn-{$dfrn}"] = System::baseUrl()."/dfrn_{$dfrn}/{$which}";
	}

	//Output all the JSON!
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($json_info);
	exit;
}
