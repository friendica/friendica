<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Session\Handler;

use Friendica\Core\Session\Handler\AbstractSessionHandler;
use Friendica\Core\Session\Handler\Database as DatabaseSessionHandler;
use Friendica\Database\Database;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;

/**
 * Unit tests for the database-based session handler.
 *
 * The parent test case covers the shared '\SessionHandlerInterface' contract.
 * Specific to this handler is that whether an `INSERT` or an `UPDATE` is issued depends on the state that `read()` left behind.
 */
class DatabaseTest extends SessionHandlerTestCase
{
	/**
	 * @param array<string, string> $server
	 */
	private function handler(Database $dba, ?LoggerInterface $logger = null, array $server = []): DatabaseSessionHandler
	{
		return new DatabaseSessionHandler($dba, $logger ?? new NullLogger(), $server);
	}

	/**
	 * Mocks the database with a realistic `isResult()` so that the handler sees the same truthiness rules as in production.
	 */
	private function createDatabaseMock(): Database&MockObject
	{
		$dba = $this->createMock(Database::class);
		$dba->method('isResult')->willReturnCallback(fn ($result): bool => is_array($result) && count($result) > 0);

		return $dba;
	}

	/**
	 * Records the persisting calls instead of asserting on them inline: the handler catches every `\Exception`, which would swallow a failed mock expectation and report it as an unrelated `write()` failure.
	 *
	 * @param array<int, array{method: string, table: string, fields: array<string, mixed>, condition: array<int|string, mixed>}> $calls
	 */
	private function recordWrites(Database&MockObject $dba, array &$calls): void
	{
		$dba->method('insert')->willReturnCallback(function (string $table, array $fields) use (&$calls): bool {
			$calls[] = ['method' => 'insert', 'table' => $table, 'fields' => $fields, 'condition' => []];

			return true;
		});

		$dba->method('update')->willReturnCallback(function (string $table, array $fields, array $condition) use (&$calls): bool {
			$calls[] = ['method' => 'update', 'table' => $table, 'fields' => $fields, 'condition' => $condition];

			return true;
		});
	}

	protected function handlerWithUnusedBackend(): SessionHandlerInterface
	{
		$dba = $this->createMock(Database::class);
		$dba->expects(self::never())->method(self::anything());

		return $this->handler($dba);
	}

	protected function handlerWithoutStoredSession(): SessionHandlerInterface
	{
		$dba = $this->createDatabaseMock();
		$dba->method('delete')->willReturn(false);

		return $this->handler($dba);
	}

	protected function handlerWithBrokenBackend(LoggerInterface $logger): SessionHandlerInterface
	{
		$dba = $this->createMock(Database::class);
		$dba->method(self::anything())->willThrowException(new \Exception('database is down'));

		return $this->handler($dba, $logger);
	}

	public function testReadReturnsTheStoredSessionData(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->expects(self::once())
			->method('selectFirst')
			->with('session', ['data'], ['sid' => self::SESSION_ID])
			->willReturn(['data' => 'authenticated|b:1;uid|i:1042;']);

		self::assertSame('authenticated|b:1;uid|i:1042;', $this->handler($dba)->read(self::SESSION_ID));
	}

	public function testReadReturnsEmptyStringForAnUnknownSession(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->method('selectFirst')->willReturn(false);

		self::assertSame('', $this->handler($dba)->read(self::SESSION_ID));
	}

	/**
	 * The request that lost its session is the interesting part of the warning, because a broken session shows up as a failing page rather than as a failing query.
	 */
	public function testReadLogsTheRequestUriWhenTheDatabaseFails(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->method('selectFirst')->willThrowException(new \Exception('database is down'));

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())
			->method('warning')
			->with('Cannot read session.', self::callback(
				fn (array $context): bool => $context['id'] === self::SESSION_ID && $context['uri'] === '/ping',
			));

