<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when page info data is being processed.
 *
 * Can be used by addons to modify the page info data.
 */
final class PageInfoEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.page_info';

	/**
	 * @internal
	 *
	 * @param array<string, mixed> $data
	 */
	public function __construct(private array $data)
	{
		parent::__construct(self::NAME);
	}

	/** @return array<string, mixed> */
	public function getDataArray(): array
	{
		return $this->data;
	}

	/** @param array<string, mixed> $data */
	public function setDataArray(array $data): void
	{
		$this->data = $data;
	}
}
