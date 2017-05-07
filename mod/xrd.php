<?php

use Friendica\App;

require_once('include/crypto.php');

function xrd_init(App $a) {

	$uri = urldecode(notags(trim($_GET['uri'])));

	if(substr($uri,0,4) === 'http') {
		$acct = false;
		$name = basename($uri);
	} else {
		$acct = true;
		$local = str_replace('acct:', '', $uri);
		if(substr($local,0,2) == '//')
			$local = substr($local,2);

		$name = substr($local,0,strpos($local,'@'));
	}

	$r = dba::select('user', array(), array('nickname' => $name), array('limit' => 1));
	if (! dbm::is_result($r)) {
		killme();
	}

	$salmon_key = salmon_key($r['spubkey']);

	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");

	$tpl = get_markup_template('xrd_diaspora.tpl');
	$dspr = replace_macros($tpl,array(
		'$baseurl' => App::get_baseurl(),
		'$dspr_guid' => $r['guid'],
		'$dspr_key' => base64_encode(pemtorsa($r['pubkey']))
	));

	$tpl = get_markup_template('xrd_person.tpl');

	$profile_url = App::get_baseurl().'/profile/'.$r['nickname'];

	if ($acct) {
		$alias = $profile_url;
	}
	else {
		$alias = 'acct:'.$r['nickname'].'@'.$a->get_hostname();

		if ($a->get_path()) {
			$alias .= '/'.$a->get_path();
		}
	}

	$o = replace_macros($tpl, array(
		'$nick'        => $r['nickname'],
		'$accturi'     => $uri,
		'$alias'       => $alias,
		'$profile_url' => $profile_url,
		'$hcard_url'   => App::get_baseurl() . '/hcard/'         . $r['nickname'],
		'$atom'        => App::get_baseurl() . '/dfrn_poll/'     . $r['nickname'],
		'$zot_post'    => App::get_baseurl() . '/post/'          . $r['nickname'],
		'$poco_url'    => App::get_baseurl() . '/poco/'          . $r['nickname'],
		'$photo'       => App::get_baseurl() . '/photo/profile/' . $r['uid']      . '.jpg',
		'$dspr'        => $dspr,
		'$salmon'      => App::get_baseurl() . '/salmon/'        . $r['nickname'],
		'$salmen'      => App::get_baseurl() . '/salmon/'        . $r['nickname'] . '/mention',
		'$subscribe'   => App::get_baseurl() . '/follow?url={uri}',
		'$modexp'      => 'data:application/magic-public-key,'  . $salmon_key,
		'$bigkey'      => salmon_key($r['pubkey']),
	));


	$arr = array('user' => $r, 'xml' => $o);
	call_hooks('personal_xrd', $arr);

	echo $arr['xml'];
	killme();

}
