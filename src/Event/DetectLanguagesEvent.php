<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after the language detection, to allow alternative language detection
 * methods to modify the result.
 */
final class DetectLanguagesEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.detect_languages';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $text,
		private array $detected,
		private readonly int $uriId,
		private readonly int $authorId,
	) {
		parent::__construct(self::NAME);
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function getDetected(): array
	{
		return $this->detected;
	}

	public function setDetected(array $detected): void
	{
		$this->detected = $detected;
	}

	public function getUriId(): int
	{
		return $this->uriId;
	}

	public function getAuthorId(): int
	{
		return $this->authorId;
	}
}
