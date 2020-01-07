<?php
/**
 * @file mod/regmod.php
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Registry\App as A;

function user_allow($hash)
{
	$register = Register::getByHash($hash);
	if (!DBA::isResult($register)) {
		return false;
	}

	$user = User::getById($register['uid']);
	if (!DBA::isResult($user)) {
		exit();
	}

	Register::deleteByHash($hash);

	DBA::update('user', ['blocked' => false, 'verified' => true], ['uid' => $register['uid']]);

	$profile = DBA::selectFirst('profile', ['net-publish'], ['uid' => $register['uid'], 'is-default' => true]);

	if (DBA::isResult($profile) && $profile['net-publish'] && Config::get('system', 'directory')) {
		$url = A::baseUrl() . '/profile/' . $user['nickname'];
		Worker::add(PRIORITY_LOW, "Directory", $url);
	}

	$l10n = L10n::withLang($register['language']);

	$res = User::sendRegisterOpenEmail(
		$l10n,
		$user,
		Config::get('config', 'sitename'),
		A::baseUrl()->get(),
		($register['password'] ?? '') ?: 'Sent in a previous email'
	);

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
	$register = Register::getByHash($hash);
	if (!DBA::isResult($register)) {
		return false;
	}

	$user = User::getById($register['uid']);
	if (!DBA::isResult($user)) {
		exit();
	}

	DBA::delete('user', ['uid' => $register['uid']]);

	Register::deleteByHash($register['hash']);

	notice(L10n::t('Registration revoked for %s', $user['username']) . EOL);
	return true;
}

function regmod_content(App $a)
{
	if (!local_user()) {
		info(L10n::t('Please login.') . EOL);
		return Login::form(A::args()->getQueryString(), intval(Config::get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED ? 0 : 1);
	}

	if (!is_site_admin() || !empty($_SESSION['submanage'])) {
		notice(L10n::t('Permission denied.') . EOL);
		return '';
	}

	if ($a->argc != 3) {
		exit();
	}

	$cmd = $a->argv[1];
	$hash = $a->argv[2];

	if ($cmd === 'deny') {
		user_deny($hash);
		A::baseUrl()->redirect('admin/users/');
	}

	if ($cmd === 'allow') {
		user_allow($hash);
		A::baseUrl()->redirect('admin/users/');
	}
}
