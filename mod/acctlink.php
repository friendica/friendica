<?php

use Friendica\App;
use Friendica\Network\Probe;

function acctlink_init()
{
	if (x($_GET, 'addr')) {
		$addr = trim($_GET['addr']);
		$res = Probe::uri($addr);
		if ($res['url']) {
			goaway($res['url']);
			killme();
		}
	}
}
