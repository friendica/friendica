<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security\TwoFactor;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Security\TwoFactor\CodeVerifier;
use PHPUnit\Framework\TestCase;
use PragmaRX\Google2FA\Google2FA;

final class CodeVerifierTest extends TestCase
{
	private const UID = 42;

	private ArrayCache $cache;
	private Google2FA $google2fa;

	/** @var array<int, array<string, mixed>> */
	private array $pConfigValues;

	/** @var bool[] */
	private array $timestepRefreshes;

	protected function setUp(): void
	{
		parent::setUp();

		$this->cache             = new ArrayCache('localhost');
		$this->google2fa         = new Google2FA();
		$this->timestepRefreshes = [];
		$this->pConfigValues     = [self::UID => ['secret' => $this->google2fa->generateSecretKey(32)]];
	}

	private function verifier(?ICanLock $lock = null): CodeVerifier
	{
		$pConfig = $this->createStub(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturnCallback(function (int $uid, string $cat, string $key, $default = null, bool $refresh = false) {
			if ($key === CodeVerifier::LAST_TIMESTEP) {
				$this->timestepRefreshes[] = $refresh;
			}

			return $this->pConfigValues[$uid][$key] ?? $default;
		});
		$pConfig->method('set')->willReturnCallback(function (int $uid, string $cat, string $key, $value): bool {
			$this->pConfigValues[$uid][$key] = $value;
			return true;
		});

		return new CodeVerifier(
			new AttemptLimiter(new Attempts($this->cache), $lock ?? new CacheLock(new ArrayCache('localhost'))),
			$pConfig,
			$this->google2fa,
		);
	}

	private function currentCode(int $uid = self::UID): string
	{
		return $this->google2fa->getCurrentOtp($this->pConfigValues[$uid]['secret']);
	}

	private function wrongCode(): string
	{
		return $this->currentCode() === '000000' ? '000001' : '000000';
	}

	private function failUntilLockedOut(CodeVerifier $verifier): void
	{
		for ($i = 0; $i < CodeVerifier::MAX_FAILURES; $i++) {
			self::assertFalse($verifier->verifyTotp(self::UID, $this->wrongCode()));
		}
	}

	public function testValidCodeIsAccepted(): void
	{
		self::assertTrue($this->verifier()->verifyTotp(self::UID, $this->currentCode()));
	}

	public function testWrongCodeIsRejected(): void
	{
		self::assertFalse($this->verifier()->verifyTotp(self::UID, $this->wrongCode()));
	}

	public function testCodeIsOnlyAcceptedOnceAcrossRequests(): void
	{
		$code = $this->currentCode();

		self::assertTrue($this->verifier()->verifyTotp(self::UID, $code));
		self::assertFalse($this->verifier()->verifyTotp(self::UID, $code));
	}

	public function testNewerCodeIsAcceptedAfterAnOlderOne(): void
	{
		$this->pConfigValues[self::UID][CodeVerifier::LAST_TIMESTEP] = $this->google2fa->getTimestamp() - 5;

		self::assertTrue($this->verifier()->verifyTotp(self::UID, $this->currentCode()));
		self::assertGreaterThanOrEqual($this->google2fa->getTimestamp() - 1, $this->pConfigValues[self::UID][CodeVerifier::LAST_TIMESTEP]);
	}

	public function testLastTimestepBypassesTheConfigCache(): void
	{
		$this->verifier()->verifyTotp(self::UID, $this->currentCode());

		self::assertSame([true], $this->timestepRefreshes);
	}

	public function testValidCodeIsRejectedWhileLockedOut(): void
	{
		$verifier = $this->verifier();
		$this->failUntilLockedOut($verifier);

		self::assertTrue($verifier->isTotpLockedOut(self::UID));
		self::assertFalse($verifier->verifyTotp(self::UID, $this->currentCode()));
	}

	public function testValidCodeResetsTheFailures(): void
	{
		$verifier = $this->verifier();
		for ($i = 0; $i < CodeVerifier::MAX_FAILURES - 1; $i++) {
			$verifier->verifyTotp(self::UID, $this->wrongCode());
		}

		self::assertTrue($verifier->verifyTotp(self::UID, $this->currentCode()));

		$verifier->verifyTotp(self::UID, $this->wrongCode());
		self::assertFalse($verifier->isTotpLockedOut(self::UID));
	}

	public function testLockoutIsPerAccount(): void
	{
		$verifier = $this->verifier();
		$this->failUntilLockedOut($verifier);

		$this->pConfigValues[43] = ['secret' => $this->google2fa->generateSecretKey(32)];

		self::assertTrue($verifier->verifyTotp(43, $this->currentCode(43)));
	}

	public function testTotpFailuresDontLockTheRecoveryCodes(): void
	{
		$verifier = $this->verifier();
		$this->failUntilLockedOut($verifier);

		self::assertFalse($verifier->isRecoveryLockedOut(self::UID));
	}

	public function testFailsClosedWithoutLock(): void
	{
		$lock = $this->createStub(ICanLock::class);
		$lock->method('acquire')->willReturn(false);

		self::assertFalse($this->verifier($lock)->verifyTotp(self::UID, $this->currentCode()));
	}

	public function testAccountWithoutSecretIsRejectedAndNotCounted(): void
	{
		$verifier = $this->verifier();

		self::assertFalse($verifier->verifyTotp(43, '000000'));
		self::assertSame([], $this->cache->getAllKeys('throttle:2fa-totp:'));
	}
}
