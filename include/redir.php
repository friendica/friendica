<?php

function auto_redir(&$a, $contact_nick) {

	// prevent looping

	if(x($_REQUEST,'redir') && intval($_REQUEST['redir']))
		return;

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

		$baseurl = App::get_baseurl();
		$domain_st = strpos($baseurl, "://");
		if($domain_st === false)
			return;
		$baseurl = substr($baseurl, $domain_st + 3);
		$nurl = normalise_link($baseurl);

		/// @todo Why is there a query for "url" *and* "nurl"? Especially this normalising is strange.
		$r = q("SELECT `id` FROM `contact` WHERE `uid` = (SELECT `uid` FROM `user` WHERE `nickname` = '%s' LIMIT 1)
		        AND `nick` = '%s' AND NOT `self` AND (`url` LIKE '%%%s%%' OR `nurl` LIKE '%%%s%%') AND NOT `blocked` AND NOT `pending` LIMIT 1",
				dbesc($contact_nick),
				dbesc($a->user['nickname']),
				dbesc($baseurl),
				dbesc($nurl)
		);

		if ((! dbm::is_result($r)) || $r[0]['id'] == remote_user()) {
			return;
		}

		$r = q("SELECT * FROM contact WHERE nick = '%s'
		        AND network = '%s' AND uid = %d  AND url LIKE '%%%s%%' LIMIT 1",
		       dbesc($contact_nick),
		       dbesc(NETWORK_DFRN),
		       intval(local_user()),
		       dbesc($baseurl)
		);

		if (! dbm::is_result($r)) {
			return;
		}

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

		// ensure that we've got a valid ID. There may be some edge cases with forums and non-duplex mode
		// that may have triggered some of the "went to {profile/intro} and got an RSS feed" issues

		if(strlen($dfrn_id) < 3)
			return;
			
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


