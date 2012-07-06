<?php

require_once('include/crypto.php');
require_once('include/items.php');
require_once('include/bb2diaspora.php');
require_once('include/contact_selectors.php');
require_once('include/queue_fn.php');
require_once('include/lock.php');


function diaspora_dispatch_public($msg) {

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		logger('mod-diaspora: disabled');
		return;
	}

	$r = q("SELECT `user`.* FROM `user` WHERE `user`.`uid` IN ( SELECT `contact`.`uid` FROM `contact` WHERE `contact`.`network` = '%s' AND `contact`.`addr` = '%s' ) AND `account_expired` = 0 ",
		dbesc(NETWORK_DIASPORA),
		dbesc($msg['author'])
	);
	if(count($r)) {
		foreach($r as $rr) {
			logger('diaspora_public: delivering to: ' . $rr['username']);
			diaspora_dispatch($rr,$msg);
		}
	}
	else
		logger('diaspora_public: no subscribers');
}



function diaspora_dispatch($importer,$msg) {

	$ret = 0;

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		logger('mod-diaspora: disabled');
		return;
	}

	// php doesn't like dashes in variable names

	$msg['message'] = str_replace(
			array('<activity_streams-photo>','</activity_streams-photo>'),
			array('<asphoto>','</asphoto>'),
			$msg['message']);


	$parsed_xml = parse_xml_string($msg['message'],false);

	$xmlbase = $parsed_xml->post;

	logger('diaspora_dispatch: ' . print_r($xmlbase,true), LOGGER_DEBUG);


	if($xmlbase->request) {
		$ret = diaspora_request($importer,$xmlbase->request);
	}
	elseif($xmlbase->status_message) {
		$ret = diaspora_post($importer,$xmlbase->status_message);
	}
	elseif($xmlbase->profile) {
		$ret = diaspora_profile($importer,$xmlbase->profile);
	}
	elseif($xmlbase->comment) {
		$ret = diaspora_comment($importer,$xmlbase->comment,$msg);
	}
	elseif($xmlbase->like) {
		$ret = diaspora_like($importer,$xmlbase->like,$msg);
	}
	elseif($xmlbase->asphoto) {
		$ret = diaspora_asphoto($importer,$xmlbase->asphoto);
	}
	elseif($xmlbase->reshare) {
		$ret = diaspora_reshare($importer,$xmlbase->reshare);
	}
	elseif($xmlbase->retraction) {
		$ret = diaspora_retraction($importer,$xmlbase->retraction,$msg);
	}
	elseif($xmlbase->signed_retraction) {
		$ret = diaspora_signed_retraction($importer,$xmlbase->signed_retraction,$msg);
	}
	elseif($xmlbase->relayable_retraction) {
		$ret = diaspora_signed_retraction($importer,$xmlbase->relayable_retraction,$msg);
	}
	elseif($xmlbase->photo) {
		$ret = diaspora_photo($importer,$xmlbase->photo,$msg);
	}
	elseif($xmlbase->conversation) {
		$ret = diaspora_conversation($importer,$xmlbase->conversation,$msg);
	}
	elseif($xmlbase->message) {
		$ret = diaspora_message($importer,$xmlbase->message,$msg);
	}
	else {
		logger('diaspora_dispatch: unknown message type: ' . print_r($xmlbase,true));
	}
	return $ret;
}

function diaspora_get_contact_by_handle($uid,$handle) {
	$r = q("SELECT * FROM `contact` WHERE `network` = '%s' AND `uid` = %d AND `addr` = '%s' LIMIT 1",
		dbesc(NETWORK_DIASPORA),
		intval($uid),
		dbesc($handle)
	);
	if($r && count($r))
		return $r[0];
	return false;
}

function find_diaspora_person_by_handle($handle) {

	$person = false;
	$update = false;
	$got_lock = false;

	$endlessloop = 0;
	$maxloops = 10;

	do {
		$r = q("select * from fcontact where network = '%s' and addr = '%s' limit 1",
			dbesc(NETWORK_DIASPORA),
			dbesc($handle)
		);
		if(count($r)) {
			$person = $r[0];
			logger('find_diaspora_person_by handle: in cache ' . print_r($r,true), LOGGER_DEBUG);

			// update record occasionally so it doesn't get stale
			$d = strtotime($person['updated'] . ' +00:00');
			if($d < strtotime('now - 14 days'))
				$update = true;
		}


		// FETCHING PERSON INFORMATION FROM REMOTE SERVER
		//
		// If the person isn't in our 'fcontact' table, or if he/she is but
		// his/her information hasn't been updated for more than 14 days, then
		// we want to fetch the person's information from the remote server.
		//
		// Note that $person isn't changed by this block of code unless the
		// person's information has been successfully fetched from the remote
		// server. So if $person was 'false' to begin with (because he/she wasn't
		// in the local cache), it'll stay false, and if $person held the local
		// cache information to begin with, it'll keep that information. That way
		// if there's a problem with the remote fetch, we can at least use our
		// cached information--it's better than nothing.

		if((! $person) || ($update))  {
			// Lock the function to prevent race conditions if multiple items
			// come in at the same time from a person who doesn't exist in
			// fcontact
			//
			// Don't loop forever. On the last loop, try to create the contact
			// whether the function is locked or not. Maybe the locking thread
			// has died or something. At any rate, a duplicate in 'fcontact'
			// is a much smaller problem than a deadlocked thread
			$got_lock = (($endlessloop + 1) < $maxloops ? lock_function('find_diaspora_person_by_handle', false) : true );

			if($got_lock) {
				logger('find_diaspora_person_by_handle: create or refresh', LOGGER_DEBUG);
				require_once('include/Scrape.php');
				$r = probe_url($handle, PROBE_DIASPORA);

				// Note that Friendica contacts can return a "Diaspora person"
				// if Diaspora connectivity is enabled on their server
				if((count($r)) && ($r['network'] === NETWORK_DIASPORA)) {
					add_fcontact($r,$update);
					$person = ($r);
				}

				unlock_function('find_diaspora_person_by_handle');
			}
			else {
				logger('find_diaspora_person_by_handle: couldn\'t lock function', LOGGER_DEBUG);
				if(! $person)
					block_on_function_lock('find_diaspora_person_by_handle');
			}
		}
	} while((! $person) && (! $got_lock) && (++$endlessloop < $maxloops));
	// We need to try again if the person wasn't in 'fcontact' but the function was locked.
	// The fact that the function was locked may mean that another process was creating the
	// person's record. It could also mean another process was creating or updating an unrelated
	// person.
	//
	// At any rate, we need to keep trying until we've either got the person or had a chance to
	// try to fetch his/her remote information. But we don't want to block on locking the
	// function, because if the other process is creating the record, then when we acquire the lock
	// we'll dive right into creating another, duplicate record. We DO want to at least wait
	// until the lock is released, so we don't flood the database with requests.
	//
	// If the person was in the 'fcontact' table, don't try again. It's not worth the time, since
	// we do have some information for the person

	return $person;
}


function get_diaspora_key($uri) {
	logger('Fetching diaspora key for: ' . $uri);

	$r = find_diaspora_person_by_handle($uri);
	if($r)
		return $r['pubkey'];
	return '';
}


function diaspora_pubmsg_build($msg,$user,$contact,$prvkey,$pubkey) {
	$a = get_app();

	logger('diaspora_pubmsg_build: ' . $msg, LOGGER_DATA);

	
	$handle = $user['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

//	$b64_data = base64_encode($msg);
//	$b64url_data = base64url_encode($b64_data);

	$b64url_data = base64url_encode($msg);

	$data = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);

	$type = 'application/xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type) . '.' 
		. base64url_encode($encoding) . '.' . base64url_encode($alg) ;

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature);

$magic_env = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<diaspora xmlns="https://joindiaspora.com/protocol" xmlns:me="http://salmon-protocol.org/ns/magic-env" >
  <header>
    <author_id>$handle</author_id>
  </header>
  <me:env>
    <me:encoding>base64url</me:encoding>
    <me:alg>RSA-SHA256</me:alg>
    <me:data type="application/xml">$data</me:data>
    <me:sig>$sig</me:sig>
  </me:env>
</diaspora>
EOT;

	logger('diaspora_pubmsg_build: magic_env: ' . $magic_env, LOGGER_DATA);
	return $magic_env;

}




