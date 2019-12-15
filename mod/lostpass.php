<?php

/**
 * @file mod/lostpass.php
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

function lostpass_post(App $a)
{
	$loginame = Strings::escapeTags(trim($_POST['login-name']));
	if (!$loginame) {
		DI::baseUrl()->redirect();
	}

	$condition = ['(`email` = ? OR `nickname` = ?) AND `verified` = 1 AND `blocked` = 0', $loginame, $loginame];
	$user = DBA::selectFirst('user', ['uid', 'username', 'nickname', 'email', 'language'], $condition);
	if (!DBA::isResult($user)) {
		notice(L10n::t('No valid account found.') . EOL);
		DI::baseUrl()->redirect();
	}

	$pwdreset_token = Strings::getRandomName(12) . random_int(1000, 9999);

	$fields = [
		'pwdreset' => $pwdreset_token,
		'pwdreset_time' => DateTimeFormat::utcNow()
	];
	$result = DBA::update('user', $fields, ['uid' => $user['uid']]);
	if ($result) {
		info(L10n::t('Password reset request issued. Check your email.') . EOL);
	}

	$sitename = Config::get('config', 'sitename');
	$resetlink = System::baseUrl() . '/lostpass/' . $pwdreset_token;

	$preamble = Strings::deindent(L10n::t('
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.', $user['username'], $sitename));
	$body = Strings::deindent(L10n::t('
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s', $resetlink, System::baseUrl(), $user['nickname']));

	notification([
		'type'     => SYSTEM_EMAIL,
		'language' => $user['language'],
		'to_name'  => $user['username'],
		'to_email' => $user['email'],
		'uid'      => $user['uid'],
		'subject'  => L10n::t('Password reset requested at %s', $sitename),
		'preamble' => $preamble,
		'body'     => $body
	]);

	DI::baseUrl()->redirect();
}

function lostpass_content(App $a)
{
	if ($a->argc > 1) {
		$pwdreset_token = $a->argv[1];

		$user = DBA::selectFirst('user', ['uid', 'username', 'nickname', 'email', 'pwdreset_time', 'language'], ['pwdreset' => $pwdreset_token]);
		if (!DBA::isResult($user)) {
			notice(L10n::t("Request could not be verified. \x28You may have previously submitted it.\x29 Password reset failed."));

			return lostpass_form();
		}

		// Password reset requests expire in 60 minutes
		if ($user['pwdreset_time'] < DateTimeFormat::utc('now - 1 hour')) {
			$fields = [
				'pwdreset' => null,
				'pwdreset_time' => null
			];
			DBA::update('user', $fields, ['uid' => $user['uid']]);

			notice(L10n::t('Request has expired, please make a new one.'));

			return lostpass_form();
		}

		return lostpass_generate_password($user);
	} else {
		return lostpass_form();
	}
}

function lostpass_form()
{
	$tpl = Renderer::getMarkupTemplate('lostpass.tpl');
	$o = Renderer::replaceMacros($tpl, [
		'$title' => L10n::t('Forgot your Password?'),
		'$desc' => L10n::t('Enter your email address and submit to have your password reset. Then check your email for further instructions.'),
		'$name' => L10n::t('Nickname or Email: '),
		'$submit' => L10n::t('Reset')
	]);

	return $o;
}

function lostpass_generate_password($user)
{
	$o = '';

	$new_password = User::generateNewPassword();
	$result = User::updatePassword($user['uid'], $new_password);
	if (DBA::isResult($result)) {
		$tpl = Renderer::getMarkupTemplate('pwdreset.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$lbl1'    => L10n::t('Password Reset'),
			'$lbl2'    => L10n::t('Your password has been reset as requested.'),
			'$lbl3'    => L10n::t('Your new password is'),
			'$lbl4'    => L10n::t('Save or copy your new password - and then'),
			'$lbl5'    => '<a href="' . System::baseUrl() . '">' . L10n::t('click here to login') . '</a>.',
			'$lbl6'    => L10n::t('Your password may be changed from the <em>Settings</em> page after successful login.'),
			'$newpass' => $new_password,
		]);

		info("Your password has been reset." . EOL);

		$sitename = Config::get('config', 'sitename');
		$preamble = Strings::deindent(L10n::t('
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records ' . "\x28" . 'or change your password immediately to
			something that you will remember' . "\x29" . '.
		', $user['username']));
		$body = Strings::deindent(L10n::t('
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		', System::baseUrl(), $user['nickname'], $new_password));

		notification([
			'type'     => SYSTEM_EMAIL,
			'language' => $user['language'],
			'to_name'  => $user['username'],
			'to_email' => $user['email'],
			'uid'      => $user['uid'],
			'subject'  => L10n::t('Your password has been changed at %s', $sitename),
			'preamble' => $preamble,
			'body'     => $body
		]);
	}

	return $o;
}
