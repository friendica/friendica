<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\Throttle\ValueObject;

/**
 * Limits for one kind of secret, e.g. a login code.
 */
final readonly class Limit
{
	/**
	 * @param string $scope           Separates the counters of different secrets
	 * @param int    $maxFailures     Failed attempts per account within $failureWindow after which no attempt is accepted
	 * @param int    $failureWindow   Time window for counting failed attempts in seconds
	 * @param int    $requestInterval Minimum time between two requests of the same subject in seconds
	 */
	public function __construct(
		public string $scope,
		public int $maxFailures,
		public int $failureWindow,
		public int $requestInterval,
	) {}
}
