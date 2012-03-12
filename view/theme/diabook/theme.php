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
