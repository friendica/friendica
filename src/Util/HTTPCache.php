<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Util;

/**
 * Shared HTTP cache lifetimes for Friendica's public endpoints.
 */
final class HTTPCache
{
	/**
	 * Three days balances performance and freshness for discovery responses.
	 * Matches the Mastodon cache duration referenced by NodeInfo and Xrd.
	 */
	public const DISCOVERY_MAX_AGE = 259200;

	private function __construct() {}
}
