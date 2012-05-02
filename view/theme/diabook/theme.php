<?php

/*
 * Name: Diabook
 * Description: Diabook: report bugs and request here: http://pad.toktan.org/p/diabook or contact me : thomas_bierey@friendica.eu
 * Version: (Version: 1.025)
 * Author: 
 */

$a = get_app();
$a->theme_info = array(
    'family' => 'diabook',
	'version' => '1.025'
);

function diabook_init(&$a) {
	
//print diabook-version for debugging
$diabook_version = "Diabook (Version: 1.025)";
$a->page['htmlhead'] .= sprintf('<script "%s" ></script>', $diabook_version);

//change css on network and profilepages
$cssFile = null;

$resolution=false;
$resolution = get_pconfig(local_user(), "diabook", "resolution");
if ($resolution===false) $resolution="normal";

//Add META viewport tag respecting the resolution to header for tablets
if ($resolution=="wide") {
  $a->page['htmlhead'] .= '<meta name="viewport" content="width=1200" />';
} else {
  $a->page['htmlhead'] .= '<meta name="viewport" content="width=980" />';
}


$color = false;
$site_color = get_config("diabook", "color" );
if (local_user()) {$color = get_pconfig(local_user(), "diabook", "color");}
if ($color===false) $color=$site_color;
if ($color===false) $color="diabook";

if ($color=="diabook") $color_path = "/";
if ($color=="aerith") $color_path = "/diabook-aerith/";
if ($color=="blue") $color_path = "/diabook-blue/";
if ($color=="red") $color_path = "/diabook-red/";
if ($color=="pink") $color_path = "/diabook-pink/";
if ($color=="green") $color_path = "/diabook-green/";
if ($color=="dark") $color_path = "/diabook-dark/";


	//profile_side at networkpages
	if ($a->argv[0] === "network" && local_user()){

	// USER MENU
	if(local_user()) {
		
		$r = q("SELECT micro FROM contact WHERE uid=%d AND self=1", intval($a->user['uid']));
				
		$userinfo = array(
					'icon' => (count($r) ? $r[0]['micro']: $a->get_baseurl()."/images/default-profile-mm.jpg"),
					'name' => $a->user['username'],
				);	
		$ps = array('usermenu'=>array());
		$ps['usermenu']['status'] = Array('profile/' . $a->user['nickname'], t('Home'), "", t('Your posts and conversations'));
		$ps['usermenu']['profile'] = Array('profile/' . $a->user['nickname']. '?tab=profile', t('Profile'), "", t('Your profile page'));
		$ps['usermenu']['contacts'] = Array('contacts' , t('Contacts'), "", t('Your contacts'));				
		$ps['usermenu']['photos'] = Array('photos/' . $a->user['nickname'], t('Photos'), "", t('Your photos'));
		$ps['usermenu']['events'] = Array('events/', t('Events'), "", t('Your events'));
		$ps['usermenu']['notes'] = Array('notes/', t('Personal notes'), "", t('Your personal photos'));
		$ps['usermenu']['community'] = Array('community/', t('Community'), "", "");
		$ps['usermenu']['pgroups'] = Array('http://dir.friendica.com/directory/forum', t('Community Pages'), "", "");

		$tpl = get_markup_template('profile_side.tpl');

		$a->page['aside'] = replace_macros($tpl, array(
				'$userinfo' => $userinfo,
				'$ps' => $ps,
			)).$a->page['aside'];

	}
	
	$ccCookie = $_COOKIE['close_pages'] + $_COOKIE['close_profiles'] + $_COOKIE['close_helpers'] + $_COOKIE['close_services'] + $_COOKIE['close_friends'] + $_COOKIE['close_twitter'] + $_COOKIE['close_lastusers'] + $_COOKIE['close_lastphotos'] + $_COOKIE['close_lastlikes'];
	
	if($ccCookie != "9") {
	// COMMUNITY
	diabook_community_info();

	// CUSTOM CSS
	if($resolution == "normal") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-network.css";}
	if($resolution == "wide") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-network-wide.css";}
	}
	}



	//right_aside at profile pages
	if ($a->argv[0].$a->argv[1] === "profile".$a->user['nickname']){
	if($ccCookie != "9") {
	// COMMUNITY
	diabook_community_info();
	
	// CUSTOM CSS
	if($resolution == "normal") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-profile.css";}
	if($resolution == "wide") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-profile-wide.css";}
	
	}
	}
	
	//js scripts
	//load jquery.cookie.js
	$cookieJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.cookie.js";
	$a->page['htmlhead'] .= sprintf('<script language="JavaScript" src="%s"></script>', $cookieJS);
	
	//load jquery.ae.image.resize.js
	$imageresizeJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.ae.image.resize.js";
	$a->page['htmlhead'] .= sprintf('<script language="JavaScript" src="%s" ></script>', $imageresizeJS);
	
	//load jquery.twitter.search.js
	if($_COOKIE['close_twitter'] != "1") {
	$twitterJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.twitter.search.js";
	$a->page['htmlhead'] .= sprintf('<script language="JavaScript" src="%s" ></script>', $twitterJS);
	}
	
	$a->page['htmlhead'] .= '
	<script>
	
	 $(function() {
		$("a.lightbox").fancybox(); // Select all links with lightbox class
	 	});
	   
	 $(window).load(function() {
		var footer_top = $(document).height() - 30;
		$("div#footerbox").attr("style", "border-top: 1px solid #D2D2D2; width: 70%;right: 15%;position: absolute;top:"+footer_top+"px;");
	 });
	</script>';
	
	//check if twitterbox is active and print
	if($_COOKIE['close_twitter'] != "1") {
		$a->page['htmlhead'] .= '
		<script>
		$(function() {
		$("#twitter").twitterSearch({    	    
		term: "friendica",
		animInSpeed: 250,
		bird:    false, 
		avatar:  false, 
		colorExterior: "#fff",
		title: "Last Tweets",
		timeout: 10000    	});
		});
		</script>';}
			
	//check if community_home-plugin is activated and change css
	$nametocheck = "communityhome";
	$r = q("select id from addon where name = '%s' and installed = 1", dbesc($nametocheck));
	if(count($r) == "1") {
	
	$a->page['htmlhead'] .= '
	<script>
	$(document).ready(function() {
	$("div#login-submit-wrapper").attr("style","padding-top: 120px;");
	});
	</script>';	
	}
	//comment-edit-wrapper on photo_view
	if ($a->argv[0].$a->argv[2] === "photos"."image"){
	$a->page['htmlhead'] .= '
	<script>
		$(function(){
		$(".comment-edit-form").css("display","table");
			});
    </script>';
	}
	//restore right hand col at settingspage
	if($a->argv[0] === "settings" && local_user()) {
	$a->page['htmlhead'] .= ' 
	<script>
	function restore_boxes(){
	$.cookie("close_pages","2", { expires: 365, path: "/" });
	$.cookie("close_helpers","2", { expires: 365, path: "/" });
	$.cookie("close_profiles","2", { expires: 365, path: "/" });
	$.cookie("close_services","2", { expires: 365, path: "/" });
	$.cookie("close_friends","2", { expires: 365, path: "/" });
	$.cookie("close_twitter","2", { expires: 365, path: "/" });
	$.cookie("close_lastusers","2", { expires: 365, path: "/" });
	$.cookie("close_lastphotos","2", { expires: 365, path: "/" });
	$.cookie("close_lastlikes","2", { expires: 365, path: "/" });
	alert("Right-hand column was restored. Please refresh your browser");
   }
	</script>';}
	
	if ($a->argv[0].$a->argv[1] === "profile".$a->user['nickname'] or $a->argv[0] === "network" && local_user()){
	$a->page['htmlhead'] .= '
	<script>

 	$(function() {
	$(".oembed.photo img").aeImageResize({height: 400, width: 400});
  	});
	</script>';
	
	if($ccCookie != "9") {
	$a->page['htmlhead'] .= '
	<script>
	$("right_aside").ready(function(){
	
	if($.cookie("close_pages") == "1") 
		{
		document.getElementById( "close_pages" ).style.display = "none";
			};
			
	if($.cookie("close_profiles") == "1") 
		{
		document.getElementById( "close_profiles" ).style.display = "none";
			};
	
	if($.cookie("close_helpers") == "1") 
		{
		document.getElementById( "close_helpers" ).style.display = "none";
			};
			
	if($.cookie("close_services") == "1") 
		{
		document.getElementById( "close_services" ).style.display = "none";
			};
			
	if($.cookie("close_friends") == "1") 
		{
		document.getElementById( "close_friends" ).style.display = "none";
			};
	
	if($.cookie("close_twitter") == "1") 
		{
		document.getElementById( "twitter" ).style.display = "none";
			};	
			
	if($.cookie("close_lastusers") == "1") 
		{
		document.getElementById( "close_lastusers" ).style.display = "none";
			};
			
	if($.cookie("close_lastphotos") == "1") 
		{
		document.getElementById( "close_lastphotos" ).style.display = "none";
			};
			
	if($.cookie("close_lastlikes") == "1") 
		{
		document.getElementById( "close_lastlikes" ).style.display = "none";
			};}

	);

	function close_pages(){
	 document.getElementById( "close_pages" ).style.display = "none";
 	$.cookie("close_pages","1", { expires: 365, path: "/" });
 	};
 
	function close_profiles(){
 	document.getElementById( "close_profiles" ).style.display = "none";
 	$.cookie("close_profiles","1", { expires: 365, path: "/" });
 	};
 
	function close_helpers(){
 	document.getElementById( "close_helpers" ).style.display = "none";
  	$.cookie("close_helpers","1", { expires: 365, path: "/" });
 	};

	function close_services(){
 	document.getElementById( "close_services" ).style.display = "none";
 	$.cookie("close_services","1", { expires: 365, path: "/" });
 	};
 
	function close_friends(){
	 document.getElementById( "close_friends" ).style.display = "none";
	 $.cookie("close_friends","1", { expires: 365, path: "/" });
	 };
 
	function close_twitter(){
 	document.getElementById( "twitter" ).style.display = "none";
	 $.cookie("close_twitter","1", { expires: 365, path: "/" });
 	};
 
	function close_lastusers(){
	 document.getElementById( "close_lastusers" ).style.display = "none";
 	$.cookie("close_lastusers","1", { expires: 365, path: "/" });
	 };

	function close_lastphotos(){
 	document.getElementById( "close_lastphotos" ).style.display = "none";
	 $.cookie("close_lastphotos","1", { expires: 365, path: "/" });
	 };
 
	function close_lastlikes(){
	 document.getElementById( "close_lastlikes" ).style.display = "none";
	 $.cookie("close_lastlikes","1", { expires: 365, path: "/" });
	 };
	</script>';}
	}
	//end js scripts

	// custom css
	if (!is_null($cssFile)) $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);

	//footer
	$tpl = get_markup_template('footer.tpl');
	$a->page['footer'] .= replace_macros($tpl, array());
	
	//
	js_in_foot();
}


 function diabook_community_info() {
	$a = get_app();
	// comunity_profiles
	if($_COOKIE['close_profiles'] != "1") {
	$aside['$comunity_profilest_title'] = t('Community Profiles');
	$aside['$comunity_profiles_items'] = array();
	$r = q("select gcontact.* from gcontact left join glink on glink.gcid = gcontact.id 
			  where glink.cid = 0 and glink.uid = 0 order by rand() limit 9");
	$tpl = file_get_contents( dirname(__file__).'/ch_directory_item.tpl');
	if(count($r)) {
		$photo = 'photo';
		foreach($r as $rr) {
			$profile_link = $a->get_baseurl() . '/profile/' . ((strlen($rr['nickname'])) ? $rr['nickname'] : $rr['profile_uid']);
			$entry = replace_macros($tpl,array(
				'$id' => $rr['id'],
				'$profile-link' => zrl($rr['url']),
				'$photo' => $rr[$photo],
				'$alt-text' => $rr['name'],
			));
			$aside['$comunity_profiles_items'][] = $entry;
		}
	}}
	
	// last 12 users
	if($_COOKIE['close_lastusers'] != "1") {
	$aside['$lastusers_title'] = t('Last users');
	$aside['$lastusers_items'] = array();
	$sql_extra = "";
	$publish = (get_config('system','publish_all') ? '' : " AND `publish` = 1 " );
	$order = " ORDER BY `register_date` DESC ";

	$r = q("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`
			FROM `profile` LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid` 
			WHERE `is-default` = 1 $publish AND `user`.`blocked` = 0 $sql_extra $order LIMIT %d , %d ",
		0,
		9
	);
	$tpl = file_get_contents( dirname(__file__).'/ch_directory_item.tpl');
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
	}}
	
	// last 10 liked items
	if($_COOKIE['close_lastlikes'] != "1") {
	$aside['$like_title'] = t('Last likes');
	$aside['$like_items'] = array();
	$r = q("SELECT `T1`.`created`, `T1`.`liker`, `T1`.`liker-link`, `item`.* FROM 
			(SELECT `parent-uri`, `created`, `author-name` AS `liker`,`author-link` AS `liker-link` 
				FROM `item` WHERE `verb`='http://activitystrea.ms/schema/1.0/like' GROUP BY `parent-uri` ORDER BY `created` DESC) AS T1
			INNER JOIN `item` ON `item`.`uri`=`T1`.`parent-uri` 
			WHERE `T1`.`liker-link` LIKE '%s%%' OR `item`.`author-link` LIKE '%s%%'
			GROUP BY `uri`
			ORDER BY `T1`.`created` DESC
			LIMIT 0,5",
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
		
	}}
	
	// last 12 photos
	if($_COOKIE['close_photos'] != "1") {
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
				AND `user`.`hidewall`=0
				ORDER BY `photo`.`edited` DESC
				LIMIT 0, 9",
				dbesc(t('Contact Photos')),
				dbesc(t('Profile Photos'))
				);
		if(count($r)) {
		$tpl = file_get_contents( dirname(__file__).'/ch_directory_item.tpl');
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
	}}
	
   //right_aside FIND FRIENDS
   if($_COOKIE['close_friends'] != "1") {
	if(local_user()) {
	$nv = array();
	$nv['title'] = Array("", t('Find Friends'), "", "");
	$nv['directory'] = Array('directory', t('Local Directory'), "", "");
	$nv['global_directory'] = Array('http://dir.friendica.com/', t('Global Directory'), "", "");
	$nv['match'] = Array('match', t('Similar Interests'), "", "");
	$nv['suggest'] = Array('suggest', t('Friend Suggestions'), "", "");
	$nv['invite'] = Array('invite', t('Invite Friends'), "", "");
	
	$nv['search'] = '<form name="simple_bar" method="get" action="http://dir.friendika.com/directory">
						<span class="sbox_l"></span>
						<span class="sbox">
						<input type="text" name="search" size="13" maxlength="50">
						</span>
						<span class="sbox_r" id="srch_clear"></span>';
	
	$aside['$nv'] = $nv;
	}}
   
   //Community_Pages at right_aside
   if($_COOKIE['close_pages'] != "1") {
   if(local_user()) {
   $page = '<div id="" >
			<h3 style="margin-top:0px;">'.t("Community Pages").'<a id="close_pages_icon"  onClick="close_pages()" class="icon close_box" title="close"></a></h3></div>
			<div id=""><ul style="margin-left: 7px;margin-top: 0px;padding-left: 0px;padding-top: 0px;">';

	$pagelist = array();

	$contacts = q("SELECT `id`, `url`, `name`, `micro`FROM `contact`
			WHERE `network`= 'dfrn' AND `forum` = 1 AND `uid` = %d
			ORDER BY `name` ASC",
			intval($a->user['uid'])
	);

	$pageD = array();

	// Look if the profile is a community page
	foreach($contacts as $contact) {
		$pageD[] = array("url"=>$contact["url"], "name"=>$contact["name"], "id"=>$contact["id"], "micro"=>$contact['micro']);
	};
	

	$contacts = $pageD;

	foreach($contacts as $contact) {
		$page .= '<li style="list-style-type: none;" class="tool"><img height="20" width="20" style="float: left; margin-right: 3px;" src="' . $contact['micro'] .'" alt="' . $contact['url'] . '" /> <a href="'.$a->get_baseurl().'/redir/'.$contact["id"].'" style="margin-top: 2px; word-wrap: break-word; width: 132px;" title="' . $contact['url'] . '" class="label" target="external-link">'.
				$contact["name"]."</a></li>";
	}
	$page .= '</ul></div></div>';
	//if (sizeof($contacts) > 0)
		$aside['$page'] = $page;	
	}}
  //END Community Page	
  
  //helpers
  if($_COOKIE['close_helpers'] != "1") {
   $helpers = array();
	$helpers['title'] = Array("", t('Help or @NewHere ?'), "", "");
	$aside['$helpers'] = $helpers;
	}
   //end helpers
   //connectable services
   if($_COOKIE['close_services'] != "1") {
   $con_services = array();
	$con_services['title'] = Array("", t('Connect Services'), "", "");
	$aside['$con_services'] = $con_services;
	}
   //end connectable services
   //get_baseurl
   $url = $a->get_baseurl($ssl_state);   
   $aside['$url'] = $url;
	//print right_aside
	$tpl = file_get_contents(dirname(__file__).'/communityhome.tpl');
	$a->page['right_aside'] = replace_macros($tpl, $aside);
	
 }

 function js_in_foot() {
	/** @purpose insert stuff in bottom of page
	 */
	$a = get_app();
	$baseurl = $a->get_baseurl($ssl_state);
	$bottom['$baseurl'] = $baseurl;
	$tpl = file_get_contents(dirname(__file__) . '/bottom.tpl');
	$a->page['footer'] = $a->page['footer'].replace_macros($tpl, $bottom);
 }







 
 

 
 
