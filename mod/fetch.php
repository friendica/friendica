<?php
/*
This file is part of the Diaspora protocol. It is used for fetching single public posts.
*/

use Friendica\App;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Protocol\Diaspora;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Util\Strings;
use Friendica\Database\DBA;

function fetch_init(App $a)
{

	if (($a->argc != 3) || (!in_array($a->argv[1], ["post", "status_message", "reshare"]))) {
		System::httpExit(404);
	}

	$guid = $a->argv[2];

	// Fetch the item
	$fields = ['uid', 'title', 'body', 'guid', 'contact-id', 'private', 'created', 'app', 'location', 'coord', 'network',
		'event-id', 'resource-id', 'author-link', 'author-avatar', 'author-name', 'plink', 'owner-link', 'attach'];
	$condition = ['wall' => true, 'private' => false, 'guid' => $guid, 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
	$item = Item::selectFirst($fields, $condition);
	if (!DBA::isResult($item)) {
		$condition = ['guid' => $guid, 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
		$item = Item::selectFirst(['author-link'], $condition);
		if (DBA::isResult($item)) {
			$parts = parse_url($item["author-link"]);
			$host = $parts["scheme"]."://".$parts["host"];

			if (Strings::normaliseLink($host) != Strings::normaliseLink(System::baseUrl())) {
				$location = $host."/fetch/".$a->argv[1]."/".urlencode($guid);

				header("HTTP/1.1 301 Moved Permanently");
				header("Location:".$location);
				exit();
			}
		}

		System::httpExit(404);
	}

	// Fetch some data from the author (We could combine both queries - but I think this is more readable)
	$user = User::getOwnerDataById($item["uid"]);
	if (!$user) {
		System::httpExit(404);
	}

	$status = Diaspora::buildStatus($item, $user);
	$xml = Diaspora::buildPostXml($status["type"], $status["message"]);

	// Send the envelope
	header("Content-Type: application/magic-envelope+xml; charset=utf-8");
	echo Diaspora::buildMagicEnvelope($xml, $user);

	exit();
}
