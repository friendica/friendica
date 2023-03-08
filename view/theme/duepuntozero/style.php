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

if (file_exists("$THEMEPATH/style.css")) {
	echo file_get_contents("$THEMEPATH/style.css");
}

/*
 * This script can be included when the maintenance mode is on, which requires us to avoid any config call
 */
if (DI::mode()->has(\Friendica\App\Mode::MAINTENANCEDISABLED)) {
	$s_colorset = DI::config()->get('duepuntozero', 'colorset');
	$colorset = DI::pConfig()->get($_REQUEST['puid'] ?? 0, 'duepuntozero', 'colorset', $s_colorset);
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
