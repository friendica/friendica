<?php

require_once('library/asn1.php');

function modexp_init(&$a) {

	if($a->argc != 2)
		killme();

	$nick = $a->argv[1];
	$r = q("SELECT `spubkey` FROM `user` WHERE `nickname` = '%s' LIMIT 1",
			dbesc($nick)
	);

	if (! dbm::is_result($r)) {
		killme();
	}

	$lines = explode("\n",$r[0]['spubkey']);
	unset($lines[0]);
	unset($lines[count($lines)]);
	$x = base64_decode(implode('',$lines));

	$r = ASN_BASE::parseASNString($x);

	$m = $r[0]->asnData[1]->asnData[0]->asnData[0]->asnData;
	$e = $r[0]->asnData[1]->asnData[0]->asnData[1]->asnData;

	header("Content-type: application/magic-public-key");
	echo 'RSA' . '.' . $m . '.' . $e ;

	killme();

}

