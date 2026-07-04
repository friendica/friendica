<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Security\PermissionSet\Factory;

use Friendica\Security\PermissionSet\Factory\PermissionSet;
use Friendica\Test\MockedTestCase;
use Friendica\Util\ACLFormatter;
use Psr\Log\NullLogger;

class PermissionSetTest extends MockedTestCase
{
	/** @var PermissionSet */
	protected $permissionSet;

	protected function setUp(): void
	{
		parent::setUp();

		$this->permissionSet = new PermissionSet(new NullLogger(), new ACLFormatter());
	}

	public static function dataInput()
	{
		return [
			'new' => [
				'input' => [
					'uid'       => 12,
					'allow_cid' => '<1>,<2>',
					'allow_gid' => '<3>,<4>',
					'deny_cid'  => '<6>',
					'deny_gid'  => '<8>',
				],
				'assertion' => [
					'id'        => null,
					'uid'       => 12,
					'allow_cid' => ['1', '2'],
					'allow_gid' => ['3', '4'],
					'deny_cid'  => ['6'],
					'deny_gid'  => ['8'],
				],
			],
			'full' => [
				'input' => [
					'id'        => 3,
					'uid'       => 12,
					'allow_cid' => '<1>,<2>',
					'allow_gid' => '<3>,<4>',
					'deny_cid'  => '<6>',
					'deny_gid'  => '<8>',
				],
				'assertion' => [
					'id'        => 3,
					'uid'       => 12,
					'allow_cid' => ['1', '2'],
					'allow_gid' => ['3', '4'],
					'deny_cid'  => ['6'],
					'deny_gid'  => ['8'],
				],
			],
			'mini' => [
				'input' => [
					'id'  => null,
					'uid' => 12,
				],
				'assertion' => [
					'id'        => null,
					'uid'       => 12,
					'allow_cid' => [],
					'allow_gid' => [],
					'deny_cid'  => [],
					'deny_gid'  => [],
				],
			],
			'wrong' => [
				'input' => [
					'id'        => 3,
					'uid'       => 12,
					'allow_cid' => '<1,<2>',
				],
				'assertion' => [
					'id'        => 3,
					'uid'       => 12,
					'allow_cid' => ['2'],
					'allow_gid' => [],
					'deny_cid'  => [],
					'deny_gid'  => [],
				],
			],
		];
	}

	protected function assertPermissionSet(\Friendica\Security\PermissionSet\Entity\PermissionSet $permissionSet, array $assertion)
	{
		self::assertEquals($assertion['id'] ?? null, $permissionSet->id);
		self::assertNotNull($permissionSet->uid);
		self::assertEquals($assertion['uid'], $permissionSet->uid);
		self::assertEquals($assertion['allow_cid'], $permissionSet->allow_cid);
		self::assertEquals($assertion['allow_gid'], $permissionSet->allow_gid);
		self::assertEquals($assertion['deny_cid'], $permissionSet->deny_cid);
		self::assertEquals($assertion['deny_gid'], $permissionSet->deny_gid);
	}

	/**
	 * Test the createFromTableRow method
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataInput')]
	public function testCreateFromTableRow(array $input, array $assertion): void
	{
		$permissionSet = $this->permissionSet->createFromTableRow($input);

		$this->assertPermissionSet($permissionSet, $assertion);
	}

	/**
	 * Test the createFromString method
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataInput')]
	public function testCreateFromString(array $input, array $assertion): void
	{
		$permissionSet = $this->permissionSet->createFromString(
			$input['uid'],
			$input['allow_cid'] ?? '',
			$input['allow_gid'] ?? '',
			$input['deny_cid']  ?? '',
			$input['deny_gid']  ?? '',
		);

		unset($assertion['id']);

		$this->assertPermissionSet($permissionSet, $assertion);
	}
}
