<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function subthread_content(App &$a) {

	if(! local_user() && ! remote_user()) {
		return;
	}

	$activity = ACTIVITY_FOLLOW;

	$item_id = (($a->argc > 1) ? notags(trim($a->argv[1])) : 0);

	$r = q("SELECT * FROM `item` WHERE `parent` = '%s' OR `parent-uri` = '%s' and parent = id LIMIT 1",
		dbesc($item_id),
		dbesc($item_id)
	);

	if(! $item_id || (! dbm::is_result($r))) {
		logger('subthread: no item ' . $item_id);
		return;
	}

	$item = $r[0];

	$owner_uid = $item['uid'];

	if(! can_write_wall($a,$owner_uid)) {
		return;
	}

	$remote_owner = null;

	if(! $item['wall']) {
		// The top level post may have been written by somebody on another system
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item['contact-id']),
			intval($item['uid'])
		);
		if (! dbm::is_result($r)) {
			return;
		}
		if (! $r[0]['self']) {
			$remote_owner = $r[0];
		}
	}

	// this represents the post owner on this system. 

	$r = q("SELECT `contact`.*, `user`.`nickname` FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
		WHERE `contact`.`self` = 1 AND `contact`.`uid` = %d LIMIT 1",
		intval($owner_uid)
	);
	if (dbm::is_result($r))
		$owner = $r[0];

	if (! $owner) {
		logger('like: no owner');
		return;
	}

	if (! $remote_owner)
		$remote_owner = $owner;


	// This represents the person posting

	if ((local_user()) && (local_user() == $owner_uid)) {
		$contact = $owner;
	} else {
		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($_SESSION['visitor_id']),
			intval($owner_uid)
		);
		if (dbm::is_result($r))
			$contact = $r[0];
	}
	if (! $contact) {
		return;
	}

	$uri = item_new_uri($a->get_hostname(),$owner_uid);

	$post_type = (($item['resource-id']) ? t('photo') : t('status'));
	$objtype = (($item['resource-id']) ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE );
	$link = xmlify('<link rel="alternate" type="text/html" href="' . App::get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . '" />' . "\n") ;
	$body = $item['body'];

	$obj = <<< EOT

	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>{$item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</object>
EOT;
	$bodyverb = t('%1$s is following %2$s\'s %3$s');

	if (! isset($bodyverb)) {
		return;
	}

	$arr = array();

	$arr['guid'] = get_guid(32);
	$arr['uri'] = $uri;
	$arr['uid'] = $owner_uid;
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = $item['wall'];
	$arr['origin'] = 1;
	$arr['gravity'] = GRAVITY_LIKE;
	$arr['parent'] = $item['id'];
	$arr['parent-uri'] = $item['uri'];
	$arr['thr-parent'] = $item['uri'];
	$arr['owner-name'] = $remote_owner['name'];
	$arr['owner-link'] = $remote_owner['url'];
	$arr['owner-avatar'] = $remote_owner['thumb'];
	$arr['author-name'] = $contact['name'];
	$arr['author-link'] = $contact['url'];
	$arr['author-avatar'] = $contact['thumb'];

	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
	$plink = '[url=' . App::get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink );

	$arr['verb'] = $activity;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['allow_cid'] = $item['allow_cid'];
	$arr['allow_gid'] = $item['allow_gid'];
	$arr['deny_cid'] = $item['deny_cid'];
	$arr['deny_gid'] = $item['deny_gid'];
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 0;

	$post_id = item_store($arr);

	if (! $item['visible']) {
		$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d",
			intval($item['id']),
			intval($owner_uid)
		);
	}

	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);

	killme();

}


