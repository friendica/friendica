<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security\Throttle;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Security\Throttle\ValueObject\Limit;
use PHPUnit\Framework\TestCase;

final class AttemptLimiterTest extends TestCase
{
	private const UID          = 42;
	private const MAX_FAILURES = 3;

	private ArrayCache $cache;
	private Limit $limit;

	protected function setUp(): void
	{
		parent::setUp();
		$this->cache = new ArrayCache('localhost');
		$this->limit = new Limit('test', self::MAX_FAILURES, 3600, 60);
	}

	private function limiter(?ICanLock $lock = null): AttemptLimiter
	{
		return new AttemptLimiter(new Attempts($this->cache), $lock ?? new CacheLock(new ArrayCache('localhost')));
	}

	private function busyLock(): ICanLock
	{
		$lock = $this->createStub(ICanLock::class);
		$lock->method('acquire')->willReturn(false);

		return $lock;
	}

	public function testSecondRequestWithinIntervalIsRejected(): void
	{
		$limiter = $this->limiter();

		self::assertTrue($limiter->tryRequest($this->limit, '192.0.2.1'));
		self::assertFalse($limiter->tryRequest($this->limit, '192.0.2.1'));
		self::assertTrue($limiter->tryRequest($this->limit, '192.0.2.2'));
	}

	public function testScopesAreSeparate(): void
	{
		$limiter = $this->limiter();
		$limiter->tryRequest($this->limit, '192.0.2.1');

		self::assertTrue($limiter->tryRequest(new Limit('other', self::MAX_FAILURES, 3600, 60), '192.0.2.1'));
	}

	public function testSubjectIsNotStoredInPlainText(): void
	{
		$this->limiter()->tryRequest($this->limit, '192.0.2.1');

		self::assertStringNotContainsString('192.0.2.1', implode(' ', $this->cache->getAllKeys()));
	}

	public function testAccountIsLockedOutAfterMaxFailures(): void
	{
		$limiter = $this->limiter();
		for ($i = 0; $i < self::MAX_FAILURES; $i++) {
			self::assertFalse($limiter->attempt($this->limit, self::UID, fn (): bool => false));
		}

		self::assertTrue($limiter->isLockedOut($this->limit, self::UID));
		self::assertFalse($limiter->attempt($this->limit, self::UID, fn (): bool => true));
	}

	public function testCheckDoesntRunWhileLockedOut(): void
	{
		$limiter = $this->limiter();
		for ($i = 0; $i < self::MAX_FAILURES; $i++) {
			$limiter->attempt($this->limit, self::UID, fn (): bool => false);
		}

		$limiter->attempt($this->limit, self::UID, function (): bool {
			self::fail('The check must not run for a locked out account');
		});

		self::assertTrue($limiter->isLockedOut($this->limit, self::UID));
	}

	public function testSuccessResetsFailures(): void
	{
		$limiter = $this->limiter();
		$limiter->attempt($this->limit, self::UID, fn (): bool => false);

		self::assertTrue($limiter->attempt($this->limit, self::UID, fn (): bool => true));
		for ($i = 0; $i < self::MAX_FAILURES - 1; $i++) {
			$limiter->attempt($this->limit, self::UID, fn (): bool => false);
		}
		self::assertFalse($limiter->isLockedOut($this->limit, self::UID));
	}

	public function testFailuresArePerAccount(): void
	{
		$limiter = $this->limiter();
		for ($i = 0; $i < self::MAX_FAILURES; $i++) {
			$limiter->attempt($this->limit, self::UID, fn (): bool => false);
		}

		self::assertFalse($limiter->isLockedOut($this->limit, self::UID + 1));
	}

	public function testAttemptFailsClosedWithoutLock(): void
	{
		self::assertFalse($this->limiter($this->busyLock())->attempt($this->limit, self::UID, fn (): bool => true));
	}

	public function testRequestFailsClosedWithoutLock(): void
	{
		self::assertFalse($this->limiter($this->busyLock())->tryRequest($this->limit, '192.0.2.1'));
	}
}
