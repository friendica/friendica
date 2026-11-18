<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired after HTML is converted to BBCode.
 *
 * Can be used by addons to modify the BBCode text.
 */
final class HtmlToBbcodeEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.html_to_bbcode_end';

	/**
	 * @internal
	 *
	 * @param string $html2bbcode
	 */
	public function __construct(
		private string $html2bbcode,
	) {
		parent::__construct(self::NAME);
	}

	public function getHtml2bbcode(): string
	{
		return $this->html2bbcode;
	}

	public function setHtml2bbcode(string $html2bbcode): void
	{
		$this->html2bbcode = $html2bbcode;
	}
}
