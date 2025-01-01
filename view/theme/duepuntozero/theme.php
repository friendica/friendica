<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\App\Mode;
use Friendica\AppHelper;
use Friendica\Core\Renderer;
use Friendica\DI;

/*
 * This script can be included even when the app is in maintenance mode which requires us to avoid any config call
 */

function duepuntozero_init(AppHelper $appHelper)
{

	Renderer::setActiveTemplateEngine('smarty3');

	$colorset = null;

	if (DI::mode()->has(Mode::MAINTENANCEDISABLED)) {
		$colorset = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset');
		if (!$colorset) {
			$colorset = DI::config()->get('duepuntozero', 'colorset');
		}          // user setting have priority, then node settings
	}

	if ($colorset) {
		if ($colorset == 'greenzero') {
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/greenzero.css" type="text/css" media="screen" />' . "\n";
		}
		if ($colorset == 'purplezero') {
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/purplezero.css" type="text/css" media="screen" />' . "\n";
		}
		if ($colorset == 'easterbunny') {
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/easterbunny.css" type="text/css" media="screen" />' . "\n";
		}
		if ($colorset == 'darkzero') {
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/darkzero.css" type="text/css" media="screen" />' . "\n";
		}
		if ($colorset == 'comix') {
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/comix.css" type="text/css" media="screen" />' . "\n";
		}
		if ($colorset == 'slackr') {
			DI::page()['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/slackr.css" type="text/css" media="screen" />' . "\n";
		}
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
