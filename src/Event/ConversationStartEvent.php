<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when rendering a conversation timeline starts
 *
 * Can be used by addons to modify the items of a conversation timeline before rendering.
 */
final class ConversationStartEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.conversation_start';

	/**
	 * @internal
	 *
	 * @param array<int, array<string, mixed>> $items
	 */
	public function __construct(
		private array $items,
		private readonly string $mode,
		private readonly bool $update,
		private readonly bool $preview,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<int, array<string, mixed>> */
	public function getItemsArray(): array
	{
		return $this->items;
	}

	/** @param array<int, array<string, mixed>> $items */
	public function setItemsArray(array $items): void
	{
		$this->items = $items;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function isUpdate(): bool
	{
		return $this->update;
	}

	public function isPreview(): bool
	{
		return $this->preview;
	}
}
