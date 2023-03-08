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

namespace Friendica\Util;

use Friendica\Core\Hook;

/**
 * Leaflet Map related functions
 */
class Map {
	public static function byCoordinates($coord, $html_mode = 0) {
		$coord = trim($coord);
		$coord = str_replace([',','/','  '],[' ',' ',' '],$coord);
		$arr = ['lat' => trim(substr($coord,0,strpos($coord,' '))), 'lon' => trim(substr($coord,strpos($coord,' ')+1)), 'mode' => $html_mode, 'html' => ''];
		Hook::callAll('generate_map',$arr);
		return ($arr['html']) ? $arr['html'] : $coord;
	}

	public static function byLocation($location, $html_mode = 0) {
		$arr = ['location' => $location, 'mode' => $html_mode, 'html' => ''];
		Hook::callAll('generate_named_map',$arr);
		return ($arr['html']) ? $arr['html'] : $location;
	}

	public static function getCoordinates($location) {
		$arr = ['location' => $location, 'lat' => false, 'lon' => false];
		Hook::callAll('Map::getCoordinates', $arr);
		return $arr;
	}
}
