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

use Friendica\DI;

/*
 * This script can be included when the maintenance mode is on, which requires us to avoid any config call and
 * use the following hardcoded defaults
 */
$color = 'dark';
$quattro_align = false;
$textarea_font_size = '20';
$post_font_size = '12';

if (DI::mode()->has(\Friendica\App\Mode::MAINTENANCEDISABLED)) {
	$site_color = DI::config()->get("quattro", "color", $color);
	$site_quattro_align = DI::config()->get("quattro", "align", $quattro_align);
	$site_textarea_font_size = DI::config()->get("quattro", "tfs", $textarea_font_size);
	$site_post_font_size = DI::config()->get("quattro", "pfs", $post_font_size);

	$uid = $_REQUEST['puid'] ?? 0;

	$color = DI::pConfig()->get($uid, "quattro", "color", $site_color);
	$quattro_align = DI::pConfig()->get($uid, 'quattro', 'align', $site_quattro_align);
	$textarea_font_size = DI::pConfig()->get($uid, "quattro", "tfs", $site_textarea_font_size);
	$post_font_size = DI::pConfig()->get($uid, "quattro", "pfs", $site_post_font_size);
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
