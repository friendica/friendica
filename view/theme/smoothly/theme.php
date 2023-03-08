<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Name: smoothly
 *
 * BEWARE: currently UNSUPPORTED
 */

/*
 * Name: Smoothly
 * Description: Theme based on Testbubble and optimized for Tablets.
 * Version: Version 2013-05-08
 * Author: Anne Walk, Devlon Duthied
 * Author: Alex <https://red.pixelbits.de/channel/alex>
 * Maintainer: Nomen Nominandum
 * Screenshot: <a href="screenshot.png">Screenshot</a>
 */

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\DI;

/*
 * This script can be included even when the app is in maintenance mode which requires us to avoid any config call
 */

function smoothly_init(App $a) {
	Renderer::setActiveTemplateEngine('smarty3');

	$cssFile = null;
	$ssl_state = false;
	$baseurl = DI::baseUrl();
	DI::page()['htmlhead'] .= <<< EOT

<script>
function cmtBbOpen(id) {
	$(".comment-edit-bb-" + id).show();
}
function cmtBbClose(comment, id) {
	$(".comment-edit-bb-" + id).hide();
}
$(document).ready(function() {

	$('html').click(function() { $("#nav-notifications-menu" ).hide(); });

	$('.group-edit-icon').hover(
		function() {
			$(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

	$('.sidebar-group-element').hover(
		function() {
			id = $(this).attr('id');
			$('#edit-' + id).addClass('icon'); $('#edit-' + id).removeClass('iconspacer');},

		function() {
			id = $(this).attr('id');
			$('#edit-' + id).removeClass('icon');$('#edit-' + id).addClass('iconspacer');}
	);


	$('.savedsearchdrop').hover(
		function() {
			$(this).addClass('drop'); $(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('drop'); $(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

	$('.savedsearchterm').hover(
		function() {
			id = $(this).attr('id');
			$('#drop-' + id).addClass('icon'); 	$('#drop-' + id).addClass('drophide'); $('#drop-' + id).removeClass('iconspacer');},

		function() {
			id = $(this).attr('id');
			$('#drop-' + id).removeClass('icon');$('#drop-' + id).removeClass('drophide'); $('#drop-' + id).addClass('iconspacer');}
	);

});

</script>
EOT;

	/** custom css **/
	if (!is_null($cssFile)) {
		DI::page()['htmlhead'] .= sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $cssFile);
	}

	_js_in_foot();
}

if (! function_exists('_js_in_foot')) {
	function _js_in_foot() {
		/** @purpose insert stuff in bottom of page
		*/
		$ssl_state = false;
		$baseurl = DI::baseUrl();
		$bottom['$baseurl'] = $baseurl;
		$tpl = Renderer::getMarkupTemplate('bottom.tpl');

		return DI::page()['bottom'] = Renderer::replaceMacros($tpl, $bottom);
	}
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getBackgroundColor()
 * @TODO Implement this function
 */
function smoothly_get_background_color(int $uid = null)
{
	return null;
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getThemeColor()
 * @TODO Implement this function
 */
function smoothly_get_theme_color(int $uid = null)
{
	return null;
}
