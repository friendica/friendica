<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Security;

use Friendica\Security\BasicAuth;
use Friendica\Test\ApiTestCase;

class BasicAuthTest extends ApiTestCase
{
	/**
	 * Test the api_source() function.
	 *
	 * @return void
	 */
	public function testApiSource(): void
	{
		self::assertEquals('api', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_source() function with a Twidere user agent.
	 *
	 * @return void
	 */
	public function testApiSourceWithTwidere(): void
	{
		$_SERVER['HTTP_USER_AGENT'] = 'Twidere';
		self::assertEquals('Twidere', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_source() function with a GET parameter.
	 *
	 * @return void
	 */
	public function testApiSourceWithGet(): void
	{
		$_REQUEST['source'] = 'source_name';
		self::assertEquals('source_name', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function without any login.
	 */
	public function testApiLoginWithoutLogin(): void
	{
		self::markTestIncomplete('Needs Refactoring of BasicAuth first.');
		/*
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::getCurrentUserID(true);
		*/
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a bad login.
	 */
	public function testApiLoginWithBadLogin(): void
	{
		self::markTestIncomplete('Needs Refactoring of BasicAuth first.');
		/*
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		$_SERVER['PHP_AUTH_USER'] = 'user@server';
		BasicAuth::getCurrentUserID(true);
		*/
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a correct login.
	 */
	public function testApiLoginWithCorrectLogin(): void
	{
		BasicAuth::setCurrentUserID();
		$_SERVER['PHP_AUTH_USER'] = 'Test user';
		$_SERVER['PHP_AUTH_PW']   = 'password';
		self::assertEquals(parent::SELF_USER['id'], BasicAuth::getCurrentUserID(true));
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a remote user.
	 */
	public function testApiLoginWithRemoteUser(): void
	{
		self::markTestIncomplete('Needs Refactoring of BasicAuth first.');
		/*
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		$_SERVER['REDIRECT_REMOTE_USER'] = '123456dXNlcjpwYXNzd29yZA==';
		BasicAuth::getCurrentUserID(true);
		*/
	}
}
