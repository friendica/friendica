<?php
/*
This file is part of the Diaspora protocol. It is used for fetching single public posts.
*/

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Protocol\Diaspora;
use Friendica\Util\XML;

function fetch_init(App $a)
{

	if (($a->argc != 3) || (!in_array($a->argv[1], ["post", "status_message", "reshare"]))) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.L10n::t('Not Found'));
		killme();
	}

	$guid = $a->argv[2];

	// Fetch the item
	$item = q(
		"SELECT `uid`, `title`, `body`, `guid`, `contact-id`, `private`, `created`, `app`, `location`, `coord`
			FROM `item` WHERE `wall` AND NOT `private` AND `guid` = '%s' AND `network` IN ('%s', '%s') AND `id` = `parent` LIMIT 1",
		dbesc($guid),
		NETWORK_DFRN,
		NETWORK_DIASPORA
	);
	if (!$item) {
		$r = q(
			"SELECT `author-link`
			FROM `item` WHERE `uid` = 0 AND `guid` = '%s' AND `network` IN ('%s', '%s') AND `id` = `parent` LIMIT 1",
			dbesc($guid),
			NETWORK_DFRN,
			NETWORK_DIASPORA
		);

		if ($r) {
			$parts = parse_url($r[0]["author-link"]);
			$host = $parts["scheme"]."://".$parts["host"];

			if (normalise_link($host) != normalise_link(System::baseUrl())) {
				$location = $host."/fetch/".$a->argv[1]."/".urlencode($guid);

				header("HTTP/1.1 301 Moved Permanently");
				header("Location:".$location);
				killme();
			}
		}

		header($_SERVER["SERVER_PROTOCOL"].' 404 '.L10n::t('Not Found'));
		killme();
	}

	// Fetch some data from the author (We could combine both queries - but I think this is more readable)
	$r = q(
		"SELECT `user`.`prvkey`, `contact`.`addr`, `user`.`nickname`, `contact`.`nick` FROM `user`
		INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`
		WHERE `user`.`uid` = %d",
		intval($item[0]["uid"])
	);

	if (!$r) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.L10n::t('Not Found'));
		killme();
	}
	$user = $r[0];

	$status = Diaspora::buildStatus($item[0], $user);
	$xml = Diaspora::buildPostXml($status["type"], $status["message"]);

	// Send the envelope
	header("Content-Type: application/magic-envelope+xml; charset=utf-8");
	echo Diaspora::buildMagicEnvelope($xml, $user);

	killme();
}
