<?php

function auto_redir(&$a, $contact_nick) {

	if(local_user()) {

		$r = q("SELECT id FROM contact WHERE uid = ( SELECT uid FROM user WHERE nickname = '%s' LIMIT 1 ) AND nick = '%s' AND network = '%s' LIMIT 1",
			   dbesc($contact_nick),
			   dbesc($a->user['nickname']),
			   dbesc(NETWORK_DFRN)
		);
		if(!$r || $r[0]['id'] == remote_user())
			return;


		$r = q("SELECT * FROM contact WHERE nick = '%s' AND network = '%s' AND uid = %d LIMIT 1",
		       dbesc($contact_nick),
		       dbesc(NETWORK_DFRN),
		       intval(local_user())
		);

		if(! $r)
			return;

		$cid = $r[0]['id'];

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

		$url = curPageURL();

		logger('check_redir: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG); 
		$dest = (($url) ? '&destination_url=' . $url : '');
		goaway ($r[0]['poll'] . '?dfrn_id=' . $dfrn_id 
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest );
	}

	return;
}


