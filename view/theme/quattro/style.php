<?php
/**
 * @file view/theme/quattro/style.php
 */
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Model\Profile;

$uid = Profile::getThemeUid();

$color = false;
$quattro_align = false;
$site_color = Config::get("quattro", "color", "dark");
$site_quattro_align = Config::get("quattro", "align", false);

if ($uid) {
	$color = PConfig::get($uid, "quattro", "color", false);
	$quattro_align = PConfig::get($uid, 'quattro', 'align', false);
}

if ($color === false) {
	$color = $site_color;
}

if ($quattro_align === false) {
	$quattro_align = $site_quattro_align;
}

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


$textarea_font_size = false;
$post_font_size = false;

$site_textarea_font_size = Config::get("quattro", "tfs", "20");
$site_post_font_size = Config::get("quattro", "pfs", "12");

if ($uid) {
	$textarea_font_size = PConfig::get($uid, "quattro", "tfs", false);
	$post_font_size = PConfig::get($uid, "quattro", "pfs", false);
}

if ($textarea_font_size === false) {
	$textarea_font_size = $site_textarea_font_size;
}
if ($post_font_size === false) {
	$post_font_size = $site_post_font_size;
}

echo "
	textarea { font-size: ${textarea_font_size}px; }
	.wall-item-comment-wrapper .comment-edit-text-full { font-size: ${textarea_font_size}px; }
	#jot .profile-jot-text:focus { font-size: ${textarea_font_size}px; }
	.wall-item-container .wall-item-content  { font-size: ${post_font_size}px; }
";
