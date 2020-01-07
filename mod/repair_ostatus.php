<?php
/**
 * @file mod/repair_ostatus.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Registry\App as A;

function repair_ostatus_content(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		A::baseUrl()->redirect('ostatus_repair');
		// NOTREACHED
	}

	$o = "<h2>".L10n::t("Resubscribing to OStatus contacts")."</h2>";

	$uid = local_user();

	$counter = intval($_REQUEST['counter']);

	$r = q("SELECT COUNT(*) AS `total` FROM `contact` WHERE
	`uid` = %d AND `network` = '%s' AND `rel` IN (%d, %d)",
		intval($uid),
		DBA::escape(Protocol::OSTATUS),
		intval(Contact::FRIEND),
		intval(Contact::SHARING));

	if (!DBA::isResult($r)) {
		return ($o . L10n::t("Error"));
	}

	$total = $r[0]["total"];

	$r = q("SELECT `url` FROM `contact` WHERE
		`uid` = %d AND `network` = '%s' AND `rel` IN (%d, %d)
		ORDER BY `url`
		LIMIT %d, 1",
		intval($uid),
		DBA::escape(Protocol::OSTATUS),
		intval(Contact::FRIEND),
		intval(Contact::SHARING), $counter++);

	if (!DBA::isResult($r)) {
		$o .= L10n::t("Done");
		return $o;
	}

	$o .= "<p>".$counter."/".$total.": ".$r[0]["url"]."</p>";

	$o .= "<p>".L10n::t("Keep this window open until done.")."</p>";

	Contact::createFromProbe($uid, $r[0]["url"], true);

	A::page()['htmlhead'] = '<meta http-equiv="refresh" content="1; URL=' . A::baseUrl() . '/repair_ostatus?counter=' . $counter . '">';

	return $o;
}
