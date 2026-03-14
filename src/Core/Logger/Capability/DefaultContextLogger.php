<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Capability;

/**
 * Add a default context to the logger that will be used for every log entry.
 */
interface DefaultContextLogger
{
	/**
	 * @return array The old context that will be replaced with the new context
	 */
	public function replaceDefaultContext(array $defaultContext): array;
}
