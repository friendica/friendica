<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Security\TwoFactor;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\DBA;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Security\TwoFactor\CodeVerifier;
use Friendica\Security\TwoFactor\Model\RecoveryCode;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\DateTimeFormat;
use PragmaRX\Google2FA\Google2FA;

final class RecoveryCodeVerificationTest extends FixtureTestCase
{
	private const UID  = 42;
	private const CODE = 'abcdef-123456';

	private CodeVerifier $verifier;

	protected function setUp(): void
	{
		parent::setUp();

		DBA::insert('2fa_recovery_codes', ['uid' => self::UID, 'code' => self::CODE, 'generated' => DateTimeFormat::utcNow()]);

		$this->verifier = new CodeVerifier(
			new AttemptLimiter(new Attempts(new ArrayCache('localhost')), new CacheLock(new ArrayCache('localhost'))),
			$this->createStub(IManagePersonalConfigValues::class),
			new Google2FA(),
		);
	}

	public function testCodeIsAcceptedAndMarkedAsUsed(): void
	{
		self::assertTrue($this->verifier->useRecoveryCode(self::UID, self::CODE));
		self::assertSame(0, RecoveryCode::countValidForUser(self::UID));
	}

	public function testCodeIsOnlyAcceptedOnce(): void
	{
		self::assertTrue($this->verifier->useRecoveryCode(self::UID, self::CODE));
		self::assertFalse($this->verifier->useRecoveryCode(self::UID, self::CODE));
	}

	public function testCodeOfAnotherAccountIsRejected(): void
	{
		self::assertFalse($this->verifier->useRecoveryCode(43, self::CODE));
		self::assertSame(1, RecoveryCode::countValidForUser(self::UID));
	}

	public function testValidCodeIsRejectedWhileLockedOutAndStaysUnused(): void
	{
		for ($i = 0; $i < CodeVerifier::MAX_FAILURES; $i++) {
			self::assertFalse($this->verifier->useRecoveryCode(self::UID, 'wrong'));
		}

		self::assertTrue($this->verifier->isRecoveryLockedOut(self::UID));
		self::assertFalse($this->verifier->useRecoveryCode(self::UID, self::CODE));
		self::assertSame(1, RecoveryCode::countValidForUser(self::UID));
	}
}
