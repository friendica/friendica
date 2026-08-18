<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after the ACL autocomplete lookup results are collected.
 *
 * Can be used by addons to add, change or remove the lookup results.
 */
final class AclLookupEndEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.acl_lookup_end';

	/**
	 * @internal
	 */
	public function __construct(
		private int $total,
		private int $start,
		private int $count,
		private readonly array $circles,
		private readonly array $contacts,
		private array $items,
		private readonly string $type,
		private readonly string $search,
	) {
		parent::__construct(self::NAME);
	}

	public function getTotal(): int
	{
		return $this->total;
	}

	public function getStart(): int
	{
		return $this->start;
	}

	public function getCount(): int
	{
		return $this->count;
	}

	public function getCircles(): array
	{
		return $this->circles;
	}

	public function getContacts(): array
	{
		return $this->contacts;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getSearch(): string
	{
		return $this->search;
	}

	public function setTotal(int $total): void
	{
		$this->total = $total;
	}

	public function setStart(int $start): void
	{
		$this->start = $start;
	}

	public function setCount(int $count): void
	{
		$this->count = $count;
	}

	public function setItems(array $items): void
	{
		$this->items = $items;
	}
}
