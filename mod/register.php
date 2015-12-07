<?php

require_once('include/enotify.php');
require_once('include/bbcode.php');
require_once('include/user.php');

if(! function_exists('register_post')) {
function register_post(&$a) {

	global $lang;

	$verified = 0;
	$blocked  = 1;

	$arr = array('post' => $_POST);
	call_hooks('register_post', $arr);

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if($max_dailies) {
		$r = q("select count(*) as total from user where register_date > UTC_TIMESTAMP - INTERVAL 1 day");
		if($r && $r[0]['total'] >= $max_dailies) {
			return;
		}
	}

	switch($a->config['register_policy']) {


	case REGISTER_OPEN:
		$blocked = 0;
		$verified = 1;
		break;

	case REGISTER_APPROVE:
		$blocked = 1;
		$verified = 0;
		break;

	default:
	case REGISTER_CLOSED:
		if((! x($_SESSION,'authenticated') && (! x($_SESSION,'administrator')))) {
			notice( t('Permission denied.') . EOL );
			return;
		}
		$blocked = 1;
		$verified = 0;
		break;
	}


	$arr = $_POST;

	$arr['blocked'] = $blocked;
	$arr['verified'] = $verified;

	$result = create_user($arr);

	if(! $result['success']) {
		notice($result['message']);
		return;
	}

	$user = $result['user'];

	if($netpublish && $a->config['register_policy'] != REGISTER_APPROVE) {
		$url = $a->get_baseurl() . '/profile/' . $user['nickname'];
		proc_run('php',"include/directory.php","$url");
	}

	$using_invites = get_config('system','invitation_only');
	$num_invites   = get_config('system','number_invites');
	$invite_id  = ((x($_POST,'invite_id'))  ? notags(trim($_POST['invite_id']))  : '');


	if( $a->config['register_policy'] == REGISTER_OPEN ) {

		if($using_invites && $invite_id) {
			q("delete * from register where hash = '%s' limit 1", dbesc($invite_id));
			set_pconfig($user['uid'],'system','invites_remaining',$num_invites);
		}

		// Only send a password mail when the password wasn't manually provided
		if (!x($_POST,'password1') OR !x($_POST,'confirm')) {
			$res = send_register_open_eml(
				$user['email'],
				$a->config['sitename'],
				$a->get_baseurl(),
				$user['username'],
				$result['password']);

			if($res) {
				info( t('Registration successful. Please check your email for further instructions.') . EOL ) ;
				goaway(z_root());
			} else {
				notice(
					sprintf(
						t('Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'),
						 $user['email'],
						 $result['password']
						 ). EOL
				);
			}
		} else {
			info( t('Registration successful.') . EOL ) ;
			goaway(z_root());
		}
	}
	elseif($a->config['register_policy'] == REGISTER_APPROVE) {
		if(! strlen($a->config['admin_email'])) {
			notice( t('Your registration can not be processed.') . EOL);
			goaway(z_root());
		}

		$hash = random_string();
		$r = q("INSERT INTO `register` ( `hash`, `created`, `uid`, `password`, `language` ) VALUES ( '%s', '%s', %d, '%s', '%s' ) ",
			dbesc($hash),
			dbesc(datetime_convert()),
			intval($user['uid']),
			dbesc($result['password']),
			dbesc($lang)
		);

		// invite system
		if($using_invites && $invite_id) {
			q("delete * from register where hash = '%s' limit 1", dbesc($invite_id));
			set_pconfig($user['uid'],'system','invites_remaining',$num_invites);
		}

		// send email to admins
		$admin_mail_list = "'".implode("','", array_map(dbesc, explode(",", str_replace(" ", "", $a->config['admin_email']))))."'";
		$adminlist = q("SELECT uid, language, email FROM user WHERE email IN (%s)",
			$admin_mail_list
		);


		foreach ($adminlist as $admin) {
			notification(array(
				'type' => NOTIFY_SYSTEM,
				'event' => 'SYSTEM_REGISTER_REQUEST',
				'source_name' => $user['username'],
				'source_mail' => $user['email'],
				'source_nick' => $user['nickname'],
				'source_link' => $a->get_baseurl()."/admin/users/",
				'link' => $a->get_baseurl()."/admin/users/",
				'source_photo' => $a->get_baseurl() . "/photo/avatar/".$user['uid'].".jpg",
				'to_email' => $admin['email'],
				'uid' => $admin['uid'],
				'language' => ($admin['language']?$admin['language']:'en'))
			);
		}


		info( t('Your registration is pending approval by the site owner.') . EOL ) ;
		goaway(z_root());


	}

	return;
}}






