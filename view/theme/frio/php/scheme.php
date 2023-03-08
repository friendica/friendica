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
 * Get info header of the scheme
 *
 * This function parses the header of the schemename.php file for informations like
 * Author, Description and Overwrites. Most of the code comes from the Addon::getInfo()
 * function. We use this to get the variables which get overwritten through the scheme.
 * All color variables which get overwritten through the theme have to be
 * listed (comma separated) in the scheme header under Overwrites:
 * This seems not to be the best solution. We need to investigate further.
 *
 * @param string $scheme Name of the scheme
 * @return array With theme information
 *    'author' => Author Name
 *    'description' => Scheme description
 *    'version' => Scheme version
 *    'overwrites' => Variables which overwriting custom settings
 */

use Friendica\DI;
use Friendica\Util\Strings;

function get_scheme_info($scheme)
{
	$theme = DI::app()->getCurrentTheme();
	$themepath = 'view/theme/' . $theme . '/';
	if (empty($scheme)) {
		$scheme = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'scheme', DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'schema', '---'));
	}

	$scheme = Strings::sanitizeFilePathItem($scheme);

	$info = [
		'name' => $scheme,
		'description' => '',
		'author' => [],
		'version' => '',
		'overwrites' => [],
		'accented' => false,
	];

	if (!is_file($themepath . 'scheme/' . $scheme . '.php')) {
		return $info;
	}

	$f = file_get_contents($themepath . 'scheme/' . $scheme . '.php');

	$r = preg_match('|/\*.*\*/|msU', $f, $m);

	if ($r) {
		$ll = explode("\n", $m[0]);
		foreach ($ll as $l) {
			$l = trim($l, "\t\n\r */");
			if ($l != '') {
				$values = array_map('trim', explode(':', $l, 2));
				if (count($values) < 2) {
					continue;
				}
				list($k, $v) = $values;
				$k = strtolower($k);
				if ($k == 'author') {
					$r = preg_match('|([^<]+)<([^>]+)>|', $v, $m);
					if ($r) {
						$info['author'][] = ['name' => $m[1], 'link' => $m[2]];
					} else {
						$info['author'][] = ['name' => $v];
					}
				} elseif ($k == 'overwrites') {
					$theme_settings = explode(',', str_replace(' ', '', $v));
					foreach ($theme_settings as $key => $value) {
						$info['overwrites'][$value] = true;
					}
				} elseif ($k == 'accented') {
					$info['accented'] = $v && $v != 'false' && $v != 'no';
				} else {
					if (array_key_exists($k, $info)) {
						$info[$k] = $v;
					}
				}
			}
		}
	}

	return $info;
}
