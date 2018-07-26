<?php
/**
 * @file mod/delegate.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\User;

require_once 'mod/settings.php';

function delegate_init(App $a)
{
	return settings_init($a);
}

function delegate_post(App $a)
{
	if (!local_user()) {
		return;
	}

	if (count($a->user) && x($a->user, 'uid') && $a->user['uid'] != local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	check_form_security_token_redirectOnErr('/delegate', 'delegate');

	$parent_uid = defaults($_POST, 'parent_user', 0);
	$parent_password = defaults($_POST, 'parent_password', '');

	if ($parent_uid != 0) {
		$user = DBA::selectFirst('user', ['nickname'], ['uid' => $parent_uid]);
		if (!DBA::isResult($user)) {
			notice(L10n::t('Parent user not found.') . EOL);
			return;
		}

		$success = User::authenticate($user['nickname'], trim($parent_password));
		if (!$success) {
			notice(L10n::t('Permission denied.') . EOL);
			return;
		}
	}

	DBA::update('user', ['parent-uid' => $parent_uid], ['uid' => local_user()]);
}

function delegate_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if ($a->argc > 2 && $a->argv[1] === 'add' && intval($a->argv[2])) {
		// delegated admins can view but not change delegation permissions
		if (x($_SESSION, 'submanage')) {
			goaway(System::baseUrl() . '/delegate');
		}

		$user_id = $a->argv[2];

		$user = DBA::selectFirst('user', ['nickname'], ['uid' => $user_id]);
		if (DBA::isResult($user)) {
			$condition = [
				'uid' => local_user(),
				'nurl' => normalise_link(System::baseUrl() . '/profile/' . $user['nickname'])
			];
			if (DBA::exists('contact', $condition)) {
				DBA::insert('manage', ['uid' => $user_id, 'mid' => local_user()]);
			}
		}
		goaway(System::baseUrl() . '/delegate');
	}

	if ($a->argc > 2 && $a->argv[1] === 'remove' && intval($a->argv[2])) {
		// delegated admins can view but not change delegation permissions
		if (x($_SESSION, 'submanage')) {
			goaway(System::baseUrl() . '/delegate');
		}

		DBA::delete('manage', ['uid' => $a->argv[2], 'mid' => local_user()]);
		goaway(System::baseUrl() . '/delegate');
	}

	// find everybody that currently has delegated management to this account/page
	$delegates = [];
	$r = q("SELECT * FROM `user` WHERE `uid` IN (SELECT `uid` FROM `manage` WHERE `mid` = %d)",
		intval(local_user())
	);
	if (DBA::isResult($r)) {
		$delegates = $r;
	}

	$uids = [];
	foreach ($delegates as $rr) {
		$uids[] = $rr['uid'];
	}

	// find every contact who might be a candidate for delegation
	$potentials = [];

	$r = q("SELECT `nurl`
		FROM `contact`
		WHERE `self` = 0
		AND SUBSTRING_INDEX(`nurl`, '/', 3) = '%s'
		AND `uid` = %d
		AND `network` = '%s' ",
		DBA::escape(normalise_link(System::baseUrl())),
		intval(local_user()),
		DBA::escape(NETWORK_DFRN)
	);
	if (DBA::isResult($r)) {
		$nicknames = [];
		foreach ($r as $rr) {
			$nicknames[] = "'" . DBA::escape(basename($rr['nurl'])) . "'";
		}

		$nicks = implode(',', $nicknames);

		// get user records for all potential page delegates who are not already delegates or managers
		$r = q("SELECT `uid`, `username`, `nickname` FROM `user` WHERE `nickname` IN ($nicks)");
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				if (!in_array($rr['uid'], $uids)) {
					$potentials[] = $rr;
				}
			}
		}
	}

	settings_init($a);

	$user = DBA::selectFirst('user', ['parent-uid', 'email'], ['uid' => local_user()]);

	$parent_user = null;

	if (DBA::isResult($user)) {
		if (!DBA::exists('user', ['parent-uid' => local_user()])) {
			$parent_uid = $user['parent-uid'];
			$parents = [0 => L10n::t('No parent user')];

			$fields = ['uid', 'username', 'nickname'];
			$condition = ['email' => $user['email'], 'verified' => true, 'blocked' => false, 'parent-uid' => 0];
			$parent_users = DBA::select('user', $fields, $condition);
			while ($parent = DBA::fetch($parent_users)) {
				if ($parent['uid'] != local_user()) {
					$parents[$parent['uid']] = sprintf('%s (%s)', $parent['username'], $parent['nickname']);
				}
			}
			$parent_user = ['parent_user', '', $parent_uid, '', $parents];
		}
	}

	if (!is_null($parent_user)) {
		$parent_password = ['parent_password', L10n::t('Parent Password:'), '', L10n::t('Please enter the password of the parent account to legitimize your request.')];
	}

	$o = replace_macros(get_markup_template('delegate.tpl'), [
		'$form_security_token' => get_form_security_token('delegate'),
		'$parent_header' => L10n::t('Parent User'),
		'$parent_user' => $parent_user,
		'$parent_password' => $parent_password,
		'$parent_desc' => L10n::t('Parent users have total control about this account, including the account settings. Please double check whom you give this access.'),
		'$submit' => L10n::t('Save Settings'),
		'$header' => L10n::t('Delegate Page Management'),
		'$delegates_header' => L10n::t('Delegates'),
		'$base' => System::baseUrl(),
		'$desc' => L10n::t('Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'),
		'$head_delegates' => L10n::t('Existing Page Delegates'),
		'$delegates' => $delegates,
		'$head_potentials' => L10n::t('Potential Delegates'),
		'$potentials' => $potentials,
		'$remove' => L10n::t('Remove'),
		'$add' => L10n::t('Add'),
		'$none' => L10n::t('No entries.')
	]);


	return $o;
}
