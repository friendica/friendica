<?php
/*
This file is part of the Diaspora protocol. It is used for fetching single public posts.
*/
require_once("include/diaspora.php");

function p_init($a){
	if ($a->argc != 2) {
		header($_SERVER["SERVER_PROTOCOL"].' 510 '.t('Not Extended'));
		killme();
	}

	$guid = $a->argv[1];

	if (strtolower(substr($guid, -4)) != ".xml") {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	$guid = strtolower(substr($guid, 0, -4));

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
				$location = $host."/p/".urlencode($guid).".xml";

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
		INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`
		WHERE `user`.`uid` = %d", intval($item[0]["uid"]));
	if (!dbm::is_result($r)) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}
	$user = $r[0];

	$status = Diaspora::build_status($item[0], $user);
	$xml = Diaspora::build_post_xml($status["type"], $status["message"]);

	header("Content-Type: application/xml; charset=utf-8");
	echo $xml;

	killme();
}
