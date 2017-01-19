<?php

/*
 * Name: Frost
 * Description: Like frosted glass
 * Credits: Navigation icons taken from http://iconza.com. Other icons taken from http://thenounproject.com, including: Like, Dislike, Black Lock, Unlock, Pencil, Tag, Camera, Paperclip (Marie Coons), Folder (Sergio Calcara), Chain-link (Andrew Fortnum), Speaker (Harold Kim), Quotes (Henry Ryder), Video Camera (Anas Ramadan), and Left Arrow, Right Arrow, and Delete X (all three P.J. Onori). All under Attribution (CC BY 3.0). Others from The Noun Project are public domain or No Rights Reserved (CC0).
 * Version: Version 0.4
 * Author: Zach P <techcity@f.shmuz.in>
 * Maintainer: Zach P <techcity@f.shmuz.in>
 */

function frost_init(App $a) {
	$a->videowidth = 400;
	$a->videoheight = 330;
	$a->theme_thread_allow = false;
	set_template_engine($a, 'smarty3');
}

function frost_content_loaded(App $a) {

	// I could do this in style.php, but by having the CSS in a file the browser will cache it,
	// making pages load faster
	if( $a->module === 'home' || $a->module === 'login' || $a->module === 'register' || $a->module === 'lostpass' ) {
		//$a->page['htmlhead'] = str_replace('$stylesheet', App::get_baseurl() . '/view/theme/frost/login-style.css', $a->page['htmlhead']);
		$a->theme['stylesheet'] = App::get_baseurl() . '/view/theme/frost/login-style.css';
	}

	if ( $a->module === 'login' ) {
		$a->page['end'] .= '<script type="text/javascript"> $(document).ready(function() { $("#id_" + window.loginName).focus();} );</script>';
	}

}

function frost_install() {
	register_hook('prepare_body_final', 'view/theme/frost/theme.php', 'frost_item_photo_links');

	logger("installed theme frost");
}

function frost_uninstall() {
	unregister_hook('bbcode', 'view/theme/frost/theme.php', 'frost_bbcode');

	logger("uninstalled theme frost");
}

function frost_item_photo_links(App $a, &$body_info) {
	require_once('include/Photo.php');
	$phototypes = Photo::supportedTypes();

	$occurence = 1;
	$p = bb_find_open_close($body_info['html'], "<a", ">");
	while($p !== false && ($occurence++ < 500)) {
		$link = substr($body_info['html'], $p['start'], $p['end'] - $p['start']);

		$matches = array();
		preg_match("/\/photos\/[\w]+\/image\/([\w]+)/", $link, $matches);
		if($matches) {

			// Replace the link for the photo's page with a direct link to the photo itself
			$newlink = str_replace($matches[0], "/photo/{$matches[1]}", $link);

			// Add a "quiet" parameter to any redir links to prevent the "XX welcomes YY" info boxes
			$newlink = preg_replace("/href=\"([^\"]+)\/redir\/([^\"]+)&url=([^\"]+)\"/", 'href="$1/redir/$2&quiet=1&url=$3"', $newlink);

			 // Having any arguments to the link for Colorbox causes it to fetch base64 code instead of the image
			$newlink = preg_replace("/\/[?&]zrl=([^&\"]+)/", '', $newlink);

			$body_info['html'] = str_replace($link, $newlink, $body_info['html']);

		}

		$p = bb_find_open_close($body_info['html'], "<a", ">", $occurence);
	}
}

