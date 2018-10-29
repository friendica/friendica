<?php
/**
 * @file mod/hostxrd.php
 */
use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;

function hostxrd_init(App $a)
{
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$pubkey = Config::get('system', 'site_pubkey');

	if (! $pubkey) {
		$res = Crypto::newKeypair(1024);

		Config::set('system','site_prvkey', $res['prvkey']);
		Config::set('system','site_pubkey', $res['pubkey']);
	}

	$tpl = Text::getMarkupTemplate('xrd_host.tpl');
	echo App::replaceMacros($tpl, [
		'$zhost' => $a->getHostName(),
		'$zroot' => System::baseUrl(),
		'$domain' => System::baseUrl(),
		'$bigkey' => Salmon::salmonKey(Config::get('system', 'site_pubkey'))]
	);

	exit();
}
