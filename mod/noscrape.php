<?php
/**
 * @file mod/noscrape.php
 */

use Friendica\App;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Registry\App as A;

function noscrape_init(App $a)
{
	if ($a->argc > 1) {
		$which = $a->argv[1];
	} else {
		exit();
	}

	$profile = 0;
	if ((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which = $a->user['nickname'];
		$profile = $a->argv[1];
	}

	Profile::load($a, $which, $profile);

	$json_info = [
		'addr'         => $a->profile['addr'],
		'nick'         => $which,
		'guid'         => $a->profile['guid'],
		'key'          => $a->profile['pubkey'],
		'homepage'     => A::baseUrl() . "/profile/{$which}",
		'comm'         => ($a->profile['account-type'] == User::ACCOUNT_TYPE_COMMUNITY),
		'account-type' => $a->profile['account-type'],
	];

	$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
	foreach ($dfrn_pages as $dfrn) {
		$json_info["dfrn-{$dfrn}"] = A::baseUrl() . "/dfrn_{$dfrn}/{$which}";
	}

	if (!$a->profile['net-publish'] || $a->profile['hidewall']) {
		header('Content-type: application/json; charset=utf-8');
		$json_info["hide"] = true;
		echo json_encode($json_info);
		exit;
	}

	$keywords = $a->profile['pub_keywords'] ?? '';
	$keywords = str_replace(['#',',',' ',',,'], ['',' ',',',','], $keywords);
	$keywords = explode(',', $keywords);

	$contactPhoto = DBA::selectFirst('contact', ['photo'], ['self' => true, 'uid' => $a->profile['uid']]);

	$json_info['fn'] = $a->profile['name'];
	$json_info['photo'] = $contactPhoto["photo"];
	$json_info['tags'] = $keywords;
	$json_info['language'] = $a->profile['language'];

	if (is_array($a->profile) && !$a->profile['hide-friends']) {
		/// @todo What should this value tell us?
		$r = q("SELECT `gcontact`.`updated` FROM `contact` INNER JOIN `gcontact` WHERE `gcontact`.`nurl` = `contact`.`nurl` AND `self` AND `uid` = %d LIMIT 1",
			intval($a->profile['uid']));
		if (DBA::isResult($r)) {
			$json_info["updated"] =  date("c", strtotime($r[0]['updated']));
		}

		$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE `uid` = %d AND `self` = 0 AND `blocked` = 0 and `pending` = 0 AND `hidden` = 0 AND `archive` = 0
				AND `network` IN ('%s', '%s', '%s', '')",
			intval($a->profile['uid']),
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::OSTATUS)
		);
		if (DBA::isResult($r)) {
			$json_info["contacts"] = intval($r[0]['total']);
		}
	}

	// We display the last activity (post or login), reduced to year and week number
	$last_active = 0;
	$condition = ['uid' => $a->profile['uid'], 'self' => true];
	$contact = DBA::selectFirst('contact', ['last-item'], $condition);
	if (DBA::isResult($contact)) {
		$last_active = strtotime($contact['last-item']);
	}

	$condition = ['uid' => $a->profile['uid']];
	$user = DBA::selectFirst('user', ['login_date'], $condition);
	if (DBA::isResult($user)) {
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

	//Output all the JSON!
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($json_info);
	exit;
}
