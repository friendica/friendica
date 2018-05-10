<?php

use Friendica\App;
use Friendica\Core\System;

function opensearch_content(App $a) {

	$tpl = get_markup_template('opensearch.tpl');

	header("Content-type: application/opensearchdescription+xml");

	$o = replace_macros($tpl, [
		'$baseurl' => System::baseUrl(),
		'$nodename' => $a->get_hostname(),
	]);

	echo $o;

	killme();
}
