<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Model;

use Friendica\Database\DBA;
use Friendica\Model\GServer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GServerTest extends TestCase
{
	private const NOW = '2026-11-14 00:00:00';

	public static function dataAbandoned(): array
	{
		return [
			'dead for seven years' => [
				'created'      => '2019-12-10 15:10:22',
				'last_contact' => '2019-12-19 14:33:11',
				'days'         => 365,
				'expected'     => true,
			],
			'never reached, created long ago' => [
				'created'      => '2019-12-10 15:10:22',
				'last_contact' => DBA::NULL_DATETIME,
				'days'         => 365,
				'expected'     => true,
			],
			'last contact within the limit' => [
				'created'      => '2019-12-10 15:10:22',
				'last_contact' => '2026-06-01 00:00:00',
				'days'         => 365,
				'expected'     => false,
			],
			'never reached, created recently' => [
				'created'      => '2026-11-01 00:00:00',
				'last_contact' => DBA::NULL_DATETIME,
				'days'         => 365,
				'expected'     => false,
			],
			'limit disabled' => [
				'created'      => '2019-12-10 15:10:22',
				'last_contact' => '2019-12-19 14:33:11',
				'days'         => 0,
				'expected'     => false,
			],
		];
	}

	/**
	 * A server that has not answered for longer than the limit must no longer be polled by the cron.
	 */
	#[DataProvider('dataAbandoned')]
	public function testIsAbandoned(string $created, string $last_contact, int $days, bool $expected): void
	{
		$this->assertSame($expected, GServer::isAbandoned($created, $last_contact, $days, self::NOW));
	}
}
