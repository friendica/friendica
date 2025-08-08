<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model;

use Friendica\Model\User;
use Friendica\Test\MockedTestCase;

class UserValidateNicknameTest extends MockedTestCase
{
	public function testValidateNickname(): void
	{
		// Adding a test case for a nickname that is already in use (which should return an error) would be a good addition
		$nickname_valid_1   = User::validateNickname("abc");
		$nickname_valid_2   = User::validateNickname("abc_123_abc");
		$nickname_invalid_1 = User::validateNickname("1abc");
		$nickname_invalid_2 = User::validateNickname("q£b");

		self::assertEquals(0, $nickname_valid_1[0]);
		self::assertEquals(0, $nickname_valid_2[0]);
		self::assertEquals(1, $nickname_invalid_1[0]);
		self::assertEquals(2, $nickname_invalid_2[0]);
	}

}
