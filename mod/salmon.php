<?php
/**
 * @file mod/salmon.php
 */
use Friendica\App;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;

require_once 'include/items.php';

function salmon_post(App $a, $xml = '') {

	if (empty($xml)) {
		$xml = file_get_contents('php://input');
	}

	logger('new salmon ' . $xml, LOGGER_DATA);

	$nick       = (($a->argc > 1) ? notags(trim($a->argv[1])) : '');
	$mentions   = (($a->argc > 2 && $a->argv[2] === 'mention') ? true : false);

	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
		DBA::escape($nick)
	);
	if (! DBA::isResult($r)) {
		System::httpExit(500);
	}

	$importer = $r[0];

	// parse the xml

	$dom = simplexml_load_string($xml,'SimpleXMLElement',0,NAMESPACE_SALMON_ME);

	$base = null;

	// figure out where in the DOM tree our data is hiding
	if (!empty($dom->provenance->data))
		$base = $dom->provenance;
	elseif (!empty($dom->env->data))
		$base = $dom->env;
	elseif (!empty($dom->data))
		$base = $dom;

	if (empty($base)) {
		logger('unable to locate salmon data in xml ');
		System::httpExit(400);
	}

	// Stash the signature away for now. We have to find their key or it won't be good for anything.


	$signature = base64url_decode($base->sig);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace([" ","\t","\r","\n"],["","","",""],$base->data);

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

	$author = OStatus::salmonAuthor($data, $importer);
	$author_link = $author["author-link"];

	if(! $author_link) {
		logger('Could not retrieve author URI.');
		System::httpExit(400);
	}

	// Once we have the author URI, go to the web and try to find their public key

	logger('Fetching key for ' . $author_link);

	$key = Salmon::getKey($author_link, $keyhash);

	if(! $key) {
		logger('Could not retrieve author key.');
		System::httpExit(400);
	}

	$key_info = explode('.',$key);

	$m = base64url_decode($key_info[1]);
	$e = base64url_decode($key_info[2]);

	logger('key details: ' . print_r($key_info,true), LOGGER_DEBUG);

	$pubkey = Crypto::meToPem($m, $e);

	// We should have everything we need now. Let's see if it verifies.

	// Try GNU Social format
	$verify = Crypto::rsaVerify($signed_data, $signature, $pubkey);
	$mode = 1;

	if (! $verify) {
		logger('message did not verify using protocol. Trying compliant format.');
		$verify = Crypto::rsaVerify($compliant_format, $signature, $pubkey);
		$mode = 2;
	}

	if (! $verify) {
		logger('message did not verify using padding. Trying old statusnet format.');
		$verify = Crypto::rsaVerify($stnet_signed_data, $signature, $pubkey);
		$mode = 3;
	}

	if (! $verify) {
		logger('Message did not verify. Discarding.');
		System::httpExit(400);
	}

	logger('Message verified with mode '.$mode);


	/*
	*
	* If we reached this point, the message is good. Now let's figure out if the author is allowed to send us stuff.
	*
	*/

	$r = q("SELECT * FROM `contact` WHERE `network` IN ('%s', '%s')
						AND (`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s')
						AND `uid` = %d LIMIT 1",
		DBA::escape(Protocol::OSTATUS),
		DBA::escape(Protocol::DFRN),
		DBA::escape(normalise_link($author_link)),
		DBA::escape($author_link),
		DBA::escape(normalise_link($author_link)),
		intval($importer['uid'])
	);

	if (!DBA::isResult($r)) {
		logger('Author ' . $author_link . ' unknown to user ' . $importer['uid'] . '.');

		if (PConfig::get($importer['uid'], 'system', 'ostatus_autofriend')) {
			$result = Contact::createFromProbe($importer['uid'], $author_link);

			if ($result['success']) {
				$r = q("SELECT * FROM `contact` WHERE `network` = '%s' AND ( `url` = '%s' OR `alias` = '%s')
					AND `uid` = %d LIMIT 1",
					DBA::escape(Protocol::OSTATUS),
					DBA::escape($author_link),
					DBA::escape($author_link),
					intval($importer['uid'])
				);
			}
		}
	}

	// Have we ignored the person?
	// If so we can not accept this post.

	//if((DBA::isResult($r)) && (($r[0]['readonly']) || ($r[0]['rel'] == Contact::FOLLOWER) || ($r[0]['blocked']))) {
	if (DBA::isResult($r) && $r[0]['blocked']) {
		logger('Ignoring this author.');
		System::httpExit(202);
		// NOTREACHED
	}

	// Placeholder for hub discovery.
	$hub = '';

	$contact_rec = ((DBA::isResult($r)) ? $r[0] : null);

	OStatus::import($data, $importer, $contact_rec, $hub);

	System::httpExit(200);
}
