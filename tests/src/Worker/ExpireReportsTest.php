<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Worker;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Moderation\Entity\Report as ReportEntity;
use Friendica\Test\MockedTestCase;
use Friendica\Worker\ExpireReports;
use Psr\Log\LoggerInterface;

class ExpireReportsTest extends MockedTestCase
{
	public function testCleanupExpiredReportsDeletesClosedAndOpenReports(): void
	{
		$database = $this->createMock(Database::class);
		$config   = $this->createMock(IManageConfigValues::class);
		$logger   = $this->createMock(LoggerInterface::class);

		$closedResult = (object) ['status' => 'closed'];
		$openResult   = (object) ['status' => 'open'];

		$config->expects($this->once())
			->method('get')
			->with('system', 'dbclean-expire-limit')
			->willReturn(2);

		$database->expects($this->exactly(2))
			->method('select')
			->willReturnCallback(function (string $table, array $fields, array $condition) use ($closedResult, $openResult) {
				self::assertSame('report', $table);
				self::assertSame(['id'], $fields);
				self::assertSame('`status` = ? AND COALESCE(`edited`, `created`) < ?', $condition[0]);
				self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $condition[2]);

				if ($condition[1] === ReportEntity::STATUS_CLOSED) {
					return $closedResult;
				}

				return $openResult;
			});

		$database->expects($this->exactly(4))
			->method('fetch')
			->willReturnCallback(function ($result) use ($closedResult, $openResult) {
				static $closedCalls = 0;
				static $openCalls   = 0;

				if ($result === $closedResult) {
					return ++$closedCalls === 1 ? ['id' => 11] : false;
				}

				if ($result === $openResult) {
					return ++$openCalls === 1 ? ['id' => 22] : false;
				}

				return false;
			});

		$database->expects($this->exactly(2))
			->method('close')
			->willReturnCallback(function ($result) use ($closedResult, $openResult): bool {
				self::assertTrue($result === $closedResult || $result === $openResult);
				return true;
			});

		$database->expects($this->exactly(6))
			->method('delete')
			->willReturnCallback(function (): bool {
				static $calls  = 0;
				$expectedCalls = [
					['report-rule', ['rid' => 11]],
					['report-post', ['rid' => 11]],
					['report',      ['id' => 11]],
					['report-rule', ['rid' => 22]],
					['report-post', ['rid' => 22]],
					['report',      ['id' => 22]],
				];

				self::assertSame($expectedCalls[$calls][0], func_get_arg(0));
				self::assertSame($expectedCalls[$calls][1], func_get_arg(1));
				$calls++;

				return true;
			});

		$logger->expects($this->exactly(2))
			->method('notice')
			->with('Deleted expired reports', $this->callback(static function (array $context): bool {
				return isset($context['label'], $context['rows']) && !isset($context['pass']) && $context['rows'] === 1;
			}));

		ExpireReports::cleanupExpiredReports($database, $config, $logger);
	}

	public function testCleanupExpiredReportsSkipsWhenLimitDisabled(): void
	{
		$database = $this->createMock(Database::class);
		$config   = $this->createMock(IManageConfigValues::class);
		$logger   = $this->createMock(LoggerInterface::class);

		$config->expects($this->once())
			->method('get')
			->with('system', 'dbclean-expire-limit')
			->willReturn(0);

		$database->expects($this->never())->method('select');
		$database->expects($this->never())->method('fetch');
		$database->expects($this->never())->method('close');
		$database->expects($this->never())->method('delete');
		$logger->expects($this->never())->method('notice');

		ExpireReports::cleanupExpiredReports($database, $config, $logger);
	}
}
