<?php
/**
 * @file mod/hcard.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Session;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;

function hcard_init(App $a)
{
	$blocked = Config::get('system', 'block_public') && !Session::isAuthenticated();

	if ($a->argc > 1) {
		$which = $a->argv[1];
	} else {
		throw new \Friendica\Network\HTTPException\NotFoundException(L10n::t('No profile'));
	}

	$profile = 0;
	if ((local_user()) && ($a->argc > 2) && ($a->argv[2] === 'view')) {
		$which   = $a->user['nickname'];
		$profile = $a->argv[1];
	}

	Profile::load($a, $which, $profile);

	if (!empty($a->profile['page-flags']) && ($a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
		$a->page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
	}
	if (!empty($a->profile['openidserver'])) {
		$a->page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
	}
	if (!empty($a->profile['openid'])) {
		$delegate = ((strstr($a->profile['openid'], '://')) ? $a->profile['openid'] : 'http://' . $a->profile['openid']);
		$a->page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
	}

	if (!$blocked) {
		$keywords = $a->profile['pub_keywords'] ?? '';
		$keywords = str_replace([',',' ',',,'], [' ',',',','], $keywords);
		if (strlen($keywords)) {
			$a->page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n";
		}
	}

	$a->page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . (($a->profile['net-publish']) ? 'true' : 'false') . '" />' . "\r\n";
	$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/dfrn_poll/' . $which .'" />' . "\r\n";
	$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . DI::baseUrl()->getHostname() . ((DI::baseUrl()->getUrlPath()) ? '/' . DI::baseUrl()->getUrlPath() : ''));
	$a->page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . System::baseUrl() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
	header('Link: <' . System::baseUrl() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

	$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
	foreach ($dfrn_pages as $dfrn) {
		$a->page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"".System::baseUrl()."/dfrn_{$dfrn}/{$which}\" />\r\n";
	}
}
