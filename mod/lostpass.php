<?php

require_once('include/email.php');
require_once('include/enotify.php');
require_once('include/text.php');

function lostpass_post(App &$a) {

	$loginame = notags(trim($_POST['login-name']));
	if(! $loginame)
		goaway(z_root());

	$r = q("SELECT * FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' ) AND `verified` = 1 AND `blocked` = 0 LIMIT 1",
		dbesc($loginame),
		dbesc($loginame)
	);

	if (! dbm::is_result($r)) {
		notice( t('No valid account found.') . EOL);
		goaway(z_root());
	}

	$uid = $r[0]['uid'];
	$username = $r[0]['username'];
	$email = $r[0]['email'];

	$new_password = autoname(12) . mt_rand(100,9999);
	$new_password_encoded = hash('whirlpool',$new_password);

	$r = q("UPDATE `user` SET `pwdreset` = '%s' WHERE `uid` = %d",
		dbesc($new_password_encoded),
		intval($uid)
	);
	if($r)
		info( t('Password reset request issued. Check your email.') . EOL);


	$sitename = $a->config['sitename'];
	$resetlink = App::get_baseurl() . '/lostpass?verify=' . $new_password;

	$preamble = deindent(t('
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email.

		Your password will not be changed unless we can verify that you
		issued this request.'));
	$body = deindent(t('
		Follow this link to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'));

	$preamble = sprintf($preamble, $username, $sitename);
	$body = sprintf($body, $resetlink, App::get_baseurl(), $email);

	notification(array(
		'type' => "SYSTEM_EMAIL",
		'to_email' => $email,
		'subject'=> sprintf( t('Password reset requested at %s'),$sitename),
		'preamble'=> $preamble,
		'body' => $body));

	goaway(z_root());

}


function lostpass_content(App &$a) {


	if(x($_GET,'verify')) {
		$verify = $_GET['verify'];
		$hash = hash('whirlpool', $verify);

		$r = q("SELECT * FROM `user` WHERE `pwdreset` = '%s' LIMIT 1",
			dbesc($hash)
		);
		if (! dbm::is_result($r)) {
			$o =  t("Request could not be verified. \x28You may have previously submitted it.\x29 Password reset failed.");
			return $o;
		}
		$uid = $r[0]['uid'];
		$username = $r[0]['username'];
		$email = $r[0]['email'];

		$new_password = autoname(6) . mt_rand(100,9999);
		$new_password_encoded = hash('whirlpool',$new_password);

		$r = q("UPDATE `user` SET `password` = '%s', `pwdreset` = ''  WHERE `uid` = %d",
			dbesc($new_password_encoded),
			intval($uid)
		);
		if($r) {
			$tpl = get_markup_template('pwdreset.tpl');
			$o .= replace_macros($tpl,array(
				'$lbl1' => t('Password Reset'),
				'$lbl2' => t('Your password has been reset as requested.'),
				'$lbl3' => t('Your new password is'),
				'$lbl4' => t('Save or copy your new password - and then'),
				'$lbl5' => '<a href="' . App::get_baseurl() . '">' . t('click here to login') . '</a>.',
				'$lbl6' => t('Your password may be changed from the <em>Settings</em> page after successful login.'),
				'$newpass' => $new_password,
				'$baseurl' => App::get_baseurl()

			));
				info("Your password has been reset." . EOL);


			$sitename = $a->config['sitename'];
			// $username, $email, $new_password
			$preamble = deindent(t('
				Dear %1$s,
					Your password has been changed as requested. Please retain this
				information for your records (or change your password immediately to
				something that you will remember).
			'));
			$body = deindent(t('
				Your login details are as follows:

				Site Location:	%1$s
				Login Name:	%2$s
				Password:	%3$s

				You may change that password from your account settings page after logging in.
			'));

			$preamble = sprintf($preamble, $username);
			$body = sprintf($body, App::get_baseurl(), $email, $new_password);

			notification(array(
				'type' => "SYSTEM_EMAIL",
				'to_email' => $email,
				'subject'=> sprintf( t('Your password has been changed at %s'),$sitename),
				'preamble'=> $preamble,
				'body' => $body));

			return $o;
		}

	}
	else {
		$tpl = get_markup_template('lostpass.tpl');

		$o .= replace_macros($tpl,array(
			'$title' => t('Forgot your Password?'),
			'$desc' => t('Enter your email address and submit to have your password reset. Then check your email for further instructions.'),
			'$name' => t('Nickname or Email: '),
			'$submit' => t('Reset')
		));

		return $o;
	}

}