if(! function_exists('register_content')) {
function register_content(&$a) {

	// logged in users can register others (people/pages/groups)
	// even with closed registrations, unless specifically prohibited by site policy.
	// 'block_extended_register' blocks all registrations, period.

	$block = get_config('system','block_extended_register');

	if(local_user() && ($block)) {
		notice("Permission denied." . EOL);
		return;
	}

	if((! local_user()) && ($a->config['register_policy'] == REGISTER_CLOSED)) {
		notice("Permission denied." . EOL);
		return;
	}

	$max_dailies = intval(get_config('system','max_daily_registrations'));
	if($max_dailies) {
		$r = q("select count(*) as total from user where register_date > UTC_TIMESTAMP - INTERVAL 1 day");
		if($r && $r[0]['total'] >= $max_dailies) {
			logger('max daily registrations exceeded.');
			notice( t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
			return;
		}
	}

	if(x($_SESSION,'theme'))
		unset($_SESSION['theme']);
	if(x($_SESSION,'mobile-theme'))
		unset($_SESSION['mobile-theme']);


	$username     = ((x($_POST,'username'))     ? $_POST['username']     : ((x($_GET,'username'))     ? $_GET['username']              : ''));
	$email        = ((x($_POST,'email'))        ? $_POST['email']        : ((x($_GET,'email'))        ? $_GET['email']                 : ''));
	$openid_url   = ((x($_POST,'openid_url'))   ? $_POST['openid_url']   : ((x($_GET,'openid_url'))   ? $_GET['openid_url']            : ''));
	$nickname     = ((x($_POST,'nickname'))     ? $_POST['nickname']     : ((x($_GET,'nickname'))     ? $_GET['nickname']              : ''));
	$photo        = ((x($_POST,'photo'))        ? $_POST['photo']        : ((x($_GET,'photo'))        ? hex2bin($_GET['photo'])        : ''));
	$invite_id    = ((x($_POST,'invite_id'))    ? $_POST['invite_id']    : ((x($_GET,'invite_id'))    ? $_GET['invite_id']             : ''));

	$noid = get_config('system','no_openid');

	if($noid) {
		$oidhtml = '';
		$fillwith = '';
		$fillext = '';
		$oidlabel = '';
	}
	else {
		$oidhtml = '<label for="register-openid" id="label-register-openid" >$oidlabel</label><input type="text" maxlength="60" size="32" name="openid_url" class="openid" id="register-openid" value="$openid" >';
		$fillwith = t("You may \x28optionally\x29 fill in this form via OpenID by supplying your OpenID and clicking 'Register'.");
		$fillext =  t('If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.');
		$oidlabel = t("Your OpenID \x28optional\x29: ");
	}

	// I set this and got even more fake names than before...

	$realpeople = ''; // t('Members of this network prefer to communicate with real people who use their real names.');

	if(get_config('system','publish_all')) {
		$profile_publish_reg = '<input type="hidden" name="profile_publish_reg" value="1" />';
	}
	else {
		$publish_tpl = get_markup_template("profile_publish.tpl");
		$profile_publish = replace_macros($publish_tpl,array(
			'$instance'     => 'reg',
			'$pubdesc'      => t('Include your profile in member directory?'),
			'$yes_selected' => ' checked="checked" ',
			'$no_selected'  => '',
			'$str_yes'      => t('Yes'),
			'$str_no'       => t('No'),
		));
	}

	$r = q("SELECT count(*) AS `contacts` FROM `contact`");
	$passwords = !$r[0]["contacts"];

	$license = '';

	$o = get_markup_template("register.tpl");

	$arr = array('template' => $o);

	call_hooks('register_form',$arr);

	$o = $arr['template'];

	$o = replace_macros($o, array(
		'$oidhtml' => $oidhtml,
		'$invitations' => get_config('system','invitation_only'),
		'$invite_desc' => t('Membership on this site is by invitation only.'),
		'$invite_label' => t('Your invitation ID: '),
		'$invite_id' => $invite_id,
		'$realpeople' => $realpeople,
		'$regtitle'  => t('Registration'),
		'$registertext' =>((x($a->config,'register_text'))
			? bbcode($a->config['register_text'])
			: "" ),
		'$fillwith'  => $fillwith,
		'$fillext'   => $fillext,
		'$oidlabel'  => $oidlabel,
		'$openid'    => $openid_url,
		'$namelabel' => t('Your Full Name ' . "\x28" . 'e.g. Joe Smith, real or real-looking' . "\x29" . ': '),
		'$addrlabel' => t('Your Email Address: '),
		'$passwords' => $passwords,
		'$password1' => array('password1', t('New Password:'), '', t('Leave empty for an auto generated password.')),
		'$password2' => array('confirm', t('Confirm:'), '', ''),
		'$nickdesc'  => str_replace('$sitename',$a->get_hostname(),t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be \'<strong>nickname@$sitename</strong>\'.')),
		'$nicklabel' => t('Choose a nickname: '),
		'$photo'     => $photo,
		'$publish'   => $profile_publish,
		'$regbutt'   => t('Register'),
		'$username'  => $username,
		'$email'     => $email,
		'$nickname'  => $nickname,
		'$license'   => $license,
		'$sitename'  => $a->get_hostname(),
		'$importh'   => t('Import'),
		'$importt'   => t('Import your profile to this friendica instance'),

	));
	return $o;

}}

