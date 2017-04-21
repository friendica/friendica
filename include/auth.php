<?php
require_once('include/security.php');
require_once('include/datetime.php');

// When the "Friendica" cookie is set, take the value to authenticate and renew the cookie.
if (isset($_COOKIE["Friendica"])) {
	$data = json_decode($_COOKIE["Friendica"]);
	if (isset($data->uid)) {
		$r = q("SELECT `user`.*, `user`.`pubkey` as `upubkey`, `user`.`prvkey` as `uprvkey`
		FROM `user` WHERE `uid` = %d AND NOT `blocked` AND NOT `account_expired` AND NOT `account_removed` AND `verified` LIMIT 1",
			intval($data->uid)
		);

		if ($r) {
			if ($data->hash != cookie_hash($r[0])) {
				logger("Hash for user ".$data->uid." doesn't fit.");
				nuke_session();
				goaway(z_root());
			}

			// Renew the cookie
			// Expires after 90 days - TODO: use a configuration variable
			new_cookie(90*24*60*60, $r[0]);

			// Do the authentification if not done by now
			if (!isset($_SESSION) OR !isset($_SESSION['authenticated'])) {
				authenticate_success($r[0]);

				if (get_config('system','paranoia'))
					$_SESSION['addr'] = $data->ip;
			}
		}
	}
}


// login/logout

if (isset($_SESSION) && x($_SESSION,'authenticated') && (!x($_POST,'auth-params') || ($_POST['auth-params'] !== 'login'))) {

	if ((x($_POST,'auth-params') && ($_POST['auth-params'] === 'logout')) || ($a->module === 'logout')) {

		// process logout request
		call_hooks("logging_out");
		nuke_session();
		info(t('Logged out.').EOL);
		goaway(z_root());
	}

	if (x($_SESSION,'visitor_id') && !x($_SESSION,'uid')) {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($_SESSION['visitor_id'])
		);
		if (dbm::is_result($r)) {
			$a->contact = $r[0];
		}
	}

	if (x($_SESSION,'uid')) {

		// already logged in user returning

		$check = get_config('system','paranoia');
		// extra paranoia - if the IP changed, log them out
		if ($check && ($_SESSION['addr'] != $_SERVER['REMOTE_ADDR'])) {
			logger('Session address changed. Paranoid setting in effect, blocking session. '.
				$_SESSION['addr'].' != '.$_SERVER['REMOTE_ADDR']);
			nuke_session();
			goaway(z_root());
		}

		$r = q("SELECT `user`.*, `user`.`pubkey` as `upubkey`, `user`.`prvkey` as `uprvkey`
		FROM `user` WHERE `uid` = %d AND NOT `blocked` AND NOT `account_expired` AND NOT `account_removed` AND `verified` LIMIT 1",
			intval($_SESSION['uid'])
		);

		if (!dbm::is_result($r)) {
			nuke_session();
			goaway(z_root());
		}

		// Make sure to refresh the last login time for the user if the user
		// stays logged in for a long time, e.g. with "Remember Me"
		$login_refresh = false;
		if (!x($_SESSION['last_login_date'])) {
			$_SESSION['last_login_date'] = datetime_convert('UTC','UTC');
		}
		if (strcmp(datetime_convert('UTC','UTC','now - 12 hours'), $_SESSION['last_login_date']) > 0) {

			$_SESSION['last_login_date'] = datetime_convert('UTC','UTC');
			$login_refresh = true;
		}
		authenticate_success($r[0], false, false, $login_refresh);
	}
} else {

	session_unset();

	if (x($_POST,'password') && strlen($_POST['password']))
		$encrypted = hash('whirlpool',trim($_POST['password']));
	else {
		if ((x($_POST,'openid_url')) && strlen($_POST['openid_url']) ||
		   (x($_POST,'username')) && strlen($_POST['username'])) {

			$noid = get_config('system','no_openid');

			$openid_url = trim((strlen($_POST['openid_url'])?$_POST['openid_url']:$_POST['username']));

			// validate_url alters the calling parameter

			$temp_string = $openid_url;

			// if it's an email address or doesn't resolve to a URL, fail.

			if ($noid || strpos($temp_string,'@') || !validate_url($temp_string)) {
				$a = get_app();
				notice(t('Login failed.').EOL);
				goaway(z_root());
				// NOTREACHED
			}

			// Otherwise it's probably an openid.

			try {
				require_once('library/openid.php');
				$openid = new LightOpenID;
				$openid->identity = $openid_url;
				$_SESSION['openid'] = $openid_url;
				$_SESSION['remember'] = $_POST['remember'];
				$openid->returnUrl = App::get_baseurl(true).'/openid';
				goaway($openid->authUrl());
			} catch (Exception $e) {
				notice(t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.').'<br /><br >'.t('The error message was:').' '.$e->getMessage());
			}
			// NOTREACHED
		}
	}

	if (x($_POST,'auth-params') && $_POST['auth-params'] === 'login') {

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

		if ($addon_auth['authenticated'] && count($addon_auth['user_record']))
			$record = $addon_auth['user_record'];
		else {

			// process normal login request

			$r = q("SELECT `user`.*, `user`.`pubkey` as `upubkey`, `user`.`prvkey` as `uprvkey`
				FROM `user` WHERE (`email` = '%s' OR `nickname` = '%s')
				AND `password` = '%s' AND NOT `blocked` AND NOT `account_expired` AND NOT `account_removed` AND `verified` LIMIT 1",
				dbesc(trim($_POST['username'])),
				dbesc(trim($_POST['username'])),
				dbesc($encrypted)
			);
			if (dbm::is_result($r))
				$record = $r[0];
		}

		if (!$record || !count($record)) {
			logger('authenticate: failed login attempt: '.notags(trim($_POST['username'])).' from IP '.$_SERVER['REMOTE_ADDR']);
			notice(t('Login failed.').EOL);
			goaway(z_root());
		}

		if (! $_POST['remember']) {
			new_cookie(0); // 0 means delete on browser exit
		}

		// if we haven't failed up this point, log them in.
		$_SESSION['remember'] = $_POST['remember'];
		$_SESSION['last_login_date'] = datetime_convert('UTC','UTC');
		authenticate_success($record, true, true);
	}
}

/**
 * @brief Kills the "Friendica" cookie and all session data
 */
function nuke_session() {

	new_cookie(-3600); // make sure cookie is deleted on browser close, as a security measure
	session_unset();
	session_destroy();
}
