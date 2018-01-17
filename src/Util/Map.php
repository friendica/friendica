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
	public static function byCoordinates($coord) {
		$coord = trim($coord);
		$coord = str_replace([',','/','  '],[' ',' ',' '],$coord);
		$arr = ['lat' => trim(substr($coord,0,strpos($coord,' '))), 'lon' => trim(substr($coord,strpos($coord,' ')+1)), 'html' => ''];
		Addon::callHooks('generate_map',$arr);
		return ($arr['html']) ? $arr['html'] : $coord;
	}

	public static function byLocation($location) {
		$arr = ['location' => $location, 'html' => ''];
		Addon::callHooks('generate_named_map',$arr);
		return ($arr['html']) ? $arr['html'] : $location;
	}
}
