<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security;

use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Security\PermissionSet\Collection\PermissionSets;
use Friendica\Security\PermissionSet\Repository\PermissionSet;
use Friendica\Security\PostPermissions;
use PHPUnit\Framework\TestCase;

class PostPermissionsTest extends TestCase
{
	private function permissions(int $localUser, int $remoteContact, array $psids = []): PostPermissions
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('getLocalUserId')->willReturn($localUser);
		$session->method('getRemoteContactID')->willReturn($remoteContact);

		$sets = $this->createStub(PermissionSets::class);
		$sets->method('count')->willReturn(count($psids));
		$sets->method('column')->willReturn($psids);

		$repo = $this->createStub(PermissionSet::class);
		$repo->method('selectByContactId')->willReturn($sets);

		return new PostPermissions($session, $repo);
	}

	public function testOwnerSeesEverything(): void
	{
		self::assertSame('', $this->permissions(42, 0)->getSQL(42));
		self::assertSame([], $this->permissions(42, 0)->getConditionArray(42));
	}

	public function testAnonymousSeesNoPrivatePosts(): void
	{
		self::assertSame(' AND `private` != 1', $this->permissions(0, 0)->getSQL(42));
		self::assertSame(['`private` != ?', 1], $this->permissions(0, 0)->getConditionArray(42));
	}

	public function testRemoteVisitorSeesMatchingPermissionSets(): void
	{
		self::assertSame(' AND (`private` != 1 OR (`private` = 1 AND `wall` AND `psid` IN (3,7)))', $this->permissions(0, 5, [3, 7])->getSQL(42));
	}
}
