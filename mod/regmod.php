<?php

function user_allow($hash) {

	$a = get_app();

	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);


	if(! count($register))
		return false;

	$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	if(! count($user))
		killme();

	$r = q("DELETE FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($register[0]['hash'])
	);


	$r = q("UPDATE `user` SET `blocked` = 0, `verified` = 1 WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	$r = q("SELECT * FROM `profile` WHERE `uid` = %d AND `is-default` = 1",
		intval($user[0]['uid'])
	);
	if(count($r) && $r[0]['net-publish']) {
		$url = $a->get_baseurl() . '/profile/' . $user[0]['nickname'];
		if($url && strlen(get_config('system','directory_submit_url')))
			proc_run('php',"include/directory.php","$url");
	}

	push_lang($register[0]['language']);

	$email_tpl = get_intltext_template("register_open_eml.tpl");
	$email_tpl = replace_macros($email_tpl, array(
			'$sitename' => $a->config['sitename'],
			'$siteurl' =>  $a->get_baseurl(),
			'$username' => $user[0]['username'],
			'$email' => $user[0]['email'],
			'$password' => $register[0]['password'],
			'$uid' => $user[0]['uid']
	));

	$res = mail($user[0]['email'], sprintf(t('Registration details for %s'), $a->config['sitename']),
		$email_tpl,
			'From: ' . t('Administrator') . '@' . $_SERVER['SERVER_NAME'] . "\n"
			. 'Content-type: text/plain; charset=UTF-8' . "\n"
			. 'Content-transfer-encoding: 8bit' );

	pop_lang();

	if($res) {
		info( t('Account approved.') . EOL );
		return true;
	}	

}


// This does not have to go through user_remove() and save the nickname
// permanently against re-registration, as the person was not yet
// allowed to have friends on this system

function user_deny($hash) {

	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);

	if(! count($register))
		return false;

	$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	
	$r = q("DELETE FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);
	$r = q("DELETE FROM `contact` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	); 
	$r = q("DELETE FROM `profile` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	); 

	$r = q("DELETE FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($register[0]['hash'])
	);
	notice( sprintf(t('Registration revoked for %s'), $user[0]['username']) . EOL);
	return true;
	
}

function regmod_content(&$a) {

	global $lang;

	$_SESSION['return_url'] = $a->cmd;

	if(! local_user()) {
		info( t('Please login.') . EOL);
		$o .= '<br /><br />' . login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
		return $o;
	}

	if((!is_site_admin()) || (x($_SESSION,'submanage') && intval($_SESSION['submanage']))) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if($a->argc != 3)
		killme();

	$cmd  = $a->argv[1];
	$hash = $a->argv[2];



	if($cmd === 'deny') {
		if (!user_deny($hash)) killme();
	}

	if($cmd === 'allow') {
		if (!user_allow($hash)) killme();
	}
}
