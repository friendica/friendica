<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security\LoginCode\Repository;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Security\LoginCode\Factory\PendingCode as PendingCodeFactory;
use Friendica\Security\LoginCode\Repository\PendingCode;
use Friendica\Security\LoginCode\ValueObject\Ticket;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PendingCodeTest extends TestCase
{
	private const UID  = 42;
	private const CODE = '123456';
	private const TTL  = 600;

	private ArrayCache $cache;
	private PendingCodeFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->cache   = new ArrayCache('localhost');
		$this->factory = new PendingCodeFactory(new NullLogger());
	}

	private function repository(): PendingCode
	{
		return new PendingCode($this->cache, $this->factory);
	}

	public function testSavedCodeIsFoundAgain(): void
	{
		$code = (new Ticket(self::UID, 'nonce'))->pendingCodeFor(self::CODE);
		$this->repository()->save($code, self::TTL);

		self::assertTrue($code->equals($this->repository()->selectByTicket(new Ticket(self::UID, 'nonce'))));
	}

	public function testDeletedCodeIsGone(): void
	{
		$this->repository()->save((new Ticket(self::UID, 'nonce'))->pendingCodeFor(self::CODE), self::TTL);
		$this->repository()->deleteByTicket(new Ticket(self::UID, 'nonce'));

		self::assertNull($this->repository()->selectByTicket(new Ticket(self::UID, 'nonce')));
	}

	public function testMalformedEntryIsIgnored(): void
	{
		$this->cache->set('login-code:' . self::UID . ':nonce', ['hash' => 'abc']);

		self::assertNull($this->repository()->selectByTicket(new Ticket(self::UID, 'nonce')));
	}

}
