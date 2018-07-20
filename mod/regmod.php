<?php
/**
 * @file mod/regmod.php
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\DBM;
use Friendica\Model\User;
use Friendica\Module\Login;

require_once 'include/enotify.php';

function user_allow($hash)
{
	$a = get_app();

	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);


	if (!DBM::is_result($register)) {
		return false;
	}

	$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);

	if (!DBM::is_result($user)) {
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
	if (DBM::is_result($r) && $r[0]['net-publish']) {
		$url = System::baseUrl() . '/profile/' . $user[0]['nickname'];
		if ($url && strlen(Config::get('system', 'directory'))) {
			Worker::add(PRIORITY_LOW, "Directory", $url);
		}
	}

	L10n::pushLang($register[0]['language']);

	$res = User::sendRegisterOpenEmail(
		$user[0]['email'],
		Config::get('config', 'sitename'),
		System::baseUrl(),
		$user[0]['username'],
		$register[0]['password']);

	L10n::popLang();

	if ($res) {
		info(L10n::t('Account approved.') . EOL);
		return true;
	}
}

// This does not have to go through user_remove() and save the nickname
// permanently against re-registration, as the person was not yet
// allowed to have friends on this system
function user_deny($hash)
{
	$register = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1",
		dbesc($hash)
	);

	if (!DBM::is_result($register)) {
		return false;
	}

	$user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($register[0]['uid'])
	);

	DBA::delete('user', ['uid' => $register[0]['uid']]);
	DBA::delete('register', ['hash' => $register[0]['hash']]);

	notice(L10n::t('Registration revoked for %s', $user[0]['username']) . EOL);
	return true;
}

function regmod_content(App $a)
{
	if (!local_user()) {
		info(L10n::t('Please login.') . EOL);
		$o = '<br /><br />' . Login::form($a->query_string, intval(Config::get('config', 'register_policy')) === REGISTER_CLOSED ? 0 : 1);
		return $o;
	}

	if ((!is_site_admin()) || (x($_SESSION, 'submanage') && intval($_SESSION['submanage']))) {
		notice(L10n::t('Permission denied.') . EOL);
		return '';
	}

	if ($a->argc != 3) {
		killme();
	}

	$cmd = $a->argv[1];
	$hash = $a->argv[2];

	if ($cmd === 'deny') {
		user_deny($hash);
		goaway(System::baseUrl() . "/admin/users/");
		killme();
	}

	if ($cmd === 'allow') {
		user_allow($hash);
		goaway(System::baseUrl() . "/admin/users/");
		killme();
	}
}
