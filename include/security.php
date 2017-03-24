<?php

/**
 * @brief Calculate the hash that is needed for the "Friendica" cookie
 *
 * @param array $user Record from "user" table
 *
 * @return string Hashed data
 */
function cookie_hash($user) {
	return(hash("sha256", get_config("system", "site_prvkey").
				$user["uprvkey"].
				$user["password"]));
}

/**
 * @brief Set the "Friendica" cookie
 *
 * @param int $time
 * @param array $user Record from "user" table
 */
function new_cookie($time, $user = array()) {

	if ($time != 0) {
		$time = $time + time();
	}

	if ($user) {
		$value = json_encode(array("uid" => $user["uid"],
					"hash" => cookie_hash($user),
					"ip" => $_SERVER['REMOTE_ADDR']));
	}
	else {
		$value = "";
	}

	setcookie("Friendica", $value, $time, "/", "",
		(get_config('system', 'ssl_policy') == SSL_POLICY_FULL), true);

}

function authenticate_success($user_record, $login_initial = false, $interactive = false, $login_refresh = false) {

	$a = get_app();

	$_SESSION['uid'] = $user_record['uid'];
	$_SESSION['theme'] = $user_record['theme'];
	$_SESSION['mobile-theme'] = get_pconfig($user_record['uid'], 'system', 'mobile_theme');
	$_SESSION['authenticated'] = 1;
	$_SESSION['page_flags'] = $user_record['page-flags'];
	$_SESSION['my_url'] = App::get_baseurl() . '/profile/' . $user_record['nickname'];
	$_SESSION['my_address'] = $user_record['nickname'] . '@' . substr(App::get_baseurl(),strpos(App::get_baseurl(),'://')+3);
	$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];

	$a->user = $user_record;

	if($interactive) {
		if ($a->user['login_date'] <= NULL_DATE) {
			$_SESSION['return_url'] = 'profile_photo/new';
			$a->module = 'profile_photo';
			info( t("Welcome ") . $a->user['username'] . EOL);
			info( t('Please upload a profile photo.') . EOL);
		}
		else
			info( t("Welcome back ") . $a->user['username'] . EOL);
	}

	$member_since = strtotime($a->user['register_date']);
	if(time() < ($member_since + ( 60 * 60 * 24 * 14)))
		$_SESSION['new_member'] = true;
	else
		$_SESSION['new_member'] = false;
	if(strlen($a->user['timezone'])) {
		date_default_timezone_set($a->user['timezone']);
		$a->timezone = $a->user['timezone'];
	}

	$master_record = $a->user;

	if((x($_SESSION,'submanage')) && intval($_SESSION['submanage'])) {
		$r = q("select * from user where uid = %d limit 1",
			intval($_SESSION['submanage'])
		);
		if (dbm::is_result($r))
			$master_record = $r[0];
	}

	$r = q("SELECT `uid`,`username`,`nickname` FROM `user` WHERE `password` = '%s' AND `email` = '%s' AND `account_removed` = 0 ",
		dbesc($master_record['password']),
		dbesc($master_record['email'])
	);
	if (dbm::is_result($r))
		$a->identities = $r;
	else
		$a->identities = array();

	$r = q("select `user`.`uid`, `user`.`username`, `user`.`nickname`
		from manage INNER JOIN user on manage.mid = user.uid where `user`.`account_removed` = 0
		and `manage`.`uid` = %d",
		intval($master_record['uid'])
	);
	if (dbm::is_result($r))
		$a->identities = array_merge($a->identities,$r);

	if($login_initial)
		logger('auth_identities: ' . print_r($a->identities,true), LOGGER_DEBUG);
	if($login_refresh)
		logger('auth_identities refresh: ' . print_r($a->identities,true), LOGGER_DEBUG);

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval($_SESSION['uid']));
	if (dbm::is_result($r)) {
		$a->contact = $r[0];
		$a->cid = $r[0]['id'];
		$_SESSION['cid'] = $a->cid;
	}

	header('X-Account-Management-Status: active; name="' . $a->user['username'] . '"; id="' . $a->user['nickname'] .'"');

	if($login_initial || $login_refresh) {

		q("UPDATE `user` SET `login_date` = '%s' WHERE `uid` = %d",
			dbesc(datetime_convert()),
			intval($_SESSION['uid'])
		);

		// Set the login date for all identities of the user
		q("UPDATE `user` SET `login_date` = '%s' WHERE `password` = '%s' AND `email` = '%s' AND `account_removed` = 0",
			dbesc(datetime_convert()),
			dbesc($master_record['password']),
			dbesc($master_record['email'])
		);


	}

	if ($login_initial) {
		// If the user specified to remember the authentication, then set a cookie
		// that expires after one week (the default is when the browser is closed).
		// The cookie will be renewed automatically.
		// The week ensures that sessions will expire after some inactivity.
		if ($_SESSION['remember']) {
			logger('Injecting cookie for remembered user '. $_SESSION['remember_user']['nickname']);
			new_cookie(604800, $user_record);
			unset($_SESSION['remember']);
		}
	}



	if ($login_initial) {
		call_hooks('logged_in', $a->user);

		if (($a->module !== 'home') && isset($_SESSION['return_url'])) {
			goaway(App::get_baseurl() . '/' . $_SESSION['return_url']);
		}
	}

}



