<?php

require_once('include/security.php');
require_once('include/bbcode.php');
require_once('include/items.php');


function mood_init(App $a) {

	if (! local_user()) {
		return;
	}

	$uid = local_user();
	$verb = notags(trim($_GET['verb']));

	if(! $verb)
		return;

	$verbs = get_mood_verbs();

	if(! in_array($verb,$verbs))
		return;

	$activity = ACTIVITY_MOOD . '#' . urlencode($verb);

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : 0);


	logger('mood: verb ' . $verb, LOGGER_DEBUG);


	if($parent) {
		$r = q("select uri, private, allow_cid, allow_gid, deny_cid, deny_gid
			from item where id = %d and parent = %d and uid = %d limit 1",
			intval($parent),
			intval($parent),
			intval($uid)
		);
		if (dbm::is_result($r)) {
			$parent_uri = $r[0]['uri'];
			$private    = $r[0]['private'];
			$allow_cid  = $r[0]['allow_cid'];
			$allow_gid  = $r[0]['allow_gid'];
			$deny_cid   = $r[0]['deny_cid'];
			$deny_gid   = $r[0]['deny_gid'];
		}
	}
	else {

		$private = 0;

		$allow_cid     =  $a->user['allow_cid'];
		$allow_gid     =  $a->user['allow_gid'];
		$deny_cid      =  $a->user['deny_cid'];
		$deny_gid      =  $a->user['deny_gid'];
	}

	$poster = $a->contact;

	$uri = item_new_uri($a->get_hostname(),$uid);

	$action = sprintf( t('%1$s is currently %2$s'), '[url=' . $poster['url'] . ']' . $poster['name'] . '[/url]' , $verbs[$verb]);

	$arr = array();
	$arr['guid']          = get_guid(32);
	$arr['uid']           = $uid;
	$arr['uri']           = $uri;
	$arr['parent-uri']    = (($parent_uri) ? $parent_uri : $uri);
	$arr['type']          = 'activity';
	$arr['wall']          = 1;
	$arr['contact-id']    = $poster['id'];
	$arr['owner-name']    = $poster['name'];
	$arr['owner-link']    = $poster['url'];
	$arr['owner-avatar']  = $poster['thumb'];
	$arr['author-name']   = $poster['name'];
	$arr['author-link']   = $poster['url'];
	$arr['author-avatar'] = $poster['thumb'];
	$arr['title']         = '';
	$arr['allow_cid']     = $allow_cid;
	$arr['allow_gid']     = $allow_gid;
	$arr['deny_cid']      = $deny_cid;
	$arr['deny_gid']      = $deny_gid;
	$arr['last-child']    = 1;
	$arr['visible']       = 1;
	$arr['verb']          = $activity;
	$arr['private']       = $private;

	$arr['origin']        = 1;
	$arr['body']          = $action;

	$item_id = item_store($arr);
	if($item_id) {
		q("UPDATE `item` SET `plink` = '%s' WHERE `uid` = %d AND `id` = %d",
			dbesc(App::get_baseurl() . '/display/' . $poster['nickname'] . '/' . $item_id),
			intval($uid),
			intval($item_id)
		);
		proc_run(PRIORITY_HIGH, "include/notifier.php", "tag", $item_id);
	}


	call_hooks('post_local_end', $arr);

	proc_run(PRIORITY_HIGH, "include/notifier.php", "like", $post_id);

	return;
}



function mood_content(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	$parent = ((x($_GET,'parent')) ? intval($_GET['parent']) : '0');



	$verbs = get_mood_verbs();

	$shortlist = array();
	foreach($verbs as $k => $v)
		if($v !== 'NOTRANSLATION')
			$shortlist[] = array($k,$v);


	$tpl = get_markup_template('mood_content.tpl');

	$o = replace_macros($tpl,array(
		'$title' => t('Mood'),
		'$desc' => t('Set your current mood and tell your friends'),
		'$verbs' => $shortlist,
		'$parent' => $parent,
		'$submit' => t('Submit'),
	));

	return $o;

}
