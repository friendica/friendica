<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Session\Handler;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Exception\CachePersistenceException;
use Friendica\Core\Session\Handler\AbstractSessionHandler;
use Friendica\Core\Session\Handler\Cache as CacheSessionHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;

/**
 * Unit tests for the cache-based session handler.
 *
 * The parent test case covers the shared '\SessionHandlerInterface' contract.
 * The trap specific to this handler is `destroy()`: `apcu_delete()` and `Memcached::delete()` return false for a key that isn't there, while Redis and the array cache always return true.
 */
class CacheTest extends SessionHandlerTestCase
{
	private const CACHE_KEY = 'session:' . self::SESSION_ID;

	private function handler(ICanCache $cache, ?LoggerInterface $logger = null): CacheSessionHandler
	{
		return new CacheSessionHandler($cache, $logger ?? new NullLogger());
	}

	protected function handlerWithUnusedBackend(): SessionHandlerInterface
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->expects(self::never())->method(self::anything());

		return $this->handler($cache);
	}

	protected function handlerWithoutStoredSession(): SessionHandlerInterface
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->method('delete')->willReturn(false);

		return $this->handler($cache);
	}

	protected function handlerWithBrokenBackend(LoggerInterface $logger): SessionHandlerInterface
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->method(self::anything())->willThrowException(new CachePersistenceException('cache is down'));

		return $this->handler($cache, $logger);
	}

	public function testReadReturnsTheStoredSessionData(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->expects(self::once())
			->method('get')
			->with(self::CACHE_KEY)
			->willReturn('authenticated|b:1;uid|i:1042;');

		self::assertSame('authenticated|b:1;uid|i:1042;', $this->handler($cache)->read(self::SESSION_ID));
	}

	public function testReadReturnsEmptyStringForAnUnknownSession(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->method('get')->willReturn(null);

		self::assertSame('', $this->handler($cache)->read(self::SESSION_ID));
	}

	/**
	 * Characterization test:
	 * the handler uses `empty()` instead of a null check, so session data that happens to be falsy is reported as a missing session.
	 * Serialized session data never looks like this in practice, but the asymmetry to the database handler is intentional here.
	 */
	public function testReadTreatsFalsySessionDataAsAMissingSession(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->method('get')->willReturn('0');

		self::assertSame('', $this->handler($cache)->read(self::SESSION_ID));
	}

	public function testWriteStoresTheDataUnderThePrefixedKeyWithTheSessionLifetime(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->expects(self::once())
			->method('set')
			->with(self::CACHE_KEY, 'uid|i:1042;', AbstractSessionHandler::EXPIRE)
			->willReturn(true);

		self::assertTrue($this->handler($cache)->write(self::SESSION_ID, 'uid|i:1042;'));
	}

	public function testWriteFailsWhenTheCacheRejectsTheValue(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->method('set')->willReturn(false);

		self::assertFalse($this->handler($cache)->write(self::SESSION_ID, 'uid|i:1042;'));
	}

	public function testWriteWithEmptyDataDropsTheCachedSession(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->expects(self::once())->method('delete')->with(self::CACHE_KEY)->willReturn(true);
		$cache->expects(self::never())->method('set');

		self::assertTrue($this->handler($cache)->write(self::SESSION_ID, ''));
	}

	/**
	 * @return array<string, array{bool}>
	 */
	public static function dataDestroyResult(): array
	{
		return [
			'session was cached'     => [true],
			'session was not cached' => [false],
		];
	}

	#[DataProvider('dataDestroyResult')]
	public function testDestroyPassesThroughTheCacheResult(bool $deleted): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->expects(self::once())->method('delete')->with(self::CACHE_KEY)->willReturn($deleted);

		self::assertSame($deleted, $this->handler($cache)->destroy(self::SESSION_ID));
	}

	public function testGarbageCollectionIsANoOpBecauseTheCacheExpiresOnItsOwn(): void
	{
		self::assertSame(0, $this->handlerWithUnusedBackend()->gc(3600));
	}
}
