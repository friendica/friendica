<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Security;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Test\FixtureTestCase;

final class PasswordChangeSessionTest extends FixtureTestCase
{
	private const UID      = 42;
	private const PASSWORD = 'Correct-Horse-Battery-77';

	protected function setUp(): void
	{
		parent::setUp();

		DI::config()->set('system', 'disable_password_exposed', true);
		DI::session()->clear();
		DI::pConfig()->delete(self::UID, 'system', User::PASSWORD_GENERATION);
	}

	protected function tearDown(): void
	{
		DI::session()->clear();

		parent::tearDown();
	}

	private function login(): void
	{
		DI::auth()->setForUser(User::getById(self::UID), false, false, false);
	}

	/**
	 * @return bool If the session is still logged in
	 */
	private function nextRequest(): bool
	{
		try {
			DI::auth()->withSession();
		} catch (FoundException) {
		}

		return DI::userSession()->getLocalUserId() === self::UID;
	}

	public function testSessionStaysValidWithoutPasswordChange(): void
	{
		$this->login();

		self::assertTrue($this->nextRequest());
		self::assertTrue($this->nextRequest());
	}

	public function testSessionThatChangedThePasswordStaysValid(): void
	{
		$this->login();
		self::assertTrue(User::updatePassword(self::UID, self::PASSWORD));

		self::assertTrue($this->nextRequest());
	}

	public function testOtherSessionEndsAfterPasswordChange(): void
	{
		$this->login();
		DI::session()->set('2fa', true);
		$other = DI::session()->get(User::PASSWORD_GENERATION);
		User::updatePassword(self::UID, self::PASSWORD);

		// The session of another device still holds the former value
		DI::session()->set(User::PASSWORD_GENERATION, $other);

		self::assertFalse($this->nextRequest());
		self::assertNull(DI::session()->get('2fa'));
	}

	public function testSessionFromBeforeTheUpdateEndsAfterPasswordChange(): void
	{
		$this->login();
		DI::session()->remove(User::PASSWORD_GENERATION);
		self::assertTrue($this->nextRequest());

		User::updatePassword(self::UID, self::PASSWORD);
		DI::session()->remove(User::PASSWORD_GENERATION);

		self::assertFalse($this->nextRequest());
	}

	public function testNewLoginAfterPasswordChangeIsValid(): void
	{
		User::updatePassword(self::UID, self::PASSWORD);

		$this->login();

		self::assertTrue($this->nextRequest());
	}

	public function testRehashOnLoginKeepsTheSessions(): void
	{
		User::updatePassword(self::UID, self::PASSWORD);
		$this->login();
		$generation = DI::pConfig()->get(self::UID, 'system', User::PASSWORD_GENERATION);

		// A login with a hash that is due for a rehash stores the same password with a new hash
		DBA::update('user', ['password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT, ['cost' => 4])], ['uid' => self::UID]);
		User::getIdFromPasswordAuthentication(self::UID, self::PASSWORD);

		self::assertSame($generation, DI::pConfig()->get(self::UID, 'system', User::PASSWORD_GENERATION));
		self::assertTrue($this->nextRequest());
	}
}