function diaspora_msg_build($msg,$user,$contact,$prvkey,$pubkey,$public = false) {
	$a = get_app();

	if($public)
		return diaspora_pubmsg_build($msg,$user,$contact,$prvkey,$pubkey);

	logger('diaspora_msg_build: ' . $msg, LOGGER_DATA);

	// without a public key nothing will work

	if(! $pubkey) {
		logger('diaspora_msg_build: pubkey missing: contact id: ' . $contact['id']);
		return '';
	}

	$inner_aes_key = random_string(32);
	$b_inner_aes_key = base64_encode($inner_aes_key);
	$inner_iv = random_string(16);
	$b_inner_iv = base64_encode($inner_iv);

	$outer_aes_key = random_string(32);
	$b_outer_aes_key = base64_encode($outer_aes_key);
	$outer_iv = random_string(16);
	$b_outer_iv = base64_encode($outer_iv);
	
	$handle = $user['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$padded_data = pkcs5_pad($msg,16);
	$inner_encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $padded_data, MCRYPT_MODE_CBC, $inner_iv);

	$b64_data = base64_encode($inner_encrypted);


	$b64url_data = base64url_encode($b64_data);
	$data = str_replace(array("\n","\r"," ","\t"),array('','','',''),$b64url_data);

	$type = 'application/xml';
	$encoding = 'base64url';
	$alg = 'RSA-SHA256';

	$signable_data = $data  . '.' . base64url_encode($type) . '.' 
		. base64url_encode($encoding) . '.' . base64url_encode($alg) ;

	$signature = rsa_sign($signable_data,$prvkey);
	$sig = base64url_encode($signature);

$decrypted_header = <<< EOT
<decrypted_header>
  <iv>$b_inner_iv</iv>
  <aes_key>$b_inner_aes_key</aes_key>
  <author_id>$handle</author_id>
</decrypted_header>
EOT;

	$decrypted_header = pkcs5_pad($decrypted_header,16);

	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $outer_aes_key, $decrypted_header, MCRYPT_MODE_CBC, $outer_iv);

	$outer_json = json_encode(array('iv' => $b_outer_iv,'key' => $b_outer_aes_key));

	$encrypted_outer_key_bundle = '';
	openssl_public_encrypt($outer_json,$encrypted_outer_key_bundle,$pubkey);

	$b64_encrypted_outer_key_bundle = base64_encode($encrypted_outer_key_bundle);

	logger('outer_bundle: ' . $b64_encrypted_outer_key_bundle . ' key: ' . $pubkey, LOGGER_DATA);

	$encrypted_header_json_object = json_encode(array('aes_key' => base64_encode($encrypted_outer_key_bundle), 
		'ciphertext' => base64_encode($ciphertext)));
	$cipher_json = base64_encode($encrypted_header_json_object);

	$encrypted_header = '<encrypted_header>' . $cipher_json . '</encrypted_header>';

$magic_env = <<< EOT
<?xml version='1.0' encoding='UTF-8'?>
<diaspora xmlns="https://joindiaspora.com/protocol" xmlns:me="http://salmon-protocol.org/ns/magic-env" >
  $encrypted_header
  <me:env>
    <me:encoding>base64url</me:encoding>
    <me:alg>RSA-SHA256</me:alg>
    <me:data type="application/xml">$data</me:data>
    <me:sig>$sig</me:sig>
  </me:env>
</diaspora>
EOT;

	logger('diaspora_msg_build: magic_env: ' . $magic_env, LOGGER_DATA);
	return $magic_env;

}

/**
 *
 * diaspora_decode($importer,$xml)
 *   array $importer -> from user table
 *   string $xml -> urldecoded Diaspora salmon 
 *
 * Returns array
 * 'message' -> decoded Diaspora XML message
 * 'author' -> author diaspora handle
 * 'key' -> author public key (converted to pkcs#8)
 *
 * Author and key are used elsewhere to save a lookup for verifying replies and likes
 */


