<?php

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/pgettext.php');
require_once('include/datetime.php');
require_once('include/enotify.php');


function create_user($arr) {

	// Required: { username, nickname, email } or { openid_url }

	$a = get_app();
	$result = array('success' => false, 'user' => null, 'password' => '', 'message' => '');

	$using_invites = get_config('system','invitation_only');
	$num_invites   = get_config('system','number_invites');


	$invite_id  = ((x($arr,'invite_id'))  ? notags(trim($arr['invite_id']))  : '');
	$username   = ((x($arr,'username'))   ? notags(trim($arr['username']))   : '');
	$nickname   = ((x($arr,'nickname'))   ? notags(trim($arr['nickname']))   : '');
	$email      = ((x($arr,'email'))      ? notags(trim($arr['email']))      : '');
	$openid_url = ((x($arr,'openid_url')) ? notags(trim($arr['openid_url'])) : '');
	$photo      = ((x($arr,'photo'))      ? notags(trim($arr['photo']))      : '');
	$password   = ((x($arr,'password'))   ? trim($arr['password'])           : '');
	$password1  = ((x($arr,'password1'))  ? trim($arr['password1'])          : '');
	$confirm    = ((x($arr,'confirm'))    ? trim($arr['confirm'])            : '');
	$blocked    = ((x($arr,'blocked'))    ? intval($arr['blocked'])  : 0);
	$verified   = ((x($arr,'verified'))   ? intval($arr['verified']) : 0);

	$publish    = ((x($arr,'profile_publish_reg') && intval($arr['profile_publish_reg'])) ? 1 : 0);
	$netpublish = ((strlen(get_config('system','directory'))) ? $publish : 0);

	if ($password1 != $confirm) {
		$result['message'] .= t('Passwords do not match. Password unchanged.') . EOL;
		return $result;
	} elseif ($password1 != "")
		$password = $password1;

	$tmp_str = $openid_url;

	if($using_invites) {
		if(! $invite_id) {
			$result['message'] .= t('An invitation is required.') . EOL;
			return $result;
		}
		$r = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1", dbesc($invite_id));
		if(! results($r)) {
			$result['message'] .= t('Invitation could not be verified.') . EOL;
			return $result;
		}
	}

	if((! x($username)) || (! x($email)) || (! x($nickname))) {
		if($openid_url) {
			if(! validate_url($tmp_str)) {
				$result['message'] .= t('Invalid OpenID url') . EOL;
				return $result;
			}
			$_SESSION['register'] = 1;
			$_SESSION['openid'] = $openid_url;
			require_once('library/openid.php');
			$openid = new LightOpenID;
			$openid->identity = $openid_url;
			$openid->returnUrl = z_root() . '/openid';
			$openid->required = array('namePerson/friendly', 'contact/email', 'namePerson');
			$openid->optional = array('namePerson/first','media/image/aspect11','media/image/default');
			try {
				$authurl = $openid->authUrl();
			} catch (Exception $e){
				$result['message'] .= t("We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID."). EOL . EOL . t("The error message was:") . $e->getMessage() . EOL;
				return $result;
			}
			goaway($authurl);
			// NOTREACHED
		}

		notice( t('Please enter the required information.') . EOL );
		return;
	}

	if(! validate_url($tmp_str))
		$openid_url = '';


	$err = '';

	// collapse multiple spaces in name
	$username = preg_replace('/ +/',' ',$username);

	if(mb_strlen($username) > 48)
		$result['message'] .= t('Please use a shorter name.') . EOL;
	if(mb_strlen($username) < 3)
		$result['message'] .= t('Name too short.') . EOL;

	// I don't really like having this rule, but it cuts down
	// on the number of auto-registrations by Russian spammers

	//  Using preg_match was completely unreliable, due to mixed UTF-8 regex support
	//	$no_utf = get_config('system','no_utf');
	//	$pat = (($no_utf) ? '/^[a-zA-Z]* [a-zA-Z]*$/' : '/^\p{L}* \p{L}*$/u' );

	// So now we are just looking for a space in the full name.

	$loose_reg = get_config('system','no_regfullname');
	if(! $loose_reg) {
		$username = mb_convert_case($username,MB_CASE_TITLE,'UTF-8');
		if(! strpos($username,' '))
			$result['message'] .= t("That doesn't appear to be your full \x28First Last\x29 name.") . EOL;
	}


	if(! allowed_email($email))
		$result['message'] .= t('Your email domain is not among those allowed on this site.') . EOL;

	if((! valid_email($email)) || (! validate_email($email)))
		$result['message'] .= t('Not a valid email address.') . EOL;

	// Disallow somebody creating an account using openid that uses the admin email address,
	// since openid bypasses email verification. We'll allow it if there is not yet an admin account.

	$adminlist = explode(",", str_replace(" ", "", strtolower($a->config['admin_email'])));

	//if((x($a->config,'admin_email')) && (strcasecmp($email,$a->config['admin_email']) == 0) && strlen($openid_url)) {
	if((x($a->config,'admin_email')) && in_array(strtolower($email), $adminlist) && strlen($openid_url)) {
		$r = q("SELECT * FROM `user` WHERE `email` = '%s' LIMIT 1",
			dbesc($email)
		);
		if (dbm::is_result($r))
			$result['message'] .= t('Cannot use that email.') . EOL;
	}

	$nickname = $arr['nickname'] = strtolower($nickname);

	if(! preg_match("/^[a-z0-9][a-z0-9\_]*$/",$nickname))
		$result['message'] .= t('Your "nickname" can only contain "a-z", "0-9" and "_".') . EOL;

	$r = q("SELECT `uid` FROM `user`
		WHERE `nickname` = '%s' LIMIT 1",
		dbesc($nickname)
	);
	if (dbm::is_result($r))
		$result['message'] .= t('Nickname is already registered. Please choose another.') . EOL;

	// Check deleted accounts that had this nickname. Doesn't matter to us,
	// but could be a security issue for federated platforms.

	$r = q("SELECT * FROM `userd`
		WHERE `username` = '%s' LIMIT 1",
		dbesc($nickname)
	);
	if (dbm::is_result($r))
		$result['message'] .= t('Nickname was once registered here and may not be re-used. Please choose another.') . EOL;

	if(strlen($result['message'])) {
		return $result;
	}

	$new_password = ((strlen($password)) ? $password : autoname(6) . mt_rand(100,9999));
	$new_password_encoded = hash('whirlpool',$new_password);

	$result['password'] = $new_password;

	require_once('include/crypto.php');

	$keys = new_keypair(4096);

	if($keys === false) {
		$result['message'] .= t('SERIOUS ERROR: Generation of security keys failed.') . EOL;
		return $result;
	}

	$default_service_class = get_config('system','default_service_class');
	if(! $default_service_class)
		$default_service_class = '';


	$prvkey = $keys['prvkey'];
	$pubkey = $keys['pubkey'];

	/**
	 *
	 * Create another keypair for signing/verifying
	 * salmon protocol messages. We have to use a slightly
	 * less robust key because this won't be using openssl
	 * but the phpseclib. Since it is PHP interpreted code
	 * it is not nearly as efficient, and the larger keys
	 * will take several minutes each to process.
	 *
	 */

	$sres    = new_keypair(512);
	$sprvkey = $sres['prvkey'];
	$spubkey = $sres['pubkey'];

	$r = q("INSERT INTO `user` ( `guid`, `username`, `password`, `email`, `openid`, `nickname`,
		`pubkey`, `prvkey`, `spubkey`, `sprvkey`, `register_date`, `verified`, `blocked`, `timezone`, `service_class`, `default-location` )
		VALUES ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, 'UTC', '%s', '' )",
		dbesc(generate_user_guid()),
		dbesc($username),
		dbesc($new_password_encoded),
		dbesc($email),
		dbesc($openid_url),
		dbesc($nickname),
		dbesc($pubkey),
		dbesc($prvkey),
		dbesc($spubkey),
		dbesc($sprvkey),
		dbesc(datetime_convert()),
		intval($verified),
		intval($blocked),
		dbesc($default_service_class)
	);

	if ($r) {
		$r = q("SELECT * FROM `user`
			WHERE `username` = '%s' AND `password` = '%s' LIMIT 1",
			dbesc($username),
			dbesc($new_password_encoded)
		);
		if (dbm::is_result($r)) {
			$u = $r[0];
			$newuid = intval($r[0]['uid']);
		}
	}
	else {
		$result['message'] .=  t('An error occurred during registration. Please try again.') . EOL ;
		return $result;
	}

	/**
	 * if somebody clicked submit twice very quickly, they could end up with two accounts
	 * due to race condition. Remove this one.
	 */

	$r = q("SELECT `uid` FROM `user`
		WHERE `nickname` = '%s' ",
		dbesc($nickname)
	);
	if ((dbm::is_result($r)) && (count($r) > 1) && $newuid) {
		$result['message'] .= t('Nickname is already registered. Please choose another.') . EOL;
		q("DELETE FROM `user` WHERE `uid` = %d",
			intval($newuid)
		);
		return $result;
	}

	if(x($newuid) !== false) {
		$r = q("INSERT INTO `profile` ( `uid`, `profile-name`, `is-default`, `name`, `photo`, `thumb`, `publish`, `net-publish` )
			VALUES ( %d, '%s', %d, '%s', '%s', '%s', %d, %d ) ",
			intval($newuid),
			t('default'),
			1,
			dbesc($username),
			dbesc(z_root() . "/photo/profile/{$newuid}.jpg"),
			dbesc(z_root() . "/photo/avatar/{$newuid}.jpg"),
			intval($publish),
			intval($netpublish)

		);
		if ($r === false) {
			$result['message'] .=  t('An error occurred creating your default profile. Please try again.') . EOL;
			// Start fresh next time.
			$r = q("DELETE FROM `user` WHERE `uid` = %d",
				intval($newuid));
			return $result;
		}

		// Create the self contact
		user_create_self_contact($newuid);

		// Create a group with no members. This allows somebody to use it
		// right away as a default group for new contacts.

		require_once('include/group.php');
		group_add($newuid, t('Friends'));

		$r = q("SELECT `id` FROM `group` WHERE `uid` = %d AND `name` = '%s'",
			intval($newuid),
			dbesc(t('Friends'))
		);
		if (dbm::is_result($r)) {
			$def_gid = $r[0]['id'];

			q("UPDATE `user` SET `def_gid` = %d WHERE `uid` = %d",
				intval($r[0]['id']),
				intval($newuid)
			);
		}

		if(get_config('system', 'newuser_private') && $def_gid) {
			q("UPDATE `user` SET `allow_gid` = '%s' WHERE `uid` = %d",
				dbesc("<" . $def_gid . ">"),
				intval($newuid)
			);
		}

	}

	// if we have no OpenID photo try to look up an avatar
	if(! strlen($photo))
		$photo = avatar_img($email);

	// unless there is no avatar-plugin loaded
	if(strlen($photo)) {
		require_once('include/Photo.php');
		$photo_failure = false;

		$filename = basename($photo);
		$img_str = fetch_url($photo,true);
		// guess mimetype from headers or filename
		$type = guess_image_type($photo,true);


		$img = new Photo($img_str, $type);
		if($img->is_valid()) {

			$img->scaleImageSquare(175);

			$hash = photo_new_resource();

			$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 4 );

			if ($r === false) {
				$photo_failure = true;
			}

			$img->scaleImage(80);

			$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 5 );

			if ($r === false) {
				$photo_failure = true;
			}

			$img->scaleImage(48);

			$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 6 );

			if ($r === false) {
				$photo_failure = true;
			}

			if (! $photo_failure) {
				q("UPDATE `photo` SET `profile` = 1 WHERE `resource-id` = '%s' ",
					dbesc($hash)
				);
			}
		}
	}

	call_hooks('register_account', $newuid);

	$result['success'] = true;
	$result['user'] = $u;
	return $result;

}

