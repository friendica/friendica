<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the content of the about page is rendered, to allow addons to add or change the HTML of the about page content.
 */
final class ModAboutContentEvent extends AbstractEvent
{
	public const NAME = 'friendica.html.mod_about_content';

	/**
	 * @internal
	 */
	public function __construct(private string $html)
	{
		parent::__construct(self::NAME);
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
