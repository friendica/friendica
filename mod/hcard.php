<?php

function hcard_init(App &$a) {

	$blocked = (((get_config('system','block_public')) && (! local_user()) && (! remote_user())) ? true : false);

	if ($a->argc > 1) {
		$which = $a->argv[1];
	}
	else {
		notice( t('No profile') . EOL );
		$a->error = 404;
		return;
	}

	$profile = 0;
	if ((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which   = $a->user['nickname'];
		$profile = $a->argv[1];
	}

	profile_load($a,$which,$profile);

	if ((x($a->profile,'page-flags')) && ($a->profile['page-flags'] == PAGE_COMMUNITY)) {
		$a->page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
	}
	if (x($a->profile,'openidserver')) {
		$a->page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
	}
	if (x($a->profile,'openid')) {
		$delegate = ((strstr($a->profile['openid'],'://')) ? $a->profile['openid'] : 'http://' . $a->profile['openid']);
		$a->page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
	}

	if (! $blocked) {
		$keywords = ((x($a->profile,'pub_keywords')) ? $a->profile['pub_keywords'] : '');
		$keywords = str_replace(array(',',' ',',,'),array(' ',',',','),$keywords);
		if (strlen($keywords)) {
			$a->page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n" ;
		}
	}

	$a->page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . (($a->profile['net-publish']) ? 'true' : 'false') . '" />' . "\r\n" ;
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . App::get_baseurl() . '/dfrn_poll/' . $which .'" />' . "\r\n" ;
	$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $a->get_hostname() . (($a->path) ? '/' . $a->path : ''));
	$a->page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . App::get_baseurl() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
	header('Link: <' . App::get_baseurl() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

	$dfrn_pages = array('request', 'confirm', 'notify', 'poll');
	foreach ($dfrn_pages as $dfrn) {
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"".App::get_baseurl()."/dfrn_{$dfrn}/{$which}\" />\r\n";
	}

}