/**
 * @brief create the "self" contact from data from the user table
 *
 * @param integer $uid
 */
function user_create_self_contact($uid) {

	// Only create the entry if it doesn't exist yet
	$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `self`", intval($uid));
	if (dbm::is_result($r)) {
		return;
	}

	$r = q("SELECT `uid`, `username`, `nickname` FROM `user` WHERE `uid` = %d", intval($uid));
	if (!dbm::is_result($r)) {
		return;
	}

	$user = $r[0];

	q("INSERT INTO `contact` (`uid`, `created`, `self`, `name`, `nick`, `photo`, `thumb`, `micro`, `blocked`, `pending`, `url`, `nurl`,
		`addr`, `request`, `notify`, `poll`, `confirm`, `poco`, `name-date`, `uri-date`, `avatar-date`, `closeness`)
		VALUES (%d, '%s', 1, '%s', '%s', '%s', '%s', '%s', 0, 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 0)",
		intval($user['uid']),
		datetime_convert(),
		dbesc($user['username']),
		dbesc($user['nickname']),
		dbesc(z_root()."/photo/profile/".$user['uid'].".jpg"),
		dbesc(z_root()."/photo/avatar/".$user['uid'].".jpg"),
		dbesc(z_root()."/photo/micro/".$user['uid'].".jpg"),
		dbesc(z_root()."/profile/".$user['nickname']),
		dbesc(normalise_link(z_root()."/profile/".$user['nickname'])),
		dbesc($user['nickname'].'@'.substr(z_root(), strpos(z_root(),'://') + 3)),
		dbesc(z_root()."/dfrn_request/".$user['nickname']),
		dbesc(z_root()."/dfrn_notify/".$user['nickname']),
		dbesc(z_root()."/dfrn_poll/".$user['nickname']),
		dbesc(z_root()."/dfrn_confirm/".$user['nickname']),
		dbesc(z_root()."/poco/".$user['nickname']),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc(datetime_convert())
	);
}

