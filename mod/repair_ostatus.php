<?php

require_once('include/Scrape.php');
require_once('include/follow.php');

function repair_ostatus_content(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$o = "<h2>".t("Resubscribing to OStatus contacts")."</h2>";

	$uid = local_user();

	$a = get_app();

	$counter = intval($_REQUEST['counter']);

        $r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE
                `uid` = %d AND `network` = '%s' AND `rel` IN (%d, %d)",
                intval($uid),
                dbesc(NETWORK_OSTATUS),
                intval(CONTACT_IS_FRIEND),
                intval(CONTACT_IS_SHARING));

	if (!$r)
		return($o.t("Error"));

	$total = $r[0]["total"];

        $r = q("SELECT `url` FROM `contact` WHERE
                `uid` = %d AND `network` = '%s' AND `rel` IN (%d, %d)
		ORDER BY `url`
		LIMIT %d, 1",
                intval($uid),
                dbesc(NETWORK_OSTATUS),
                intval(CONTACT_IS_FRIEND),
                intval(CONTACT_IS_SHARING), $counter++);

	if (!$r) {
		$o .= t("Done");
		return $o;
	}

	$o .= "<p>".$counter."/".$total.": ".$r[0]["url"]."</p>";

	$o .= "<p>".t("Keep this window open until done.")."</p>";

	$result = new_contact($uid,$r[0]["url"],true);

	$a->page['htmlhead'] = '<meta http-equiv="refresh" content="1; URL='.App::get_baseurl().'/repair_ostatus?counter='.$counter.'">';

	return $o;
}
