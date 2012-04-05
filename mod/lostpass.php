<?php


function lostpass_post(&$a) {

	$loginame = notags(trim($_POST['login-name']));
	if(! $loginame)
		goaway(z_root());

	$r = q("SELECT * FROM `user` WHERE ( `email` = '%s' OR `nickname` = '%s' ) AND `verified` = 1 AND `blocked` = 0 LIMIT 1",
		dbesc($loginame),
		dbesc($loginame)
	);

	if(! count($r)) {
		notice( t('No valid account found.') . EOL);
		goaway(z_root());
	}

	$uid = $r[0]['uid'];
	$username = $r[0]['username'];
	$email = $r[0]['email'];

	$new_password = autoname(12) . mt_rand(100,9999);
	$new_password_encoded = hash('whirlpool',$new_password);

	$r = q("UPDATE `user` SET `pwdreset` = '%s' WHERE `uid` = %d LIMIT 1",
		dbesc($new_password_encoded),
		intval($uid)
	);
	if($r)
		info( t('Password reset request issued. Check your email.') . EOL);

	$email_tpl = get_intltext_template("lostpass_eml.tpl");
	$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => $a->config['sitename'],
			'$siteurl' =>  $a->get_baseurl(),
			'$username' => $username,
			'$email' => $email,
			'$reset_link' => $a->get_baseurl() . '/lostpass?verify=' . $new_password
	));

	$res = mail($email, sprintf( t('Password reset requested at %s'),$a->config['sitename']),
			$email_tpl,
			'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
			. 'Content-type: text/plain; charset=UTF-8' . "\n"
			. 'Content-transfer-encoding: 8bit' );


	goaway(z_root());
}


function lostpass_content(&$a) {


	if(x($_GET,'verify')) {
		$verify = $_GET['verify'];
		$hash = hash('whirlpool', $verify);

		$r = q("SELECT * FROM `user` WHERE `pwdreset` = '%s' LIMIT 1",
			dbesc($hash)
		);
		if(! count($r)) {
			notice( t("Request could not be verified. \x28You may have previously submitted it.\x29 Password reset failed.") . EOL);
			goaway(z_root());
			return;
		}
		$uid = $r[0]['uid'];
		$username = $r[0]['username'];
		$email = $r[0]['email'];

		$new_password = autoname(6) . mt_rand(100,9999);
		$new_password_encoded = hash('whirlpool',$new_password);

		$r = q("UPDATE `user` SET `password` = '%s', `pwdreset` = ''  WHERE `uid` = %d LIMIT 1",
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
				'$lbl5' => '<a href="' . $a->get_baseurl() . '">' . t('click here to login') . '</a>.',
				'$lbl6' => t('Your password may be changed from the <em>Settings</em> page after successful login.'),
				'$newpass' => $new_password,
				'$baseurl' => $a->get_baseurl()

			));
				info("Your password has been reset." . EOL);



			$email_tpl = get_intltext_template("passchanged_eml.tpl");
			$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => $a->config['sitename'],
			'$siteurl' =>  $a->get_baseurl(),
			'$username' => $username,
			'$email' => $email,
			'$new_password' => $new_password,
			'$uid' => $newuid ));

			$res = mail($email,"Your password has changed at {$a->config['sitename']}",$email_tpl,
				'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
				. 'Content-type: text/plain; charset=UTF-8' . "\n"
				. 'Content-transfer-encoding: 8bit' );

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