/**
 * @brief send registration confiŕmation with the intormation that reg is pending
 *
 * @param string $email
 * @param string $sitename
 * @param string $username
 * @return NULL|boolean from notification() and email() inherited 
 */
function send_register_pending_eml($email, $sitename, $username) {
	$body = deindent(t('
		Dear %1$s,
			Thank you for registering at %2$s. Your account is pending for approval by the administrator.
	'));

	$body = sprintf($body, $username, $sitename);

	return notification(array(
		'type' => "SYSTEM_EMAIL",
		'to_email' => $email,
		'subject'=> sprintf( t('Registration at %s'), $sitename),
		'body' => $body));
}

/*
 * send registration confirmation.
 * It's here as a function because the mail is sent
 * from different parts
 */
function send_register_open_eml($email, $sitename, $siteurl, $username, $password){
	$preamble = deindent(t('
		Dear %1$s,
			Thank you for registering at %2$s. Your account has been created.
	'));
	$body = deindent(t('
		The login details are as follows:
			Site Location:	%3$s
			Login Name:	%1$s
			Password:	%5$s

		You may change your password from your account "Settings" page after logging
		in.

		Please take a few moments to review the other account settings on that page.

		You may also wish to add some basic information to your default profile
		(on the "Profiles" page) so that other people can easily find you.

		We recommend setting your full name, adding a profile photo,
		adding some profile "keywords" (very useful in making new friends) - and
		perhaps what country you live in; if you do not wish to be more specific
		than that.

		We fully respect your right to privacy, and none of these items are necessary.
		If you are new and do not know anybody here, they may help
		you to make some new and interesting friends.


		Thank you and welcome to %2$s.'));

		$preamble = sprintf($preamble, $username, $sitename);
		$body = sprintf($body, $email, $sitename, $siteurl, $username, $password);

		return notification(array(
			'type' => "SYSTEM_EMAIL",
			'to_email' => $email,
			'subject'=> sprintf( t('Registration details for %s'), $sitename),
			'preamble'=> $preamble,
			'body' => $body));
}
