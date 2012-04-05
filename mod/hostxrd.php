<?php

require_once('include/crypto.php');

function hostxrd_init(&$a) {
	header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	$pubkey = get_config('system','site_pubkey');

	if(! $pubkey) {

		// should only have to ever do this once.

		$res=openssl_pkey_new(array(
			'digest_alg' => 'sha1',
			'private_key_bits' => 4096,
			'encrypt_key' => false ));


		$prvkey = '';

		openssl_pkey_export($res, $prvkey);

		// Get public key

		$pkey = openssl_pkey_get_details($res);
		$pubkey = $pkey["key"];

		set_config('system','site_prvkey', $prvkey);
		set_config('system','site_pubkey', $pubkey);
	}

	$tpl = file_get_contents('view/xrd_host.tpl');
	echo str_replace(array(
		'$zhost','$zroot','$domain','$zot_post','$bigkey'),array($a->get_hostname(),z_root(),z_path(),z_root() . '/post', salmon_key(get_config('system','site_pubkey'))),$tpl);
	session_write_close();
	exit();

}