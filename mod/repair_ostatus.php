<?php
/**
 * @file mod/repair_ostatus.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Model\Contact;

function repair_ostatus_content(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$o = "<h2>".L10n::t("Resubscribing to OStatus contacts")."</h2>";

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
		return($o.L10n::t("Error"));

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
		$o .= L10n::t("Done");
		return $o;
	}

	$o .= "<p>".$counter."/".$total.": ".$r[0]["url"]."</p>";

	$o .= "<p>".L10n::t("Keep this window open until done.")."</p>";

	$result = Contact::createFromProbe($uid, $r[0]["url"], true);

	$a->page['htmlhead'] = '<meta http-equiv="refresh" content="1; URL='.System::baseUrl().'/repair_ostatus?counter='.$counter.'">';

	return $o;
}
