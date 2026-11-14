<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Security\Account;

use Friendica\DI;
use Friendica\Security\Account\Factory\Account as AccountFactory;
use Friendica\Security\Account\Repository\Account;
use Friendica\Test\FixtureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;

final class AccountTest extends FixtureTestCase
{
	private const USER_ID  = 42; // Local user from api.fixture.php.
	private const NICKNAME = 'selfcontact';
	private const EMAIL    = 'selfcontact@example.com';

	protected function setUp(): void
	{
		parent::setUp();
		DI::dba()->update('user', ['email' => self::EMAIL], ['uid' => self::USER_ID]);
	}

	private function accounts(): Account
	{
		return new Account(DI::dba(), new AccountFactory(new NullLogger()));
	}

	public function testFindsAccountByNickname(): void
	{
		$account = $this->accounts()->selectByLogin(self::NICKNAME);

		self::assertSame(self::USER_ID, $account?->uid);
		self::assertSame(self::EMAIL, $account->email);
	}

	public function testFindsAccountByEmail(): void
	{
		self::assertSame(self::USER_ID, $this->accounts()->selectByLogin(self::EMAIL)?->uid);
	}

	public function testUnknownLoginReturnsNull(): void
	{
		self::assertNull($this->accounts()->selectByLogin('unknown'));
	}

	public function testEmptyLoginReturnsNull(): void
	{
		DI::dba()->update('user', ['email' => ''], ['uid' => self::USER_ID]);

		self::assertNull($this->accounts()->selectByLogin(''));
	}

	/**
	 * @return array<string, array{string, int}>
	 */
	public static function dataExcludedAccount(): array
	{
		return [
			'not verified' => ['verified', 0],
			'blocked'      => ['blocked', 1],
			'removed'      => ['account_removed', 1],
			'expired'      => ['account_expired', 1],
		];
	}

	#[DataProvider('dataExcludedAccount')]
	public function testExcludedAccountIsNotFound(string $field, int $value): void
	{
		DI::dba()->update('user', [$field => $value], ['uid' => self::USER_ID]);

		self::assertNull($this->accounts()->selectByLogin(self::NICKNAME));
	}
}
