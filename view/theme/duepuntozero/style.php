<?php
/**
 * @file view/theme/duepuntozero/style.php
 */
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Model\Profile;

if (file_exists("$THEMEPATH/style.css")) {
	echo file_get_contents("$THEMEPATH/style.css");
}

$uid = Profile::getThemeUid();

$s_colorset = Config::get('duepuntozero', 'colorset');
$colorset = PConfig::get($uid, 'duepuntozero', 'colorset');

if (!x($colorset)) {
	$colorset = $s_colorset;
}

$setcss = '';

if ($colorset) {
	if ($colorset == 'greenzero') {
		$setcss = file_get_contents('view/theme/duepuntozero/deriv/greenzero.css');
	}

	if ($colorset == 'purplezero') {
		$setcss = file_get_contents('view/theme/duepuntozero/deriv/purplezero.css');
	}

	if ($colorset == 'easterbunny') {
		$setcss = file_get_contents('view/theme/duepuntozero/deriv/easterbunny.css');
	}

	if ($colorset == 'darkzero') {
		$setcss = file_get_contents('view/theme/duepuntozero/deriv/darkzero.css');
	}

	if ($colorset == 'comix') {
		$setcss = file_get_contents('view/theme/duepuntozero/deriv/comix.css');
	}

	if ($colorset == 'slackr') {
		$setcss = file_get_contents('view/theme/duepuntozero/deriv/slackr.css');
	}
}

echo $setcss;
