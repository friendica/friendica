<?php

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
    

	require_once('include/user.php');

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

		$email_tpl = get_intltext_template("register_open_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $user['username'],
				'$email' => $user['email'],
				'$password' => $result['password'],
				'$uid' => $user['uid'] ));

		$res = mail($user['email'], sprintf(t('Registration details for %s'), $a->config['sitename']),
			$email_tpl, 
				'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
				. 'Content-type: text/plain; charset=UTF-8' . "\n"
				. 'Content-transfer-encoding: 8bit' );


		if($res) {
			info( t('Registration successful. Please check your email for further instructions.') . EOL ) ;
			goaway(z_root());
		}
		else {
			notice( t('Failed to send email message. Here is the message that failed.') . $email_tpl . EOL );
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

		$r = q("SELECT `language` FROM `user` WHERE `email` = '%s' LIMIT 1",
			dbesc($a->config['admin_email'])
		);
		if(count($r))
			push_lang($r[0]['language']);
		else
			push_lang('en');

		if($using_invites && $invite_id) {
			q("delete * from register where hash = '%s' limit 1", dbesc($invite_id));
			set_pconfig($user['uid'],'system','invites_remaining',$num_invites);
		}

		$email_tpl = get_intltext_template("register_verify_eml.tpl");
		$email_tpl = replace_macros($email_tpl, array(
				'$sitename' => $a->config['sitename'],
				'$siteurl' =>  $a->get_baseurl(),
				'$username' => $user['username'],
				'$email' => $user['email'],
				'$password' => $result['password'],
				'$uid' => $user['uid'],
				'$hash' => $hash
		 ));

		$res = mail($a->config['admin_email'], sprintf(t('Registration request at %s'), $a->config['sitename']),
			$email_tpl,
				'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
				. 'Content-type: text/plain; charset=UTF-8' . "\n"
				. 'Content-transfer-encoding: 8bit' );

		pop_lang();

		if($res) {
			info( t('Your registration is pending approval by the site owner.') . EOL ) ;
			goaway(z_root());
		}

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
			? '<div class="error-message">' . $a->config['register_text'] . '</div>'
			: "" ),
		'$fillwith'  => $fillwith,
		'$fillext'   => $fillext,
		'$oidlabel'  => $oidlabel,
		'$openid'    => $openid_url,
		'$namelabel' => t('Your Full Name ' . "\x28" . 'e.g. Joe Smith' . "\x29" . ': '),
		'$addrlabel' => t('Your Email Address: '),
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
      
	));
	return $o;

}}

