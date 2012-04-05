<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function tagger_content(&$a) {

	if(! local_user() && ! remote_user()) {
		return;
	}

	$term = notags(trim($_GET['term']));
	// no commas allowed
	$term = str_replace(array(',',' '),array('','_'),$term);

	if(! $term)
		return;

	$item_id = (($a->argc > 1) ? notags(trim($a->argv[1])) : 0);

	logger('tagger: tag ' . $term . ' item ' . $item_id);


	$r = q("SELECT * FROM `item` WHERE `id` = '%s' LIMIT 1",
		dbesc($item_id)
	);

	if(! $item_id || (! count($r))) {
		logger('tagger: no item ' . $item_id);
		return;
	}

	$item = $r[0];

	$owner_uid = $item['uid'];

	$r = q("select `nickname`,`blocktags` from user where uid = %d limit 1",
		intval($owner_uid)
	);
	if(count($r)) {
		$owner_nick = $r[0]['nickname'];
		$blocktags = $r[0]['blocktags'];
	}

	if(local_user() != $owner_uid)
		return;

	if(remote_user()) {
		$r = q("select * from contact where id = %d AND `uid` = %d limit 1",
			intval(remote_user()),
			intval($item['uid'])
		);
	}
	else {
		$r = q("select * from contact where self = 1 and uid = %d limit 1",
			intval(local_user())
		);
	}
	if(count($r))
			$contact = $r[0];
	else {
		logger('tagger: no contact_id');
		return;
	}

	$uri = item_new_uri($a->get_hostname(),$owner_uid);

	$post_type = (($item['resource-id']) ? t('photo') : t('status'));
	$targettype = (($item['resource-id']) ? ACTIVITY_OBJ_PHOTO : ACTIVITY_OBJ_NOTE ); 

	$link = xmlify('<link rel="alternate" type="text/html" href="' 
		. $a->get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . '" />' . "\n") ;

	$body = $item['body'];

	$target = <<< EOT
	<target>
		<type>$targettype</type>
		<local>1</local>
		<id>{$item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</target>
EOT;

	$tagid = $a->get_baseurl() . '/search?search=' . $term;
	$objtype = ACTIVITY_OBJ_TAGTERM;

	$obj = <<< EOT
	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>$tagid</id>
		<link>$tagid</link>
		<title>$term</title>
		<content>$term</content>
	</object>
EOT;

	$bodyverb = t('%1$s tagged %2$s\'s %3$s with %4$s');

	if(! isset($bodyverb))
			return; 

	$termlink = html_entity_decode('&#x2317;') . '[url=' . $a->get_baseurl() . '/search?search=' . urlencode($term) . ']'. $term . '[/url]';

	$arr = array();

	$arr['uri'] = $uri;
	$arr['uid'] = $owner_uid;
	$arr['contact-id'] = $contact['id'];
	$arr['type'] = 'activity';
	$arr['wall'] = $item['wall'];
	$arr['gravity'] = GRAVITY_COMMENT;
	$arr['parent'] = $item['id'];
	$arr['parent-uri'] = $item['uri'];
	$arr['owner-name'] = $item['author-name'];
	$arr['owner-link'] = $item['author-link'];
	$arr['owner-avatar'] = $item['author-avatar'];
	$arr['author-name'] = $contact['name'];
	$arr['author-link'] = $contact['url'];
	$arr['author-avatar'] = $contact['thumb'];
	
	$ulink = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
	$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
	$plink = '[url=' . $item['plink'] . ']' . $post_type . '[/url]';
	$arr['body'] =  sprintf( $bodyverb, $ulink, $alink, $plink, $termlink );

	$arr['verb'] = ACTIVITY_TAG;
	$arr['target-type'] = $targettype;
	$arr['target'] = $target;
	$arr['object-type'] = $objtype;
	$arr['object'] = $obj;
	$arr['private'] = $item['private'];
	$arr['allow_cid'] = $item['allow_cid'];
	$arr['allow_gid'] = $item['allow_gid'];
	$arr['deny_cid'] = $item['deny_cid'];
	$arr['deny_gid'] = $item['deny_gid'];
	$arr['visible'] = 1;
	$arr['unseen'] = 1;
	$arr['last-child'] = 1;
	$arr['origin'] = 1;

	$post_id = item_store($arr);	

	q("UPDATE `item` set plink = '%s' where id = %d limit 1",
		dbesc($a->get_baseurl() . '/display/' . $owner_nick . '/' . $post_id),
		intval($post_id)
	);
		

	if(! $item['visible']) {
		$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item['id']),
			intval($owner_uid)
		);
	}			

	if((! $blocktags) && (! stristr($item['tag'], ']' . $term . '[' ))) {
		q("update item set tag = '%s' where id = %d limit 1",
			dbesc($item['tag'] . (strlen($item['tag']) ? ',' : '') . '#[url=' . $a->get_baseurl() . '/search?search=' . $term . ']'. $term . '[/url]'),
			intval($item['id'])
		);
	}

	// if the original post is on this site, update it.

	$r = q("select `tag`,`id`,`uid` from item where `origin` = 1 AND `uri` = '%s' LIMIT 1",
		dbesc($item['uri'])
	);
	if(count($r)) {
		$x = q("SELECT `blocktags` FROM `user` WHERE `uid` = %d limit 1",
			intval($r[0]['uid'])
		);
		if(count($x) && !$x[0]['blocktags'] && (! stristr($r[0]['tag'], ']' . $term . '['))) {
			q("update item set tag = '%s' where id = %d limit 1",
				dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . '#[url=' . $a->get_baseurl() . '/search?search=' . $term . ']'. $term . '[/url]'),
				intval($r[0]['id'])
			);
		}

	}
		

	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);

	proc_run('php',"include/notifier.php","tag","$post_id");

	killme();

	return; // NOTREACHED


}