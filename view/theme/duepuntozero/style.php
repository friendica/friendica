<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\DI;

if (file_exists("$THEMEPATH/style.css")) {
	echo file_get_contents("$THEMEPATH/style.css");
}

/*
 * This script can be included when the maintenance mode is on, which requires us to avoid any config call
 */
if (DI::mode()->has(\Friendica\App\Mode::MAINTENANCEDISABLED)) {
	$s_colorset = DI::config()->get('duepuntozero', 'colorset');
	$colorset   = DI::pConfig()->get($_REQUEST['puid'] ?? 0, 'duepuntozero', 'colorset', $s_colorset);
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
