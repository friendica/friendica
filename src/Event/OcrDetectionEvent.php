<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when OCR detection is run on an image.
 *
 * Modifiable: description
 */
final class OcrDetectionEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.ocr_detection';

	/**
	 * @internal
	 *
	 * @param string $img_str      binary image data
	 * @param ?string $description the detected image description
	 */
	public function __construct(
		private readonly string $img_str,
		private ?string $description = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getImgStr(): string
	{
		return $this->img_str;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}
}