function diaspora_decode($importer,$xml) {

	$public = false;
	$basedom = parse_xml_string($xml);

	$children = $basedom->children('https://joindiaspora.com/protocol');

	if($children->header) {
		$public = true;
		$author_link = str_replace('acct:','',$children->header->author_id);
	}
	else {

		$encrypted_header = json_decode(base64_decode($children->encrypted_header));
	
		$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
		$ciphertext = base64_decode($encrypted_header->ciphertext);

		$outer_key_bundle = '';
		openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$importer['prvkey']);

		$j_outer_key_bundle = json_decode($outer_key_bundle);

		$outer_iv = base64_decode($j_outer_key_bundle->iv);
		$outer_key = base64_decode($j_outer_key_bundle->key);

		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $outer_key, $ciphertext, MCRYPT_MODE_CBC, $outer_iv);


		$decrypted = pkcs5_unpad($decrypted);

		/**
		 * $decrypted now contains something like
		 *
		 *  <decrypted_header>
		 *     <iv>8e+G2+ET8l5BPuW0sVTnQw==</iv>
		 *     <aes_key>UvSMb4puPeB14STkcDWq+4QE302Edu15oaprAQSkLKU=</aes_key>

***** OBSOLETE

		 *     <author>
		 *       <name>Ryan Hughes</name>
		 *       <uri>acct:galaxor@diaspora.pirateship.org</uri>
		 *     </author>

***** CURRENT

		 *     <author_id>galaxor@diaspora.priateship.org</author_id>

***** END DIFFS

		 *  </decrypted_header>
		 */

		logger('decrypted: ' . $decrypted, LOGGER_DEBUG);
		$idom = parse_xml_string($decrypted,false);

		$inner_iv = base64_decode($idom->iv);
		$inner_aes_key = base64_decode($idom->aes_key);

		$author_link = str_replace('acct:','',$idom->author_id);

	}

	$dom = $basedom->children(NAMESPACE_SALMON_ME);

	// figure out where in the DOM tree our data is hiding

	if($dom->provenance->data)
		$base = $dom->provenance;
	elseif($dom->env->data)
		$base = $dom->env;
	elseif($dom->data)
		$base = $dom;
	
	if(! $base) {
		logger('mod-diaspora: unable to locate salmon data in xml ');
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


	$signed_data = $data  . '.' . base64url_encode($type) . '.' . base64url_encode($encoding) . '.' . base64url_encode($alg);


	// decode the data
	$data = base64url_decode($data);


	if($public) {
		$inner_decrypted = $data;
	}
	else {

		// Decode the encrypted blob

		$inner_encrypted = base64_decode($data);
		$inner_decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $inner_encrypted, MCRYPT_MODE_CBC, $inner_iv);
		$inner_decrypted = pkcs5_unpad($inner_decrypted);
	}

	if(! $author_link) {
		logger('mod-diaspora: Could not retrieve author URI.');
		http_status_exit(400);
	}

	// Once we have the author URI, go to the web and try to find their public key
	// (first this will look it up locally if it is in the fcontact cache)
	// This will also convert diaspora public key from pkcs#1 to pkcs#8

	logger('mod-diaspora: Fetching key for ' . $author_link );
 	$key = get_diaspora_key($author_link);

	if(! $key) {
		logger('mod-diaspora: Could not retrieve author key.');
		http_status_exit(400);
	}

	$verify = rsa_verify($signed_data,$signature,$key);

	if(! $verify) {
		logger('mod-diaspora: Message did not verify. Discarding.');
		http_status_exit(400);
	}

	logger('mod-diaspora: Message verified.');

	return array('message' => $inner_decrypted, 'author' => $author_link, 'key' => $key);

}

	
function diaspora_request($importer,$xml) {

	$a = get_app();

	$sender_handle = unxmlify($xml->sender_handle);
	$recipient_handle = unxmlify($xml->recipient_handle);

	if(! $sender_handle || ! $recipient_handle)
		return;
	 
	$contact = diaspora_get_contact_by_handle($importer['uid'],$sender_handle);

	if($contact) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.

		if($contact['rel'] == CONTACT_IS_FOLLOWER && $importer['page-flags'] != PAGE_COMMUNITY) {
			q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval(CONTACT_IS_FRIEND),
				intval($contact['id']),
				intval($importer['uid'])
			);
		}
		// send notification

		$r = q("SELECT `hide-friends` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval($importer['uid'])
		);

		if((count($r)) && (! $r[0]['hide-friends']) && (! $contact['hidden'])) {
			require_once('include/items.php');

			$self = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
				intval($importer['uid'])
			);

			// they are not CONTACT_IS_FOLLOWER anymore but that's what we have in the array

			if(count($self) && $contact['rel'] == CONTACT_IS_FOLLOWER) {

				$arr = array();
				$arr['uri'] = $arr['parent-uri'] = item_new_uri($a->get_hostname(), $importer['uid']); 
				$arr['uid'] = $importer['uid'];
				$arr['contact-id'] = $self[0]['id'];
				$arr['wall'] = 1;
				$arr['type'] = 'wall';
				$arr['gravity'] = 0;
				$arr['origin'] = 1;
				$arr['author-name'] = $arr['owner-name'] = $self[0]['name'];
				$arr['author-link'] = $arr['owner-link'] = $self[0]['url'];
				$arr['author-avatar'] = $arr['owner-avatar'] = $self[0]['thumb'];
				$arr['verb'] = ACTIVITY_FRIEND;
				$arr['object-type'] = ACTIVITY_OBJ_PERSON;
				
				$A = '[url=' . $self[0]['url'] . ']' . $self[0]['name'] . '[/url]';
				$B = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
				$BPhoto = '[url=' . $contact['url'] . ']' . '[img]' . $contact['thumb'] . '[/img][/url]';
				$arr['body'] =  sprintf( t('%1$s is now friends with %2$s'), $A, $B)."\n\n\n".$Bphoto;

				$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PERSON . '</type><title>' . $contact['name'] . '</title>'
					. '<id>' . $contact['url'] . '/' . $contact['name'] . '</id>';
				$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $contact['url'] . '" />' . "\n");
				$arr['object'] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $contact['thumb'] . '" />' . "\n");
				$arr['object'] .= '</link></object>' . "\n";
				$arr['last-child'] = 1;

				$arr['allow_cid'] = $user[0]['allow_cid'];
				$arr['allow_gid'] = $user[0]['allow_gid'];
				$arr['deny_cid']  = $user[0]['deny_cid'];
				$arr['deny_gid']  = $user[0]['deny_gid'];

				$i = item_store($arr);
				if($i)
			    	proc_run('php',"include/notifier.php","activity","$i");

			}

		}

		return;
	}
	
	$ret = find_diaspora_person_by_handle($sender_handle);


	if((! count($ret)) || ($ret['network'] != NETWORK_DIASPORA)) {
		logger('diaspora_request: Cannot resolve diaspora handle ' . $sender_handle . ' for ' . $recipient_handle);
		return;
	}

	$batch = (($ret['batch']) ? $ret['batch'] : implode('/', array_slice(explode('/',$ret['url']),0,3)) . '/receive/public');



	$r = q("INSERT INTO `contact` (`uid`, `network`,`addr`,`created`,`url`,`nurl`,`batch`,`name`,`nick`,`photo`,`pubkey`,`notify`,`poll`,`blocked`,`priority`)
		VALUES ( %d, '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s',%d,%d) ",
		intval($importer['uid']),
		dbesc($ret['network']),
		dbesc($ret['addr']),
		datetime_convert(),
		dbesc($ret['url']),
		dbesc(normalise_link($ret['url'])),
		dbesc($batch),
		dbesc($ret['name']),
		dbesc($ret['nick']),
		dbesc($ret['photo']),
		dbesc($ret['pubkey']),
		dbesc($ret['notify']),
		dbesc($ret['poll']),
		1,
		2
	);
		 
	// find the contact record we just created

	$contact_record = diaspora_get_contact_by_handle($importer['uid'],$sender_handle);

	if(! $contact_record) {
		logger('diaspora_request: unable to locate newly created contact record.');
		return;
	}

	$g = q("select def_gid from user where uid = %d limit 1",
		intval($importer['uid'])
	);
	if($g && intval($g[0]['def_gid'])) {
		require_once('include/group.php');
		group_add_member($importer['uid'],'',$contact_record['id'],$g[0]['def_gid']);
	}

	if($importer['page-flags'] == PAGE_NORMAL) {

		$hash = random_string() . (string) time();   // Generate a confirm_key
	
		$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime` )
			VALUES ( %d, %d, %d, %d, '%s', '%s', '%s' )",
			intval($importer['uid']),
			intval($contact_record['id']),
			0,
			0,
			dbesc( t('Sharing notification from Diaspora network')),
			dbesc($hash),
			dbesc(datetime_convert())
		);
	}
	else {

		// automatic friend approval

		require_once('include/Photo.php');

		$photos = import_profile_photo($contact_record['photo'],$importer['uid'],$contact_record['id']);
		
		// technically they are sharing with us (CONTACT_IS_SHARING), 
		// but if our page-type is PAGE_COMMUNITY or PAGE_SOAPBOX
		// we are going to change the relationship and make them a follower.

		if($importer['page-flags'] == PAGE_FREELOVE)
			$new_relation = CONTACT_IS_FRIEND;
		else
			$new_relation = CONTACT_IS_FOLLOWER;

		$r = q("UPDATE `contact` SET 
			`photo` = '%s', 
			`thumb` = '%s',
			`micro` = '%s', 
			`rel` = %d, 
			`name-date` = '%s', 
			`uri-date` = '%s', 
			`avatar-date` = '%s', 
			`blocked` = 0, 
			`pending` = 0
			WHERE `id` = %d LIMIT 1
			",
			dbesc($photos[0]),
			dbesc($photos[1]),
			dbesc($photos[2]),
			intval($new_relation),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($contact_record['id'])
		);

		$u = q("select * from user where uid = %d limit 1",intval($importer['uid']));
		if($u)
			$ret = diaspora_share($u[0],$contact_record);
	}

	return;
}

function diaspora_post_allow($importer,$contact) {
	if(($contact['blocked']) || ($contact['readonly']))
		return false;
	if($contact['rel'] == CONTACT_IS_SHARING || $contact['rel'] == CONTACT_IS_FRIEND)
		return true;
	if($contact['rel'] == CONTACT_IS_FOLLOWER)
		if($importer['page-flags'] == PAGE_COMMUNITY)
			return true;
	return false;
}


function diaspora_post($importer,$xml) {

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if(! diaspora_post_allow($importer,$contact)) {
		logger('diaspora_post: Ignoring this author.');
		return 202;
	}

	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_post: message exists: ' . $guid);
		return;
	}

	// allocate a guid on our system - we aren't fixing any collisions.
	// we're ignoring them

	$g = q("select * from guid where guid = '%s' limit 1",
		dbesc($guid)
	);
	if(! count($g)) {
		q("insert into guid ( guid ) values ( '%s' )",
			dbesc($guid)
		);
	}

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	$body = diaspora2bb($xml->raw_message);

	$datarray = array();

	$str_tags = '';

	$tags = get_tags($body);

	if(count($tags)) {
		foreach($tags as $tag) {
			if(strpos($tag,'#') === 0) {
				if(strpos($tag,'[url='))
					continue;

				// don't link tags that are already embedded in links

				if(preg_match('/\[(.*?)' . preg_quote($tag,'/') . '(.*?)\]/',$body))
					continue;
				if(preg_match('/\[(.*?)\]\((.*?)' . preg_quote($tag,'/') . '(.*?)\)/',$body))
					continue;

				$basetag = str_replace('_',' ',substr($tag,1));
				$body = str_replace($tag,'#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]',$body);
				if(strlen($str_tags))
					$str_tags .= ',';
				$str_tags .= '#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]';
				continue;
			}
		}
	}

	$cnt = preg_match_all('/@\[url=(.*?)\[\/url\]/ism',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			if(strlen($str_tags))
				$str_tags .= ',';
			$str_tags .= '@[url=' . $mtch[1] . '[/url]';	
		}
	}

	$datarray['uid'] = $importer['uid'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = 0;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $datarray['parent-uri'] = $message_id;
	$datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['private'] = $private;
	$datarray['parent'] = 0;
	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];
	$datarray['author-name'] = $contact['name'];
	$datarray['author-link'] = $contact['url'];
	$datarray['author-avatar'] = $contact['thumb'];
	$datarray['body'] = $body;
	$datarray['tag'] = $str_tags;
	$datarray['app']  = 'Diaspora';

	// if empty content it might be a photo that hasn't arrived yet. If a photo arrives, we'll make it visible.

	$datarray['visible'] = ((strlen($body)) ? 1 : 0);

	$message_id = item_store($datarray);

	if($message_id) {
		q("update item set plink = '%s' where id = %d limit 1",
			dbesc($a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id),
			intval($message_id)
		);
	}

	return;

}

function diaspora_reshare($importer,$xml) {

	logger('diaspora_reshare: init: ' . print_r($xml,true));

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));


	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if(! diaspora_post_allow($importer,$contact)) {
		logger('diaspora_reshare: Ignoring this author: ' . $diaspora_handle . ' ' . print_r($xml,true));
		return 202;
	}

	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_reshare: message exists: ' . $guid);
		return;
	}

	$orig_author = notags(unxmlify($xml->root_diaspora_id));
	$orig_guid = notags(unxmlify($xml->root_guid));

	$source_url = 'https://' . substr($orig_author,strpos($orig_author,'@')+1) . '/p/' . $orig_guid . '.xml';
	$x = fetch_url($source_url);
	if(! $x)
		$x = fetch_url(str_replace('https://','http://',$source_url));
	if(! $x) {
		logger('diaspora_reshare: unable to fetch source url ' . $source_url);
		return;
	}
	logger('diaspora_reshare: source: ' . $x);

	$x = str_replace(array('<activity_streams-photo>','</activity_streams-photo>'),array('<asphoto>','</asphoto>'),$x);
	$source_xml = parse_xml_string($x,false);

	if(strlen($source_xml->post->asphoto->objectId) && ($source_xml->post->asphoto->objectId != 0) && ($source_xml->post->asphoto->image_url)) {
		$body = '[url=' . notags(unxmlify($source_xml->post->asphoto->image_url)) . '][img]' . notags(unxmlify($source_xml->post->asphoto->objectId)) . '[/img][/url]' . "\n";
		$body = scale_external_images($body,false);
	}
	elseif($source_xml->post->asphoto->image_url) {
		$body = '[img]' . notags(unxmlify($source_xml->post->asphoto->image_url)) . '[/img]' . "\n";
		$body = scale_external_images($body);
	}
	elseif($source_xml->post->status_message) {
		$body = diaspora2bb($source_xml->post->status_message->raw_message);
		$body = scale_external_images($body);

	}
	else {
		logger('diaspora_reshare: no reshare content found: ' . print_r($source_xml,true));
		return;
	}
	if(! $body) {
		logger('diaspora_reshare: empty body: source= ' . $x);
		return;
	}

	$person = find_diaspora_person_by_handle($orig_author);

	if(is_array($person) && x($person,'name') && x($person,'url'))
		$details = '[url=' . $person['url'] . ']' . $person['name'] . '[/url]';
	else
		$details = $orig_author;
	
	$prefix = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8') . $details . "\n"; 


	// allocate a guid on our system - we aren't fixing any collisions.
	// we're ignoring them

	$g = q("select * from guid where guid = '%s' limit 1",
		dbesc($guid)
	);
	if(! count($g)) {
		q("insert into guid ( guid ) values ( '%s' )",
			dbesc($guid)
		);
	}

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	$datarray = array();

	$str_tags = '';

	$tags = get_tags($body);

	if(count($tags)) {
		foreach($tags as $tag) {
			if(strpos($tag,'#') === 0) {
				if(strpos($tag,'[url='))
					continue;

				// don't link tags that are already embedded in links

				if(preg_match('/\[(.*?)' . preg_quote($tag,'/') . '(.*?)\]/',$body))
					continue;
				if(preg_match('/\[(.*?)\]\((.*?)' . preg_quote($tag,'/') . '(.*?)\)/',$body))
					continue;


				$basetag = str_replace('_',' ',substr($tag,1));
				$body = str_replace($tag,'#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]',$body);
				if(strlen($str_tags))
					$str_tags .= ',';
				$str_tags .= '#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]';
				continue;
			}
		}
	}
	
	$datarray['uid'] = $importer['uid'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = 0;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $datarray['parent-uri'] = $message_id;
	$datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['private'] = $private;
	$datarray['parent'] = 0;
	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];
	$datarray['author-name'] = $contact['name'];
	$datarray['author-link'] = $contact['url'];
	$datarray['author-avatar'] = $contact['thumb'];
	$datarray['body'] = $prefix . $body;
	$datarray['tag'] = $str_tags;
	$datarray['app']  = 'Diaspora';

	$message_id = item_store($datarray);

	if($message_id) {
		q("update item set plink = '%s' where id = %d limit 1",
			dbesc($a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id),
			intval($message_id)
		);
	}

	return;

}


function diaspora_asphoto($importer,$xml) {
	logger('diaspora_asphoto called');

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if(! diaspora_post_allow($importer,$contact)) {
		logger('diaspora_asphoto: Ignoring this author.');
		return 202;
	}

	$message_id = $diaspora_handle . ':' . $guid;
	$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($message_id),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_asphoto: message exists: ' . $guid);
		return;
	}

	// allocate a guid on our system - we aren't fixing any collisions.
	// we're ignoring them

	$g = q("select * from guid where guid = '%s' limit 1",
		dbesc($guid)
	);
	if(! count($g)) {
		q("insert into guid ( guid ) values ( '%s' )",
			dbesc($guid)
		);
	}

	$created = unxmlify($xml->created_at);
	$private = ((unxmlify($xml->public) == 'false') ? 1 : 0);

	if(strlen($xml->objectId) && ($xml->objectId != 0) && ($xml->image_url)) {
		$body = '[url=' . notags(unxmlify($xml->image_url)) . '][img]' . notags(unxmlify($xml->objectId)) . '[/img][/url]' . "\n";
		$body = scale_external_images($body,false);
	}
	elseif($xml->image_url) {
		$body = '[img]' . notags(unxmlify($xml->image_url)) . '[/img]' . "\n";
		$body = scale_external_images($body);
	}
	else {
		logger('diaspora_asphoto: no photo url found.');
		return;
	}

	$datarray = array();

	
	$datarray['uid'] = $importer['uid'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = 0;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $datarray['parent-uri'] = $message_id;
	$datarray['created'] = $datarray['edited'] = datetime_convert('UTC','UTC',$created);
	$datarray['private'] = $private;
	$datarray['parent'] = 0;
	$datarray['owner-name'] = $contact['name'];
	$datarray['owner-link'] = $contact['url'];
	$datarray['owner-avatar'] = $contact['thumb'];
	$datarray['author-name'] = $contact['name'];
	$datarray['author-link'] = $contact['url'];
	$datarray['author-avatar'] = $contact['thumb'];
	$datarray['body'] = $body;
	
	$datarray['app']  = 'Diaspora/Cubbi.es';

	$message_id = item_store($datarray);

	if($message_id) {
		q("update item set plink = '%s' where id = %d limit 1",
			dbesc($a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id),
			intval($message_id)
		);
	}

	return;

}






function diaspora_comment($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$parent_guid = notags(unxmlify($xml->parent_guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$target_type = notags(unxmlify($xml->target_type));
	$text = unxmlify($xml->text);
	$author_signature = notags(unxmlify($xml->author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact) {
		logger('diaspora_comment: cannot find contact: ' . $msg['author']);
		return;
	}

	if(! diaspora_post_allow($importer,$contact)) {
		logger('diaspora_comment: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($guid)
	);
	if(count($r)) {
		logger('diaspora_comment: our comment just got relayed back to us (or there was a guid collision) : ' . $guid);
		return;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_comment: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
		return;
	}
	$parent_item = $r[0];


	/* How Diaspora performs comment signature checking:

	   - If an item has been sent by the comment author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner should check
	     the author_signature, then create a parent_author_signature before relaying the comment on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	$signed_data = $guid . ';' . $parent_guid . ';' . $text . ';' . $diaspora_handle;
	$key = $msg['key'];

	if($parent_author_signature) {
		// If a parent_author_signature exists, then we've received the comment
		// relayed from the top-level post owner. There's no need to check the
		// author_signature if the parent_author_signature is valid

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			logger('diaspora_comment: top-level owner verification failed.');
			return;
		}
	}
	else {
		// If there's no parent_author_signature, then we've received the comment
		// from the comment creator. In that case, the person is commenting on
		// our post, so he/she must be a contact of ours and his/her public key
		// should be in $msg['key']

		$author_signature = base64_decode($author_signature);

		if(! rsa_verify($signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_comment: comment author verification failed.');
			return;
		}
	}

	// Phew! Everything checks out. Now create an item.

	// Find the original comment author information.
	// We need this to make sure we display the comment author
	// information (name and avatar) correctly.
	if(strcasecmp($diaspora_handle,$msg['author']) == 0)
		$person = $contact;
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);	

		if(! is_array($person)) {
			logger('diaspora_comment: unable to find author details');
			return;
		}
	}

	$body = diaspora2bb($text);

	$message_id = $diaspora_handle . ':' . $guid;

	$datarray = array();

	$str_tags = '';

	$tags = get_tags($body);

	if(count($tags)) {
		foreach($tags as $tag) {
			if(strpos($tag,'#') === 0) {
				if(strpos($tag,'[url='))
					continue;

				// don't link tags that are already embedded in links

				if(preg_match('/\[(.*?)' . preg_quote($tag,'/') . '(.*?)\]/',$body))
					continue;
				if(preg_match('/\[(.*?)\]\((.*?)' . preg_quote($tag,'/') . '(.*?)\)/',$body))
					continue;


				$basetag = str_replace('_',' ',substr($tag,1));
				$body = str_replace($tag,'#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]',$body);
				if(strlen($str_tags))
					$str_tags .= ',';
				$str_tags .= '#[url=' . $a->get_baseurl() . '/search?tag=' . rawurlencode($basetag) . ']' . $basetag . '[/url]';
				continue;
			}
		}
	}

	$datarray['uid'] = $importer['uid'];
	$datarray['contact-id'] = $contact['id'];
	$datarray['wall'] = $parent_item['wall'];
	$datarray['gravity'] = GRAVITY_COMMENT;
	$datarray['guid'] = $guid;
	$datarray['uri'] = $message_id;
	$datarray['parent-uri'] = $parent_item['uri'];

	// No timestamps for comments? OK, we'll the use current time.
	$datarray['created'] = $datarray['edited'] = datetime_convert();
	$datarray['private'] = $parent_item['private'];

	$datarray['owner-name'] = $parent_item['owner-name'];
	$datarray['owner-link'] = $parent_item['owner-link'];
	$datarray['owner-avatar'] = $parent_item['owner-avatar'];

	$datarray['author-name'] = $person['name'];
	$datarray['author-link'] = $person['url'];
	$datarray['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
	$datarray['body'] = $body;
	$datarray['tag'] = $str_tags;

	// We can't be certain what the original app is if the message is relayed.
	if(($parent_item['origin']) && (! $parent_author_signature)) 
		$datarray['app']  = 'Diaspora';

	$message_id = item_store($datarray);

	if($message_id) {
		q("update item set plink = '%s' where id = %d limit 1",
			dbesc($a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id),
			intval($message_id)
		);
	}

	if(($parent_item['origin']) && (! $parent_author_signature)) {
		q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
			intval($message_id),
			dbesc($author_signed_data),
			dbesc(base64_encode($author_signature)),
			dbesc($diaspora_handle)
		);

		// if the message isn't already being relayed, notify others
		// the existence of parent_author_signature means the parent_author or owner
		// is already relaying.

		proc_run('php','include/notifier.php','comment',$message_id);
	}

	$myconv = q("SELECT `author-link`, `author-avatar`, `parent` FROM `item` WHERE `parent-uri` = '%s' AND `uid` = %d AND `parent` != 0 AND `deleted` = 0 ",
		dbesc($parent_item['uri']),
		intval($importer['uid'])
	);

	if(count($myconv)) {
		$importer_url = $a->get_baseurl() . '/profile/' . $importer['nickname'];

		foreach($myconv as $conv) {

			// now if we find a match, it means we're in this conversation
	
			if(! link_compare($conv['author-link'],$importer_url))
				continue;

			require_once('include/enotify.php');
								
			$conv_parent = $conv['parent'];

			notification(array(
				'type'         => NOTIFY_COMMENT,
				'notify_flags' => $importer['notify-flags'],
				'language'     => $importer['language'],
				'to_name'      => $importer['username'],
				'to_email'     => $importer['email'],
				'uid'          => $importer['uid'],
				'item'         => $datarray,
				'link'		   => $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id,
				'source_name'  => $datarray['author-name'],
				'source_link'  => $datarray['author-link'],
				'source_photo' => $datarray['author-avatar'],
				'verb'         => ACTIVITY_POST,
				'otype'        => 'item',
				'parent'       => $conv_parent,

			));

			// only send one notification
			break;
		}
	}
	return;
}




function diaspora_conversation($importer,$xml,$msg) {

	$a = get_app();

	$guid = notags(unxmlify($xml->guid));
	$subject = notags(unxmlify($xml->subject));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$participant_handles = notags(unxmlify($xml->participant_handles));
	$created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml->created_at)));

	$parent_uri = $diaspora_handle . ':' . $guid;
 
	$messages = $xml->message;

	if(! count($messages)) {
		logger('diaspora_conversation: empty conversation');
		return;
	}

	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact) {
		logger('diaspora_conversation: cannot find contact: ' . $msg['author']);
		return;
	}

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_conversation: Ignoring this author.');
		return 202;
	}

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer['uid']),
		dbesc($guid)
	);
	if(count($c))
		$conversation = $c[0];
	else {
		$r = q("insert into conv (uid,guid,creator,created,updated,subject,recips) values(%d, '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval($importer['uid']),
			dbesc($guid),
			dbesc($diaspora_handle),
			dbesc(datetime_convert('UTC','UTC',$created_at)),
			dbesc(datetime_convert()),
			dbesc($subject),
			dbesc($participant_handles)
		);
		if($r)
			$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
	        intval($importer['uid']),
    	    dbesc($guid)
    	);
	    if(count($c))
    	    $conversation = $c[0];
	}
	if(! $conversation) {
		logger('diaspora_conversation: unable to create conversation.');
		return;
	}

	foreach($messages as $mesg) {

		$reply = 0;

		$msg_guid = notags(unxmlify($mesg->guid));
		$msg_parent_guid = notags(unxmlify($mesg->parent_guid));
		$msg_parent_author_signature = notags(unxmlify($mesg->parent_author_signature));
		$msg_author_signature = notags(unxmlify($mesg->author_signature));
		$msg_text = unxmlify($mesg->text);
		$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($mesg->created_at)));
		$msg_diaspora_handle = notags(unxmlify($mesg->diaspora_handle));
		$msg_conversation_guid = notags(unxmlify($mesg->conversation_guid));
		if($msg_conversation_guid != $guid) {
			logger('diaspora_conversation: message conversation guid does not belong to the current conversation. ' . $xml);
			continue;
		}

		$body = diaspora2bb($msg_text);
		$message_id = $msg_diaspora_handle . ':' . $msg_guid;

		$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

		$author_signature = base64_decode($msg_author_signature);

		if(strcasecmp($msg_diaspora_handle,$msg['author']) == 0) {
			$person = $contact;
			$key = $msg['key'];
		}
		else {
			$person = find_diaspora_person_by_handle($msg_diaspora_handle);	

			if(is_array($person) && x($person,'pubkey'))
				$key = $person['pubkey'];
			else {
				logger('diaspora_conversation: unable to find author details');
				continue;
			}
		}

		if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_conversation: verification failed.');
			continue;
		}

		if($msg_parent_author_signature) {
			$owner_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($mesg->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;

			$parent_author_signature = base64_decode($msg_parent_author_signature);

			$key = $msg['key'];

			if(! rsa_verify($owner_signed_data,$parent_author_signature,$key,'sha256')) {
				logger('diaspora_conversation: owner verification failed.');
				continue;
			}
		}

		$r = q("select id from mail where `uri` = '%s' limit 1",
			dbesc($message_id)
		);
		if(count($r)) {
			logger('diaspora_conversation: duplicate message already delivered.', LOGGER_DEBUG);
			continue;
		}

		q("insert into mail ( `uid`, `guid`, `convid`, `from-name`,`from-photo`,`from-url`,`contact-id`,`title`,`body`,`seen`,`reply`,`uri`,`parent-uri`,`created`) values ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s','%s','%s')",
			intval($importer['uid']),
			dbesc($msg_guid),
			intval($conversation['id']),
			dbesc($person['name']),
			dbesc($person['photo']),
			dbesc($person['url']),
			intval($contact['id']),	 
			dbesc($subject),
			dbesc($body),
			0,
			0,
			dbesc($message_id),
			dbesc($parent_uri),
			dbesc($msg_created_at)
		);			

		q("update conv set updated = '%s' where id = %d limit 1",
			dbesc(datetime_convert()),
			intval($conversation['id'])
		);		

		require_once('include/enotify.php');
		notification(array(			
			'type' => NOTIFY_MAIL,
			'notify_flags' => $importer['notify-flags'],
			'language' => $importer['language'],
			'to_name' => $importer['username'],
			'to_email' => $importer['email'],
			'uid' =>$importer['importer_uid'],
			'item' => array('subject' => $subject, 'body' => $body),
			'source_name' => $person['name'],
			'source_link' => $person['url'],
			'source_photo' => $person['thumb'],
			'verb' => ACTIVITY_POST,
			'otype' => 'mail'
		));
	}	

	return;
}

function diaspora_message($importer,$xml,$msg) {

	$a = get_app();

	$msg_guid = notags(unxmlify($xml->guid));
	$msg_parent_guid = notags(unxmlify($xml->parent_guid));
	$msg_parent_author_signature = notags(unxmlify($xml->parent_author_signature));
	$msg_author_signature = notags(unxmlify($xml->author_signature));
	$msg_text = unxmlify($xml->text);
	$msg_created_at = datetime_convert('UTC','UTC',notags(unxmlify($xml->created_at)));
	$msg_diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$msg_conversation_guid = notags(unxmlify($xml->conversation_guid));

	$parent_uri = $diaspora_handle . ':' . $msg_parent_guid;
 
	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg_diaspora_handle);
	if(! $contact) {
		logger('diaspora_message: cannot find contact: ' . $msg_diaspora_handle);
		return;
	}

	if(($contact['rel'] == CONTACT_IS_FOLLOWER) || ($contact['blocked']) || ($contact['readonly'])) { 
		logger('diaspora_message: Ignoring this author.');
		return 202;
	}

	$conversation = null;

	$c = q("select * from conv where uid = %d and guid = '%s' limit 1",
		intval($importer['uid']),
		dbesc($msg_conversation_guid)
	);
	if(count($c))
		$conversation = $c[0];
	else {
		logger('diaspora_message: conversation not available.');
		return;
	}

	$reply = 0;
			
	$body = diaspora2bb($msg_text);
	$message_id = $msg_diaspora_handle . ':' . $msg_guid;

	$author_signed_data = $msg_guid . ';' . $msg_parent_guid . ';' . $msg_text . ';' . unxmlify($xml->created_at) . ';' . $msg_diaspora_handle . ';' . $msg_conversation_guid;


	$author_signature = base64_decode($msg_author_signature);

	$person = find_diaspora_person_by_handle($msg_diaspora_handle);	
	if(is_array($person) && x($person,'pubkey'))
		$key = $person['pubkey'];
	else {
		logger('diaspora_message: unable to find author details');
		return;
	}

	if(! rsa_verify($author_signed_data,$author_signature,$key,'sha256')) {
		logger('diaspora_message: verification failed.');
		return;
	}

	$r = q("select id from mail where `uri` = '%s' and uid = %d limit 1",
		dbesc($message_id),
		intval($importer['uid'])
	);
	if(count($r)) {
		logger('diaspora_message: duplicate message already delivered.', LOGGER_DEBUG);
		return;
	}

	q("insert into mail ( `uid`, `guid`, `convid`, `from-name`,`from-photo`,`from-url`,`contact-id`,`title`,`body`,`seen`,`reply`,`uri`,`parent-uri`,`created`) values ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s','%s','%s')",
		intval($importer['uid']),
		dbesc($msg_guid),
		intval($conversation['id']),
		dbesc($person['name']),
		dbesc($person['photo']),
		dbesc($person['url']),
		intval($contact['id']),	 
		dbesc($conversation['subject']),
		dbesc($body),
		0,
		1,
		dbesc($message_id),
		dbesc($parent_uri),
		dbesc($msg_created_at)
	);			

	q("update conv set updated = '%s' where id = %d limit 1",
		dbesc(datetime_convert()),
		intval($conversation['id'])
	);		
	
	return;
}


function diaspora_photo($importer,$xml,$msg) {

	$a = get_app();

	logger('diaspora_photo: init',LOGGER_DEBUG);

	$remote_photo_path = notags(unxmlify($xml->remote_photo_path));

	$remote_photo_name = notags(unxmlify($xml->remote_photo_name));

	$status_message_guid = notags(unxmlify($xml->status_message_guid));

	$guid = notags(unxmlify($xml->guid));

	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$public = notags(unxmlify($xml->public));

	$created_at = notags(unxmlify($xml_created_at));

	logger('diaspora_photo: status_message_guid: ' . $status_message_guid, LOGGER_DEBUG);

	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact) {
		logger('diaspora_photo: contact record not found: ' . $msg['author'] . ' handle: ' . $diaspora_handle);
		return;
	}

	if(! diaspora_post_allow($importer,$contact)) {
		logger('diaspora_photo: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($status_message_guid)
	);
	if(! count($r)) {
		logger('diaspora_photo: parent item not found: parent: ' . $parent_guid . ' item: ' . $guid);
		return;
	}

	$parent_item = $r[0];

	$link_text = '[img]' . $remote_photo_path . $remote_photo_name . '[/img]' . "\n";

	$link_text = scale_external_images($link_text, true,
	                                   array($remote_photo_name, 'scaled_full_' . $remote_photo_name));

	if(strpos($parent_item['body'],$link_text) === false) {
		$r = q("update item set `body` = '%s', `visible` = 1 where `id` = %d and `uid` = %d limit 1",
			dbesc($link_text . $parent_item['body']),
			intval($parent_item['id']),
			intval($parent_item['uid'])
		);
	}

	return;
}




function diaspora_like($importer,$xml,$msg) {

	$a = get_app();
	$guid = notags(unxmlify($xml->guid));
	$parent_guid = notags(unxmlify($xml->parent_guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$target_type = notags(unxmlify($xml->target_type));
	$positive = notags(unxmlify($xml->positive));
	$author_signature = notags(unxmlify($xml->author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	// likes on comments not supported here and likes on photos not supported by Diaspora

	if($target_type !== 'Post')
		return;

	$contact = diaspora_get_contact_by_handle($importer['uid'],$msg['author']);
	if(! $contact) {
		logger('diaspora_like: cannot find contact: ' . $msg['author']);
		return;
	}

	if(! diaspora_post_allow($importer,$contact)) {
		logger('diaspora_like: Ignoring this author.');
		return 202;
	}

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($parent_guid)
	);
	if(! count($r)) {
		logger('diaspora_like: parent item not found: ' . $guid);
		return;
	}

	$parent_item = $r[0];

	$r = q("SELECT * FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
		intval($importer['uid']),
		dbesc($guid)
	);
	if(count($r)) {
		if($positive === 'true') {
			logger('diaspora_like: duplicate like: ' . $guid);
			return;
		} 
		// Note: I don't think "Like" objects with positive = "false" are ever actually used
		// It looks like "RelayableRetractions" are used for "unlike" instead
		if($positive === 'false') {
			logger('diaspora_like: received a like with positive set to "false"...ignoring');
/*			q("UPDATE `item` SET `deleted` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($r[0]['id']),
				intval($importer['uid'])
			);*/
			// FIXME--actually don't unless it turns out that Diaspora does indeed send out "false" likes
			//  send notification via proc_run()
			return;
		}
	}
	// Note: I don't think "Like" objects with positive = "false" are ever actually used
	// It looks like "RelayableRetractions" are used for "unlike" instead
	if($positive === 'false') {
		logger('diaspora_like: received a like with positive set to "false"');
		logger('diaspora_like: unlike received with no corresponding like...ignoring');
		return;	
	}


	/* How Diaspora performs "like" signature checking:

	   - If an item has been sent by the like author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner should check
	     the author_signature, then create a parent_author_signature before relaying the like on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	$signed_data = $guid . ';' . $target_type . ';' . $parent_guid . ';' . $positive . ';' . $diaspora_handle;
	$key = $msg['key'];

	if($parent_author_signature) {
		// If a parent_author_signature exists, then we've received the like
		// relayed from the top-level post owner. There's no need to check the
		// author_signature if the parent_author_signature is valid

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			logger('diaspora_like: top-level owner verification failed.');
			return;
		}
	}
	else {
		// If there's no parent_author_signature, then we've received the like
		// from the like creator. In that case, the person is "like"ing
		// our post, so he/she must be a contact of ours and his/her public key
		// should be in $msg['key']

		$author_signature = base64_decode($author_signature);

		if(! rsa_verify($signed_data,$author_signature,$key,'sha256')) {
			logger('diaspora_like: like creator verification failed.');
			return;
		}
	}

	// Phew! Everything checks out. Now create an item.

	// Find the original comment author information.
	// We need this to make sure we display the comment author
	// information (name and avatar) correctly.
	if(strcasecmp($diaspora_handle,$msg['author']) == 0)
		$person = $contact;
	else {
		$person = find_diaspora_person_by_handle($diaspora_handle);

		if(! is_array($person)) {
			logger('diaspora_like: unable to find author details');
			return;
		}
	}

	$uri = $diaspora_handle . ':' . $guid;

	$activity = ACTIVITY_LIKE;
	$post_type = (($parent_item['resource-id']) ? t('photo') : t('status'));
	$objtype = (($parent_item['resource-id']) ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 
	$link = xmlify('<link rel="alternate" type="text/html" href="' . $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $parent_item['id'] . '" />' . "\n") ;
	$body = $parent_item['body'];

	$obj = <<< EOT

	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>{$parent_item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</object>
EOT;
	$bodyverb = t('%1$s likes %2$s\'s %3$s');

	$arr = array();

	$arr['uri'] = $uri;
	$arr['uid'] = $importer['uid'];
	$arr['guid'] = $guid;
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = $parent_item['wall'];
	$arr['gravity'] = GRAVITY_LIKE;
	$arr['parent'] = $parent_item['id'];
	$arr['parent-uri'] = $parent_item['uri'];

	$arr['owner-name'] = $parent_item['name'];
	$arr['owner-link'] = $parent_item['url'];
	$arr['owner-avatar'] = $parent_item['thumb'];

	$arr['author-name'] = $person['name'];
	$arr['author-link'] = $person['url'];
	$arr['author-avatar'] = ((x($person,'thumb')) ? $person['thumb'] : $person['photo']);
	
	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $parent_item['author-link'] . ']' . $parent_item['author-name'] . '[/url]';
	$plink = '[url=' . $a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $parent_item['id'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['app']  = 'Diaspora';

	$arr['private'] = $parent_item['private'];
	$arr['verb'] = $activity;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 0;

	$message_id = item_store($arr);


	if($message_id) {
		q("update item set plink = '%s' where id = %d limit 1",
			dbesc($a->get_baseurl() . '/display/' . $importer['nickname'] . '/' . $message_id),
			intval($message_id)
		);
	}

	if(! $parent_author_signature) {
		q("insert into sign (`iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
			intval($message_id),
			dbesc($author_signed_data),
			dbesc(base64_encode($author_signature)),
			dbesc($diaspora_handle)
		);
	}

	// if the message isn't already being relayed, notify others
	// the existence of parent_author_signature means the parent_author or owner
	// is already relaying. The parent_item['origin'] indicates the message was created on our system

	if(($parent_item['origin']) && (! $parent_author_signature))
		proc_run('php','include/notifier.php','comment',$message_id);

	return;
}