function can_write_wall(App $a, $owner) {

	static $verified = 0;

	if ((! (local_user())) && (! (remote_user()))) {
		return false;
	}

	$uid = local_user();

	if (($uid) && ($uid == $owner)) {
		return true;
	}

	if (remote_user()) {

		// use remembered decision and avoid a DB lookup for each and every display item
		// DO NOT use this function if there are going to be multiple owners

		// We have a contact-id for an authenticated remote user, this block determines if the contact
		// belongs to this page owner, and has the necessary permissions to post content

		if ($verified === 2) {
			return true;
		} elseif ($verified === 1) {
			return false;
		} else {
			$cid = 0;

			if (is_array($_SESSION['remote'])) {
				foreach ($_SESSION['remote'] as $visitor) {
					if ($visitor['uid'] == $owner) {
						$cid = $visitor['cid'];
						break;
					}
				}
			}

			if (! $cid) {
				return false;
			}

			$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` INNER JOIN `user` on `user`.`uid` = `contact`.`uid`
				WHERE `contact`.`uid` = %d AND `contact`.`id` = %d AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
				AND `user`.`blockwall` = 0 AND `readonly` = 0  AND ( `contact`.`rel` IN ( %d , %d ) OR `user`.`page-flags` = %d ) LIMIT 1",
				intval($owner),
				intval($cid),
				intval(CONTACT_IS_SHARING),
				intval(CONTACT_IS_FRIEND),
				intval(PAGE_COMMUNITY)
			);

			if (dbm::is_result($r)) {
				$verified = 2;
				return true;
			}
			else {
				$verified = 1;
			}
		}
	}

	return false;
}


function permissions_sql($owner_id,$remote_verified = false,$groups = null) {

	$local_user = local_user();
	$remote_user = remote_user();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	$sql = " AND allow_cid = ''
			 AND allow_gid = ''
			 AND deny_cid  = ''
			 AND deny_gid  = ''
	";

	/**
	 * Profile owner - everything is visible
	 */

	if(($local_user) && ($local_user == $owner_id)) {
		$sql = '';
	}

	/**
	 * Authenticated visitor. Unless pre-verified,
	 * check that the contact belongs to this $owner_id
	 * and load the groups the visitor belongs to.
	 * If pre-verified, the caller is expected to have already
	 * done this and passed the groups into this function.
	 */

	elseif($remote_user) {

		if(! $remote_verified) {
			$r = q("SELECT id FROM contact WHERE id = %d AND uid = %d AND blocked = 0 LIMIT 1",
				intval($remote_user),
				intval($owner_id)
			);
			if (dbm::is_result($r)) {
				$remote_verified = true;
				$groups = init_groups_visitor($remote_user);
			}
		}
		if($remote_verified) {

			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . intval($g) . '>';
			}

			/*$sql = sprintf(
				" AND ( allow_cid = '' OR allow_cid REGEXP '<%d>' )
				  AND ( deny_cid  = '' OR  NOT deny_cid REGEXP '<%d>' )
				  AND ( allow_gid = '' OR allow_gid REGEXP '%s' )
				  AND ( deny_gid  = '' OR NOT deny_gid REGEXP '%s')
				",
				intval($remote_user),
				intval($remote_user),
				dbesc($gs),
				dbesc($gs)
			);*/
			$sql = sprintf(
				" AND ( NOT (deny_cid REGEXP '<%d>' OR deny_gid REGEXP '%s')
				  AND ( allow_cid REGEXP '<%d>' OR allow_gid REGEXP '%s' OR ( allow_cid = '' AND allow_gid = '') )
				  )
				",
				intval($remote_user),
				dbesc($gs),
				intval($remote_user),
				dbesc($gs)
			);
		}
	}
	return $sql;
}


function item_permissions_sql($owner_id,$remote_verified = false,$groups = null) {

	$local_user = local_user();
	$remote_user = remote_user();

	/**
	 * Construct permissions
	 *
	 * default permissions - anonymous user
	 */

	$sql = " AND `item`.allow_cid = ''
			 AND `item`.allow_gid = ''
			 AND `item`.deny_cid  = ''
			 AND `item`.deny_gid  = ''
			 AND `item`.private = 0
	";

	/**
	 * Profile owner - everything is visible
	 */

	if($local_user && ($local_user == $owner_id)) {
		$sql = '';
	}

	/**
	 * Authenticated visitor. Unless pre-verified,
	 * check that the contact belongs to this $owner_id
	 * and load the groups the visitor belongs to.
	 * If pre-verified, the caller is expected to have already
	 * done this and passed the groups into this function.
	 */

	elseif($remote_user) {

		if(! $remote_verified) {
			$r = q("SELECT id FROM contact WHERE id = %d AND uid = %d AND blocked = 0 LIMIT 1",
				intval($remote_user),
				intval($owner_id)
			);
			if (dbm::is_result($r)) {
				$remote_verified = true;
				$groups = init_groups_visitor($remote_user);
			}
		}
		if($remote_verified) {

			$gs = '<<>>'; // should be impossible to match

			if(is_array($groups) && count($groups)) {
				foreach($groups as $g)
					$gs .= '|<' . intval($g) . '>';
			}

			$sql = sprintf(
				/*" AND ( private = 0 OR ( private in (1,2) AND wall = 1 AND ( allow_cid = '' OR allow_cid REGEXP '<%d>' )
				  AND ( deny_cid  = '' OR  NOT deny_cid REGEXP '<%d>' )
				  AND ( allow_gid = '' OR allow_gid REGEXP '%s' )
				  AND ( deny_gid  = '' OR NOT deny_gid REGEXP '%s')))
				",
				intval($remote_user),
				intval($remote_user),
				dbesc($gs),
				dbesc($gs)
*/
				" AND ( `item`.private = 0 OR ( `item`.private in (1,2) AND `item`.`wall` = 1
				  AND ( NOT (`item`.deny_cid REGEXP '<%d>' OR `item`.deny_gid REGEXP '%s')
				  AND ( `item`.allow_cid REGEXP '<%d>' OR `item`.allow_gid REGEXP '%s' OR ( `item`.allow_cid = '' AND `item`.allow_gid = '')))))
				",
				intval($remote_user),
				dbesc($gs),
				intval($remote_user),
				dbesc($gs)
			);
		}
	}

	return $sql;
}


/*
 * Functions used to protect against Cross-Site Request Forgery
 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amout of time (3hours).
 * The "typename" seperates the security tokens of different types of forms. This could be relevant in the following case:
 *    A security token is used to protekt a link from CSRF (e.g. the "delete this profile"-link).
 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but somethimes they still are,
 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
 */
function get_form_security_token($typename = '') {
	$a = get_app();

	$timestamp = time();
	$sec_hash = hash('whirlpool', $a->user['guid'] . $a->user['prvkey'] . session_id() . $timestamp . $typename);

	return $timestamp . '.' . $sec_hash;
}

function check_form_security_token($typename = '', $formname = 'form_security_token') {
	if (!x($_REQUEST, $formname)) return false;
	$hash = $_REQUEST[$formname];

	$max_livetime = 10800; // 3 hours

	$a = get_app();

	$x = explode('.', $hash);
	if (time() > (IntVal($x[0]) + $max_livetime)) return false;

	$sec_hash = hash('whirlpool', $a->user['guid'] . $a->user['prvkey'] . session_id() . $x[0] . $typename);

	return ($sec_hash == $x[1]);
}

function check_form_security_std_err_msg() {
	return t('The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.') . EOL;
}
function check_form_security_token_redirectOnErr($err_redirect, $typename = '', $formname = 'form_security_token') {
	if (!check_form_security_token($typename, $formname)) {
		$a = get_app();
		logger('check_form_security_token failed: user ' . $a->user['guid'] . ' - form element ' . $typename);
		logger('check_form_security_token failed: _REQUEST data: ' . print_r($_REQUEST, true), LOGGER_DATA);
		notice( check_form_security_std_err_msg() );
		goaway(App::get_baseurl() . $err_redirect );
	}
}
function check_form_security_token_ForbiddenOnErr($typename = '', $formname = 'form_security_token') {
	if (!check_form_security_token($typename, $formname)) {
	    $a = get_app();
		logger('check_form_security_token failed: user ' . $a->user['guid'] . ' - form element ' . $typename);
		logger('check_form_security_token failed: _REQUEST data: ' . print_r($_REQUEST, true), LOGGER_DATA);
		header('HTTP/1.1 403 Forbidden');
		killme();
	}
}

// Returns an array of group id's this contact is a member of.
// This array will only contain group id's related to the uid of this
// DFRN contact. They are *not* neccessarily unique across the entire site.


if(! function_exists('init_groups_visitor')) {
function init_groups_visitor($contact_id) {
	$groups = array();
	$r = q("SELECT `gid` FROM `group_member`
		WHERE `contact-id` = %d ",
		intval($contact_id)
	);
	if (dbm::is_result($r)) {
		foreach($r as $rr)
			$groups[] = $rr['gid'];
	}
	return $groups;
}}

