<?php

require_once('include/Scrape.php');
require_once('include/follow.php');

function ostatus_subscribe_content(App &$a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$o = "<h2>".t("Subscribing to OStatus contacts")."</h2>";

	$uid = local_user();

	$a = get_app();

	$counter = intval($_REQUEST['counter']);

	if (get_pconfig($uid, "ostatus", "legacy_friends") == "") {

		if ($_REQUEST["url"] == "") {
			return $o.t("No contact provided.");
		}

		$contact = probe_url($_REQUEST["url"]);

		if (!$contact) {
			return $o.t("Couldn't fetch information for contact.");
		}

		$api = $contact["baseurl"]."/api/";

		// Fetching friends
		$data = z_fetch_url($api."statuses/friends.json?screen_name=".$contact["nick"]);

		if (!$data["success"]) {
			return $o.t("Couldn't fetch friends for contact.");
		}

		set_pconfig($uid, "ostatus", "legacy_friends", $data["body"]);
	}

	$friends = json_decode(get_pconfig($uid, "ostatus", "legacy_friends"));

	$total = sizeof($friends);

	if ($counter >= $total) {
		$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL='.App::get_baseurl().'/settings/connectors">';
		del_pconfig($uid, "ostatus", "legacy_friends");
		del_pconfig($uid, "ostatus", "legacy_contact");
		$o .= t("Done");
		return $o;
	}

	$friend = $friends[$counter++];

	$url = $friend->statusnet_profile_url;

	$o .= "<p>".$counter."/".$total.": ".$url;

	$data = probe_url($url);
	if ($data["network"] == NETWORK_OSTATUS) {
		$result = new_contact($uid,$url,true);
		if ($result["success"]) {
			$o .= " - ".t("success");
		} else {
			$o .= " - ".t("failed");
		}
	} else {
		$o .= " - ".t("ignored");
	}

	$o .= "</p>";

	$o .= "<p>".t("Keep this window open until done.")."</p>";

	$a->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL='.App::get_baseurl().'/ostatus_subscribe?counter='.$counter.'">';

	return $o;
}
