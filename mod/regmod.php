<?php

require_once('include/enotify.php');
require_once('include/user.php');

function user_allow($hash) {

	$a = get_app();

	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);


	if (! dbm::is_result($register)) {
		return false;
	}

	$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);

	if (! dbm::is_result($user)) {
		killme();
	}

	$r = q("DELETE FROM `register` WHERE `hash` = '%s'",
		dbesc($register[0]['hash'])
	);


	$r = q("UPDATE `user` SET `blocked` = 0, `verified` = 1 WHERE `uid` = %d",
		intval($register[0]['uid'])
	);

	$r = q("SELECT * FROM `profile` WHERE `uid` = %d AND `is-default` = 1",
		intval($user[0]['uid'])
	);
	if (dbm::is_result($r) && $r[0]['net-publish']) {
		$url = App::get_baseurl() . '/profile/' . $user[0]['nickname'];
		if ($url && strlen(get_config('system','directory'))) {
			proc_run(PRIORITY_LOW, "include/directory.php", $url);
		}
	}

	push_lang($register[0]['language']);

	send_register_open_eml(
		$user[0]['email'],
		$a->config['sitename'],
		App::get_baseurl(),
		$user[0]['username'],
		$register[0]['password']);

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

	if(! dbm::is_result($register))
		return false;

	$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);

	$r = q("DELETE FROM `user` WHERE `uid` = %d",
		intval($register[0]['uid'])
	);
	$r = q("DELETE FROM `contact` WHERE `uid` = %d",
		intval($register[0]['uid'])
	);
	$r = q("DELETE FROM `profile` WHERE `uid` = %d",
		intval($register[0]['uid'])
	);

	$r = q("DELETE FROM `register` WHERE `hash` = '%s'",
		dbesc($register[0]['hash'])
	);
	notice( sprintf(t('Registration revoked for %s'), $user[0]['username']) . EOL);
	return true;

}

function regmod_content(App $a) {

	global $lang;

	$_SESSION['return_url'] = $a->cmd;

	if (! local_user()) {
		info( t('Please login.') . EOL);
		$o .= '<br /><br />' . login(($a->config['register_policy'] == REGISTER_CLOSED) ? 0 : 1);
		return $o;
	}

	if ((!is_site_admin()) || (x($_SESSION,'submanage') && intval($_SESSION['submanage']))) {
		notice( t('Permission denied.') . EOL);
		return '';
	}

	if ($a->argc != 3) {
		killme();
	}

	$cmd  = $a->argv[1];
	$hash = $a->argv[2];



	if ($cmd === 'deny') {
		user_deny($hash);
		goaway(App::get_baseurl()."/admin/users/");
		killme();
	}

	if ($cmd === 'allow') {
		user_allow($hash);
		goaway(App::get_baseurl()."/admin/users/");
		killme();
	}
}
