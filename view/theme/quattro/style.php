<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\DI;

/*
 * This script can be included when the maintenance mode is on, which requires us to avoid any config call and
 * use the following hardcoded defaults
 */
$color              = 'dark';
$quattro_align      = false;
$textarea_font_size = '20';
$post_font_size     = '12';

if (DI::mode()->has(\Friendica\App\Mode::MAINTENANCEDISABLED)) {
	$site_color              = DI::config()->get("quattro", "color", $color);
	$site_quattro_align      = DI::config()->get("quattro", "align", $quattro_align);
	$site_textarea_font_size = DI::config()->get("quattro", "tfs", $textarea_font_size);
	$site_post_font_size     = DI::config()->get("quattro", "pfs", $post_font_size);

	$uid = $_REQUEST['puid'] ?? 0;

	$color              = DI::pConfig()->get($uid, "quattro", "color", $site_color);
	$quattro_align      = DI::pConfig()->get($uid, 'quattro', 'align', $site_quattro_align);
	$textarea_font_size = DI::pConfig()->get($uid, "quattro", "tfs", $site_textarea_font_size);
	$post_font_size     = DI::pConfig()->get($uid, "quattro", "pfs", $site_post_font_size);
}

$color = \Friendica\Util\Strings::sanitizeFilePathItem($color);

if (file_exists("$THEMEPATH/$color/style.css")) {
	echo file_get_contents("$THEMEPATH/$color/style.css");
}


if ($quattro_align == "center") {
	echo "
		html { width: 100%; margin:0px; padding:0px; }
		body {
			margin: 50px auto;
			width: 900px;
		}
	";
}


echo "
	textarea { font-size: ${textarea_font_size}px; }
	.wall-item-comment-wrapper .comment-edit-text-full { font-size: ${textarea_font_size}px; }
	#jot .profile-jot-text:focus { font-size: ${textarea_font_size}px; }
	.wall-item-container .wall-item-content  { font-size: ${post_font_size}px; }
";
