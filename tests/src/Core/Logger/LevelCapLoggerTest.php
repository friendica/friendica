<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Type\LevelCapLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LevelCapLoggerTest extends TestCase
{
	public static function dataLevels(): array
	{
		return [
			'emergency is capped' => [LogLevel::EMERGENCY, LogLevel::NOTICE],
			'alert is capped'     => [LogLevel::ALERT, LogLevel::NOTICE],
			'critical is capped'  => [LogLevel::CRITICAL, LogLevel::NOTICE],
			'error is capped'     => [LogLevel::ERROR, LogLevel::NOTICE],
			'warning is capped'   => [LogLevel::WARNING, LogLevel::NOTICE],
			'notice is kept'      => [LogLevel::NOTICE, LogLevel::NOTICE],
			'info is kept'        => [LogLevel::INFO, LogLevel::INFO],
			'debug is kept'       => [LogLevel::DEBUG, LogLevel::DEBUG],
		];
	}

	#[DataProvider('dataLevels')]
	public function testLevel(string $level, string $expected): void
	{
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('log')->with($expected, 'message', ['key' => 'value']);

		(new LevelCapLogger($logger))->log($level, 'message', ['key' => 'value']);
	}

	public function testShortcutMethodIsCapped(): void
	{
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('log')->with(LogLevel::NOTICE, 'message', ['key' => 'value']);

		(new LevelCapLogger($logger))->error('message', ['key' => 'value']);
	}
}
