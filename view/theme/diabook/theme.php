<?php

/*
 * Name: Diabook
 * Description: Diabook: report bugs and request here: http://pad.toktan.org/p/diabook or contact me : thomas_bierey@friendica.eu
 * Version: (Version: 1.022)
 * Author: 
 */


//print diabook-version for debugging
$diabook_version = "Diabook (Version: 1.022)";
$a->page['htmlhead'] .= sprintf('<script "%s" ></script>', $diabook_version);

//change css on network and profilepages
$cssFile = null;

$resolution=false;
$resolution = get_pconfig(local_user(), "diabook", "resolution");
if ($resolution===false) $resolution="normal";

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


/**
 * prints last community activity
 */


 
function diabook_community_info(){
	$a = get_app();


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
	}
	
   
   //right_aside FIND FRIENDS
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
	};
   
   //Community Page
   if(local_user()) {
   $page = '<div id="page-sidebar-right_aside" class="widget">
			<div class="title tool">
			<h3>'.t("Community Pages").'<a id="close_pages_icon"  onClick="close_pages()" class="icon close_box" title="close"></a></h3></div>
			<div id="sidebar-page-list"><ul>';

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
		$page .= '<li style="list-style-type: none;" class="tool"><img height="20" width="20" style="float: left; margin-right: 3px;" src="' . $contact['micro'] .'" alt="' . $contact['url'] . '" /> <a href="'.$a->get_baseurl().'/redir/'.$contact["id"].'" style="margin-top: 2px;" title="' . $contact['url'] . '" class="label" target="external-link">'.
				$contact["name"]."</a></li>";
	}
	$page .= '</ul></div></div>';
	//if (sizeof($contacts) > 0)
		
		$aside['$page'] = $page;	
	}
  //END Community Page	
  //helpers
   $helpers = array();
	$helpers['title'] = Array("", t('Help or @NewHere ?'), "", "");
	
	$aside['$helpers'] = $helpers;
   //end helpers
   //connectable services
   $con_services = array();
	$con_services['title'] = Array("", t('Connect Services'), "", "");
	
	$aside['$con_services'] = $con_services;
   //end connectable services
  
  
   //get_baseurl
   $url = $a->get_baseurl($ssl_state);   
   $aside['$url'] = $url;

	$tpl = file_get_contents(dirname(__file__).'/communityhome.tpl');
	$a->page['right_aside'] = replace_macros($tpl, $aside);
	
	
	
	
}


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

		$a->page['aside'] .= replace_macros($tpl, array(
				'$userinfo' => $userinfo,
				'$ps' => $ps,
			));

	}
	
	$ccCookie = $_COOKIE['close_pages'] + $_COOKIE['close_helpers'] + $_COOKIE['close_services'] + $_COOKIE['close_friends'] + $_COOKIE['close_lastusers'] + $_COOKIE['close_lastphotos'] + $_COOKIE['close_lastlikes'];
	
	if($ccCookie != "7") {
	// COMMUNITY
	diabook_community_info();

	// CUSTOM CSS
	if($resolution == "normal") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-network.css";}
	if($resolution == "wide") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-network-wide.css";}
	}
}



//right_aside at profile pages
if ($a->argv[0].$a->argv[1] === "profile".$a->user['nickname']){
	if($ccCookie != "7") {
	// COMMUNITY
	diabook_community_info();
	
	// CUSTOM CSS
	if($resolution == "normal") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-profile.css";}
	if($resolution == "wide") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-profile-wide.css";}
	
	}
}

// custom css
if (!is_null($cssFile)) $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);

//footer
$tpl = get_markup_template('footer.tpl');
$a->page['footer'] .= replace_macros($tpl, array());

//load jquery.cookie.js
$cookieJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.cookie.js";
$a->page['htmlhead'] .= sprintf('<script language="JavaScript" src="%s"></script>', $cookieJS);

//load jquery.ae.image.resize.js
$imageresizeJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.ae.image.resize.js";
$a->page['htmlhead'] .= sprintf('<script language="JavaScript" src="%s" ></script>', $imageresizeJS);

//load jquery.autogrow-textarea.js
$autogrowJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.autogrow.textarea.js";
$a->page['htmlhead'] .= sprintf('<script language="JavaScript" src="%s" ></script>', $autogrowJS);

//js scripts
//comment-edit-wrapper on photo_view
if ($a->argv[0].$a->argv[2] === "photos"."image"){

$a->page['htmlhead'] .= '
<script>
	$(function(){
	
		$(".comment-edit-form").css("display","table");
			
			});
    </script>';
	
}

$a->page['htmlhead'] .= '

<script>
 $(function() {
	$("a.lightbox").fancybox(); // Select all links with lightbox class
 });
   
 </script>';
 
 
$a->page['htmlhead'] .= '

<script type="text/javascript">

function tautogrow(id){
		$("textarea#comment-edit-text-" +id).autogrow(); 	
 	};
 </script>';
 
 
$a->page['htmlhead'] .= '
 <script>
 
$(document).ready(function() {
    $("iframe").each(function(){
        var ifr_source = $(this).attr("src");
        var wmode = "wmode=transparent";
        if(ifr_source.indexOf("?") != -1) {
            var getQString = ifr_source.split("?");
            var oldString = getQString[1];
            var newString = getQString[0];
            $(this).attr("src",newString+"?"+wmode+"&"+oldString);
        }
        else $(this).attr("src",ifr_source+"?"+wmode);
       
    });
      
});

function yt_iframe() {

	$("iframe").load(function() { 
	var ifr_src = $(this).contents().find("body iframe").attr("src");
	$("iframe").contents().find("body iframe").attr("src", ifr_src+"&wmode=transparent");
    });

	};

function scrolldown(){
			$("html, body").animate({scrollTop:$(document).height()}, "slow");
			return false;
		};
		
function scrolltop(){
			$("html, body").animate({scrollTop:0}, "slow");
			return false;
		};
	 	
$(window).scroll(function () { 
		
				
		var scrollInfo = $(window).scrollTop();      
		
		if (scrollInfo <= "900"){
      $("a#top").attr("id","down");
      $("a#down").attr("onclick","scrolldown()");
	 	$("img#scroll_top_bottom").attr("src","view/theme/diabook/icons/scroll_bottom.png");
	 	} 
	 	    
      if (scrollInfo > "900"){
      $("a#down").attr("id","top");
      $("a#top").attr("onclick","scrolltop()");
	 	$("img#scroll_top_bottom").attr("src","view/theme/diabook/icons/scroll_top.png");
	 	}
		
    });
  
 </script>';
 
if($a->argv[0] === "settings" && local_user()) {
$a->page['htmlhead'] .= ' 
<script>
function restore_boxes(){
	$.cookie("close_pages","2", { expires: 365, path: "/" });
	$.cookie("close_helpers","2", { expires: 365, path: "/" });
	$.cookie("close_services","2", { expires: 365, path: "/" });
	$.cookie("close_friends","2", { expires: 365, path: "/" });
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


	if($ccCookie != "7") {
$a->page['htmlhead'] .= '
<script>
$("right_aside").ready(function(){
	
	if($.cookie("close_pages") == "1") 
		{
		document.getElementById( "close_pages" ).style.display = "none";
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
</script>';}}

$a->page['htmlhead'] .= ' 

<script>
function insertFormatting(comment,BBcode,id) {
	
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
								}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url"){
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
			} else			
		selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url"){
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
			} else
		textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
	}
	return true;
}

function cmtBbOpen(id) {
	$(".comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$(".comment-edit-bb-" + id).hide();
}

</script> ';