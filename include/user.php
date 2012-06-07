<?php

require_once('include/config.php');
require_once('include/network.php');
require_once('include/plugin.php');
require_once('include/text.php');
require_once('include/pgettext.php');
require_once('include/datetime.php');

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
	$blocked    = ((x($arr,'blocked'))    ? intval($arr['blocked'])  : 0);
	$verified   = ((x($arr,'verified'))   ? intval($arr['verified']) : 0);

	$publish    = ((x($arr,'profile_publish_reg') && intval($arr['profile_publish_reg'])) ? 1 : 0);
	$netpublish = ((strlen(get_config('system','directory_submit_url'))) ? $publish : 0);
		
	$tmp_str = $openid_url;

	if($using_invites) {
		if(! $invite_id) {
			$result['message'] .= t('An invitation is required.') . EOL;
			return $result;
		}
		$r = q("select * from register where `hash` = '%s' limit 1", dbesc($invite_id));
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
			$openid->returnUrl = $a->get_baseurl() . '/openid'; 
			$openid->required = array('namePerson/friendly', 'contact/email', 'namePerson');
			$openid->optional = array('namePerson/first','media/image/aspect11','media/image/default');
			goaway($openid->authUrl());
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

	if((x($a->config,'admin_email')) && (strcasecmp($email,$a->config['admin_email']) == 0) && strlen($openid_url)) {
		$r = q("SELECT * FROM `user` WHERE `email` = '%s' LIMIT 1",
			dbesc($email)
		);
		if(count($r))
			$result['message'] .= t('Cannot use that email.') . EOL;
	}

	$nickname = $arr['nickname'] = strtolower($nickname);

	if(! preg_match("/^[a-z][a-z0-9\-\_]*$/",$nickname))
		$result['message'] .= t('Your "nickname" can only contain "a-z", "0-9", "-", and "_", and must also begin with a letter.') . EOL;
	$r = q("SELECT `uid` FROM `user`
               	WHERE `nickname` = '%s' LIMIT 1",
               	dbesc($nickname)
	);
	if(count($r))
		$result['message'] .= t('Nickname is already registered. Please choose another.') . EOL;

	// Check deleted accounts that had this nickname. Doesn't matter to us,
	// but could be a security issue for federated platforms.

	$r = q("SELECT * FROM `userd`
               	WHERE `username` = '%s' LIMIT 1",
               	dbesc($nickname)
	);
	if(count($r))
		$result['message'] .= t('Nickname was once registered here and may not be re-used. Please choose another.') . EOL;

	if(strlen($result['message'])) {
		return $result;
	}

	$new_password = ((strlen($password)) ? $password : autoname(6) . mt_rand(100,9999));
	$new_password_encoded = hash('whirlpool',$new_password);

	$result['password'] = $new_password;

	require_once('include/crypto.php');

	$keys = new_keypair(1024);

	if($keys === false) {
		$result['message'] .= t('SERIOUS ERROR: Generation of security keys failed.') . EOL;
		return $result;
	}

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
		`pubkey`, `prvkey`, `spubkey`, `sprvkey`, `register_date`, `verified`, `blocked`, `timezone` )
		VALUES ( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, 'UTC' )",
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
		intval($blocked)
	);

	if($r) {
		$r = q("SELECT * FROM `user` 
			WHERE `username` = '%s' AND `password` = '%s' LIMIT 1",
			dbesc($username),
			dbesc($new_password_encoded)
		);
		if($r !== false && count($r)) {
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
	if((count($r) > 1) && $newuid) {
		$result['message'] .= t('Nickname is already registered. Please choose another.') . EOL;
		q("DELETE FROM `user` WHERE `uid` = %d LIMIT 1",
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
			dbesc($a->get_baseurl() . "/photo/profile/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}.jpg"),
			intval($publish),
			intval($netpublish)

		);
		if($r === false) {
			$result['message'] .=  t('An error occurred creating your default profile. Please try again.') . EOL;
			// Start fresh next time.
			$r = q("DELETE FROM `user` WHERE `uid` = %d",
				intval($newuid));
			return $result;
		}
		$r = q("INSERT INTO `contact` ( `uid`, `created`, `self`, `name`, `nick`, `photo`, `thumb`, `micro`, `blocked`, `pending`, `url`, `nurl`,
			`request`, `notify`, `poll`, `confirm`, `poco`, `name-date`, `uri-date`, `avatar-date`, `closeness` )
			VALUES ( %d, '%s', 1, '%s', '%s', '%s', '%s', '%s', 0, 0, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 0 ) ",
			intval($newuid),
			datetime_convert(),
			dbesc($username),
			dbesc($nickname),
			dbesc($a->get_baseurl() . "/photo/profile/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/avatar/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/photo/micro/{$newuid}.jpg"),
			dbesc($a->get_baseurl() . "/profile/$nickname"),
			dbesc(normalise_link($a->get_baseurl() . "/profile/$nickname")),
			dbesc($a->get_baseurl() . "/dfrn_request/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_notify/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_poll/$nickname"),
			dbesc($a->get_baseurl() . "/dfrn_confirm/$nickname"),
			dbesc($a->get_baseurl() . "/poco/$nickname"),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert())
		);

		// Create a group with no members. This allows somebody to use it 
		// right away as a default group for new contacts. 

		require_once('include/group.php');
		group_add($newuid, t('Friends'));

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

			if($r === false)
				$photo_failure = true;

			$img->scaleImage(80);

			$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 5 );

			if($r === false)
				$photo_failure = true;

			$img->scaleImage(48);

			$r = $img->store($newuid, 0, $hash, $filename, t('Profile Photos'), 6 );

			if($r === false)
				$photo_failure = true;

			if(! $photo_failure) {
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
