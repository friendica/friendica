<?php

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBM;

require_once 'mod/settings.php';

function delegate_init(App $a)
{
	return settings_init($a);
}

function delegate_content(App $a)
{
	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	if ($a->argc > 2 && $a->argv[1] === 'add' && intval($a->argv[2])) {
		// delegated admins can view but not change delegation permissions
		if (x($_SESSION, 'submanage')) {
			goaway(System::baseUrl() . '/delegate');
		}

		$user_id = $a->argv[2];

		$user = dba::selectFirst('user', ['nickname'], ['uid' => $user_id]);
		if (DBM::isResult($user)) {
			$condition = [
				'uid' => local_user(),
				'nurl' => normalise_link(System::baseUrl() . '/profile/' . $user['nickname'])
			];
			if (dba::exists('contact', $condition)) {
				dba::insert('manage', ['uid' => $user_id, 'mid' => local_user()]);
			}
		}
		goaway(System::baseUrl() . '/delegate');
	}

	if ($a->argc > 2 && $a->argv[1] === 'remove' && intval($a->argv[2])) {
		// delegated admins can view but not change delegation permissions
		if (x($_SESSION, 'submanage')) {
			goaway(System::baseUrl() . '/delegate');
		}

		dba::delete('manage', ['uid' => $a->argv[2], 'mid' => local_user()]);
		goaway(System::baseUrl() . '/delegate');
	}

	// These people can manage this account/page with full privilege
	$full_managers = [];
	$r = q("SELECT * FROM `user` WHERE `email` = '%s' AND `password` = '%s' ",
		dbesc($a->user['email']),
		dbesc($a->user['password'])
	);
	if (DBM::isResult($r)) {
		$full_managers = $r;
	}

	// find everybody that currently has delegated management to this account/page
	$delegates = [];
	$r = q("SELECT * FROM `user` WHERE `uid` IN (SELECT `uid` FROM `manage` WHERE `mid` = %d)",
		intval(local_user())
	);
	if (DBM::isResult($r)) {
		$delegates = $r;
	}

	$uids = [];
	foreach ($full_managers as $rr) {
		$uids[] = $rr['uid'];
	}

	foreach ($delegates as $rr) {
		$uids[] = $rr['uid'];
	}

	// find every contact who might be a candidate for delegation

	$r = q("SELECT `nurl`
		FROM `contact`
		WHERE `self` = 0
		AND SUBSTRING_INDEX(`nurl`, '/', 3) = '%s'
		AND `uid` = %d
		AND `network` = '%s' ",
		dbesc(normalise_link(System::baseUrl())),
		intval(local_user()),
		dbesc(NETWORK_DFRN)
	);
	if (!DBM::isResult($r)) {
		notice(t('No potential page delegates located.') . EOL);
		return;
	}

	$nicknames = [];
	foreach ($r as $rr) {
		$nicknames[] = "'" . dbesc(basename($rr['nurl'])) . "'";
	}

	$potentials = [];

	$nicks = implode(',', $nicknames);

	// get user records for all potential page delegates who are not already delegates or managers
	$r = q("SELECT `uid`, `username`, `nickname` FROM `user` WHERE `nickname` IN ($nicks)");
	if (DBM::isResult($r)) {
		foreach ($r as $rr) {
			if (!in_array($rr['uid'], $uids)) {
				$potentials[] = $rr;
			}
		}
	}

	settings_init($a);

	$o = replace_macros(get_markup_template('delegate.tpl'), [
		'$header' => t('Delegate Page Management'),
		'$base' => System::baseUrl(),
		'$desc' => t('Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'),
		'$head_managers' => t('Existing Page Managers'),
		'$managers' => $full_managers,
		'$head_delegates' => t('Existing Page Delegates'),
		'$delegates' => $delegates,
		'$head_potentials' => t('Potential Delegates'),
		'$potentials' => $potentials,
		'$remove' => t('Remove'),
		'$add' => t('Add'),
		'$none' => t('No entries.')
	]);


	return $o;
}
