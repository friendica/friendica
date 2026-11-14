<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Security;

use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Request;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Session\Type\ArraySession;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User\Cookie;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Security\Authentication;
use Friendica\Test\FixtureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;

final class LoginCodeAuthenticationTest extends FixtureTestCase
{
	private const UID = 42; // Local user from api.fixture.php.

	private UserSession $session;
	private bool $twoFactorEnabled;

	protected function setUp(): void
	{
		parent::setUp();

		$this->session          = new UserSession(new ArraySession());
		$this->twoFactorEnabled = false;

		DI::dba()->update('user', ['login_date' => '2026-01-01 00:00:00'], ['uid' => self::UID]);
	}

	/**
	 * @return string The path the login redirected to
	 */
	private function login(int $uid, string $return_path = ''): string
	{
		$baseUrl = $this->createStub(BaseURL::class);
		$baseUrl->method('__toString')->willReturn('https://friendica.local');
		$baseUrl->method('redirect')->willReturnCallback(function (string $to = ''): void {
			throw new FoundException($to);
		});

		$pConfig = $this->createStub(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturnCallback(fn (int $uid, string $cat, string $key): bool => $cat === '2fa' && $key === 'verified' && $this->twoFactorEnabled);

		$auth = new Authentication(
			$this->createStub(IManageConfigValues::class),
			$this->createStub(Mode::class),
			$baseUrl,
			DI::l10n(),
			DI::dba(),
			new NullLogger(),
			$this->createStub(Cookie::class),
			$this->session,
			$pConfig,
			$this->createStub(AppHelper::class),
			$this->createStub(Request::class),
		);

		try {
			$auth->withLoginCode($uid, false, $return_path);
		} catch (FoundException $redirect) {
			return $redirect->getMessage();
		}

		self::fail('The login did not redirect');
	}

	public function testActiveUserIsLoggedInAndRedirected(): void
	{
		self::assertSame('settings', $this->login(self::UID, 'settings'));
		self::assertSame(self::UID, $this->session->getLocalUserId());
	}

	public function testUnknownUserIsNotLoggedIn(): void
	{
		$this->login(999999);

		self::assertFalse($this->session->getLocalUserId());
	}

	#[DataProvider('inactiveAccounts')]
	public function testInactiveUserIsNotLoggedIn(array $fields): void
	{
		DBA::update('user', $fields, ['uid' => self::UID]);

		$this->login(self::UID, 'settings');

		self::assertFalse($this->session->getLocalUserId());
	}

	/**
	 * @return array<string, array{array<string, int>}>
	 */
	public static function inactiveAccounts(): array
	{
		return [
			'blocked'    => [['blocked' => 1]],
			'unverified' => [['verified' => 0]],
			'expired'    => [['account_expired' => 1]],
			'removed'    => [['account_removed' => 1]],
		];
	}

	public function testTwoFactorIsStillRequired(): void
	{
		$this->twoFactorEnabled = true;

		self::assertSame('2fa', $this->login(self::UID, 'settings'));
	}

	public function testVerificationFromThePreviousSessionIsNotKept(): void
	{
		$this->twoFactorEnabled = true;
		$this->session->set('2fa', 'old-code');

		self::assertSame('2fa', $this->login(self::UID, 'settings'));
	}
}
