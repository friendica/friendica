<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired before BBCode is converted to HTML.
 *
 * Can be used by addons to modify the BBCode text.
 */
final class BbcodeToHtmlStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.bbcode_to_html_start';

	/**
	 * @internal
	 *
	 * @param string $bbcode2html
	 */
	public function __construct(
		private string $bbcode2html,
	) {
		parent::__construct(self::NAME);
	}

	public function getBbcode2html(): string
	{
		return $this->bbcode2html;
	}

	public function setBbcode2html(string $bbcode2html): void
	{
		$this->bbcode2html = $bbcode2html;
	}
}
