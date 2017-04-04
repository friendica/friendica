<?php

	// send a private message




function send_message($recipient=0, $body='', $subject='', $replyto=''){

	$a = get_app();

	if (! $recipient) return -1;

	if (! strlen($subject))
		$subject = t('[no subject]');

	$me = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
		intval(local_user())
	);
	$contact = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($recipient),
			intval(local_user())
	);

	if (! (count($me) && (count($contact)))) {
		return -2;
	}

	$guid = get_guid(32);
 	$uri = 'urn:X-dfrn:' . App::get_baseurl() . ':' . local_user() . ':' . $guid;

	$convid = 0;
	$reply = false;

	// look for any existing conversation structure

	if (strlen($replyto)) {
		$reply = true;
		$r = q("select convid from mail where uid = %d and ( uri = '%s' or `parent-uri` = '%s' ) limit 1",
			intval(local_user()),
			dbesc($replyto),
			dbesc($replyto)
		);
		if (dbm::is_result($r))
			$convid = $r[0]['convid'];
	}

	if (! $convid) {

		// create a new conversation

		$recip_host = substr($contact[0]['url'],strpos($contact[0]['url'],'://')+3);
		$recip_host = substr($recip_host,0,strpos($recip_host,'/'));

		$recip_handle = (($contact[0]['addr']) ? $contact[0]['addr'] : $contact[0]['nick'] . '@' . $recip_host);
		$sender_handle = $a->user['nickname'] . '@' . substr(App::get_baseurl(), strpos(App::get_baseurl(),'://') + 3);

		$conv_guid = get_guid(32);
		$convuri = $recip_handle.':'.$conv_guid;

		$handles = $recip_handle . ';' . $sender_handle;

		$r = q("insert into conv (uid,guid,creator,created,updated,subject,recips) values(%d, '%s', '%s', '%s', '%s', '%s', '%s') ",
			intval(local_user()),
			dbesc($conv_guid),
			dbesc($sender_handle),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			dbesc($subject),
			dbesc($handles)
		);

		$r = q("select * from conv where guid = '%s' and uid = %d limit 1",
			dbesc($conv_guid),
			intval(local_user())
		);
		if (dbm::is_result($r))
			$convid = $r[0]['id'];
	}

	if (! $convid) {
		logger('send message: conversation not found.');
		return -4;
	}

	if (! strlen($replyto)) {
		$replyto = $convuri;
	}


	$r = q("INSERT INTO `mail` ( `uid`, `guid`, `convid`, `from-name`, `from-photo`, `from-url`,
		`contact-id`, `title`, `body`, `seen`, `reply`, `replied`, `uri`, `parent-uri`, `created`)
		VALUES ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, %d, '%s', '%s', '%s' )",
		intval(local_user()),
		dbesc($guid),
		intval($convid),
		dbesc($me[0]['name']),
		dbesc($me[0]['thumb']),
		dbesc($me[0]['url']),
		intval($recipient),
		dbesc($subject),
		dbesc($body),
		1,
		intval($reply),
		0,
		dbesc($uri),
		dbesc($replyto),
		datetime_convert()
	);


	$r = q("SELECT * FROM `mail` WHERE `uri` = '%s' and `uid` = %d LIMIT 1",
		dbesc($uri),
		intval(local_user())
	);
	if (dbm::is_result($r))
		$post_id = $r[0]['id'];

	/**
	 *
	 * When a photo was uploaded into the message using the (profile wall) ajax
	 * uploader, The permissions are initially set to disallow anybody but the
	 * owner from seeing it. This is because the permissions may not yet have been
	 * set for the post. If it's private, the photo permissions should be set
	 * appropriately. But we didn't know the final permissions on the post until
	 * now. So now we'll look for links of uploaded messages that are in the
	 * post and set them to the same permissions as the post itself.
	 *
	 */

	$match = null;

	if (preg_match_all("/\[img\](.*?)\[\/img\]/",$body,$match)) {
		$images = $match[1];
		if (count($images)) {
			foreach ($images as $image) {
				if (! stristr($image,App::get_baseurl() . '/photo/')) {
					continue;
				}
				$image_uri = substr($image,strrpos($image,'/') + 1);
				$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				$r = q("UPDATE `photo` SET `allow_cid` = '%s'
					WHERE `resource-id` = '%s' AND `album` = '%s' AND `uid` = %d ",
					dbesc('<' . $recipient . '>'),
					dbesc($image_uri),
					dbesc( t('Wall Photos')),
					intval(local_user())
				);
			}
		}
	}

	if ($post_id) {
		proc_run(PRIORITY_HIGH, "include/notifier.php", "mail", $post_id);
		return intval($post_id);
	} else {
		return -3;
	}

}

function send_wallmessage($recipient='', $body='', $subject='', $replyto=''){

	if (! $recipient) {
		return -1;
	}

	if (! strlen($subject)) {
		$subject = t('[no subject]');
	}

	$guid = get_guid(32);
 	$uri = 'urn:X-dfrn:' . App::get_baseurl() . ':' . local_user() . ':' . $guid;

	$convid = 0;
	$reply = false;

	require_once('include/Scrape.php');

	$me = probe_url($replyto);

	if (! $me['name']) {
		return -2;
	}

	$conv_guid = get_guid(32);

	$recip_handle = $recipient['nickname'] . '@' . substr(App::get_baseurl(), strpos(App::get_baseurl(),'://') + 3);

	$sender_nick = basename($replyto);
	$sender_host = substr($replyto,strpos($replyto,'://')+3);
	$sender_host = substr($sender_host,0,strpos($sender_host,'/'));
	$sender_handle = $sender_nick . '@' . $sender_host;

	$handles = $recip_handle . ';' . $sender_handle;

	$r = q("INSERT INTO `conv` (`uid`,`guid`,`creator`,`created`,`updated`,`subject`,`recips`) values(%d, '%s', '%s', '%s', '%s', '%s', '%s') ",
		intval($recipient['uid']),
		dbesc($conv_guid),
		dbesc($sender_handle),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc($subject),
		dbesc($handles)
	);

	$r = q("SELECT * FROM `conv` WHERE `guid` = '%s' AND `uid` = %d LIMIT 1",
		dbesc($conv_guid),
		intval($recipient['uid'])
	);


	if (! dbm::is_result($r)) {
		logger('send message: conversation not found.');
		return -4;
	}

	$convid = $r[0]['id'];

	$r = q("INSERT INTO `mail` ( `uid`, `guid`, `convid`, `from-name`, `from-photo`, `from-url`,
		`contact-id`, `title`, `body`, `seen`, `reply`, `replied`, `uri`, `parent-uri`, `created`, `unknown`)
		VALUES ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, %d, '%s', '%s', '%s', %d )",
		intval($recipient['uid']),
		dbesc($guid),
		intval($convid),
		dbesc($me['name']),
		dbesc($me['photo']),
		dbesc($me['url']),
		0,
		dbesc($subject),
		dbesc($body),
		0,
		0,
		0,
		dbesc($uri),
		dbesc($replyto),
		datetime_convert(),
		1
	);

	return 0;

}
