<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core\Logger\Type;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Decorates a logger and caps all messages above notice level to notice.
 *
 * This is meant for third party libraries that log conditions as errors or warnings
 * which the calling code already handles on its own, e.g. by catching the exception.
 * Uses the decorator pattern (https://en.wikipedia.org/wiki/Decorator_pattern)
 *
 * LoggerTrait is used instead of AbstractLogger, so that the introspection still finds
 * the real caller of the logger and not this class.
 */
final readonly class LevelCapLogger implements LoggerInterface
{
	use LoggerTrait;

	private const ABOVE_NOTICE = [
		LogLevel::EMERGENCY,
		LogLevel::ALERT,
		LogLevel::CRITICAL,
		LogLevel::ERROR,
		LogLevel::WARNING,
	];

	public function __construct(private LoggerInterface $logger) {}

	public function log($level, string|Stringable $message, array $context = []): void
	{
		$this->logger->log(in_array($level, self::ABOVE_NOTICE, true) ? LogLevel::NOTICE : $level, $message, $context);
	}
}
