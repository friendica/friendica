<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Security\PermissionSet\Entity;

use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Test\MockedTestCase;

class PermissionSetTest extends MockedTestCase
{
	public static function dateAllowedContacts()
	{
		return [
			'default' => [
				'id'         => 10,
				'allow_cid'  => ['1', '2'],
				'allow_gid'  => ['3', '4'],
				'deny_cid'   => ['5', '6', '7'],
				'deny_gid'   => ['8'],
				'update_cid' => ['10'],
			],
		];
	}

	/**
	 * Test if the call "withAllowedContacts()" creates a clone
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dateAllowedContacts')]
	public function testWithAllowedContacts(int $id, array $allow_cid, array $allow_gid, array $deny_cid, array $deny_gid, array $update_cid): void
	{
		$permissionSetOrig = new PermissionSet(
			$id,
			$allow_cid,
			$allow_gid,
			$deny_cid,
			$deny_gid,
		);

		$permissionSetNew = $permissionSetOrig->withAllowedContacts($update_cid);

		self::assertNotSame($permissionSetOrig, $permissionSetNew);
		self::assertEquals($update_cid, $permissionSetNew->allow_cid);
		self::assertEquals($allow_cid, $permissionSetOrig->allow_cid);
	}
}
