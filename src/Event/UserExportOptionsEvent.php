<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when the export options shown on the "Export personal data" page are collected, to allow addons to add, change or remove options.
 */
final class UserExportOptionsEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.user_export_options';

	/**
	 * @internal
	 *
	 * @param list<array{0: string, 1: string, 2: string}> $options The export options, each with the link URL, the link text and the help text
	 */
	public function __construct(
		private array $options,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return list<array{0: string, 1: string, 2: string}> The export options
	 */
	public function getOptionsArray(): array
	{
		return $this->options;
	}

	/**
	 * @param list<array{0: string, 1: string, 2: string}> $options The export options
	 */
	public function setOptionsArray(array $options): void
	{
		$this->options = $options;
	}
}
