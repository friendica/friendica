<?php
/**
 * @file src/Util/Map.php
 */
namespace Friendica\Util;

use Friendica\Core\Addon;

/**
 * Leaflet Map related functions
 */
class Map {
	public static function byCoordinates($coord, $html_mode = 0) {
		$coord = trim($coord);
		$coord = str_replace([',','/','  '],[' ',' ',' '],$coord);
		$arr = ['lat' => trim(substr($coord,0,strpos($coord,' '))), 'lon' => trim(substr($coord,strpos($coord,' ')+1)), 'mode' => $html_mode, 'html' => ''];
		Addon::callHooks('generate_map',$arr);
		return ($arr['html']) ? $arr['html'] : $coord;
	}

	public static function byLocation($location, $html_mode = 0) {
		$arr = ['location' => $location, 'mode' => $html_mode, 'html' => ''];
		Addon::callHooks('generate_named_map',$arr);
		return ($arr['html']) ? $arr['html'] : $location;
	}

	public static function getCoordinates($location) {
		$arr = ['location' => $location, 'lat' => false, 'lon' => false];
		Addon::callHooks('Map::getCoordinates', $arr);
		return $arr;
	}
}
