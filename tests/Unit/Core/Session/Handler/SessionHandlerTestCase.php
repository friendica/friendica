<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Session\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

/**
 * The part of the `\SessionHandlerInterface` contract that every session handler has to fulfil, no matter which backend it stores the session in.
 *
 * PHP turns a falsy return value of `write()` into `"session_write_close(): Failed to write session data"`, so a handler must not pass a missing session through as a failure - that was the bug behind PR #16008.
 * Everything that depends on how a specific backend stores a session is tested in the concrete test cases.
 */
abstract class SessionHandlerTestCase extends TestCase
{
	protected const SESSION_ID = 'jd45n0sk39fh3nzau1cbwx01qy';

	/**
	 * A handler whose backend must not be touched at all.
	 */
	abstract protected function handlerWithUnusedBackend(): SessionHandlerInterface;

	/**
	 * A handler whose backend holds no session, so removing it reports that nothing was removed.
	 *
	 * `apcu_delete()` and `Memcached::delete()` return false for a key that isn't there, a `DELETE` finds no matching row.
	 */
	abstract protected function handlerWithoutStoredSession(): SessionHandlerInterface;

	/**
	 * A handler whose backend fails on every single operation.
	 */
	abstract protected function handlerWithBrokenBackend(LoggerInterface $logger): SessionHandlerInterface;

	/**
	 * A backend failure has to end up in the log exactly once; the message itself is backend-specific and stays with the concrete test case.
	 */
	protected function loggerExpectingOneWarning(): LoggerInterface&MockObject
	{
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects(self::once())->method('warning');

		return $logger;
	}

	public function testOpenAndCloseAlwaysSucceed(): void
	{
		$handler = $this->handlerWithUnusedBackend();

		self::assertTrue($handler->open('', 'PHPSESSID'));
		self::assertTrue($handler->close());
	}

	public function testReadWithoutIdReturnsEmptyStringWithoutTouchingTheBackend(): void
	{
		self::assertSame('', $this->handlerWithUnusedBackend()->read(''));
	}

	public function testWriteWithoutIdFailsWithoutTouchingTheBackend(): void
	{
		self::assertFalse($this->handlerWithUnusedBackend()->write('', 'uid|i:1042;'));
	}

	/**
	 * Regression test for PR #16008:
	 * an anonymous request (e.g. `/ping`) gets a session id but never stores anything, so PHP calls `write()` with empty data at shutdown and there is nothing to remove.
	 * `write()` must report success regardless.
	 */
	public function testWriteWithEmptyDataSucceedsWhenNoSessionWasStored(): void
	{
		self::assertTrue($this->handlerWithoutStoredSession()->write(self::SESSION_ID, ''));
	}

	/**
	 * Flip side of the fix above:
	 * a genuinely broken backend is no longer reported to the session layer either, it only shows up as a logged warning.
	 */
	public function testWriteWithEmptyDataSucceedsEvenWhenTheBackendIsBroken(): void
	{
		$handler = $this->handlerWithBrokenBackend($this->loggerExpectingOneWarning());

		self::assertTrue($handler->write(self::SESSION_ID, ''));
	}

	public function testReadReturnsEmptyStringWhenTheBackendIsBroken(): void
	{
		$handler = $this->handlerWithBrokenBackend($this->loggerExpectingOneWarning());

		self::assertSame('', $handler->read(self::SESSION_ID));
	}

	public function testWriteFailsWhenTheBackendIsBroken(): void
	{
		$handler = $this->handlerWithBrokenBackend($this->loggerExpectingOneWarning());

		self::assertFalse($handler->write(self::SESSION_ID, 'uid|i:1042;'));
	}

	public function testDestroyFailsWhenTheBackendIsBroken(): void
	{
		$handler = $this->handlerWithBrokenBackend($this->loggerExpectingOneWarning());

		self::assertFalse($handler->destroy(self::SESSION_ID));
	}
}
