<?php
/**
 * @file mod/register.php
 */
use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Model\User;

require_once 'include/enotify.php';
require_once 'include/bbcode.php';

function register_post(App $a)
{
	check_form_security_token_redirectOnErr('/register', 'register');

	global $lang;

	$verified = 0;
	$blocked  = 1;

	$arr = ['post' => $_POST];
	Addon::callHooks('register_post', $arr);

	$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
	if ($max_dailies) {
		$r = q("select count(*) as total from user where register_date > UTC_TIMESTAMP - INTERVAL 1 day");
		if ($r && $r[0]['total'] >= $max_dailies) {
			return;
		}
	}

	switch ($a->config['register_policy']) {
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
			if ((!x($_SESSION, 'authenticated') && (!x($_SESSION, 'administrator')))) {
				notice(t('Permission denied.') . EOL);
				return;
			}
			$blocked = 1;
			$verified = 0;
			break;
	}


	$arr = $_POST;

	$arr['blocked'] = $blocked;
	$arr['verified'] = $verified;
	$arr['language'] = get_browser_language();

	try {
		$result = User::create($arr);
	} catch (Exception $e) {
		notice($e->getMessage());
		return;
	}

	$user = $result['user'];

	if ($netpublish && $a->config['register_policy'] != REGISTER_APPROVE) {
		$url = System::baseUrl() . '/profile/' . $user['nickname'];
		Worker::add(PRIORITY_LOW, "Directory", $url);
	}

	$using_invites = Config::get('system', 'invitation_only');
	$num_invites   = Config::get('system', 'number_invites');
	$invite_id = ((x($_POST, 'invite_id')) ? notags(trim($_POST['invite_id'])) : '');

	if ($a->config['register_policy'] == REGISTER_OPEN) {
		if ($using_invites && $invite_id) {
			q("delete * from register where hash = '%s' limit 1", dbesc($invite_id));
			PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
		}

		// Only send a password mail when the password wasn't manually provided
		if (!x($_POST, 'password1') || !x($_POST, 'confirm')) {
			$res = User::sendRegisterOpenEmail(
					$user['email'], $a->config['sitename'], System::baseUrl(), $user['username'], $result['password']);

			if ($res) {
				info(t('Registration successful. Please check your email for further instructions.') . EOL);
				goaway(System::baseUrl());
			} else {
				notice(
					t('Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.',
						$user['email'],
						$result['password'])
					. EOL
				);
			}
		} else {
			info(t('Registration successful.') . EOL);
			goaway(System::baseUrl());
		}
	} elseif ($a->config['register_policy'] == REGISTER_APPROVE) {
		if (!strlen($a->config['admin_email'])) {
			notice(t('Your registration can not be processed.') . EOL);
			goaway(System::baseUrl());
		}

		$hash = random_string();
		$r = q("INSERT INTO `register` ( `hash`, `created`, `uid`, `password`, `language`, `note` ) VALUES ( '%s', '%s', %d, '%s', '%s', '%s' ) ",
			dbesc($hash),
			dbesc(datetime_convert()),
			intval($user['uid']),
			dbesc($result['password']),
			dbesc($lang),
			dbesc($_POST['permonlybox'])
		);

		// invite system
		if ($using_invites && $invite_id) {
			q("DELETE * FROM `register` WHERE `hash` = '%s' LIMIT 1", dbesc($invite_id));
			PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
		}

		// send email to admins
		$admin_mail_list = "'" . implode("','", array_map(dbesc, explode(",", str_replace(" ", "", $a->config['admin_email'])))) . "'";
		$adminlist = q("SELECT uid, language, email FROM user WHERE email IN (%s)",
			$admin_mail_list
		);

		// send notification to admins
		foreach ($adminlist as $admin) {
			notification([
				'type'         => NOTIFY_SYSTEM,
				'event'        => 'SYSTEM_REGISTER_REQUEST',
				'source_name'  => $user['username'],
				'source_mail'  => $user['email'],
				'source_nick'  => $user['nickname'],
				'source_link'  => System::baseUrl() . "/admin/users/",
				'link'         => System::baseUrl() . "/admin/users/",
				'source_photo' => System::baseUrl() . "/photo/avatar/" . $user['uid'] . ".jpg",
				'to_email'     => $admin['email'],
				'uid'          => $admin['uid'],
				'language'     => $admin['language'] ? $admin['language'] : 'en',
				'show_in_notification_page' => false
			]);
		}
		// send notification to the user, that the registration is pending
		User::sendRegisterPendingEmail(
			$user['email'], $a->config['sitename'], $user['username']);

		info(t('Your registration is pending approval by the site owner.') . EOL);
		goaway(System::baseUrl());
	}

	return;
}

