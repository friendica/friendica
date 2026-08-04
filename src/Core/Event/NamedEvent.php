<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Event;

/**
 * Interface for named events.
 *
 * @internal
 */
interface NamedEvent
{
	public function getName(): string;
}
