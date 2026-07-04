<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core;

use Friendica\Core\ACL;
use Friendica\Test\FixtureTestCase;

class ACLTest extends FixtureTestCase
{
	/**
	 * Test the ACL::isValidContact() function.
	 *
	 * @return void
	 */
	public function testCheckAclInput(): void
	{
		$result = ACL::isValidContact('<aclstring>', '42');
		self::assertFalse($result);
	}

	/**
	 * Test the ACL::isValidContact() function with an empty ACL string.
	 *
	 * @return void
	 */
	public function testCheckAclInputWithEmptyAclString(): void
	{
		$result = ACL::isValidContact('', '42');
		self::assertTrue($result);
	}
}
