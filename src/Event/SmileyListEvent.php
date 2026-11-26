<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when the smiley list is being built.
 *
 * Can be used by addons to modify the smiley texts and icons.
 */
final class SmileyListEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.smiley_list';

	/**
	 * @internal
	 *
	 * @param array<int, string> $texts
	 * @param array<int, string> $icons
	 */
	public function __construct(
		private array $texts,
		private array $icons,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<int, string> */
	public function getTexts(): array
	{
		return $this->texts;
	}

	/** @param array<int, string> $texts */
	public function setTexts(array $texts): void
	{
		$this->texts = $texts;
	}

	/** @return array<int, string> */
	public function getIcons(): array
	{
		return $this->icons;
	}

	/** @param array<int, string> $icons */
	public function setIcons(array $icons): void
	{
		$this->icons = $icons;
	}
}
