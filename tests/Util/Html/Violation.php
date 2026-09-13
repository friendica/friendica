<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Util\Html;

/**
 * A single markup problem found by {@see Invariants}.
 */
final readonly class Violation
{
	public function __construct(
		public string $rule,
		public string $location,
		public int $line,
		public string $snippet,
	) {}

	public function where(): string
	{
		return $this->line > 0 ? $this->location . ':' . $this->line : $this->location;
	}
}
