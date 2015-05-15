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

	$item = q("SELECT `body`, `guid`, `contact-id`, `private`, `created`, `app` FROM `item` WHERE `uid` = 0 AND `guid` = '%s' AND `network` IN ('%s', '%s') LIMIT 1",
		dbesc($guid), NETWORK_DFRN, NETWORK_DIASPORA);
	if (!$item) {
		header($_SERVER["SERVER_PROTOCOL"].' 404 '.t('Not Found'));
		killme();
	}

	$post = array();

	$reshared = diaspora_is_reshare($item[0]["body"]);

	if ($reshared) {
		$nodename = "reshare";
		$post["root_diaspora_id"] = $reshared["root_handle"];
		$post["root_guid"] = $reshared["root_guid"];
		$post["guid"] = $item[0]["guid"];
		$post["diaspora_handle"] = diaspora_handle_from_contact($item[0]["contact-id"]);
		$post["public"] = (!$item[0]["private"] ? 'true':'false');
		$post["created_at"] = datetime_convert('UTC','UTC',$item[0]["created"]);
	} else {
		$nodename = "status_message";
		$post["raw_message"] = str_replace("&", "&amp;", bb2diaspora($item[0]["body"]));
		$post["guid"] = $item[0]["guid"];
		$post["diaspora_handle"] = diaspora_handle_from_contact($item[0]["contact-id"]);
		$post["public"] = (!$item[0]["private"] ? 'true':'false');
		$post["created_at"] = datetime_convert('UTC','UTC',$item[0]["created"]);
		$post["provider_display_name"] = $item[0]["app"];
	}

	$dom = new DOMDocument("1.0");
	$root = $dom->createElement("XML");
	$dom->appendChild($root);
	$postelement = $dom->createElement("post");
	$root->appendChild($postelement);
	$statuselement = $dom->createElement($nodename);
	$postelement->appendChild($statuselement);

	foreach($post AS $index => $value) {
		$postnode = $dom->createElement($index, $value);
		$statuselement->appendChild($postnode);
	}

	header("Content-Type: application/xml; charset=utf-8");
	$xml = $dom->saveXML();

	// Diaspora doesn't send the XML header, so we remove them as well.
	// So we avoid possible compatibility problems.
	if (substr($xml, 0, 21) == '<?xml version="1.0"?>')
		$xml = trim(substr($xml, 21));

	echo $xml;

	killme();
}
