<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Protocol\ATProtocol;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Decorates the logger passed to the Jetstream WebSocket connection.
 *
 * The phrity/websocket library logs a dropped connection (remote EOF or a read timeout)
 * as an error, even though Jetstream::listen() already catches and handles both cases by
 * reconnecting. Those two specific, already-handled cases are downgraded to notice here so
 * they don't look like unhandled errors; anything else keeps its original level.
 */
class JetstreamConnectionLogger extends AbstractLogger
{
	public function __construct(private readonly LoggerInterface $logger) {}

	public function log($level, string|Stringable $message, array $context = []): void
	{
		if ($level === LogLevel::ERROR && ($context['scope'] ?? null) === 'connection'
			&& (!empty($context['meta']['eof']) || !empty($context['meta']['timed_out']))) {
			$level = LogLevel::NOTICE;
		}

		$this->logger->log($level, $message, $context);
	}
}