function diaspora_retraction($importer,$xml) {


	$guid = notags(unxmlify($xml->guid));
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));
	$type = notags(unxmlify($xml->type));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if($type === 'Person') {
		require_once('include/Contact.php');
		contact_remove($contact['id']);
	}
	elseif($type === 'Post') {
		$r = q("select * from item where guid = '%s' and uid = %d and not file like '%%[%%' limit 1",
			dbesc('guid'),
			intval($importer['uid'])
		);
		if(count($r)) {
			if(link_compare($r[0]['author-link'],$contact['url'])) {
				q("update item set `deleted` = 1, `changed` = '%s' where `id` = %d limit 1",
					dbesc(datetime_convert()),			
					intval($r[0]['id'])
				);
			}
		}
	}

	return 202;
	// NOTREACHED
}

function diaspora_signed_retraction($importer,$xml,$msg) {


	$guid = notags(unxmlify($xml->target_guid));
	$diaspora_handle = notags(unxmlify($xml->sender_handle));
	$type = notags(unxmlify($xml->target_type));
	$sig = notags(unxmlify($xml->target_author_signature));

	$parent_author_signature = (($xml->parent_author_signature) ? notags(unxmlify($xml->parent_author_signature)) : '');

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact) {
		logger('diaspora_signed_retraction: no contact');
		return;
	}


	$signed_data = $guid . ';' . $type ;
	$key = $msg['key'];

	/* How Diaspora performs relayable_retraction signature checking:

	   - If an item has been sent by the item author to the top-level post owner to relay on
	     to the rest of the contacts on the top-level post, the top-level post owner checks
	     the author_signature, then creates a parent_author_signature before relaying the item on
	   - If an item has been relayed on by the top-level post owner, the contacts who receive it
	     check only the parent_author_signature. Basically, they trust that the top-level post
	     owner has already verified the authenticity of anything he/she sends out
	   - In either case, the signature that get checked is the signature created by the person
	     who sent the salmon
	*/

	if($parent_author_signature) {

		$parent_author_signature = base64_decode($parent_author_signature);

		if(! rsa_verify($signed_data,$parent_author_signature,$key,'sha256')) {
			logger('diaspora_signed_retraction: top-level post owner verification failed');
			return;
		}

	}
	else {

		$sig_decode = base64_decode($sig);

		if(! rsa_verify($signed_data,$sig_decode,$key,'sha256')) {
			logger('diaspora_signed_retraction: retraction owner verification failed.' . print_r($msg,true));
			return;
		}
	}

	if($type === 'StatusMessage' || $type === 'Comment' || $type === 'Like') {
		$r = q("select * from item where guid = '%s' and uid = %d and not file like '%%[%%' limit 1",
			dbesc($guid),
			intval($importer['uid'])
		);
		if(count($r)) {
			if(link_compare($r[0]['author-link'],$contact['url'])) {
				q("update item set `deleted` = 1, `edited` = '%s', `changed` = '%s', `body` = '' , `title` = '' where `id` = %d limit 1",
					dbesc(datetime_convert()),			
					dbesc(datetime_convert()),			
					intval($r[0]['id'])
				);
	
				// Now check if the retraction needs to be relayed by us
				//
				// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
				// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
				// The only item with `parent` and `id` as the parent id is the parent item.
				$p = q("select origin from item where parent = %d and id = %d limit 1",
					$r[0]['parent'],
					$r[0]['parent']
				);
				if(count($p)) {
					if(($p[0]['origin']) && (! $parent_author_signature)) {
						q("insert into sign (`retract_iid`,`signed_text`,`signature`,`signer`) values (%d,'%s','%s','%s') ",
							$r[0]['id'],
							dbesc($signed_data),
							dbesc($sig),
							dbesc($diaspora_handle)
						);

						// the existence of parent_author_signature would have meant the parent_author or owner
						// is already relaying.
						logger('diaspora_signed_retraction: relaying relayable_retraction');

						proc_run('php','include/notifier.php','relayable_retraction',$r[0]['id']);
					}
				}
			}
		}
	}
	else
		logger('diaspora_signed_retraction: unknown type: ' . $type);

	return 202;
	// NOTREACHED
}

