<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the coordinates of a location are looked up, to allow addons to provide the coordinates.
 */
final class MapGetCoordinatesEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.map_get_coordinates';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $location,
		private ?string $lat = null,
		private ?string $lon = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getLocation(): string
	{
		return $this->location;
	}

	public function getLatitude(): ?string
	{
		return $this->lat;
	}

	public function setLatitude(?string $lat): void
	{
		$this->lat = $lat;
	}

	public function getLongitude(): ?string
	{
		return $this->lon;
	}

	public function setLongitude(?string $lon): void
	{
		$this->lon = $lon;
	}
}
