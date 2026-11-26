<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when a map is generated from coordinates, to allow addons to provide the map HTML.
 */
final class GenerateMapEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.generate_map';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $lat,
		private readonly string $lon,
		private readonly int $mode,
		private string $html = '',
	) {
		parent::__construct(self::NAME);
	}

	public function getLatitude(): string
	{
		return $this->lat;
	}

	public function getLongitude(): string
	{
		return $this->lon;
	}

	public function getMode(): int
	{
		return $this->mode;
	}

	public function getHtml(): string
	{
		return $this->html;
	}

	public function setHtml(string $html): void
	{
		$this->html = $html;
	}
}
