<?php
require_once("boot.php");
require_once("include/ostatus.php");

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

function handle_pubsubhubbub($id) {
	global $a, $db;

	$r = q("SELECT * FROM `push_subscriber` WHERE `id` = %d", intval($id));
	if (!$r)
		return;
	else
		$rr = $r[0];

	logger("Generate feed of user ".$rr['nickname']." to ".$rr['callback_url']." - last updated ".$rr['last_update'], LOGGER_DEBUG);

	$params = ostatus::feed($a, $rr['nickname'], $rr['last_update']);
	$hmac_sig = hash_hmac("sha1", $params, $rr['secret']);

	$headers = array("Content-type: application/atom+xml",
			sprintf("Link: <%s>;rel=hub,<%s>;rel=self",
				App::get_baseurl().'/pubsubhubbub',
				$rr['topic']),
			"X-Hub-Signature: sha1=".$hmac_sig);

	logger('POST '.print_r($headers, true)."\n".$params, LOGGER_DEBUG);

	post_url($rr['callback_url'], $params, $headers);
	$ret = $a->get_curl_code();

	if ($ret >= 200 && $ret <= 299) {
		logger('successfully pushed to '.$rr['callback_url']);

		// set last_update to "now", and reset push=0
		$date_now = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
		q("UPDATE `push_subscriber` SET `push` = 0, last_update = '%s' WHERE id = %d",
			dbesc($date_now),
			intval($rr['id']));

	} else {
		logger('error when pushing to '.$rr['callback_url'].' HTTP: '.$ret);

		// we use the push variable also as a counter, if we failed we
		// increment this until some upper limit where we give up
		$new_push = intval($rr['push']) + 1;

		if ($new_push > 30) // OK, let's give up
			$new_push = 0;

		q("UPDATE `push_subscriber` SET `push` = %d WHERE id = %d",
			$new_push,
			intval($rr['id']));
	}
}


function pubsubpublish_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}

	if(is_null($db)){
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	require_once('include/items.php');

	load_config('config');
	load_config('system');

	// Don't check this stuff if the function is called by the poller
	if (App::callstack() != "poller_run") {
		if (App::is_already_running("pubsubpublish", "include/pubsubpublish.php", 540)) {
			return;
		}
	}

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	if ($argc > 1) {
		$pubsubpublish_id = intval($argv[1]);
	}
	else {
		// We'll push to each subscriber that has push > 0,
		// i.e. there has been an update (set in notifier.php).
		$r = q("SELECT `id`, `callback_url` FROM `push_subscriber` WHERE `push` > 0");

		// Use the delivery interval that is also used for the notifier
		$interval = Config::get("system", "delivery_interval", 2);

		// If we are using the worker we don't need a delivery interval
		if (get_config("system", "worker")) {
			$interval = false;
		}

		foreach ($r as $rr) {
			logger("Publish feed to ".$rr["callback_url"], LOGGER_DEBUG);
			proc_run(PRIORITY_HIGH, 'include/pubsubpublish.php', $rr["id"]);

			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	handle_pubsubhubbub($pubsubpublish_id);

	return;

}

if (array_search(__file__,get_included_files())===0){
  pubsubpublish_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}

