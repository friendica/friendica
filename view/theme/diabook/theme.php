<?php

/*
 * Name: Diabook
 * Description: Diabook: report bugs and request here: http://pad.toktan.org/p/diabook or contact me : thomas_bierey@friendica.eu
 * Version: 
 * Author: 
 */

$a->theme_info = array(
  'extends' => 'diabook',
);

//profile_side at networkpages

if($is_url = preg_match ("/\bnetwork\b/i", $_SERVER['REQUEST_URI'])) {
//

$nav['usermenu']=array();
$userinfo = null;

if(local_user()) {
	


$r = q("SELECT micro FROM contact WHERE uid=%d AND self=1", intval($a->user['uid']));
		
$userinfo = array(
			'icon' => (count($r) ? $r[0]['micro']: $a->get_baseurl()."/images/default-profile-mm.jpg"),
			'name' => $a->user['username'],
		);	
	
$ps['usermenu'][status] = Array('profile/' . $a->user['nickname'], t('Home'), "", t('Your posts and conversations'));
$ps['usermenu'][profile] = Array('profile/' . $a->user['nickname']. '?tab=profile', t('Profile'), "", t('Your profile page'));
$ps['usermenu'][photos] = Array('photos/' . $a->user['nickname'], t('Photos'), "", t('Your photos'));
$ps['usermenu'][events] = Array('events/', t('Events'), "", t('Your events'));
$ps['usermenu'][notes] = Array('notes/', t('Personal notes'), "", t('Your personal photos'));
$ps['usermenu'][community] = Array('community/', t('Community'), "", "");


$tpl = get_markup_template('profile_side.tpl');

$a->page['aside'] .= replace_macros($tpl, array(
		'$userinfo' => $userinfo,
		'$ps' => $ps,
	));

}

//right_aside at networkpages

// last 12 users
	$aside['$lastusers_title'] = t('Last users');
	$aside['$lastusers_items'] = array();
	$sql_extra = "";
	$publish = (get_config('system','publish_all') ? '' : " AND `publish` = 1 " );
	$order = " ORDER BY `register_date` DESC ";

	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`
			FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` 
			WHERE `is-default` = 1 $publish AND `user`.`blocked` = 0 $sql_extra $order LIMIT %d , %d ",
		0,
		12
	);
	$tpl = file_get_contents( dirname(__file__).'/directory_item.tpl');
	if(count($r)) {
		$photo = 'thumb';
		foreach($r as $rr) {
			$profile_link = $a->get_baseurl() . '/profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);
			$entry = replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $profile_link,
				'$photo' => $rr[$photo],
				'$alt-text' => $rr['name'],
			));
			$aside['$lastusers_items'][] = $entry;
		}
	}
	
