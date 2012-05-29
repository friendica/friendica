<?php

/*
 * Name: Diabook
 * Description: Diabook: report bugs and request here: http://pad.toktan.org/p/diabook or contact me : thomas_bierey@friendica.eu
 * Version: (Version: 1.027)
 * Author: 
 */

$a = get_app();


function diabook_init(&$a) {
	
//print diabook-version for debugging
$diabook_version = "Diabook (Version: 1.027)";
$a->page['htmlhead'] .= sprintf('<META NAME=generator CONTENT="%s"/>', $diabook_version);

//init css on network and profilepages
$cssFile = null;

// Preload config
load_config("diabook");
load_pconfig(local_user(), "diabook");

//get statuses of boxes at right-hand-column
$close_pages = false;
$site_close_pages = get_config("diabook", "close_pages" );
if (local_user()) {$close_pages = get_pconfig(local_user(), "diabook", "close_pages");}
if ($close_pages===false) $close_pages=$site_close_pages;
if ($close_pages===false) $close_pages="1";

$close_profiles = false;
$site_close_profiles = get_config("diabook", "close_profiles" );
if (local_user()) {$close_profiles = get_pconfig(local_user(), "diabook", "close_profiles");}
if ($close_profiles===false) $close_profiles=$site_close_profiles;
if ($close_profiles===false) $close_profiles="0";

$close_helpers = false;
$site_close_helpers = get_config("diabook", "close_helpers" );
if (local_user()) {$close_helpers = get_pconfig(local_user(), "diabook", "close_helpers");}
if ($close_helpers===false) $close_helpers=$site_close_helpers;
if ($close_helpers===false) $close_helpers="0";

$close_services = false;
$site_close_services = get_config("diabook", "close_services" );
if (local_user()) {$close_services = get_pconfig(local_user(), "diabook", "close_services");}
if ($close_services===false) $close_services=$site_close_services;
if ($close_services===false) $close_services="0";

$close_friends = false;
$site_close_friends = get_config("diabook", "close_friends" );
if (local_user()) {$close_friends = get_pconfig(local_user(), "diabook", "close_friends");}
if ($close_friends===false) $close_friends=$site_close_friends;
if ($close_friends===false) $close_friends="0";

$close_lastusers = false;
$site_close_lastusers = get_config("diabook", "close_lastusers" );
if (local_user()) {$close_lastusers = get_pconfig(local_user(), "diabook", "close_lastusers");}
if ($close_lastusers===false) $close_lastusers=$site_close_lastusers;
if ($close_lastusers===false) $close_lastusers="0";

$close_lastphotos = false;
$site_close_lastphotos = get_config("diabook", "close_lastphotos" );
if (local_user()) {$close_lastphotos = get_pconfig(local_user(), "diabook", "close_lastphotos");}
if ($close_lastphotos===false) $close_lastphotos=$site_close_lastphotos;
if ($close_lastphotos===false) $close_lastphotos="0";

$close_lastlikes = false;
$site_close_lastlikes = get_config("diabook", "close_lastlikes" );
if (local_user()) {$close_lastlikes = get_pconfig(local_user(), "diabook", "close_lastlikes");}
if ($close_lastlikes===false) $close_lastlikes=$site_close_lastlikes;
if ($close_lastlikes===false) $close_lastlikes="0";

$close_twitter = false;
$site_close_twitter = get_config("diabook", "close_twitter" );
if (local_user()) {$close_twitter = get_pconfig(local_user(), "diabook", "close_twitter");}
if ($close_twitter===false) $close_twitter=$site_close_twitter;
if ($close_twitter===false) $close_twitter="1";

$close_mapquery = false;
$site_close_mapquery = get_config("diabook", "close_mapquery" );
if (local_user()) {$close_mapquery = get_pconfig(local_user(), "diabook", "close_mapquery");}
if ($close_mapquery===false) $close_mapquery=$site_close_mapquery;
if ($close_mapquery===false) $close_mapquery="1";

//get resolution (wide/normal)
$resolution=false;
$resolution = get_pconfig(local_user(), "diabook", "resolution");
if ($resolution===false) $resolution="normal";

//Add META viewport tag respecting the resolution to header for tablets
if ($resolution=="wide") {
  $a->page['htmlhead'] .= '<meta name="viewport" content="width=1200" />';
} else {
  $a->page['htmlhead'] .= '<meta name="viewport" content="width=980" />';
}
//get colour-scheme
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

	
	//build personal menue at lefthand-col (id="profile_side") and boxes at right-hand-col at networkpages
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
	
	$ccCookie = $close_pages + $close_mapquery + $close_profiles + $close_helpers + $close_services + $close_friends + $close_twitter + $close_lastusers + $close_lastphotos + $close_lastlikes;
	//if all boxes closed, dont build right-hand-col and dont use special css
	if($ccCookie != "10") {
	// COMMUNITY
	diabook_community_info();

	// CUSTOM CSS
	if($resolution == "normal") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-network.css";}
	if($resolution == "wide") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-network-wide.css";}
	}
	}



	//build boxes at right_aside at profile pages
	if ($a->argv[0].$a->argv[1] === "profile".$a->user['nickname']){
	if($ccCookie != "10") {
	// COMMUNITY
	diabook_community_info();
	
	// CUSTOM CSS
	if($resolution == "normal") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-profile.css";}
	if($resolution == "wide") {$cssFile = $a->get_baseurl($ssl_state)."/view/theme/diabook".$color_path."style-profile-wide.css";}
	
	}
	}
	
	//write js-scripts to the head-section:
	//load jquery.cookie.js
	$cookieJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.cookie.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s"></script>', $cookieJS);
	//load jquery.ae.image.resize.js
	$imageresizeJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.ae.image.resize.min.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $imageresizeJS);
	//load jquery.ui.js
	if($ccCookie != "10") {
	$jqueryuiJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery-ui-1.8.20.custom.min.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $jqueryuiJS);
	$jqueryuicssJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/jquery-ui-1.8.20.custom.css";
	$a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $jqueryuicssJS);
	}	
	//load jquery.twitter.search.js
	if($close_twitter != "1") {
	$twitterJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.twitter.search.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $twitterJS);
	}
	//load jquery.mapquery.js
	if($close_mapquery != "1") {
	$mqtmplJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.tmpl.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $mqtmplJS);
	$mapqueryJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.mapquery.core.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $mapqueryJS);
	$openlayersJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/OpenLayers.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $openlayersJS);
	$mqmouseposJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.mapquery.mqMousePosition.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $mqmouseposJS);
	$mousewheelJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.mousewheel.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $mousewheelJS);
   $mqlegendJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.mapquery.legend.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $mqlegendJS);
	$mqlayermanagerJS = $a->get_baseurl($ssl_state)."/view/theme/diabook/js/jquery.mapquery.mqLayerManager.js";
	$a->page['htmlhead'] .= sprintf('<script type="text/javascript" src="%s" ></script>', $mqlayermanagerJS);
	}
	
	$a->page['htmlhead'] .= '
	<script>
	 $(function() {
		$("a.lightbox").fancybox(); // Select all links with lightbox class
	 	$("a#twittersettings-link").fancybox({onClosed: function() { $("#twittersettings").attr("style","display: none;");}} ); 
	   $("a#mapcontrol-link").fancybox({onClosed: function() { $("#mapcontrol").attr("style","display: none;");}} ); 
	   $("a#closeicon").fancybox({onClosed: function() { $("#boxsettings").attr("style","display: none;");}} ); 	 
	 	});
	   
	 $(window).load(function() {
		var footer_top = $(document).height() - 30;
		$("div#footerbox").attr("style", "border-top: 1px solid #D2D2D2; width: 70%;right: 15%;position: absolute;top:"+footer_top+"px;");
	 });
	</script>';
	
	//check if mapquerybox is active and print
	if($close_mapquery != "1") {
		$ELZoom=false;
		$ELPosX=false;
		$ELPosy=false;
		$site_ELZoom = get_config("diabook", "ELZoom" );
		$site_ELPosX = get_config("diabook", "ELPosX" );
		$site_ELPosY = get_config("diabook", "ELPosY" );
		$ELZoom = get_pconfig(local_user(), "diabook", "ELZoom");
		$ELPosX = get_pconfig(local_user(), "diabook", "ELPosX");
		$ELPosY = get_pconfig(local_user(), "diabook", "ELPosY");
		if ($ELZoom===false) $ELZoom=$site_ELZoom;
		if ($ELPosX===false) $ELPosX=$site_ELPosX;
		if ($ELPosY===false) $ELPosY=$site_ELPosY;
		if ($ELZoom===false) $ELZoom="0";	
		if ($ELPosX===false) $ELPosX="0";		
		if ($ELPosY===false) $ELPosY="0";			
		$a->page['htmlhead'] .= '
		<script>
		
    $(function() {
    $("#map").mapQuery({
        layers:[{         //add layers to your map; you need to define at least one to be able to see anything on the map
            type:"osm"  //add a layer of the type osm (OpenStreetMap)
            }],
        center:({zoom:'.$ELZoom.',position:['.$ELPosX.','.$ELPosY.']}),
       });
    
    });
    	 
    function open_mapcontrol() {
		$("div#mapcontrol").attr("style","display: block;width:900px;height:900px;");
		$("#map2").mapQuery({
			layers:[{type:"osm", label:"OpenStreetMap" },
					  {type:"wms", label:"Population density 2010", legend:{url:"http://mapserver.edugis.nl/cgi-bin/mapserv?map=maps/edugis/cache/population.map&version=1.1.1&service=WMS&request=GetLegendGraphic&layer=Bevolkingsdichtheid_2010&format=image/png"}, url:"http://t1.edugis.nl/tiles/tilecache.py?map=maps/edugis/cache/population.map", 
					  layers:"Bevolkingsdichtheid_2010" },
					  {type:"wms", 
						  label:"OpenLayers WMS", 
						  url:"http://labs.metacarta.com/wms/vmap0", 
						  layers:"basic" }],			
			center:({zoom:'.$ELZoom.',position:['.$ELPosX.','.$ELPosY.']})}); 
									
		$("#mouseposition").mqMousePosition({
        map: "#map2",
        x:"",
        y:"",
        precision:4
     		}); 
     		
     	$("#layermanager").mqLayerManager({map:"#map2"}); 
     	$( "div#layermanager" ).accordion({header: ".mq-layermanager-element-header"});  
      $(".mq-layermanager-element-content").attr("style", "");     	
     		
     	map = $("#map2").mapQuery().data("mapQuery");
     	textarea = document.getElementById("id_diabook_ELZoom");
    	textarea.value = "'.$ELZoom.'";
		$("#map2").bind("mousewheel", function(event, delta) {
		if (delta > 0 && textarea.value < 18){
			 textarea.value = textarea.value - delta*-1; }
		if (delta < 0 && textarea.value > "0"){
			 textarea.value = textarea.value - delta*-1; }
			});
		};
		</script>';
	}
	//check if twitterbox is active and print
	if($close_twitter != "1") {
		$TSearchTerm=false;
		$site_TSearchTerm = get_config("diabook", "TSearchTerm" );
		$TSearchTerm = get_pconfig(local_user(), "diabook", "TSearchTerm");
		if ($TSearchTerm===false) $TSearchTerm=$site_TSearchTerm;
		if ($TSearchTerm===false) $TSearchTerm="friendica";		
		$a->page['htmlhead'] .= '
		<script>
		$(function() {
		$("#twitter").twitterSearch({    	    
		term: "'.$TSearchTerm.'",
		animInSpeed: 250,
		bird:    false, 
		avatar:  false, 
		colorExterior: "#fff",
		timeout: 10000    	});
		});
		function open_twittersettings() {
		$("div#twittersettings").attr("style","display: block;");
		};
		</script>';}
			
	//check if community_home-plugin is activated and change css.. we need this, that the submit-wrapper doesn't overlay the login-panel if communityhome-plugin is active
	$nametocheck = "communityhome";
	$r = q("select id from addon where name = '%s' and installed = 1", dbesc($nametocheck));
	if(count($r) == "1" && $a->argv[0] === "home" ) {
	
	$a->page['htmlhead'] .= '
	<script>
	$(function() {
	$("div#login-submit-wrapper").attr("style","padding-top: 120px;");
	});
	</script>';	
	}
	//comment-edit-wrapper on photo_view... we need this to workaround a global bug in photoview, where the comment-box is between the last comment the the comment before the last
	if ($a->argv[0].$a->argv[2] === "photos"."image"){
	$a->page['htmlhead'] .= '
	<script>
		$(function(){
		$(".comment-edit-form").css("display","table");
			});
    </script>';
	}
	//restore (only) the order right hand col at settingspage
	if($a->argv[0] === "settings" && local_user()) {
	$a->page['htmlhead'] .= ' 
	<script>
	function restore_boxes(){
	$.cookie("Boxorder",null, { expires: 365, path: "/" });
	alert("Boxorder at right-hand column was restored. Please refresh your browser");
   }
	</script>';}
	
	if ($a->argv[0].$a->argv[1] === "profile".$a->user['nickname'] or $a->argv[0] === "network" && local_user()){
	$a->page['htmlhead'] .= '
	<script>
 	$(function() {
	$(".oembed.photo img").aeImageResize({height: 400, width: 400});
  	});
	</script>';
	
	if($ccCookie != "10") {
	$a->page['htmlhead'] .= '
	<script>
	$("right_aside").ready(function(){
	
	if('.$close_pages.') 
		{
		document.getElementById( "close_pages" ).style.display = "none";
			};
			
	if('.$close_mapquery.') 
		{
		document.getElementById( "close_mapquery" ).style.display = "none";
			};
			
	if('.$close_profiles.') 
		{
		document.getElementById( "close_profiles" ).style.display = "none";
			};
	
	if('.$close_helpers.') 
		{
		document.getElementById( "close_helpers" ).style.display = "none";
			};
			
	if('.$close_services.') 
		{
		document.getElementById( "close_services" ).style.display = "none";
			};
			
	if('.$close_friends.') 
		{
		document.getElementById( "close_friends" ).style.display = "none";
			};
	
	if('.$close_twitter.') 
		{
		document.getElementById( "close_twitter" ).style.display = "none";
			};	
			
	if('.$close_lastusers.') 
		{
		document.getElementById( "close_lastusers" ).style.display = "none";
			};
			
	if('.$close_lastphotos.') 
		{
		document.getElementById( "close_lastphotos" ).style.display = "none";
			};
			
	if('.$close_lastlikes.') 
		{
		document.getElementById( "close_lastlikes" ).style.display = "none";
			};}

	);
 	 
	</script>';}
	}
	//end js scripts

	// custom css
	if (!is_null($cssFile)) $a->page['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
	
	//footer
	$tpl = get_markup_template('footer.tpl');
	$a->page['footer'] .= replace_macros($tpl, array());
	
	//
	js_diabook_footer();
}


 function diabook_community_info() {
	$a = get_app();
	
	$close_pages = false;
	$site_close_pages = get_config("diabook", "close_pages" );
	if (local_user()) {$close_pages = get_pconfig(local_user(), "diabook", "close_pages");}
	if ($close_pages===false) $close_pages=$site_close_pages;
	if ($close_pages===false) $close_pages="1";
	
	$close_profiles = false;
	$site_close_profiles = get_config("diabook", "close_profiles" );
	if (local_user()) {$close_profiles = get_pconfig(local_user(), "diabook", "close_profiles");}
	if ($close_profiles===false) $close_profiles=$site_close_profiles;
	if ($close_profiles===false) $close_profiles="0";
	
	$close_helpers = false;
	$site_close_helpers = get_config("diabook", "close_helpers" );
	if (local_user()) {$close_helpers = get_pconfig(local_user(), "diabook", "close_helpers");}
	if ($close_helpers===false) $close_helpers=$site_close_helpers;
	if ($close_helpers===false) $close_helpers="0";
	
	$close_services = false;
	$site_close_services = get_config("diabook", "close_services" );
	if (local_user()) {$close_services = get_pconfig(local_user(), "diabook", "close_services");}
	if ($close_services===false) $close_services=$site_close_services;
	if ($close_services===false) $close_services="0";
	
	$close_friends = false;
	$site_close_friends = get_config("diabook", "close_friends" );
	if (local_user()) {$close_friends = get_pconfig(local_user(), "diabook", "close_friends");}
	if ($close_friends===false) $close_friends=$site_close_friends;
	if ($close_friends===false) $close_friends="0";
	
	$close_lastusers = false;
	$site_close_lastusers = get_config("diabook", "close_lastusers" );
	if (local_user()) {$close_lastusers = get_pconfig(local_user(), "diabook", "close_lastusers");}
	if ($close_lastusers===false) $close_lastusers=$site_close_lastusers;
	if ($close_lastusers===false) $close_lastusers="0";
	
	$close_lastphotos = false;
	$site_close_lastphotos = get_config("diabook", "close_lastphotos" );
	if (local_user()) {$close_lastphotos = get_pconfig(local_user(), "diabook", "close_lastphotos");}
	if ($close_lastphotos===false) $close_lastphotos=$site_close_lastphotos;
	if ($close_lastphotos===false) $close_lastphotos="0";
	
	$close_lastlikes = false;
	$site_close_lastlikes = get_config("diabook", "close_lastlikes" );
	if (local_user()) {$close_lastlikes = get_pconfig(local_user(), "diabook", "close_lastlikes");}
	if ($close_lastlikes===false) $close_lastlikes=$site_close_lastlikes;
	if ($close_lastlikes===false) $close_lastlikes="0";
	
	$close_twitter = false;
	$site_close_twitter = get_config("diabook", "close_twitter" );
	if (local_user()) {$close_twitter = get_pconfig(local_user(), "diabook", "close_twitter");}
	if ($close_twitter===false) $close_twitter=$site_close_twitter;
	if ($close_twitter===false) $close_twitter="1";
	
	$close_mapquery = false;
	$site_close_mapquery = get_config("diabook", "close_mapquery" );
	if (local_user()) {$close_mapquery = get_pconfig(local_user(), "diabook", "close_mapquery");}
	if ($close_mapquery===false) $close_mapquery=$site_close_mapquery;
	if ($close_mapquery===false) $close_mapquery="1";
	

	// comunity_profiles
	if($close_profiles != "1") {
	$aside['$comunity_profiles_title'] = t('Community Profiles');
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
	if($close_lastusers != "1") {
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
	if($close_lastlikes != "1") {
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
	if($close_lastphotos != "1") {
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
   if($close_friends != "1") {
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
   if($close_pages != "1") {
   if(local_user()) {
   $page = '
			<h3 style="margin-top:0px;">'.t("Community Pages").'<a id="closeicon" href="#boxsettings" onClick="open_boxsettings(); return false;" style="text-decoration:none;" class="icon close_box" title="'.t("Settings").'"></a></h3>
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
	$page .= '</ul></div>';
	//if (sizeof($contacts) > 0)
		$aside['$page'] = $page;	
	}}
  //END Community Page	
  
   //mapquery

  if($close_mapquery != "1") {
   $mapquery = array();
	$mapquery['title'] = Array("", "<a id='mapcontrol-link' href='#mapcontrol' style='text-decoration:none;' onclick='open_mapcontrol(); return false;'>".t('Earth Layers')."</a>", "", "");
	$aside['$mapquery'] = $mapquery;
	$ELZoom = get_pconfig(local_user(), 'diabook', 'ELZoom' );
	$ELPosX = get_pconfig(local_user(), 'diabook', 'ELPosX' );
	$ELPosY = get_pconfig(local_user(), 'diabook', 'ELPosY' );
	$aside['$ELZoom'] = array('diabook_ELZoom', t('Set zoomfactor for Earth Layers'), $ELZoom, '', $ELZoom);
	$aside['$ELPosX'] = array('diabook_ELPosX', t('Set longitude (X) for Earth Layers'), $ELPosX, '', $ELPosX);	
	$aside['$ELPosY'] = array('diabook_ELPosY', t('Set latitude (Y) for Earth Layers'), $ELPosY, '', $ELPosY);	
	if (isset($_POST['diabook-settings-map-sub']) && $_POST['diabook-settings-map-sub']!=''){	
		set_pconfig(local_user(), 'diabook', 'ELZoom', $_POST['diabook_ELZoom']);
		set_pconfig(local_user(), 'diabook', 'ELPosX', $_POST['diabook_ELPosX']);	
		set_pconfig(local_user(), 'diabook', 'ELPosY', $_POST['diabook_ELPosY']);		
		header("Location: network");
		}
	}
   //end mapquery
   		
  //helpers
  if($close_helpers != "1") {
   $helpers = array();
	$helpers['title'] = Array("", t('Help or @NewHere ?'), "", "");
	$aside['$helpers'] = $helpers;
	}
   //end helpers
   //connectable services
   if($close_services != "1") {
   $con_services = array();
	$con_services['title'] = Array("", t('Connect Services'), "", "");
	$aside['$con_services'] = $con_services;
	}
   //end connectable services
   //twitter
   if($close_twitter != "1") {
   $twitter = array();
	$twitter['title'] = Array("", "<a id='twittersettings-link' href='#twittersettings' style='text-decoration:none;' onclick='open_twittersettings(); return false;'>".t('Last Tweets')."</a>", "", "");
	$aside['$twitter'] = $twitter;
	$TSearchTerm = get_pconfig(local_user(), 'diabook', 'TSearchTerm' );
	$aside['$TSearchTerm'] = array('diabook_TSearchTerm', t('Set twitter search term'), $TSearchTerm, '', $TSearchTerm);
	$baseurl = $a->get_baseurl($ssl_state); 
	$aside['$baseurl'] = $baseurl;
	if (isset($_POST['diabook-settings-sub']) && $_POST['diabook-settings-sub']!=''){	
		set_pconfig(local_user(), 'diabook', 'TSearchTerm', $_POST['diabook_TSearchTerm']);	
		header("Location: network");
		}
	}
   //end twitter
   if($ccCookie != "10") {
   $close_pages = get_pconfig(local_user(), 'diabook', 'close_pages' );
	$close_mapquery = get_pconfig(local_user(), 'diabook', 'close_mapquery' );
	$close_profiles = get_pconfig(local_user(), 'diabook', 'close_profiles' );
	$close_helpers = get_pconfig(local_user(), 'diabook', 'close_helpers' );
	$close_services = get_pconfig(local_user(), 'diabook', 'close_services' );
	$close_friends = get_pconfig(local_user(), 'diabook', 'close_friends' );
	$close_twitter = get_pconfig(local_user(), 'diabook', 'close_twitter' );
	$close_lastusers = get_pconfig(local_user(), 'diabook', 'close_lastusers' );
	$close_lastphotos = get_pconfig(local_user(), 'diabook', 'close_lastphotos' );
	$close_lastlikes = get_pconfig(local_user(), 'diabook', 'close_lastlikes' );
	$close_pagesC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_mapqueryC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_profilesC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_helpersC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_servicesC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_friendsC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_twitterC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_lastusersC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_lastphotosC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$close_lastlikesC = array('1'=>t("don't show"),	'0'=>t("show"),);
	$boxsettings['title'] = Array("", t('Show/hide boxes at right-hand column:'), "", "");
	$aside['$boxsettings'] = $boxsettings;
	$aside['$close_pages'] = array('diabook_close_pages', t('Community Pages'), $close_pages, '', $close_pagesC);	
	$aside['$close_mapquery'] = array('diabook_close_mapquery', t('Earth Layers'), $close_mapquery, '', $close_mapqueryC);		
	$aside['$close_profiles'] = array('diabook_close_profiles', t('Community Profiles'), $close_profiles, '', $close_profilesC);		
	$aside['$close_helpers'] = array('diabook_close_helpers', t('Help or @NewHere ?'), $close_helpers, '', $close_helpersC);
	$aside['$close_services'] = array('diabook_close_services', t('Connect Services'), $close_services, '', $close_servicesC);			
	$aside['$close_friends'] = array('diabook_close_friends', t('Find Friends'), $close_friends, '', $close_friendsC);	
	$aside['$close_twitter'] = array('diabook_close_twitter', t('Last Tweets'), $close_twitter, '', $close_twitterC);			
	$aside['$close_lastusers'] = array('diabook_close_lastusers', t('Last users'), $close_lastusers, '', $close_lastusersC);				
	$aside['$close_lastphotos'] = array('diabook_close_lastphotos', t('Last photos'), $close_lastphotos, '', $close_lastphotosC);				
	$aside['$close_lastlikes'] = array('diabook_close_lastlikes', t('Last likes'), $close_lastlikes, '', $close_lastlikesC);		
   $aside['$sub'] = t('Submit');
   $baseurl = $a->get_baseurl($ssl_state); 
   $aside['$baseurl'] = $baseurl;
   if (isset($_POST['diabook-settings-box-sub']) && $_POST['diabook-settings-box-sub']!=''){	
		set_pconfig(local_user(), 'diabook', 'close_pages', $_POST['diabook_close_pages']);
		set_pconfig(local_user(), 'diabook', 'close_mapquery', $_POST['diabook_close_mapquery']);
		set_pconfig(local_user(), 'diabook', 'close_profiles', $_POST['diabook_close_profiles']);
		set_pconfig(local_user(), 'diabook', 'close_helpers', $_POST['diabook_close_helpers']);
		set_pconfig(local_user(), 'diabook', 'close_services', $_POST['diabook_close_services']);
		set_pconfig(local_user(), 'diabook', 'close_friends', $_POST['diabook_close_friends']);
		set_pconfig(local_user(), 'diabook', 'close_twitter', $_POST['diabook_close_twitter']);
		set_pconfig(local_user(), 'diabook', 'close_lastusers', $_POST['diabook_close_lastusers']);
		set_pconfig(local_user(), 'diabook', 'close_lastphotos', $_POST['diabook_close_lastphotos']);
		set_pconfig(local_user(), 'diabook', 'close_lastlikes', $_POST['diabook_close_lastlikes']);	
		}
	}
   $close = t('Settings');
   $aside['$close'] = $close;
   //get_baseurl
   $url = $a->get_baseurl($ssl_state);   
   $aside['$url'] = $url;
	//print right_aside
	$tpl = file_get_contents(dirname(__file__).'/communityhome.tpl');
	$a->page['right_aside'] = replace_macros($tpl, $aside);
	
 }

 function js_diabook_footer() {
	/** @purpose insert stuff in bottom of page
	 */
	$a = get_app();
	$baseurl = $a->get_baseurl($ssl_state);
	$bottom['$baseurl'] = $baseurl;
	$tpl = file_get_contents(dirname(__file__) . '/bottom.tpl');
	$a->page['footer'] = $a->page['footer'].replace_macros($tpl, $bottom);
 }

	
