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
 */

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\DI;

/*
 * This script can be included even when the app is in maintenance mode which requires us to avoid any config call
 */

function duepuntozero_init(App $a) {

	Renderer::setActiveTemplateEngine('smarty3');

	$colorset = null;

	if (DI::mode()->has(App\Mode::MAINTENANCEDISABLED)) {
		$colorset = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset');
		if (!$colorset)
			$colorset = DI::config()->get('duepuntozero', 'colorset');          // user setting have priority, then node settings
	}

	if ($colorset) {
		if ($colorset == 'greenzero')
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/greenzero.css" type="text/css" media="screen" />' . "\n";
		if ($colorset == 'purplezero')
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/purplezero.css" type="text/css" media="screen" />' . "\n";
		if ($colorset == 'easterbunny')
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/easterbunny.css" type="text/css" media="screen" />' . "\n";
		if ($colorset == 'darkzero')
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/darkzero.css" type="text/css" media="screen" />' . "\n";
		if ($colorset == 'comix')
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/comix.css" type="text/css" media="screen" />' . "\n";
		if ($colorset == 'slackr')
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/slackr.css" type="text/css" media="screen" />' . "\n";
	}
DI::page()['htmlhead'] .= <<< EOT
<script>
function cmtBbOpen(comment, id) {
	if ($(comment).hasClass('comment-edit-text-full')) {
		$(".comment-edit-bb-" + id).show();
		return true;
	}
	return false;
}
function cmtBbClose(comment, id) {
	return false;
}
$(document).ready(function() {

	$('html').click(function() { $("#nav-notifications-menu" ).hide(); });

	$('.circle-edit-icon').hover(
		function() {
			$(this).addClass('icon'); $(this).removeClass('iconspacer');},
		function() {
			$(this).removeClass('icon'); $(this).addClass('iconspacer');}
	);

	$('.sidebar-circle-element').hover(
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
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getBackgroundColor()
 * @TODO Implement this function
 */
function duepuntozero_get_background_color(int $uid = null)
{
	return null;
}

/**
 * @param int|null $uid
 * @return null
 * @see \Friendica\Core\Theme::getThemeColor()
 * @TODO Implement this function
 */
function duepuntozero_get_theme_color(int $uid = null)
{
	return null;
}
