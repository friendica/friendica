<?php

function redir_init(App &$a) {

	$url = ((x($_GET,'url')) ? $_GET['url'] : '');
	$quiet = ((x($_GET,'quiet')) ? '&quiet=1' : '');
	$con_url = ((x($_GET,'conurl')) ? $_GET['conurl'] : '');

	// traditional DFRN

	if( $con_url || (local_user() && $a->argc > 1 && intval($a->argv[1])) ) {

		if($con_url) {
			$con_url = str_replace('https', 'http', $con_url);

			$r = q("SELECT * FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($con_url),
				intval(local_user())
			);

			if((! dbm::is_result($r)) || ($r[0]['network'] !== NETWORK_DFRN))
				goaway(z_root());

			$cid = $r[0]['id'];
		}
		else {
			$cid = $a->argv[1];

			$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($cid),
				intval(local_user())
			);

			if((! dbm::is_result($r)) || ($r[0]['network'] !== NETWORK_DFRN))
				goaway(z_root());
		}

		$dfrn_id = $orig_id = (($r[0]['issued-id']) ? $r[0]['issued-id'] : $r[0]['dfrn-id']);

		if($r[0]['duplex'] && $r[0]['issued-id']) {
			$orig_id = $r[0]['issued-id'];
			$dfrn_id = '1:' . $orig_id;
		}
		if($r[0]['duplex'] && $r[0]['dfrn-id']) {
			$orig_id = $r[0]['dfrn-id'];
			$dfrn_id = '0:' . $orig_id;
		}

		$sec = random_string();

		q("INSERT INTO `profile_check` ( `uid`, `cid`, `dfrn_id`, `sec`, `expire`)
			VALUES( %d, %s, '%s', '%s', %d )",
			intval(local_user()),
			intval($cid),
			dbesc($dfrn_id),
			dbesc($sec),
			intval(time() + 45)
		);

		logger('mod_redir: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG); 
		$dest = (($url) ? '&destination_url=' . $url : '');
		goaway ($r[0]['poll'] . '?dfrn_id=' . $dfrn_id 
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest . $quiet );
	}

	if (local_user()) {
		$handle = $a->user['nickname'] . '@' . substr(App::get_baseurl(),strpos(App::get_baseurl(),'://')+3);
	}
	if (remote_user()) {
		$handle = $_SESSION['handle'];
	}

	if ($url) {
		$url = str_replace('{zid}','&zid=' . $handle,$url);
		goaway($url);
	}

	goaway(z_root());
}
