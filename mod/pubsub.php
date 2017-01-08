<?php

function hub_return($valid,$body) {

	if($valid) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 200 ' . 'OK');
		echo $body;
		killme();
	}
	else {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . 'Not Found');
		killme();
	}

	// NOTREACHED
}

// when receiving an XML feed, always return OK

function hub_post_return() {

	header($_SERVER["SERVER_PROTOCOL"] . ' 200 ' . 'OK');
	killme();

}



function pubsub_init(App &$a) {

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

	if($_SERVER['REQUEST_METHOD'] === 'GET') {

		$hub_mode      = ((x($_GET,'hub_mode'))          ? notags(trim($_GET['hub_mode']))          : '');
		$hub_topic     = ((x($_GET,'hub_topic'))         ? notags(trim($_GET['hub_topic']))         : '');
		$hub_challenge = ((x($_GET,'hub_challenge'))     ? notags(trim($_GET['hub_challenge']))     : '');
		$hub_lease     = ((x($_GET,'hub_lease_seconds')) ? notags(trim($_GET['hub_lease_seconds'])) : '');
		$hub_verify    = ((x($_GET,'hub_verify_token'))  ? notags(trim($_GET['hub_verify_token']))  : '');

		logger('pubsub: Subscription from ' . $_SERVER['REMOTE_ADDR']);
		logger('pubsub: data: ' . print_r($_GET,true), LOGGER_DATA);

		$subscribe = (($hub_mode === 'subscribe') ? 1 : 0);

		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
			dbesc($nick)
		);
		if (! dbm::is_result($r)) {
			logger('pubsub: local account not found: ' . $nick);
			hub_return(false, '');
		}


		$owner = $r[0];

		$sql_extra = ((strlen($hub_verify)) ? sprintf(" AND `hub-verify` = '%s' ", dbesc($hub_verify)) : '');

		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d 
			AND `blocked` = 0 AND `pending` = 0 $sql_extra LIMIT 1",
			intval($contact_id),
			intval($owner['uid'])
		);
		if (! dbm::is_result($r)) {
			logger('pubsub: contact '.$contact_id.' not found.');
			hub_return(false, '');
		}

		if ($hub_topic)
			if(! link_compare($hub_topic,$r[0]['poll'])) {
				logger('pubsub: hub topic ' . $hub_topic . ' != ' . $r[0]['poll']);
				// should abort but let's humour them.
			}

		$contact = $r[0];

		// We must initiate an unsubscribe request with a verify_token. 
		// Don't allow outsiders to unsubscribe us.

		if($hub_mode === 'unsubscribe') {
			if(! strlen($hub_verify)) {
				logger('pubsub: bogus unsubscribe');
				hub_return(false, '');
			}
			logger('pubsub: unsubscribe success');
		}

		if ($hub_mode)
			$r = q("UPDATE `contact` SET `subhub` = %d WHERE `id` = %d",
				intval($subscribe),
				intval($contact['id'])
			);

 		hub_return(true, $hub_challenge);
	}
}

require_once('include/security.php');

function pubsub_post(App &$a) {

	$xml = file_get_contents('php://input');

	logger('pubsub: feed arrived from ' . $_SERVER['REMOTE_ADDR'] . ' for ' .  $a->cmd );
	logger('pubsub: user-agent: ' . $_SERVER['HTTP_USER_AGENT'] );
	logger('pubsub: data: ' . $xml, LOGGER_DATA);

//	if(! stristr($xml,'<?xml')) {
//		logger('pubsub_post: bad xml');
//		hub_post_return();
//	}

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$contact_id = (($a->argc > 2) ? intval($a->argv[2])       : 0 );

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
		dbesc($nick)
	);
	if (! dbm::is_result($r)) {
		hub_post_return();
	}

	$importer = $r[0];

	$r = q("SELECT * FROM `contact` WHERE `subhub` AND `id` = %d AND `uid` = %d
		AND (`rel` = %d OR `rel` = %d OR network = '%s') AND NOT `blocked` LIMIT 1",
		intval($contact_id),
		intval($importer['uid']),
		intval(CONTACT_IS_SHARING),
		intval(CONTACT_IS_FRIEND),
		dbesc(NETWORK_FEED)
	);

	if (! dbm::is_result($r)) {
		logger('pubsub: no contact record for "'.$nick.' ('.$contact_id.')" - ignored. '.$xml);
		hub_post_return();
	}

	$contact = $r[0];

	// we have no way to match Diaspora guid's with atom post id's and could get duplicates.
	// we'll assume that direct delivery is robust (and this is a bad assumption, but the duplicates are messy).

	if($r[0]['network'] === NETWORK_DIASPORA)
		hub_post_return();

	$feedhub = '';

	require_once('include/items.php');

	consume_feed($xml,$importer,$contact,$feedhub,1,1);

	// do it a second time so that any children find their parents.

	consume_feed($xml,$importer,$contact,$feedhub,1,2);

	hub_post_return();

}



