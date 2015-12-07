<?php

if (file_exists("$THEMEPATH//style.css")){
	echo file_get_contents("$THEMEPATH//style.css");
}

$uid = get_theme_uid();

$style = get_pconfig( $uid, 'vier', 'style');

if ($style == "")
	$style = get_config('vier', 'style');

if ($style == "")
	$style = "plus";

if ($style == "flat")
	$stylecss = file_get_contents('view/theme/vier/flat.css');
else if ($style == "netcolour")
	$stylecss = file_get_contents('view/theme/vier/netcolour.css');
else if ($style == "breathe")
	$stylecss = file_get_contents('view/theme/vier/breathe.css');
else if ($style == "plus")
	$stylecss = file_get_contents('view/theme/vier/plus.css');
else if ($style == "dark")
	$stylecss = file_get_contents('view/theme/vier/dark.css');

echo $stylecss;
