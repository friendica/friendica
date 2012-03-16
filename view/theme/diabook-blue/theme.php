<?php

/*
 * Name: Diabook-blue
 * Description: Diabook-blue: report bugs and request here: http://pad.toktan.org/p/diabook or contact me : thomas_bierey@friendica.eu
 * Version: 
 * Author: 
 */

$a->theme_info = array(
  'extends' => 'diabook',
);

//fancybox: provide $photo.href to photo_top.tpl to img in org. scale

//profile_side

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

if($is_url = preg_match ("/\bnetwork\b/i", $_SERVER['REQUEST_URI'])) {
$tpl = get_markup_template('profile_side.tpl');

$a->page['aside'] .= replace_macros($tpl, array(
		'$userinfo' => $userinfo,
		'$ps' => $ps,
	));
}
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
 


 $(document).ready(function() {
	$("a.fancy-photo").fancybox(); // Select all links with lightbox class 
	$("a.fancy-album").fancybox();
});



 
 </script>
EOT;
