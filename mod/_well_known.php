<?php
/// @TODO This file has DOS line endings!
require_once("mod/hostxrd.php");
require_once("mod/nodeinfo.php");

function _well_known_init(App &$a){
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

function wk_social_relay(App &$a) {

	define('SR_SCOPE_ALL', 'all');
	define('SR_SCOPE_TAGS', 'tags');

	$subscribe = (bool)get_config('system', 'relay_subscribe');

	if ($subscribe)
		$scope = get_config('system', 'relay_scope');
	else
		$scope = "";

	$tags = array();

	if ($scope == SR_SCOPE_TAGS) {

		$server_tags = get_config('system', 'relay_server_tags');
		$tagitems = explode(",", $server_tags);

		foreach($tagitems AS $tag)
			$tags[trim($tag, "# ")] = trim($tag, "# ");

		if (get_config('system', 'relay_user_tags')) {
			$terms = q("SELECT DISTINCT(`term`) FROM `search`");

			foreach($terms AS $term) {
				$tag = trim($term["term"], "#");
				$tags[$tag] = $tag;
			}
		}
	}

	$taglist = array();
	foreach($tags AS $tag)
		$taglist[] = $tag;

	$relay = array("subscribe" => $subscribe,
			"scope" => $scope,
			"tags" => $taglist);

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($relay, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	exit;
}
