<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Friendica is initialized.
 */
final class InitEvent extends AbstractEvent
{
	public const NAME = 'friendica.init';

	/** @internal */
	public function __construct()
	{
		parent::__construct(self::NAME);
	}
}
