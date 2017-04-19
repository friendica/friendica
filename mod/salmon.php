<?php

require_once('include/salmon.php');
require_once('include/ostatus.php');
require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/follow.php');

function salmon_return($val) {

	if($val >= 400)
		$err = 'Error';
	if($val >= 200 && $val < 300)
		$err = 'OK';

	logger('mod-salmon returns ' . $val);
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);
	killme();

}

function salmon_post(App $a) {

	$xml = file_get_contents('php://input');

	logger('mod-salmon: new salmon ' . $xml, LOGGER_DATA);

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$mentions   = (($a->argc > 2 && $a->argv[2] === 'mention') ? true : false);

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
		dbesc($nick)
	);
	if (! dbm::is_result($r)) {
		http_status_exit(500);
	}

	$importer = $r[0];

	// parse the xml

	$dom = simplexml_load_string($xml,'SimpleXMLElement',0,NAMESPACE_SALMON_ME);

	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;

	if(! $base) {
		logger('mod-salmon: unable to locate salmon data in xml ');
		http_status_exit(400);
	}

	// Stash the signature away for now. We have to find their key or it won't be good for anything.


	$signature = base64url_decode($base->sig);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);

	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0];
	$encoding = $base->encoding;
	$alg = $base->alg;

	// Salmon magic signatures have evolved and there is no way of knowing ahead of time which
	// flavour we have. We'll try and verify it regardless.

	$stnet_signed_data = $data;

	$signed_data = $data  . '.' . base64url_encode($type) . '.' . base64url_encode($encoding) . '.' . base64url_encode($alg);

	$compliant_format = str_replace('=', '', $signed_data);


	// decode the data
	$data = base64url_decode($data);

	$author = ostatus::salmon_author($data,$importer);
	$author_link = $author["author-link"];

	if(! $author_link) {
		logger('mod-salmon: Could not retrieve author URI.');
		http_status_exit(400);
	}

	// Once we have the author URI, go to the web and try to find their public key

	logger('mod-salmon: Fetching key for ' . $author_link);

	$key = get_salmon_key($author_link,$keyhash);

	if(! $key) {
		logger('mod-salmon: Could not retrieve author key.');
		http_status_exit(400);
	}

	$key_info = explode('.',$key);

	$m = base64url_decode($key_info[1]);
	$e = base64url_decode($key_info[2]);

	logger('mod-salmon: key details: ' . print_r($key_info,true), LOGGER_DEBUG);

	$pubkey = metopem($m,$e);

	// We should have everything we need now. Let's see if it verifies.

	// Try GNU Social format
	$verify = rsa_verify($signed_data, $signature, $pubkey);
	$mode = 1;

	if (! $verify) {
		logger('mod-salmon: message did not verify using protocol. Trying compliant format.');
		$verify = rsa_verify($compliant_format, $signature, $pubkey);
		$mode = 2;
	}

	if (! $verify) {
		logger('mod-salmon: message did not verify using padding. Trying old statusnet format.');
		$verify = rsa_verify($stnet_signed_data, $signature, $pubkey);
		$mode = 3;
	}

	if (! $verify) {
		logger('mod-salmon: Message did not verify. Discarding.');
		http_status_exit(400);
	}

	logger('mod-salmon: Message verified with mode '.$mode);


	/*
	*
	* If we reached this point, the message is good. Now let's figure out if the author is allowed to send us stuff.
	*
	*/

	$r = q("SELECT * FROM `contact` WHERE `network` IN ('%s', '%s')
						AND (`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s')
						AND `uid` = %d LIMIT 1",
		dbesc(NETWORK_OSTATUS),
		dbesc(NETWORK_DFRN),
		dbesc(normalise_link($author_link)),
		dbesc($author_link),
		dbesc(normalise_link($author_link)),
		intval($importer['uid'])
	);
	if (! dbm::is_result($r)) {
		logger('mod-salmon: Author unknown to us.');
		if(get_pconfig($importer['uid'],'system','ostatus_autofriend')) {
			$result = new_contact($importer['uid'],$author_link);
			if($result['success']) {
				$r = q("SELECT * FROM `contact` WHERE `network` = '%s' AND ( `url` = '%s' OR `alias` = '%s')
					AND `uid` = %d LIMIT 1",
					dbesc(NETWORK_OSTATUS),
					dbesc($author_link),
					dbesc($author_link),
					intval($importer['uid'])
				);
			}
		}
	}

	// Have we ignored the person?
	// If so we can not accept this post.

	//if((dbm::is_result($r)) && (($r[0]['readonly']) || ($r[0]['rel'] == CONTACT_IS_FOLLOWER) || ($r[0]['blocked']))) {
	if (dbm::is_result($r) && $r[0]['blocked']) {
		logger('mod-salmon: Ignoring this author.');
		http_status_exit(202);
		// NOTREACHED
	}

	// Placeholder for hub discovery.
	$hub = '';

	$contact_rec = ((dbm::is_result($r)) ? $r[0] : null);

	ostatus::import($data,$importer,$contact_rec, $hub);

	http_status_exit(200);
}
