<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\GenerateMapEvent;
use Friendica\DI;

/**
 * Leaflet Map related functions
 */
class Map
{
	public static function byCoordinates($coord, $html_mode = 0): string
	{
		$coord = trim((string) $coord);
		$coord = str_replace([',','/','  '], [' ',' ',' '], $coord);
		$lat   = trim(substr($coord, 0, strpos($coord, ' ')));
		$lon   = trim(substr($coord, strpos($coord, ' ') + 1));
		$event = DI::eventDispatcher()->dispatch(new GenerateMapEvent($lat, $lon, $html_mode));
		return $event->getHtml() ?: $coord;
	}

	public static function byLocation($location, $html_mode = 0)
	{
		$arr = ['location' => $location, 'mode' => $html_mode, 'html' => ''];
		$arr = DI::eventDispatcher()->dispatch(new ArrayFilterEvent(ArrayFilterEvent::GENERATE_NAMED_MAP, $arr))->getArray();
		return $arr['html'] ?: $location;
	}

	public static function getCoordinates($location)
	{
		$arr = ['location' => $location, 'lat' => false, 'lon' => false];
		$arr = DI::eventDispatcher()->dispatch(new ArrayFilterEvent(ArrayFilterEvent::MAP_GET_COORDINATES, $arr))->getArray();
		return $arr;
	}
}
