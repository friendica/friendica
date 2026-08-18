<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when a location is being rendered.
 *
 * Can be used by addons to provide their own location rendering.
 */
final class RenderLocationEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.render_location';

	/**
	 * @internal
	 *
	 * @param string $location
	 * @param string $coord
	 */
	public function __construct(
		private readonly string $location,
		private readonly string $coord,
		private string $html = '',
	) {
		parent::__construct(self::NAME);
	}

	public function getLocation(): string
	{
		return $this->location;
	}

	public function getCoord(): string
	{
		return $this->coord;
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