function diaspora_profile($importer,$xml) {

	$a = get_app();
	$diaspora_handle = notags(unxmlify($xml->diaspora_handle));

	$contact = diaspora_get_contact_by_handle($importer['uid'],$diaspora_handle);
	if(! $contact)
		return;

	if($contact['blocked']) {
		logger('diaspora_post: Ignoring this author.');
		return 202;
	}

	$name = unxmlify($xml->first_name) . ((strlen($xml->last_name)) ? ' ' . unxmlify($xml->last_name) : '');
	$image_url = unxmlify($xml->image_url);
	$birthday = unxmlify($xml->birthday);

	$r = q("SELECT DISTINCT ( `resource-id` ) FROM `photo` WHERE  `uid` = %d AND `contact-id` = %d AND `album` = 'Contact Photos' ",
		intval($importer['uid']),
		intval($contact['id'])
	);
	$oldphotos = ((count($r)) ? $r : null);

	require_once('include/Photo.php');

	$images = import_profile_photo($image_url,$importer['uid'],$contact['id']);
	
	// Generic birthday. We don't know the timezone. The year is irrelevant. 

	$birthday = str_replace('1000','1901',$birthday);

	$birthday = datetime_convert('UTC','UTC',$birthday,'Y-m-d');

	// this is to prevent multiple birthday notifications in a single year
	// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

	if(substr($birthday,5) === substr($contact['bd'],5))
		$birthday = $contact['bd'];

	// TODO: update name on item['author-name'] if the name changed. See consume_feed()
	// Not doing this currently because D* protocol is scheduled for revision soon. 

	$r = q("UPDATE `contact` SET `name` = '%s', `name-date` = '%s', `photo` = '%s', `thumb` = '%s', `micro` = '%s', `avatar-date` = '%s' , `bd` = '%s' WHERE `id` = %d AND `uid` = %d LIMIT 1",
		dbesc($name),
		dbesc(datetime_convert()),
		dbesc($images[0]),
		dbesc($images[1]),
		dbesc($images[2]),
		dbesc(datetime_convert()),
		dbesc($birthday),
		intval($contact['id']),
		intval($importer['uid'])
	); 

	if($r) {
		if($oldphotos) {
			foreach($oldphotos as $ph) {
				q("DELETE FROM `photo` WHERE `uid` = %d AND `contact-id` = %d AND `album` = 'Contact Photos' AND `resource-id` = '%s' ",
					intval($importer['uid']),
					intval($contact['id']),
					dbesc($ph['resource-id'])
				);
			}
		}
	}	

	return;

}

