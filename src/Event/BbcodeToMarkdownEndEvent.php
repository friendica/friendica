<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired after BBCode is converted to Markdown.
 *
 * Can be used by addons to modify the Markdown text.
 */
final class BbcodeToMarkdownEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.bbcode_to_markdown_end';

	/**
	 * @internal
	 *
	 * @param string $bbcode2markdown
	 */
	public function __construct(
		private string $bbcode2markdown,
	) {
		parent::__construct(self::NAME);
	}

	public function getBbcode2markdown(): string
	{
		return $this->bbcode2markdown;
	}

	public function setBbcode2markdown(string $bbcode2markdown): void
	{
		$this->bbcode2markdown = $bbcode2markdown;
	}
}
