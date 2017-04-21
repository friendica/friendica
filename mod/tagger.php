<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function tagger_content(App $a) {

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

	if(! $item_id || (! dbm::is_result($r))) {
		logger('tagger: no item ' . $item_id);
		return;
	}

	$item = $r[0];

	$owner_uid = $item['uid'];

	$r = q("select `nickname`,`blocktags` from user where uid = %d limit 1",
		intval($owner_uid)
	);
	if (dbm::is_result($r)) {
		$owner_nick = $r[0]['nickname'];
		$blocktags = $r[0]['blocktags'];
	}

	if(local_user() != $owner_uid)
		return;

	$r = q("select * from contact where self = 1 and uid = %d limit 1",
		intval(local_user())
	);
	if (dbm::is_result($r))
			$contact = $r[0];
	else {
		logger('tagger: no contact_id');
		return;
	}

	$uri = item_new_uri($a->get_hostname(),$owner_uid);
	$xterm = xmlify($term);
	$post_type = (($item['resource-id']) ? t('photo') : t('status'));
	$targettype = (($item['resource-id']) ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE );

	$link = xmlify('<link rel="alternate" type="text/html" href="'
		. App::get_baseurl() . '/display/' . $owner['nickname'] . '/' . $item['id'] . '" />' . "\n") ;

	$body = xmlify($item['body']);

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

	$tagid = App::get_baseurl() . '/search?tag=' . $term;
	$objtype = ACTIVITY_OBJ_TAGTERM;

	$obj = <<< EOT
	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>$tagid</id>
		<link>$tagid</link>
		<title>$xterm</title>
		<content>$xterm</content>
	</object>
EOT;

	$bodyverb = t('%1$s tagged %2$s\'s %3$s with %4$s');

	if (! isset($bodyverb)) {
		return;
	}

	$termlink = html_entity_decode('&#x2317;') . '[url=' . App::get_baseurl() . '/search?tag=' . urlencode($term) . ']'. $term . '[/url]';

	$arr = array();

	$arr['guid'] = get_guid(32);
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

//	q("UPDATE `item` set plink = '%s' where id = %d",
//		dbesc(App::get_baseurl() . '/display/' . $owner_nick . '/' . $post_id),
//		intval($post_id)
//	);


	if(! $item['visible']) {
		$r = q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d",
			intval($item['id']),
			intval($owner_uid)
		);
	}

	$term_objtype = (($item['resource-id']) ? TERM_OBJ_PHOTO : TERM_OBJ_POST );
        $t = q("SELECT count(tid) as tcount FROM term WHERE oid=%d AND term='%s'",
                intval($item['id']),
                dbesc($term)
        );
	if((! $blocktags) && $t[0]['tcount']==0 ) {
		/*q("update item set tag = '%s' where id = %d",
			dbesc($item['tag'] . (strlen($item['tag']) ? ',' : '') . '#[url=' . App::get_baseurl() . '/search?tag=' . $term . ']'. $term . '[/url]'),
			intval($item['id'])
		);*/

		q("INSERT INTO term (oid, otype, type, term, url, uid) VALUE (%d, %d, %d, '%s', '%s', %d)",
		   intval($item['id']),
		   $term_objtype,
		   TERM_HASHTAG,
		   dbesc($term),
		   dbesc(App::get_baseurl() . '/search?tag=' . $term),
		   intval($owner_uid)
		);
	}

	// if the original post is on this site, update it.

	$r = q("select `tag`,`id`,`uid` from item where `origin` = 1 AND `uri` = '%s' LIMIT 1",
		dbesc($item['uri'])
	);
	if (dbm::is_result($r)) {
		$x = q("SELECT `blocktags` FROM `user` WHERE `uid` = %d limit 1",
			intval($r[0]['uid'])
		);
		$t = q("SELECT count(tid) as tcount FROM term WHERE oid=%d AND term='%s'",
			intval($r[0]['id']),
			dbesc($term)
		);
		if(count($x) && !$x[0]['blocktags'] && $t[0]['tcount']==0){
			q("INSERT INTO term (oid, otype, type, term, url, uid) VALUE (%d, %d, %d, '%s', '%s', %d)",
	                   intval($r[0]['id']),
	                   $term_objtype,
	                   TERM_HASHTAG,
	                   dbesc($term),
	                   dbesc(App::get_baseurl() . '/search?tag=' . $term),
	                   intval($owner_uid)
	                );
		}

		/*if(count($x) && !$x[0]['blocktags'] && (! stristr($r[0]['tag'], ']' . $term . '['))) {
			q("update item set tag = '%s' where id = %d",
				dbesc($r[0]['tag'] . (strlen($r[0]['tag']) ? ',' : '') . '#[url=' . App::get_baseurl() . '/search?tag=' . $term . ']'. $term . '[/url]'),
				intval($r[0]['id'])
			);
		}*/

	}


	$arr['id'] = $post_id;

	call_hooks('post_local_end', $arr);

	proc_run(PRIORITY_HIGH, "include/notifier.php", "tag", $post_id);

	killme();

	return; // NOTREACHED


}
