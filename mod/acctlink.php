<?php

require_once('include/Scrape.php');

function acctlink_init(App &$a) {

	if(x($_GET,'addr')) {
		$addr = trim($_GET['addr']);
		$res = probe_url($addr);
		//logger('acctlink: ' . print_r($res,true));
		if($res['url']) {
			goaway($res['url']);
			killme();
		}
	}
}
