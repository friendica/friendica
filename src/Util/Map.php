<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Core\Hook;

/**
 * Leaflet Map related functions
 */
class Map
{
	public static function byCoordinates($coord, $html_mode = 0)
	{
		$coord = trim($coord);
		$coord = str_replace([',','/','  '], [' ',' ',' '], $coord);
		$arr   = ['lat' => trim(substr($coord, 0, strpos($coord, ' '))), 'lon' => trim(substr($coord, strpos($coord, ' ') + 1)), 'mode' => $html_mode, 'html' => ''];
		Hook::callAll('generate_map', $arr);
		return $arr['html'] ?: $coord;
	}

	public static function byLocation($location, $html_mode = 0)
	{
		$arr = ['location' => $location, 'mode' => $html_mode, 'html' => ''];
		Hook::callAll('generate_named_map', $arr);
		return $arr['html'] ?: $location;
	}

	public static function getCoordinates($location)
	{
		$arr = ['location' => $location, 'lat' => false, 'lon' => false];
		Hook::callAll('Map::getCoordinates', $arr);
		return $arr;
	}
}
