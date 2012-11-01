<?php

function auto_redir(&$a, $contact_nick) {

	if((! $contact_nick) || ($contact_nick === $a->user['nickname']))
		return;

	if(local_user()) {

		// We need to find out if $contact_nick is a user on this hub, and if so, if I
		// am a contact of that user. However, that user may have other contacts with the
		// same nickname as me on other hubs or other networks. Exclude these by requiring
		// that the contact have a local URL. I will be the only person with my nickname at
		// this URL, so if a result is found, then I am a contact of the $contact_nick user.
		//
		// We also have to make sure that I'm a legitimate contact--I'm not blocked or pending.

		$baseurl = $a->get_baseurl();
		$domain_st = strpos($baseurl, "://");
		if($domain_st === false)
			return;
		$baseurl = substr($baseurl, $domain_st + 3);

		$r = q("SELECT id FROM contact WHERE uid = ( SELECT uid FROM user WHERE nickname = '%s' LIMIT 1 )
		        AND nick = '%s' AND self = 0 AND url LIKE '%%%s%%' AND blocked = 0 AND pending = 0 LIMIT 1",
			   dbesc($contact_nick),
			   dbesc($a->user['nickname']),
		       dbesc($baseurl)
		);

		if((!$r) || (! count($r)) || $r[0]['id'] == remote_user())
			return;


		$r = q("SELECT * FROM contact WHERE nick = '%s'
		        AND network = '%s' AND uid = %d  AND url LIKE '%%%s%%' LIMIT 1",
		       dbesc($contact_nick),
		       dbesc(NETWORK_DFRN),
		       intval(local_user()),
		       dbesc($baseurl)
		);

		if(! ($r && count($r)))
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

		logger('auto_redir: ' . $r[0]['name'] . ' ' . $sec, LOGGER_DEBUG); 
		$dest = (($url) ? '&destination_url=' . $url : '');
		goaway ($r[0]['poll'] . '?dfrn_id=' . $dfrn_id 
			. '&dfrn_version=' . DFRN_PROTOCOL_VERSION . '&type=profile&sec=' . $sec . $dest );
	}

	return;
}