function diaspora_share($me,$contact) {
	$a = get_app();
	$myaddr = $me['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];

	$tpl = get_markup_template('diaspora_share.tpl');
	$msg = replace_macros($tpl, array(
		'$sender' => $myaddr,
		'$recipient' => $theiraddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey'])));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey']));

	return(diaspora_transmit($owner,$contact,$slap, false));
}

function diaspora_unshare($me,$contact) {

	$a = get_app();
	$myaddr = $me['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$tpl = get_markup_template('diaspora_retract.tpl');
	$msg = replace_macros($tpl, array(
		'$guid'   => $me['guid'],
		'$type'   => 'Person',
		'$handle' => $myaddr
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey'])));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$me,$contact,$me['prvkey'],$contact['pubkey']));

	return(diaspora_transmit($owner,$contact,$slap, false));

}



function diaspora_send_status($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
	$theiraddr = $contact['addr'];

	$images = array();

	$title = $item['title'];
	$body = $item['body'];

/*
	// We're trying to match Diaspora's split message/photo protocol but
	// all the photos are displayed on D* as links and not img's - even
	// though we're sending pretty much precisely what they send us when
	// doing the same operation.  
	// Commented out for now, we'll use bb2diaspora to convert photos to markdown
	// which seems to get through intact.

	$cnt = preg_match_all('|\[img\](.*?)\[\/img\]|',$body,$matches,PREG_SET_ORDER);
	if($cnt) {
		foreach($matches as $mtch) {
			$detail = array();
			$detail['str'] = $mtch[0];
			$detail['path'] = dirname($mtch[1]) . '/';
			$detail['file'] = basename($mtch[1]);
			$detail['guid'] = $item['guid'];
			$detail['handle'] = $myaddr;
			$images[] = $detail;
			$body = str_replace($detail['str'],$mtch[1],$body);
		}
	}
*/
	// Removal of tags
	$body = preg_replace('/#\[url\=(\w+.*?)\](\w+.*?)\[\/url\]/i', '#$2', $body);

	//if(strlen($title))
	//	$body = "[b]".html_entity_decode($title)."[/b]\n\n".$body;

	// convert to markdown
	$body = xmlify(html_entity_decode(bb2diaspora($body)));
	//$body = bb2diaspora($body);

	// Adding the title
	if(strlen($title))
		$body = "## ".html_entity_decode($title)."\n\n".$body;

	if($item['attach']) {
		$cnt = preg_match_all('/href=\"(.*?)\"(.*?)title=\"(.*?)\"/ism',$item['attach'],$matches,PREG_SET_ORDER);
		if(cnt) {
			$body .= "\n" . t('Attachments:') . "\n";
			foreach($matches as $mtch) {
				$body .= '[' . $mtch[3] . '](' . $mtch[1] . ')' . "\n";
			}
		}
	}	


	$public = (($item['private']) ? 'false' : 'true');

	require_once('include/datetime.php');
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');

	$tpl = get_markup_template('diaspora_post.tpl');
	$msg = replace_macros($tpl, array(
		'$body' => $body,
		'$guid' => $item['guid'],
		'$handle' => xmlify($myaddr),
		'$public' => $public,
		'$created' => $created
	));

	logger('diaspora_send_status: ' . $owner['username'] . ' -> ' . $contact['name'] . ' base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch)));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch));

	$return_code = diaspora_transmit($owner,$contact,$slap,$public_batch);

	if(count($images)) {
		diaspora_send_images($item,$owner,$contact,$images,$public_batch);
	}

	return $return_code;
}


function diaspora_send_images($item,$owner,$contact,$images,$public_batch = false) {
	$a = get_app();
	if(! count($images))
		return;
	$mysite = substr($a->get_baseurl(),strpos($a->get_baseurl(),'://') + 3) . '/photo';

	$tpl = get_markup_template('diaspora_photo.tpl');
	foreach($images as $image) {
		if(! stristr($image['path'],$mysite))
			continue;
		$resource = str_replace('.jpg','',$image['file']);
		$resource = substr($resource,0,strpos($resource,'-'));

		$r = q("select * from photo where `resource-id` = '%s' and `uid` = %d limit 1",
			dbesc($resource),
			intval($owner['uid'])
		);
		if(! count($r))
			continue;
		$public = (($r[0]['allow_cid'] || $r[0]['allow_gid'] || $r[0]['deny_cid'] || $r[0]['deny_gid']) ? 'false' : 'true' );
		$msg = replace_macros($tpl,array(		
			'$path' => xmlify($image['path']),
			'$filename' => xmlify($image['file']),
			'$msg_guid' => xmlify($image['guid']),
			'$guid' => xmlify($r[0]['guid']),
			'$handle' => xmlify($image['handle']),
			'$public' => xmlify($public),
			'$created_at' => xmlify(datetime_convert('UTC','UTC',$r[0]['created'],'Y-m-d H:i:s \U\T\C'))
		));


		logger('diaspora_send_photo: base message: ' . $msg, LOGGER_DATA);
		$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch)));
		//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch));

		diaspora_transmit($owner,$contact,$slap,$public_batch);
	}

}

function diaspora_send_followup($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
//	$theiraddr = $contact['addr'];

	// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
	// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
	// The only item with `parent` and `id` as the parent id is the parent item.
	$p = q("select guid from item where parent = %d and id = %d limit 1",
		intval($item['parent']),
		intval($item['parent'])
	);
	if(count($p))
		$parent_guid = $p[0]['guid'];
	else
		return;

	if($item['verb'] === ACTIVITY_LIKE) {
		$tpl = get_markup_template('diaspora_like.tpl');
		$like = true;
		$target_type = 'Post';
//		$positive = (($item['deleted']) ? 'false' : 'true');
		$positive = 'true';

		if(($item['deleted']))
			logger('diaspora_send_followup: received deleted "like". Those should go to diaspora_send_retraction');
	}
	else {
		$tpl = get_markup_template('diaspora_comment.tpl');
		$like = false;
	}

	$text = html_entity_decode(bb2diaspora($item['body']));

	// sign it

	if($like)
		$signed_text = $item['guid'] . ';' . $target_type . ';' . $parent_guid . ';' . $positive . ';' . $myaddr;
	else
		$signed_text = $item['guid'] . ';' . $parent_guid . ';' . $text . ';' . $myaddr;

	$authorsig = base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha256'));

	$msg = replace_macros($tpl,array(
		'$guid' => xmlify($item['guid']),
		'$parent_guid' => xmlify($parent_guid),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($myaddr)
	));

	logger('diaspora_followup: base message: ' . $msg, LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch)));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch));

	return(diaspora_transmit($owner,$contact,$slap,$public_batch));
}


