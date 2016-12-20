<?php
require_once('mod/settings.php');

function delegate_init(App &$a) {
	return settings_init($a);
}


function delegate_content(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if($a->argc > 2 && $a->argv[1] === 'add' && intval($a->argv[2])) {

		// delegated admins can view but not change delegation permissions

		if (x($_SESSION,'submanage') && intval($_SESSION['submanage'])) {
			goaway(App::get_baseurl() . '/delegate');
		}


		$id = $a->argv[2];

		$r = q("select `nickname` from user where uid = %d limit 1",
			intval($id)
		);
		if (dbm::is_result($r)) {
			$r = q("select id from contact where uid = %d and nurl = '%s' limit 1",
				intval(local_user()),
				dbesc(normalise_link(App::get_baseurl() . '/profile/' . $r[0]['nickname']))
			);
			if (dbm::is_result($r)) {
				q("insert into manage ( uid, mid ) values ( %d , %d ) ",
					intval($a->argv[2]),
					intval(local_user())
				);
			}
		}
		goaway(App::get_baseurl() . '/delegate');
	}

	if($a->argc > 2 && $a->argv[1] === 'remove' && intval($a->argv[2])) {

		// delegated admins can view but not change delegation permissions

		if (x($_SESSION,'submanage') && intval($_SESSION['submanage'])) {
			goaway(App::get_baseurl() . '/delegate');
		}

		q("delete from manage where uid = %d and mid = %d limit 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		goaway(App::get_baseurl() . '/delegate');

	}

	$full_managers = array();

	// These people can manage this account/page with full privilege

	$r = q("SELECT * FROM `user` WHERE `email` = '%s' AND `password` = '%s' ",
		dbesc($a->user['email']),
		dbesc($a->user['password'])
	);
	if (dbm::is_result($r))
		$full_managers = $r;

	$delegates = array();

	// find everybody that currently has delegated management to this account/page

	$r = q("select * from user where uid in ( select uid from manage where mid = %d ) ",
		intval(local_user())
	);

	if (dbm::is_result($r))
		$delegates = $r;

	$uids = array();

	if(count($full_managers))
		foreach($full_managers as $rr)
			$uids[] = $rr['uid'];

	if(count($delegates))
		foreach($delegates as $rr)
			$uids[] = $rr['uid'];

	// find every contact who might be a candidate for delegation

	$r = q("select nurl from contact where substring_index(contact.nurl,'/',3) = '%s' 
		and contact.uid = %d and contact.self = 0 and network = '%s' ",
		dbesc(normalise_link(App::get_baseurl())),
		intval(local_user()),
		dbesc(NETWORK_DFRN)
	); 

	if (! dbm::is_result($r)) {
		notice( t('No potential page delegates located.') . EOL);
		return;
	}

	$nicknames = array();

	if (dbm::is_result($r)) {
		foreach($r as $rr) {
			$nicknames[] = "'" . dbesc(basename($rr['nurl'])) . "'";
		}
	}

	$potentials = array();

	$nicks = implode(',',$nicknames);

	// get user records for all potential page delegates who are not already delegates or managers

	$r = q("select `uid`, `username`, `nickname` from user where nickname in ( $nicks )");

	if (dbm::is_result($r))
		foreach($r as $rr)
			if(! in_array($rr['uid'],$uids))
				$potentials[] = $rr;

	require_once("mod/settings.php");
	settings_init($a);

	$o = replace_macros(get_markup_template('delegate.tpl'),array(
		'$header' => t('Delegate Page Management'),
		'$base' => App::get_baseurl(),
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
	));


	return $o;


}
