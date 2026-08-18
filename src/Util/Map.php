<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Event\GenerateMapEvent;
use Friendica\Event\GenerateNamedMapEvent;
use Friendica\Event\MapGetCoordinatesEvent;
use Friendica\DI;

/**
 * Leaflet Map related functions
 */
class Map
{
	public static function byCoordinates($coord, $html_mode = 0)
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
		$event = DI::eventDispatcher()->dispatch(new GenerateNamedMapEvent($location, $html_mode));
		return $event->getHtml() ?: $location;
	}

	public static function getCoordinates($location)
	{
		$event = DI::eventDispatcher()->dispatch(new MapGetCoordinatesEvent($location));
		return [
			'location' => $event->getLocation(),
			'lat'      => $event->getLatitude()  ?? false,
			'lon'      => $event->getLongitude() ?? false,
		];
	}
}
