<?php
/**
 * @file mod/xrd.php
 */

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Protocol\Salmon;

function xrd_init(App $a)
{
	if ($a->argv[0] == 'xrd') {
		if (empty($_GET['uri'])) {
			killme();
		}

		$uri = urldecode(notags(trim($_GET['uri'])));
		if (defaults($_SERVER, 'HTTP_ACCEPT', '') == 'application/jrd+json') {
			$mode = 'json';
		} else {
			$mode = 'xml';
		}
	} else {
		if (empty($_GET['resource'])) {
			killme();
		}

		$uri = urldecode(notags(trim($_GET['resource'])));
		if (defaults($_SERVER, 'HTTP_ACCEPT', '') == 'application/xrd+xml') {
			$mode = 'xml';
		} else {
			$mode = 'json';
		}
	}

	if (substr($uri, 0, 4) === 'http') {
		$name = ltrim(basename($uri), '~');
	} else {
		$local = str_replace('acct:', '', $uri);
		if (substr($local, 0, 2) == '//') {
			$local = substr($local, 2);
		}

		$name = substr($local, 0, strpos($local, '@'));
	}

	$user = DBA::selectFirst('user', [], ['nickname' => $name]);
	if (!DBA::isResult($user)) {
		killme();
	}

	$profile_url = System::baseUrl().'/profile/'.$user['nickname'];

	$alias = str_replace('/profile/', '/~', $profile_url);

	$addr = 'acct:'.$user['nickname'].'@'.$a->get_hostname();
	if ($a->get_path()) {
		$addr .= '/'.$a->get_path();
	}

	if ($mode == 'xml') {
		xrd_xml($a, $addr, $alias, $profile_url, $user);
	} else {
		xrd_json($a, $addr, $alias, $profile_url, $user);
	}
}

function xrd_json($a, $uri, $alias, $profile_url, $r)
{
	$salmon_key = Salmon::salmonKey($r['spubkey']);

	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");

	$json = ['subject' => $uri,
		'aliases' => [$alias, $profile_url],
		'links' => [
			['rel' => NAMESPACE_DFRN, 'href' => $profile_url],
			['rel' => NAMESPACE_FEED, 'type' => 'application/atom+xml', 'href' => System::baseUrl().'/dfrn_poll/'.$r['nickname']],
			['rel' => 'http://webfinger.net/rel/profile-page', 'type' => 'text/html', 'href' => $profile_url],
			['rel' => 'http://microformats.org/profile/hcard', 'type' => 'text/html', 'href' => System::baseUrl().'/hcard/'.$r['nickname']],
			['rel' => NAMESPACE_POCO, 'href' => System::baseUrl().'/poco/'.$r['nickname']],
			['rel' => 'http://webfinger.net/rel/avatar', 'type' => 'image/jpeg', 'href' => System::baseUrl().'/photo/profile/'.$r['uid'].'.jpg'],
			['rel' => 'http://joindiaspora.com/seed_location', 'type' => 'text/html', 'href' => System::baseUrl()],
			['rel' => 'salmon', 'href' => System::baseUrl().'/salmon/'.$r['nickname']],
			['rel' => 'http://salmon-protocol.org/ns/salmon-replies', 'href' => System::baseUrl().'/salmon/'.$r['nickname']],
			['rel' => 'http://salmon-protocol.org/ns/salmon-mention', 'href' => System::baseUrl().'/salmon/'.$r['nickname'].'/mention'],
			['rel' => 'http://ostatus.org/schema/1.0/subscribe', 'template' => System::baseUrl().'/follow?url={uri}'],
			['rel' => 'magic-public-key', 'href' => 'data:application/magic-public-key,'.$salmon_key],
			['rel' => 'http://purl.org/openwebauth/v1', 'type' => 'application/x-dfrn+json', 'href' => System::baseUrl().'/owa']
		]
	];
	echo json_encode($json);
	killme();
}

function xrd_xml($a, $uri, $alias, $profile_url, $r)
{
	$salmon_key = Salmon::salmonKey($r['spubkey']);

	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");

	$tpl = get_markup_template('xrd_person.tpl');

	$o = replace_macros($tpl, [
		'$nick'        => $r['nickname'],
		'$accturi'     => $uri,
		'$alias'       => $alias,
		'$profile_url' => $profile_url,
		'$hcard_url'   => System::baseUrl() . '/hcard/'         . $r['nickname'],
		'$atom'        => System::baseUrl() . '/dfrn_poll/'     . $r['nickname'],
		'$poco_url'    => System::baseUrl() . '/poco/'          . $r['nickname'],
		'$photo'       => System::baseUrl() . '/photo/profile/' . $r['uid']      . '.jpg',
		'$baseurl'     => System::baseUrl(),
		'$salmon'      => System::baseUrl() . '/salmon/'        . $r['nickname'],
		'$salmen'      => System::baseUrl() . '/salmon/'        . $r['nickname'] . '/mention',
		'$subscribe'   => System::baseUrl() . '/follow?url={uri}',
		'$openwebauth' => System::baseUrl() . '/owa',
		'$modexp'      => 'data:application/magic-public-key,'  . $salmon_key]
	);

	$arr = ['user' => $r, 'xml' => $o];
	Addon::callHooks('personal_xrd', $arr);

	echo $arr['xml'];
	killme();
}
