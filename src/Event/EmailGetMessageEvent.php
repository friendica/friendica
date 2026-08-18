<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when an email message is fetched, to allow addons to modify the message data.
 */
final class EmailGetMessageEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.email_get_message';

	/**
	 * @internal
	 */
	public function __construct(
		private string $text,
		private string $html,
		private array $item = [],
	) {
		parent::__construct(self::NAME);
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function setText(string $text): void
	{
		$this->text = $text;
	}

	public function getHtml(): string
	{
		return $this->html;
	}

	public function setHtml(string $html): void
	{
		$this->html = $html;
	}

	public function getItemArray(): array
	{
		return $this->item;
	}

	public function setItemArray(array $item): void
	{
		$this->item = $item;
	}
}
