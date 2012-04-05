<?php


require_once('include/security.php');

function nuke_session() {
	unset($_SESSION['authenticated']);
	unset($_SESSION['uid']);
	unset($_SESSION['visitor_id']);
	unset($_SESSION['administrator']);
	unset($_SESSION['cid']);
	unset($_SESSION['theme']);
	unset($_SESSION['page_flags']);
}


// login/logout 




if((isset($_SESSION)) && (x($_SESSION,'authenticated')) && ((! (x($_POST,'auth-params'))) || ($_POST['auth-params'] !== 'login'))) {

	if(((x($_POST,'auth-params')) && ($_POST['auth-params'] === 'logout')) || ($a->module === 'logout')) {
	
		// process logout request
		call_hooks("logging_out");
		nuke_session();
		info( t('Logged out.') . EOL);
		goaway(z_root());
	}

	if(x($_SESSION,'visitor_id') && (! x($_SESSION,'uid'))) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($_SESSION['visitor_id'])
		);
		if(count($r)) {
			$a->contact = $r[0];
		}
	}

	if(x($_SESSION,'uid')) {

		// already logged in user returning

		$check = get_config('system','paranoia');
		// extra paranoia - if the IP changed, log them out
		if($check && ($_SESSION['addr'] != $_SERVER['REMOTE_ADDR'])) {
			nuke_session();
			goaway(z_root());
		}

		$r = q("SELECT `user`.*, `user`.`pubkey` as `upubkey`, `user`.`prvkey` as `uprvkey` 
		FROM `user` WHERE `uid` = %d AND `blocked` = 0 AND `account_expired` = 0 AND `verified` = 1 LIMIT 1",
			intval($_SESSION['uid'])
		);

		if(! count($r)) {
			nuke_session();
			goaway(z_root());
		}

		authenticate_success($r[0]);
	}
}
else {

	if(isset($_SESSION)) {
		nuke_session();
	}

	if((x($_POST,'password')) && strlen($_POST['password']))
		$encrypted = hash('whirlpool',trim($_POST['password']));
	else {
		if((x($_POST,'openid_url')) && strlen($_POST['openid_url']) ||
		   (x($_POST,'username')) && strlen($_POST['username'])) {

			$noid = get_config('system','no_openid');

			$openid_url = trim((strlen($_POST['openid_url'])?$_POST['openid_url']:$_POST['username']) );

			// validate_url alters the calling parameter

			$temp_string = $openid_url;

			// if it's an email address or doesn't resolve to a URL, fail.

			if(($noid) || (strpos($temp_string,'@')) || (! validate_url($temp_string))) {
				$a = get_app();
				notice( t('Login failed.') . EOL);
				goaway(z_root());
				// NOTREACHED
			}

			// Otherwise it's probably an openid.

                        try {
			require_once('library/openid.php');
			$openid = new LightOpenID;
			$openid->identity = $openid_url;
			$_SESSION['openid'] = $openid_url;
			$a = get_app();
			$openid->returnUrl = $a->get_baseurl(true) . '/openid'; 
                        goaway($openid->authUrl());
                        } catch (Exception $e) {
                            notice( t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.').'<br /><br >'. t('The error message was:').' '.$e->getMessage());
                        }
			// NOTREACHED
		}
	}

	if((x($_POST,'auth-params')) && $_POST['auth-params'] === 'login') {

		$record = null;

		$addon_auth = array(
			'username' => trim($_POST['username']), 
			'password' => trim($_POST['password']),
			'authenticated' => 0,
			'user_record' => null
		);

		/**
		 *
		 * A plugin indicates successful login by setting 'authenticated' to non-zero value and returning a user record
		 * Plugins should never set 'authenticated' except to indicate success - as hooks may be chained
		 * and later plugins should not interfere with an earlier one that succeeded.
		 *
		 */

		call_hooks('authenticate', $addon_auth);

		if(($addon_auth['authenticated']) && (count($addon_auth['user_record']))) {
			$record = $addon_auth['user_record'];
		}
		else {

			// process normal login request

			$r = q("SELECT `user`.*, `user`.`pubkey` as `upubkey`, `user`.`prvkey` as `uprvkey`  
				FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' ) 
				AND `password` = '%s' AND `blocked` = 0 AND `account_expired` = 0 AND `verified` = 1 LIMIT 1",
				dbesc(trim($_POST['username'])),
				dbesc(trim($_POST['username'])),
				dbesc($encrypted)
			);
			if(count($r))
				$record = $r[0];
		}

		if((! $record) || (! count($record))) {
			logger('authenticate: failed login attempt: ' . notags(trim($_POST['username'])) . ' from IP ' . $_SERVER['REMOTE_ADDR']); 
			notice( t('Login failed.') . EOL );
			goaway(z_root());
  		}

		// if we haven't failed up this point, log them in.

		authenticate_success($record, true, true);
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
	if(count($r)) {
		foreach($r as $rr)
			$groups[] = $rr['gid'];
	}
	return $groups;
}}


