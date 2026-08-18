<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when formatting a post for display
 *
 * Can be used by addons to modify the template data of a post.
 */
final class DisplayItemEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.display_item';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $item
	 * @param array<string, mixed> $output
	 */
	public function __construct(
		private readonly array $item,
		private array $output,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getItemArray(): array
	{
		return $this->item;
	}

	/** @return array<string, mixed> */
	public function getTemplateDataArray(): array
	{
		return $this->output;
	}

	/** @param array<string, mixed> $output */
	public function setTemplateDataArray(array $output): void
	{
		$this->output = $output;
	}
}