function register_content(App $a)
{
	// logged in users can register others (people/pages/groups)
	// even with closed registrations, unless specifically prohibited by site policy.
	// 'block_extended_register' blocks all registrations, period.
	$block = Config::get('system', 'block_extended_register');

	if (local_user() && ($block)) {
		notice("Permission denied." . EOL);
		return;
	}

	if ((!local_user()) && ($a->config['register_policy'] == REGISTER_CLOSED)) {
		notice("Permission denied." . EOL);
		return;
	}

	$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
	if ($max_dailies) {
		$r = q("select count(*) as total from user where register_date > UTC_TIMESTAMP - INTERVAL 1 day");
		if ($r && $r[0]['total'] >= $max_dailies) {
			logger('max daily registrations exceeded.');
			notice(t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
			return;
		}
	}

	if (x($_SESSION, 'theme')) {
		unset($_SESSION['theme']);
	}
	if (x($_SESSION, 'mobile-theme')) {
		unset($_SESSION['mobile-theme']);
	}


	$username   = x($_REQUEST, 'username')   ? $_REQUEST['username']   : '';
	$email      = x($_REQUEST, 'email')      ? $_REQUEST['email']      : '';
	$openid_url = x($_REQUEST, 'openid_url') ? $_REQUEST['openid_url'] : '';
	$nickname   = x($_REQUEST, 'nickname')   ? $_REQUEST['nickname']   : '';
	$photo      = x($_REQUEST, 'photo')      ? $_REQUEST['photo']      : '';
	$invite_id  = x($_REQUEST, 'invite_id')  ? $_REQUEST['invite_id']  : '';

	$noid = Config::get('system', 'no_openid');

	if ($noid) {
		$oidhtml  = '';
		$fillwith = '';
		$fillext  = '';
		$oidlabel = '';
	} else {
		$oidhtml  = '<label for="register-openid" id="label-register-openid" >$oidlabel</label><input type="text" maxlength="60" size="32" name="openid_url" class="openid" id="register-openid" value="$openid" >';
		$fillwith = t("You may \x28optionally\x29 fill in this form via OpenID by supplying your OpenID and clicking 'Register'.");
		$fillext  = t('If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.');
		$oidlabel = t("Your OpenID \x28optional\x29: ");
	}

	// I set this and got even more fake names than before...
	$realpeople = ''; // t('Members of this network prefer to communicate with real people who use their real names.');

	if (Config::get('system', 'publish_all')) {
		$profile_publish = '<input type="hidden" name="profile_publish_reg" value="1" />';
	} else {
		$publish_tpl = get_markup_template("profile_publish.tpl");
		$profile_publish = replace_macros($publish_tpl, [
			'$instance' => 'reg',
			'$pubdesc' => t('Include your profile in member directory?'),
			'$yes_selected' => ' checked="checked" ',
			'$no_selected' => '',
			'$str_yes' => t('Yes'),
			'$str_no' => t('No'),
		]);
	}

	$r = q("SELECT COUNT(*) AS `contacts` FROM `contact`");
	$passwords = !$r[0]["contacts"];

	$license = '';

	$tpl = get_markup_template("register.tpl");

	$arr = ['template' => $tpl];

	Addon::callHooks('register_form', $arr);

	$tpl = $arr['template'];

	$o = replace_macros($tpl, [
		'$oidhtml' => $oidhtml,
		'$invitations' => Config::get('system', 'invitation_only'),
		'$permonly'    => $a->config['register_policy'] == REGISTER_APPROVE,
		'$permonlybox' => ['permonlybox', t('Note for the admin'), '', t('Leave a message for the admin, why you want to join this node')],
		'$invite_desc' => t('Membership on this site is by invitation only.'),
		'$invite_label' => t('Your invitation ID: '),
		'$invite_id'  => $invite_id,
		'$realpeople' => $realpeople,
		'$regtitle'  => t('Registration'),
		'$registertext' => x($a->config, 'register_text') ? bbcode($a->config['register_text']) : "",
		'$fillwith'  => $fillwith,
		'$fillext'   => $fillext,
		'$oidlabel'  => $oidlabel,
		'$openid'    => $openid_url,
		'$namelabel' => t('Your Full Name ' . "\x28" . 'e.g. Joe Smith, real or real-looking' . "\x29" . ': '),
		'$addrlabel' => t('Your Email Address: (Initial information will be send there, so this has to be an existing address.)'),
		'$passwords' => $passwords,
		'$password1' => ['password1', t('New Password:'), '', t('Leave empty for an auto generated password.')],
		'$password2' => ['confirm', t('Confirm:'), '', ''],
		'$nickdesc'  => t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be \'<strong>nickname@%s</strong>\'.', $a->get_hostname()),
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
		'$form_security_token' => get_form_security_token("register")
	]);
	return $o;
}
