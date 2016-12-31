<?php

function post_var($name) {
	return (x($_POST, $name)) ? notags(trim($_POST[$name])) : '';
}

function pubsubhubbub_init(&$a) {
	// PuSH subscription must be considered "public" so just block it
	// if public access isn't enabled.
	if (get_config('system', 'block_public')) {
		http_status_exit(403);
	}

	// Subscription request from subscriber
	// https://pubsubhubbub.googlecode.com/git/pubsubhubbub-core-0.4.html#anchor4
	// Example from GNU Social:
    // [hub_mode] => subscribe
    // [hub_callback] => http://status.local/main/push/callback/1
    // [hub_verify] => sync
    // [hub_verify_token] => af11...
    // [hub_secret] => af11...
    // [hub_topic] => http://friendica.local/dfrn_poll/sazius

	if($_SERVER['REQUEST_METHOD'] === 'POST') {
		$hub_mode = post_var('hub_mode');
		$hub_callback = post_var('hub_callback');
		$hub_verify = post_var('hub_verify');
		$hub_verify_token = post_var('hub_verify_token');
		$hub_secret = post_var('hub_secret');
		$hub_topic = post_var('hub_topic');

		// check for valid hub_mode
		if ($hub_mode === 'subscribe') {
			$subscribe = 1;
		} else if ($hub_mode === 'unsubscribe') {
			$subscribe = 0;
		} else {
			logger("pubsubhubbub: invalid hub_mode=$hub_mode, ignoring.");
			http_status_exit(404);
		}

		logger("pubsubhubbub: $hub_mode request from " .
			   $_SERVER['REMOTE_ADDR']);

		// get the nick name from the topic, a bit hacky but needed
		$nick = substr(strrchr($hub_topic, "/"), 1);

		if (!$nick) {
			logger('pubsubhubbub: bad hub_topic=$hub_topic, ignoring.');
			http_status_exit(404);
		}

		// fetch user from database given the nickname
		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s'" .
			   " AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
			   dbesc($nick));

		if (!dbm::is_result($r)) {
			logger('pubsubhubbub: local account not found: ' . $nick);
			http_status_exit(404);
		}

		$owner = $r[0];

		// abort if user's wall is supposed to be private
		if ($r[0]['hidewall']) {
			logger('pubsubhubbub: local user ' . $nick .
				   'has chosen to hide wall, ignoring.');
			http_status_exit(403);
		}

		// get corresponding row from contact table
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND NOT `blocked`".
			   " AND NOT `pending` AND `self` LIMIT 1",
			   intval($owner['uid']));
		if (!dbm::is_result($r)) {
			logger('pubsubhubbub: contact not found.');
			http_status_exit(404);
		}

		$contact = $r[0];

		// sanity check that topic URLs are the same
		if(!link_compare($hub_topic, $contact['poll'])) {
			logger('pubsubhubbub: hub topic ' . $hub_topic . ' != ' .
				   $contact['poll']);
			http_status_exit(404);
		}

		// do subscriber verification according to the PuSH protocol
		$hub_challenge = random_string(40);
		$params = 'hub.mode=' .
			($subscribe == 1 ? 'subscribe' : 'unsubscribe') .
			'&hub.topic=' . urlencode($hub_topic) .
			'&hub.challenge=' . $hub_challenge .
			'&hub.lease_seconds=604800' .
			'&hub.verify_token=' . $hub_verify_token;

		// lease time is hard coded to one week (in seconds)
		// we don't actually enforce the lease time because GNU
		// Social/StatusNet doesn't honour it (yet)

		$body = fetch_url($hub_callback . "?" . $params);
		$ret = $a->get_curl_code();

		// give up if the HTTP return code wasn't a success (2xx)
		if ($ret < 200 || $ret > 299) {
			logger("pubsubhubbub: subscriber verification at $hub_callback ".
				   "returned $ret, ignoring.");
			http_status_exit(404);
		}

		// check that the correct hub_challenge code was echoed back
		if (trim($body) !== $hub_challenge) {
			logger("pubsubhubbub: subscriber did not echo back ".
				   "hub.challenge, ignoring.");
			logger("\"$hub_challenge\" != \"".trim($body)."\"");
			http_status_exit(404);
		}

		// fetch the old subscription if it exists
		$r = q("SELECT * FROM `push_subscriber` WHERE `callback_url` = '%s'",
		  dbesc($hub_callback));

		// delete old subscription if it exists
		q("DELETE FROM `push_subscriber` WHERE `callback_url` = '%s'",
		  dbesc($hub_callback));

		if ($subscribe) {
			$last_update = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			$push_flag = 0;

			// if we are just updating an old subscription, keep the
			// old values for push and last_update
			if (dbm::is_result($r)) {
				$last_update = $r[0]['last_update'];
				$push_flag = $r[0]['push'];
			}

			// subscribe means adding the row to the table
			q("INSERT INTO `push_subscriber` (`uid`, `callback_url`, " .
			  "`topic`, `nickname`, `push`, `last_update`, `secret`) values " .
			  "(%d, '%s', '%s', '%s', %d, '%s', '%s')",
			  intval($owner['uid']),
			  dbesc($hub_callback),
			  dbesc($hub_topic),
			  dbesc($nick),
			  intval($push_flag),
			  dbesc($last_update),
			  dbesc($hub_secret));
			logger("pubsubhubbub: successfully subscribed [$hub_callback].");
		} else {
			logger("pubsubhubbub: successfully unsubscribed [$hub_callback].");
			// we do nothing here, since the row was already deleted
		}
		http_status_exit(202);
	}

	killme();
}

?>