function diaspora_send_relay($item,$owner,$contact,$public_batch = false) {


	$a = get_app();
	$myaddr = $owner['nickname'] . '@' . substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);
//	$theiraddr = $contact['addr'];

	$body = $item['body'];
	$text = html_entity_decode(bb2diaspora($body));


	// The first item in the `item` table with the parent id is the parent. However, MySQL doesn't always
	// return the items ordered by `item`.`id`, in which case the wrong item is chosen as the parent.
	// The only item with `parent` and `id` as the parent id is the parent item.
	$p = q("select guid from item where parent = %d and id = %d limit 1",
		intval($item['parent']),
		intval($item['parent'])
	);
	if(count($p))
		$parent_guid = $p[0]['guid'];
	else
		return;

	$like = false;
	$relay_retract = false;
	$sql_sign_id = 'iid';
	if( $item['deleted']) {
		$relay_retract = true;

		$target_type = ( ($item['verb'] === ACTIVITY_LIKE) ? 'Like' : 'Comment');

		$sql_sign_id = 'retract_iid';
		$tpl = get_markup_template('diaspora_relayable_retraction.tpl');
	}
	elseif($item['verb'] === ACTIVITY_LIKE) {
		$like = true;

		$target_type = 'Post';
//		$positive = (($item['deleted']) ? 'false' : 'true');
		$positive = 'true';

		$tpl = get_markup_template('diaspora_like_relay.tpl');
	}
	else { // item is a comment
		$tpl = get_markup_template('diaspora_comment_relay.tpl');
	}


	// fetch the original signature	if the relayable was created by a Diaspora
	// or DFRN user. Relayables for other networks are not supported.

	$r = q("select * from sign where " . $sql_sign_id . " = %d limit 1",
		intval($item['id'])
	);
	if(count($r)) { 
		$orig_sign = $r[0];
		$signed_text = $orig_sign['signed_text'];
		$authorsig = $orig_sign['signature'];
		$handle = $orig_sign['signer'];
	}
	else {

		// Author signature information (for likes, comments, and retractions of likes or comments,
		// whether from Diaspora or Friendica) must be placed in the `sign` table before this 
		// function is called
		logger('diaspora_send_relay: original author signature not found, cannot send relayable');
		return;
	}

	if($relay_retract)
		$sender_signed_text = $item['guid'] . ';' . $target_type;
	elseif($like)
		$sender_signed_text = $item['guid'] . ';' . $target_type . ';' . $parent_guid . ';' . $positive . ';' . $handle;
	else
		$sender_signed_text = $item['guid'] . ';' . $parent_guid . ';' . $text . ';' . $handle;

	// Sign the relayable with the top-level owner's signature
	//
	// We'll use the $sender_signed_text that we just created, instead of the $signed_text
	// stored in the database, because that provides the best chance that Diaspora will
	// be able to reconstruct the signed text the same way we did. This is particularly a
	// concern for the comment, whose signed text includes the text of the comment. The
	// smallest change in the text of the comment, including removing whitespace, will
	// make the signature verification fail. Since we translate from BB code to Diaspora's
	// markup at the top of this function, which is AFTER we placed the original $signed_text
	// in the database, it's hazardous to trust the original $signed_text.

	$parentauthorsig = base64_encode(rsa_sign($sender_signed_text,$owner['uprvkey'],'sha256'));

	$msg = replace_macros($tpl,array(
		'$guid' => xmlify($item['guid']),
		'$parent_guid' => xmlify($parent_guid),
		'$target_type' =>xmlify($target_type),
		'$authorsig' => xmlify($authorsig),
		'$parentsig' => xmlify($parentauthorsig),
		'$body' => xmlify($text),
		'$positive' => xmlify($positive),
		'$handle' => xmlify($handle)
	));

	logger('diaspora_send_relay: base message: ' . $msg, LOGGER_DATA);


	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch)));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch));

	return(diaspora_transmit($owner,$contact,$slap,$public_batch));

}



