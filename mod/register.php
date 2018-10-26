<?php
/**
 * @file mod/register.php
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Text;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Model;
use Friendica\Module\Tos;

require_once 'include/enotify.php';

function register_post(App $a)
{
	BaseModule::checkFormSecurityTokenRedirectOnError('/register', 'register');

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

	switch (Config::get('config', 'register_policy')) {
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
			if (empty($_SESSION['authenticated']) && empty($_SESSION['administrator'])) {
				notice(L10n::t('Permission denied.') . EOL);
				return;
			}
			$blocked = 1;
			$verified = 0;
			break;
	}

	$netpublish = !empty($_POST['profile_publish_reg']);

	$arr = $_POST;

	$arr['blocked'] = $blocked;
	$arr['verified'] = $verified;
	$arr['language'] = L10n::detectLanguage();

	try {
		$result = Model\User::create($arr);
	} catch (Exception $e) {
		notice($e->getMessage());
		return;
	}

	$user = $result['user'];

	if ($netpublish && intval(Config::get('config', 'register_policy')) !== REGISTER_APPROVE) {
		$url = $a->getBaseUrl() . '/profile/' . $user['nickname'];
		Worker::add(PRIORITY_LOW, "Directory", $url);
	}

	$using_invites = Config::get('system', 'invitation_only');
	$num_invites   = Config::get('system', 'number_invites');
	$invite_id = ((x($_POST, 'invite_id')) ? Text::noTags(trim($_POST['invite_id'])) : '');

	if (intval(Config::get('config', 'register_policy')) === REGISTER_OPEN) {
		if ($using_invites && $invite_id) {
			Model\Register::deleteByHash($invite_id);
			PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
		}

		// Only send a password mail when the password wasn't manually provided
		if (!x($_POST, 'password1') || !x($_POST, 'confirm')) {
			$res = Model\User::sendRegisterOpenEmail(
				$user,
				Config::get('config', 'sitename'),
				$a->getBaseUrl(),
				$result['password']
			);

			if ($res) {
				info(L10n::t('Registration successful. Please check your email for further instructions.') . EOL);
				$a->internalRedirect();
			} else {
				notice(
					L10n::t('Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.',
						$user['email'],
						$result['password'])
					. EOL
				);
			}
		} else {
			info(L10n::t('Registration successful.') . EOL);
			$a->internalRedirect();
		}
	} elseif (intval(Config::get('config', 'register_policy')) === REGISTER_APPROVE) {
		if (!strlen(Config::get('config', 'admin_email'))) {
			notice(L10n::t('Your registration can not be processed.') . EOL);
			$a->internalRedirect();
		}

		Model\Register::createForApproval($user['uid'], Config::get('system', 'language'), $_POST['permonlybox']);

		// invite system
		if ($using_invites && $invite_id) {
			Model\Register::deleteByHash($invite_id);
			PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
		}

		// send email to admins
		$admin_mail_list = "'" . implode("','", array_map(['Friendica\Database\DBA', 'escape'], explode(",", str_replace(" ", "", Config::get('config', 'admin_email'))))) . "'";
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
				'source_link'  => $a->getBaseUrl() . "/admin/users/",
				'link'         => $a->getBaseUrl() . "/admin/users/",
				'source_photo' => $a->getBaseUrl() . "/photo/avatar/" . $user['uid'] . ".jpg",
				'to_email'     => $admin['email'],
				'uid'          => $admin['uid'],
				'language'     => $admin['language'] ? $admin['language'] : 'en',
				'show_in_notification_page' => false
			]);
		}
		// send notification to the user, that the registration is pending
		Model\User::sendRegisterPendingEmail(
			$user,
			Config::get('config', 'sitename'),
			$a->getBaseURL(),
			$result['password']
		);

		info(L10n::t('Your registration is pending approval by the site owner.') . EOL);
		$a->internalRedirect();
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

	if ((!local_user()) && (intval(Config::get('config', 'register_policy')) === REGISTER_CLOSED)) {
		notice("Permission denied." . EOL);
		return;
	}

	$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
	if ($max_dailies) {
		$r = q("select count(*) as total from user where register_date > UTC_TIMESTAMP - INTERVAL 1 day");
		if ($r && $r[0]['total'] >= $max_dailies) {
			logger('max daily registrations exceeded.');
			notice(L10n::t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
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
		$fillwith = L10n::t("You may \x28optionally\x29 fill in this form via OpenID by supplying your OpenID and clicking 'Register'.");
		$fillext  = L10n::t('If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.');
		$oidlabel = L10n::t("Your OpenID \x28optional\x29: ");
	}

	// I set this and got even more fake names than before...
	$realpeople = ''; // L10n::t('Members of this network prefer to communicate with real people who use their real names.');

	if (Config::get('system', 'publish_all')) {
		$profile_publish = '<input type="hidden" name="profile_publish_reg" value="1" />';
	} else {
		$publish_tpl = get_markup_template("profile_publish.tpl");
		$profile_publish = replace_macros($publish_tpl, [
			'$instance' => 'reg',
			'$pubdesc' => L10n::t('Include your profile in member directory?'),
			'$yes_selected' => '',
			'$no_selected' => ' checked="checked"',
			'$str_yes' => L10n::t('Yes'),
			'$str_no' => L10n::t('No'),
		]);
	}

	$r = q("SELECT COUNT(*) AS `contacts` FROM `contact`");
	$passwords = !$r[0]["contacts"];

	$license = '';

	$tpl = get_markup_template("register.tpl");

	$arr = ['template' => $tpl];

	Addon::callHooks('register_form', $arr);

	$tpl = $arr['template'];

	$tos = new Tos();

	$o = replace_macros($tpl, [
		'$oidhtml' => $oidhtml,
		'$invitations' => Config::get('system', 'invitation_only'),
		'$permonly'    => intval(Config::get('config', 'register_policy')) === REGISTER_APPROVE,
		'$permonlybox' => ['permonlybox', L10n::t('Note for the admin'), '', L10n::t('Leave a message for the admin, why you want to join this node')],
		'$invite_desc' => L10n::t('Membership on this site is by invitation only.'),
		'$invite_label' => L10n::t('Your invitation code: '),
		'$invite_id'  => $invite_id,
		'$realpeople' => $realpeople,
		'$regtitle'  => L10n::t('Registration'),
		'$registertext' => BBCode::convert(Config::get('config', 'register_text', '')),
		'$fillwith'  => $fillwith,
		'$fillext'   => $fillext,
		'$oidlabel'  => $oidlabel,
		'$openid'    => $openid_url,
		'$namelabel' => L10n::t('Your Full Name ' . "\x28" . 'e.g. Joe Smith, real or real-looking' . "\x29" . ': '),
		'$addrlabel' => L10n::t("Your Email Address: \x28Initial information will be send there, so this has to be an existing address.\x29"),
		'$passwords' => $passwords,
		'$password1' => ['password1', L10n::t('New Password:'), '', L10n::t('Leave empty for an auto generated password.')],
		'$password2' => ['confirm', L10n::t('Confirm:'), '', ''],
		'$nickdesc'  => L10n::t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be \'<strong>nickname@%s</strong>\'.', $a->getHostName()),
		'$nicklabel' => L10n::t('Choose a nickname: '),
		'$photo'     => $photo,
		'$publish'   => $profile_publish,
		'$regbutt'   => L10n::t('Register'),
		'$username'  => $username,
		'$email'     => $email,
		'$nickname'  => $nickname,
		'$license'   => $license,
		'$sitename'  => $a->getHostName(),
		'$importh'   => L10n::t('Import'),
		'$importt'   => L10n::t('Import your profile to this friendica instance'),
		'$showtoslink' => Config::get('system', 'tosdisplay'),
		'$tostext'   => L10n::t('Terms of Service'),
		'$showprivstatement' => Config::get('system', 'tosprivstatement'),
		'$privstatement' => $tos->privacy_complete,
		'$baseurl'   => System::baseurl(),
		'$form_security_token' => BaseModule::getFormSecurityToken("register"),
		'$explicit_content' => Config::get('system', 'explicit_content', false),
		'$explicit_content_note' => L10n::t('Note: This node explicitly contains adult content')
	]);
	return $o;
}
