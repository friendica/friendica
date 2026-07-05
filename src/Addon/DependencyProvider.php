<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Addon;

/**
 * Interface that Addon can provide some dependencies and/or strategies.
 */
interface DependencyProvider
{
	/**
	 * Returns an array of Dice rules.
	 */
	public function provideDependencyRules(): array;

	/**
	 * Returns an array of strategy rules.
	 */
	public function provideStrategyRules(): array;
}
