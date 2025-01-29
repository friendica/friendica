<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Allow Event listener to modify HTML.
 *
 * @internal
 */
final class HtmlFilterEvent extends Event
{
	public const HEAD = 'friendica.html.head';

	public const FOOTER = 'friendica.html.footer';

	public const PAGE_HEADER = 'friendica.html.page_header';

	public const PAGE_CONTENT_TOP = 'friendica.html.page_content_top';

	public const PAGE_END = 'friendica.html.page_end';

	private string $html;

	public function __construct(string $name, string $html)
	{
		parent::__construct($name);

		$this->html = $html;
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
