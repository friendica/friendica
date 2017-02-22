<?php

use \Friendica\Core\Config;

require_once("mod/hostxrd.php");
require_once("mod/nodeinfo.php");

function _well_known_init(App $a) {
	if ($a->argc > 1) {
		switch($a->argv[1]) {
			case "host-meta":
				hostxrd_init($a);
				break;
			case "x-social-relay":
				wk_social_relay($a);
				break;
			case "nodeinfo":
				nodeinfo_wellknown($a);
				break;
		}
	}
	http_status_exit(404);
	killme();
}

function wk_social_relay(App $a) {

	$subscribe = (bool)Config::get('system', 'relay_subscribe', false);

	if ($subscribe) {
		$scope = Config::get('system', 'relay_scope', SR_SCOPE_ALL);
	} else {
		$scope = SR_SCOPE_NONE;
	}

	$tags = array();

	if ($scope == SR_SCOPE_TAGS) {
		$server_tags = Config::get('system', 'relay_server_tags');
		$tagitems = explode(",", $server_tags);

		foreach($tagitems AS $tag) {
			$tags[trim($tag, "# ")] = trim($tag, "# ");
		}

		if (Config::get('system', 'relay_user_tags')) {
			$terms = q("SELECT DISTINCT(`term`) FROM `search`");

			foreach($terms AS $term) {
				$tag = trim($term["term"], "#");
				$tags[$tag] = $tag;
			}
		}
	}

	$taglist = array();
	foreach($tags AS $tag) {
		$taglist[] = $tag;
	}

	$relay = array("subscribe" => $subscribe,
			"scope" => $scope,
			"tags" => $taglist);

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($relay, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	exit;
}
