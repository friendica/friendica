<?php
/*
This file is part of the Diaspora protocol. It is used for fetching single public posts.
*/
require_once("include/crypto.php");
require_once("include/diaspora.php");
require_once("include/xml.php");

/// @TODO You always make it like this: function foo(&$a)
/// @TODO This means that the value of $a can be changed in anything, remove & and use App as type-hint
function fetch_init(App &$a){

	if (($a->argc != 3) OR (!in_array($a->argv[1], array("post", "status_message", "reshare")))) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	$guid = $a->argv[2];

	// Fetch the item
	$item = q("SELECT `uid`, `title`, `body`, `guid`, `contact-id`, `private`, `created`, `app`, `location`, `coord`
			FROM `item` WHERE `wall` AND NOT `private` AND `guid` = '%s' AND `network` IN ('%s', '%s') AND `id` = `parent` LIMIT 1",
		dbesc($guid), NETWORK_DFRN, NETWORK_DIASPORA);
	if (!$item) {
		$r = q("SELECT `author-link`
			FROM `item` WHERE `uid` = 0 AND `guid` = '%s' AND `network` IN ('%s', '%s') AND `id` = `parent` LIMIT 1",
			dbesc($guid), NETWORK_DFRN, NETWORK_DIASPORA);
		if ($r) {
			$parts = parse_url($r[0]["author-link"]);
			$host = $parts["scheme"]."://".$parts["host"];

			if (normalise_link($host) != normalise_link(App::get_baseurl())) {
				$location = $host."/fetch/".$a->argv[1]."/".urlencode($guid);

				header("HTTP/1.1 301 Moved Permanently");
				header("Location:".$location);
				killme();
			}
		}

		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	// Fetch some data from the author (We could combine both queries - but I think this is more readable)
	$r = q("SELECT `user`.`prvkey`, `contact`.`addr`, `user`.`nickname`, `contact`.`nick` FROM `user`
		INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid`
		WHERE `user`.`uid` = %d", intval($item[0]["uid"]));
	if (!$r) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}
	$user = $r[0];

	$status = Diaspora::build_status($item[0], $user);
	$xml = Diaspora::build_post_xml($status["type"], $status["message"]);

	// Send the envelope
	header("Content-Type: application/magic-envelope+xml; charset=utf-8");
	echo Diaspora::build_magic_envelope($xml, $user);

	killme();
}
