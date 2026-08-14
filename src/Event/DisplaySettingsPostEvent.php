<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired when display settings are saved, to notify addons about the submitted request data.
 */
final class DisplaySettingsPostEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.display_settings_post';

	/**
	 * @internal
	 *
	 * @param array $request The submitted request data
	 */
	public function __construct(
		private readonly array $request,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array The submitted request data
	 */
	public function getRequestArray(): array
	{
		return $this->request;
	}
}
