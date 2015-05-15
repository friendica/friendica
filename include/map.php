<?php

/**
 * Leaflet Map related functions
 */
 
 function generate_map($coord) {
	$coord = trim($coord);
	$coord = str_replace(array(',','/','  '),array(' ',' ',' '),$coord);
	$arr = array('lat' => trim(substr($coord,0,strpos($coord,' '))), 'lon' => trim(substr($coord,strpos($coord,' ')+1)), 'html' => '');
	call_hooks('generate_map',$arr);
	return (($arr['html']) ? $arr['html'] : $coord);
}
function generate_named_map($location) {
	$arr = array('location' => $location, 'html' => '');
	call_hooks('generate_named_map',$arr);
	return (($arr['html']) ? $arr['html'] : $location);
}