		$this->handler($dba, $logger, ['REQUEST_URI' => '/ping'])->read(self::SESSION_ID);
	}

	public function testWriteInsertsANewRowWithTheShortDefaultLifetimeWhenTheSessionIsUnknown(): void
	{
		$calls = [];
		$dba   = $this->createDatabaseMock();
		$this->recordWrites($dba, $calls);

		self::assertTrue($this->handler($dba)->write(self::SESSION_ID, 'uid|i:1042;'));

		self::assertSame(['insert'], array_column($calls, 'method'));
		self::assertSame('session', $calls[0]['table']);
		self::assertSame(self::SESSION_ID, $calls[0]['fields']['sid']);
		self::assertSame('uid|i:1042;', $calls[0]['fields']['data']);
		self::assertEqualsWithDelta(time() + 300, $calls[0]['fields']['expire'], 2);
	}

	public function testWriteUpdatesTheExistingRowWithTheFullLifetimeAfterASuccessfulRead(): void
	{
		$calls = [];
		$dba   = $this->createDatabaseMock();
		$dba->method('selectFirst')->willReturn(['data' => 'uid|i:1042;']);
		$this->recordWrites($dba, $calls);

		$handler = $this->handler($dba);
		$handler->read(self::SESSION_ID);

		self::assertTrue($handler->write(self::SESSION_ID, 'uid|i:1042;authenticated|b:1;'));

		self::assertSame(['update'], array_column($calls, 'method'));
		self::assertSame('session', $calls[0]['table']);
		self::assertSame('uid|i:1042;authenticated|b:1;', $calls[0]['fields']['data']);
		self::assertEqualsWithDelta(time() + AbstractSessionHandler::EXPIRE, $calls[0]['fields']['expire'], 2);

		// The row is only touched when data or expiry actually changed
		self::assertSame('`sid` = ? AND (`data` != ? OR `expire` != ?)', $calls[0]['condition'][0]);
		self::assertSame(self::SESSION_ID, $calls[0]['condition'][1]);
		self::assertSame('uid|i:1042;authenticated|b:1;', $calls[0]['condition'][2]);
	}

	/**
	 * The "session exists" flag mirrors the outcome of the last `read()` instead of latching on the first hit, so a session that vanished between two reads is inserted again rather than producing an `UPDATE` that matches no row.
	 */
	public function testWriteFallsBackToInsertWhenALaterReadNoLongerFindsTheSession(): void
	{
		$calls = [];
		$dba   = $this->createDatabaseMock();
		$dba->method('selectFirst')->willReturnOnConsecutiveCalls(['data' => 'uid|i:1042;'], false);
		$this->recordWrites($dba, $calls);

		$handler = $this->handler($dba);
		$handler->read(self::SESSION_ID);
		$handler->read(self::SESSION_ID);

		self::assertTrue($handler->write(self::SESSION_ID, 'uid|i:1042;'));

		self::assertSame(['insert'], array_column($calls, 'method'));
	}

	public function testWriteWithEmptyDataDeletesTheSessionRow(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->expects(self::once())->method('delete')->with('session', ['sid' => self::SESSION_ID])->willReturn(true);
		$dba->expects(self::never())->method('insert');
		$dba->expects(self::never())->method('update');

		self::assertTrue($this->handler($dba)->write(self::SESSION_ID, ''));
	}

	/**
	 * @return array<string, array{bool}>
	 */
	public static function dataDestroyResult(): array
	{
		return [
			'query succeeded' => [true],
			'query failed'    => [false],
		];
	}

	#[DataProvider('dataDestroyResult')]
	public function testDestroyPassesThroughTheDeleteResult(bool $deleted): void
	{
		$dba = $this->createDatabaseMock();
		$dba->expects(self::once())->method('delete')->with('session', ['sid' => self::SESSION_ID])->willReturn($deleted);

		self::assertSame($deleted, $this->handler($dba)->destroy(self::SESSION_ID));
	}

	public function testGarbageCollectionDeletesExpiredSessionsAndReportsNoDeletedRows(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->expects(self::once())
			->method('delete')
			->with('session', self::callback(function (array $condition): bool {
				self::assertSame('`expire` < ?', $condition[0]);
				self::assertEqualsWithDelta(time(), $condition[1], 2);

				return true;
			}))
			->willReturn(true);

		// TODO: the handler cannot report the real number of deleted rows yet, see `Database::gc()`
		self::assertSame(0, $this->handler($dba)->gc(3600));
	}

	public function testGarbageCollectionReturnsFalseWhenTheDeleteFails(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->method('delete')->willReturn(false);

		self::assertFalse($this->handler($dba)->gc(3600));
	}

	public function testGarbageCollectionReturnsFalseAndLogsAWarningWhenTheDatabaseThrows(): void
	{
		$dba = $this->createDatabaseMock();
		$dba->method('delete')->willThrowException(new \Exception('database is down'));

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('warning')->with('Cannot use garbage collector.');

		self::assertFalse($this->handler($dba, $logger)->gc(3600));
	}
}
