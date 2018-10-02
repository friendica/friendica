<?php
/**
 * @file mod/webfinger.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Network\Probe;

function webfinger_content(App $a)
{
	if (!local_user()) {
		System::httpExit(
			403,
			[
				"title" => L10n::t("Public access denied."),
				"description" => L10n::t("Only logged in users are permitted to perform a probing.")
			]
		);
		killme();
	}

	$o  = '<h3>Webfinger Diagnostic</h3>';

	$o .= '<form action="webfinger" method="get">';
	$o .= 'Lookup address: <input type="text" style="width: 250px;" name="addr" value="' . defaults($_GET, 'addr', '') .'" />';
	$o .= '<input type="submit" name="submit" value="Submit" /></form>';

	$o .= '<br /><br />';

	if (x($_GET, 'addr')) {
		$addr = trim($_GET['addr']);
		$res = Probe::lrdd($addr);
		$o .= '<pre>';
		$o .= str_replace("\n", '<br />', print_r($res, true));
		$o .= '</pre>';
	}
	return $o;
}