// last 10 liked items
	$aside['$like_title'] = t('Last likes');
	$aside['$like_items'] = array();
	$r = q("SELECT `T1`.`created`, `T1`.`liker`, `T1`.`liker-link`, `item`.* FROM 
			(SELECT `parent-uri`, `created`, `author-name` AS `liker`,`author-link` AS `liker-link` 
				FROM `item` WHERE `verb`='http://activitystrea.ms/schema/1.0/like' GROUP BY `parent-uri` ORDER BY `created` DESC) AS T1
			INNER JOIN `item` ON `item`.`uri`=`T1`.`parent-uri` 
			WHERE `T1`.`liker-link` LIKE '%s%%' OR `item`.`author-link` LIKE '%s%%'
			GROUP BY `uri`
			ORDER BY `T1`.`created` DESC
			LIMIT 0,10",
			$a->get_baseurl(),$a->get_baseurl()
			);

	foreach ($r as $rr) {
		$author	 = '<a href="' . $rr['liker-link'] . '">' . $rr['liker'] . '</a>';
		$objauthor =  '<a href="' . $rr['author-link'] . '">' . $rr['author-name'] . '</a>';
		
		//var_dump($rr['verb'],$rr['object-type']); killme();
		switch($rr['verb']){
			case 'http://activitystrea.ms/schema/1.0/post':
				switch ($rr['object-type']){
					case 'http://activitystrea.ms/schema/1.0/event':
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if ($rr['resource-id']){
					$post_type = t('photo');
					$m=array();	preg_match("/\[url=([^]]*)\]/", $rr['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '<a href="' . $rr['plink'] . '">' . $post_type . '</a>';

		$aside['$like_items'][] = sprintf( t('%1$s likes %2$s\'s %3$s'), $author, $objauthor, $plink);
		
	}
// last 12 photos
	$aside['$photos_title'] = t('Last photos');
	$aside['$photos_items'] = array();
	$r = q("SELECT `photo`.`id`, `photo`.`resource-id`, `photo`.`scale`, `photo`.`desc`, `user`.`nickname`, `user`.`username` FROM 
				(SELECT `resource-id`, MAX(`scale`) as maxscale FROM `photo` 
					WHERE `profile`=0 AND `contact-id`=0 AND `album` NOT IN ('Contact Photos', '%s', 'Profile Photos', '%s')
						AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`='' GROUP BY `resource-id`) AS `t1`
				INNER JOIN `photo` ON `photo`.`resource-id`=`t1`.`resource-id` AND `photo`.`scale` = `t1`.`maxscale`,
				`user` 
				WHERE `user`.`uid` = `photo`.`uid`
				AND `user`.`blockwall`=0
				ORDER BY `photo`.`edited` DESC
				LIMIT 0, 12",
				dbesc(t('Contact Photos')),
				dbesc(t('Profile Photos'))
				);
		if(count($r)) {
		$tpl = file_get_contents( dirname(__file__).'/directory_item.tpl');
		foreach($r as $rr) {
			$photo_page = $a->get_baseurl() . '/photos/' . $rr['nickname'] . '/image/' . $rr['resource-id'];
			$photo_url = $a->get_baseurl() . '/photo/' .  $rr['resource-id'] . '-' . $rr['scale'] .'.jpg';
		
			$entry = replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $photo_page,
				'$photo' => $photo_url,
				'$alt-text' => $rr['username']." : ".$rr['desc'],
			));

			$aside['$photos_items'][] = $entry;
		}
	}
	

	$tpl = file_get_contents(dirname(__file__).'/communityhome.tpl');
	$a->page['right_aside'] = replace_macros($tpl, $aside);
	
}

//right_aside at profile pages

if($is_url = preg_match ("/\bprofile\b/i", $_SERVER['REQUEST_URI'])) {
//right_aside

// last 12 users
	$aside['$lastusers_title'] = t('Last users');
	$aside['$lastusers_items'] = array();
	$sql_extra = "";
	$publish = (get_config('system','publish_all') ? '' : " AND `publish` = 1 " );
	$order = " ORDER BY `register_date` DESC ";

	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`
			FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` 
			WHERE `is-default` = 1 $publish AND `user`.`blocked` = 0 $sql_extra $order LIMIT %d , %d ",
		0,
		12
	);
	$tpl = file_get_contents( dirname(__file__).'/directory_item.tpl');
	if(count($r)) {
		$photo = 'thumb';
		foreach($r as $rr) {
			$profile_link = $a->get_baseurl() . '/profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);
			$entry = replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $profile_link,
				'$photo' => $rr[$photo],
				'$alt-text' => $rr['name'],
			));
			$aside['$lastusers_items'][] = $entry;
		}
	}
	
// last 10 liked items
	$aside['$like_title'] = t('Last likes');
	$aside['$like_items'] = array();
	$r = q("SELECT `T1`.`created`, `T1`.`liker`, `T1`.`liker-link`, `item`.* FROM 
			(SELECT `parent-uri`, `created`, `author-name` AS `liker`,`author-link` AS `liker-link` 
				FROM `item` WHERE `verb`='http://activitystrea.ms/schema/1.0/like' GROUP BY `parent-uri` ORDER BY `created` DESC) AS T1
			INNER JOIN `item` ON `item`.`uri`=`T1`.`parent-uri` 
			WHERE `T1`.`liker-link` LIKE '%s%%' OR `item`.`author-link` LIKE '%s%%'
			GROUP BY `uri`
			ORDER BY `T1`.`created` DESC
			LIMIT 0,10",
			$a->get_baseurl(),$a->get_baseurl()
			);

	foreach ($r as $rr) {
		$author	 = '<a href="' . $rr['liker-link'] . '">' . $rr['liker'] . '</a>';
		$objauthor =  '<a href="' . $rr['author-link'] . '">' . $rr['author-name'] . '</a>';
		
		//var_dump($rr['verb'],$rr['object-type']); killme();
		switch($rr['verb']){
			case 'http://activitystrea.ms/schema/1.0/post':
				switch ($rr['object-type']){
					case 'http://activitystrea.ms/schema/1.0/event':
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if ($rr['resource-id']){
					$post_type = t('photo');
					$m=array();	preg_match("/\[url=([^]]*)\]/", $rr['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '<a href="' . $rr['plink'] . '">' . $post_type . '</a>';

		$aside['$like_items'][] = sprintf( t('%1$s likes %2$s\'s %3$s'), $author, $objauthor, $plink);
		
	}
// last 12 photos
	$aside['$photos_title'] = t('Last photos');
	$aside['$photos_items'] = array();
	$r = q("SELECT `photo`.`id`, `photo`.`resource-id`, `photo`.`scale`, `photo`.`desc`, `user`.`nickname`, `user`.`username` FROM 
				(SELECT `resource-id`, MAX(`scale`) as maxscale FROM `photo` 
					WHERE `profile`=0 AND `contact-id`=0 AND `album` NOT IN ('Contact Photos', '%s', 'Profile Photos', '%s')
						AND `allow_cid`='' AND `allow_gid`='' AND `deny_cid`='' AND `deny_gid`='' GROUP BY `resource-id`) AS `t1`
				INNER JOIN `photo` ON `photo`.`resource-id`=`t1`.`resource-id` AND `photo`.`scale` = `t1`.`maxscale`,
				`user` 
				WHERE `user`.`uid` = `photo`.`uid`
				AND `user`.`blockwall`=0
				ORDER BY `photo`.`edited` DESC
				LIMIT 0, 12",
				dbesc(t('Contact Photos')),
				dbesc(t('Profile Photos'))
				);
		if(count($r)) {
		$tpl = file_get_contents( dirname(__file__).'/directory_item.tpl');
		foreach($r as $rr) {
			$photo_page = $a->get_baseurl() . '/photos/' . $rr['nickname'] . '/image/' . $rr['resource-id'];
			$photo_url = $a->get_baseurl() . '/photo/' .  $rr['resource-id'] . '-' . $rr['scale'] .'.jpg';
		
			$entry = replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => $photo_page,
				'$photo' => $photo_url,
				'$alt-text' => $rr['username']." : ".$rr['desc'],
			));

			$aside['$photos_items'][] = $entry;
		}
	}
	

	$tpl = file_get_contents(dirname(__file__).'/communityhome.tpl');
	$a->page['right_aside'] = replace_macros($tpl, $aside);
	
}

//js scripts
$a->page['htmlhead'] .= <<< EOT

<script>

//contacts
$('html').click(function() {
 $('#nav-contacts-linkmenu').removeClass('selected');
 document.getElementById( "nav-contacts-menu" ).style.display = "none";
 });
 
 $('#nav-contacts-linkmenu').click(function(event){
     event.stopPropagation();
 });

//messages
$('html').click(function() {
 $('#nav-messages-linkmenu').removeClass('selected');
 document.getElementById( "nav-messages-menu" ).style.display = "none";
 });

 $('#nav-messages-linkmenu').click(function(event){
     event.stopPropagation();
 });

//notifications
$('html').click(function() {
 $('#nav-notifications-linkmenu').removeClass('selected');
 document.getElementById( "nav-notifications-menu" ).style.display = "none";
 });

 $('#nav-notifications-linkmenu').click(function(event){
     event.stopPropagation();
 });

//usermenu
$('html').click(function() {
 $('#nav-user-linkmenu').removeClass('selected');
 document.getElementById( "nav-user-menu" ).style.display = "none";
 });

 $('#nav-user-linkmenu').click(function(event){
     event.stopPropagation();
 });
 
 //settingsmenu
 $('html').click(function() {
 $('#nav-site-linkmenu').removeClass('selected');
 document.getElementById( "nav-site-menu" ).style.display = "none";
 });

 $('#nav-site-linkmenu').click(function(event){
     event.stopPropagation();
 });
 //appsmenu
 $('html').click(function() {
 $('#nav-apps-link').removeClass('selected');
 document.getElementById( "nav-apps-menu" ).style.display = "none";
 });

 $('#nav-apps-link').click(function(event){
     event.stopPropagation();
 });
 
 $(function() {
	$('a.lightbox').fancybox(); // Select all links with lightbox class
});

 
 </script>
EOT;
