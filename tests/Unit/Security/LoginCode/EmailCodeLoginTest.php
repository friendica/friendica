<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security\LoginCode;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Security\Account\Repository\Account;
use Friendica\Security\Account\ValueObject\Account as AccountObject;
use Friendica\Security\LoginCode\CodeMail;
use Friendica\Security\LoginCode\EmailCodeLogin;
use Friendica\Security\LoginCode\Factory\PendingCode as PendingCodeFactory;
use Friendica\Security\LoginCode\Repository\PendingCode as PendingCodeRepository;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Security\LoginCode\ValueObject\Ticket;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class EmailCodeLoginTest extends TestCase
{
	private const UID   = 42;
	private const LOGIN = 'alice';
	private const IP    = '192.0.2.1';

	private ArrayCache $cache;
	/** @var string[] */
	private array $sentCodes = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->cache     = new ArrayCache('localhost');
		$this->sentCodes = [];
	}

	private bool $mailWorks = true;

	private function service(?ICanLock $lock = null): EmailCodeLogin
	{
		$accounts = $this->createStub(Account::class);
		$accounts->method('selectByLogin')->willReturnCallback(fn (string $login): ?\Friendica\Security\Account\ValueObject\Account => $login === self::LOGIN
			? new AccountObject(self::UID, 'Alice', self::LOGIN, 'alice@example.com', 'en')
			: null);

		$mail = $this->createStub(CodeMail::class);
		$mail->method('send')->willReturnCallback(function (AccountObject $account, string $code): bool {
			$this->sentCodes[] = $code;
			return $this->mailWorks;
		});

		$factory = new PendingCodeFactory(new NullLogger());

		return new EmailCodeLogin(
			$accounts,
			new PendingCodeRepository($this->cache, $factory),
			new AttemptLimiter(new Attempts($this->cache), $lock ?? new CacheLock(new ArrayCache('localhost'))),
			$mail,
			new NullLogger(),
		);
	}

	/**
	 * Lets the request throttles expire, like waiting REQUEST_INTERVAL seconds.
	 */
	private function waitForNextRequest(): void
	{
		foreach ($this->cache->getAllKeys('throttle:login-code:request:') as $key) {
			$this->cache->delete($key);
		}
	}

	private function wrongCode(string $code): string
	{
		return $code === '000000' ? '000001' : '000000';
	}

	public function testUnknownAccountGetsTicketWithoutMail(): void
	{
		$ticket = $this->service()->request('mallory', self::IP);

		self::assertSame(Ticket::UNKNOWN_ACCOUNT, $ticket->uid);
		self::assertNotSame('', $ticket->nonce);
		self::assertSame([], $this->sentCodes);
	}

	public function testRequestSendsSixDigitCode(): void
	{
		$ticket = $this->service()->request(self::LOGIN, self::IP);

		self::assertSame(self::UID, $ticket->uid);
		self::assertCount(1, $this->sentCodes);
		self::assertMatchesRegularExpression('/^\d{6}$/', $this->sentCodes[0]);
	}

	public function testCodeIsNotStoredInPlainText(): void
	{
		$ticket = $this->service()->request(self::LOGIN, self::IP);
		$stored = $this->cache->get('login-code:' . self::UID . ':' . $ticket->nonce);

		self::assertNotNull($stored);
		self::assertStringNotContainsString($this->sentCodes[0], serialize($stored));
	}

	public function testSameAddressIsThrottled(): void
	{
		$service = $this->service();
		$service->request(self::LOGIN, self::IP);
		$this->cache->delete('throttle:login-code:request:' . hash('sha256', 'uid:' . self::UID));

		$service->request(self::LOGIN, self::IP);

		self::assertCount(1, $this->sentCodes);
	}

	public function testSameAccountIsThrottledAcrossAddresses(): void
	{
		$service = $this->service();
		$service->request(self::LOGIN, self::IP);

		$service->request(self::LOGIN, '198.51.100.7');

		self::assertCount(1, $this->sentCodes);
	}

	public function testThrottledRequestReturnsTicketThatNeverMatches(): void
	{
		$service = $this->service();
		$first   = $service->request(self::LOGIN, self::IP);
		$second  = $service->request(self::LOGIN, '198.51.100.7');

		self::assertSame(self::UID, $second->uid);
		self::assertFalse($service->verify($second, $this->sentCodes[0], self::IP));
		self::assertTrue($service->verify($first, $this->sentCodes[0], self::IP));
	}

	public function testCodeCanOnlyBeUsedOnce(): void
	{
		$service = $this->service();
		$ticket  = $service->request(self::LOGIN, self::IP);

		self::assertTrue($service->verify($ticket, $this->sentCodes[0], self::IP));
		self::assertFalse($service->verify($ticket, $this->sentCodes[0], self::IP));
	}

	public function testCodeIsBoundToTheRequestingTicket(): void
	{
		$service = $this->service();
		$service->request(self::LOGIN, self::IP);

		self::assertFalse($service->verify(new Ticket(self::UID, 'nonce-of-another-session'), $this->sentCodes[0], self::IP));
	}

	public function testCodeIsBoundToTheAccount(): void
	{
		$service = $this->service();
		$ticket  = $service->request(self::LOGIN, self::IP);

		self::assertFalse($service->verify(new Ticket(self::UID + 1, $ticket->nonce), $this->sentCodes[0], self::IP));
	}

	public function testUnknownAccountTicketNeverMatches(): void
	{
		$service = $this->service();
		$ticket  = $service->request('mallory', self::IP);

		self::assertFalse($service->verify($ticket, '123456', self::IP));
	}

	public function testNewCodeReplacesPendingCodeOfTheSameSession(): void
	{
		$service = $this->service();
		$first   = $service->request(self::LOGIN, self::IP);
		$this->waitForNextRequest();
		$second = $service->request(self::LOGIN, self::IP, $first->nonce);

		self::assertFalse($service->verify($second, $this->sentCodes[0], self::IP));
		self::assertTrue($service->verify($second, $this->sentCodes[1], self::IP));
	}

	public function testThrottledRequestOfTheSameSessionKeepsTheMailedCodeValid(): void
	{
		$service = $this->service();
		$first   = $service->request(self::LOGIN, self::IP);
		$second  = $service->request(self::LOGIN, self::IP, $first->nonce);

		self::assertCount(1, $this->sentCodes);
		self::assertTrue($service->verify($second, $this->sentCodes[0], self::IP));
	}

	public function testRequestFromAnotherSessionDoesNotInvalidateThePendingCode(): void
	{
		$service = $this->service();
		$victim  = $service->request(self::LOGIN, self::IP);
		$this->waitForNextRequest();
		$service->request(self::LOGIN, '198.51.100.7');

		self::assertTrue($service->verify($victim, $this->sentCodes[0], self::IP));
	}

	public function testFailureLimitSpansNewCodes(): void
	{
		$service  = $this->service();
		$failures = 0;
		while ($failures < EmailCodeLogin::MAX_FAILURES) {
			$this->waitForNextRequest();
			$ticket = $service->request(self::LOGIN, self::IP);
			$code   = $this->sentCodes[array_key_last($this->sentCodes)];
			for ($i = 0; $i < 3 && $failures < EmailCodeLogin::MAX_FAILURES; $i++, $failures++) {
				self::assertFalse($service->verify($ticket, $this->wrongCode($code), self::IP));
			}
		}

		self::assertFalse($service->verify($ticket, $code, self::IP));
	}

	public function testCodeIsDiscardedIfMailCannotBeSent(): void
	{
		$this->mailWorks = false;
		$service         = $this->service();
		$ticket          = $service->request(self::LOGIN, self::IP);

		self::assertFalse($service->verify($ticket, $this->sentCodes[0], self::IP));
	}

	public function testFailureWindowIsNotExtendedByLaterFailures(): void
	{
		$service = $this->service();
		$ticket  = $service->request(self::LOGIN, self::IP);
		$service->verify($ticket, $this->wrongCode($this->sentCodes[0]), self::IP);

		$key   = 'throttle:login-code:failures:' . self::UID;
		$until = $this->cache->get($key)['until'];

		$service->verify($ticket, $this->wrongCode($this->sentCodes[0]), self::IP);

		self::assertSame($until, $this->cache->get($key)['until']);
		self::assertSame(2, $this->cache->get($key)['count']);
	}

	public function testLockedOutAccountGetsNoNewCode(): void
	{
		$this->cache->set('throttle:login-code:failures:' . self::UID, ['count' => EmailCodeLogin::MAX_FAILURES, 'until' => time() + 3600], 3600);

		$ticket = $this->service()->request(self::LOGIN, self::IP);

		self::assertSame(self::UID, $ticket->uid);
		self::assertSame([], $this->sentCodes);
	}

	public function testSuccessResetsFailures(): void
	{
		$service = $this->service();
		$ticket  = $service->request(self::LOGIN, self::IP);
		$service->verify($ticket, $this->wrongCode($this->sentCodes[0]), self::IP);

		self::assertTrue($service->verify($ticket, $this->sentCodes[0], self::IP));
		self::assertNull($this->cache->get('throttle:login-code:failures:' . self::UID));
	}

	public function testVerifyFailsClosedWithoutLock(): void
	{
		$lock = $this->createStub(ICanLock::class);
		$lock->method('acquire')->willReturn(true);
		$ticket = $this->service($lock)->request(self::LOGIN, self::IP);

		$busy = $this->createStub(ICanLock::class);
		$busy->method('acquire')->willReturn(false);

		self::assertFalse($this->service($busy)->verify($ticket, $this->sentCodes[0], self::IP));
		self::assertIsArray($this->cache->get('login-code:' . self::UID . ':' . $ticket->nonce));
	}
}