function diaspora_send_retraction($item,$owner,$contact,$public_batch = false) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	// Check whether the retraction is for a top-level post or whether it's a relayable
	if( $item['uri'] !== $item['parent-uri'] ) {

		$tpl = get_markup_template('diaspora_relay_retraction.tpl');
		$target_type = (($item['verb'] === ACTIVITY_LIKE) ? 'Like' : 'Comment');
	}
	else {
		
		$tpl = get_markup_template('diaspora_signed_retract.tpl');
		$target_type = 'StatusMessage';
	}

	$signed_text = $item['guid'] . ';' . $target_type;

	$msg = replace_macros($tpl, array(
		'$guid'   => xmlify($item['guid']),
		'$type'   => xmlify($target_type),
		'$handle' => xmlify($myaddr),
		'$signature' => xmlify(base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha256')))
	));

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch)));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($msg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],$public_batch));

	return(diaspora_transmit($owner,$contact,$slap,$public_batch));
}

function diaspora_send_mail($item,$owner,$contact) {

	$a = get_app();
	$myaddr = $owner['nickname'] . '@' .  substr($a->get_baseurl(), strpos($a->get_baseurl(),'://') + 3);

	$r = q("select * from conv where id = %d and uid = %d limit 1",
		intval($item['convid']),
		intval($item['uid'])
	);

	if(! count($r)) {
		logger('diaspora_send_mail: conversation not found.');
		return;
	}
	$cnv = $r[0];

	$conv = array(
		'guid' => xmlify($cnv['guid']),
		'subject' => xmlify($cnv['subject']),
		'created_at' => xmlify(datetime_convert('UTC','UTC',$cnv['created'],'Y-m-d H:i:s \U\T\C')),
		'diaspora_handle' => xmlify($cnv['creator']),
		'participant_handles' => xmlify($cnv['recips'])
	);

	$body = bb2diaspora($item['body']);
	$created = datetime_convert('UTC','UTC',$item['created'],'Y-m-d H:i:s \U\T\C');
 
	$signed_text =  $item['guid'] . ';' . $cnv['guid'] . ';' . $body .  ';' 
		. $created . ';' . $myaddr . ';' . $cnv['guid'];

	$sig = base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha256'));

	$msg = array(
		'guid' => xmlify($item['guid']),
		'parent_guid' => xmlify($cnv['guid']),
		'parent_author_signature' => (($item['reply']) ? null : xmlify($sig)),
		'author_signature' => xmlify($sig),
		'text' => xmlify($body),
		'created_at' => xmlify($created),
		'diaspora_handle' => xmlify($myaddr),
		'conversation_guid' => xmlify($cnv['guid'])
	);

	if($item['reply']) {
		$tpl = get_markup_template('diaspora_message.tpl');
		$xmsg = replace_macros($tpl, array('$msg' => $msg));
	}
	else {
		$conv['messages'] = array($msg);
		$tpl = get_markup_template('diaspora_conversation.tpl');
		$xmsg = replace_macros($tpl, array('$conv' => $conv));
	}

	logger('diaspora_conversation: ' . print_r($xmsg,true), LOGGER_DATA);

	$slap = 'xml=' . urlencode(urlencode(diaspora_msg_build($xmsg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],false)));
	//$slap = 'xml=' . urlencode(diaspora_msg_build($xmsg,$owner,$contact,$owner['uprvkey'],$contact['pubkey'],false));

	return(diaspora_transmit($owner,$contact,$slap,false));


}

function diaspora_transmit($owner,$contact,$slap,$public_batch) {

	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		return 200;
	}

	$a = get_app();
	$logid = random_string(4);
	$dest_url = (($public_batch) ? $contact['batch'] : $contact['notify']);
	if(! $dest_url) {
		logger('diaspora_transmit: no url for contact: ' . $contact['id'] . ' batch mode =' . $public_batch);
		return 0;
	} 

	logger('diaspora_transmit: ' . $logid . ' ' . $dest_url);

	if(was_recently_delayed($contact['id'])) {
		$return_code = 0;
	}
	else {
		if(! intval(get_config('system','diaspora_test'))) {
			post_url($dest_url . '/', $slap);
			$return_code = $a->get_curl_code();
		}
		else {
			logger('diaspora_transmit: test_mode');
			return 200;
		}
	}
	
	logger('diaspora_transmit: ' . $logid . ' returns: ' . $return_code);

	if((! $return_code) || (($return_code == 503) && (stristr($a->get_curl_headers(),'retry-after')))) {
		logger('diaspora_transmit: queue message');

		$r = q("SELECT id from queue where cid = %d and network = '%s' and content = '%s' and batch = %d limit 1",
			intval($contact['id']),
			dbesc(NETWORK_DIASPORA),
			dbesc($slap),
			intval($public_batch)
		);
		if(count($r)) {
			logger('diaspora_transmit: add_to_queue ignored - identical item already in queue');
		}
		else {
			// queue message for redelivery
			add_to_queue($contact['id'],NETWORK_DIASPORA,$slap,$public_batch);
		}
	}


	return(($return_code) ? $return_code : (-1));
}


