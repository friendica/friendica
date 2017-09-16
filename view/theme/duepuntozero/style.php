<?php
if (file_exists("$THEMEPATH/style.css")){
    echo file_get_contents("$THEMEPATH/style.css");
}
$uid = get_theme_uid();

$s_colorset = get_config('duepuntozero','colorset');
$colorset = get_pconfig( $uid, 'duepuntozero', 'colorset');
if (!x($colorset))
    $colorset = $s_colorset;

if ($colorset) {
    if ($colorset == 'greenzero')
	$setcss = file_get_contents('view/theme/duepuntozero/deriv/greenzero.css');
    if ($colorset == 'purplezero')
	$setcss = file_get_contents('view/theme/duepuntozero/deriv/purplezero.css');
    if ($colorset == 'easterbunny')
	$setcss = file_get_contents('view/theme/duepuntozero/deriv/easterbunny.css');
    if ($colorset == 'darkzero')
	$setcss = file_get_contents('view/theme/duepuntozero/deriv/darkzero.css');
    if ($colorset == 'comix')
	$setcss = file_get_contents('view/theme/duepuntozero/deriv/comix.css');
    if ($colorset == 'slackr')
	$setcss = file_get_contents('view/theme/duepuntozero/deriv/slackr.css');
}

echo $setcss;
