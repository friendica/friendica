<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when a link is being parsed.
 *
 * Can be used by addons to provide their own parsed content.
 */
final class ParseLinkEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.parse_link';

	/**
	 * @internal
	 *
	 * @param string $url
	 * @param string $format
	 */
	public function __construct(
		private readonly string $url,
		private readonly string $format,
		private ?string $text = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getFormat(): string
	{
		return $this->format;
	}

	public function getText(): ?string
	{
		return $this->text;
	}

	public function setText(?string $text): void
	{
		$this->text = $text;
	}
}
