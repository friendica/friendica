<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Security\PermissionSet\Factory;

use Friendica\Security\PermissionSet\Factory\PermissionSet;
use Friendica\Test\MockedTest;
use Friendica\Util\ACLFormatter;
use Psr\Log\NullLogger;

class PermissionSetTest extends MockedTest
{
	/** @var PermissionSet */
	protected $permissionSet;

	protected function setUp(): void
	{
		parent::setUp();

		$this->permissionSet = new PermissionSet(new NullLogger(), new ACLFormatter());
	}

	public function dataInput()
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
			]
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
	 *
	 * @dataProvider dataInput
	 */
	public function testCreateFromTableRow(array $input, array $assertion)
	{
		$permissionSet = $this->permissionSet->createFromTableRow($input);

		$this->assertPermissionSet($permissionSet, $assertion);
	}

	/**
	 * Test the createFromString method
	 *
	 * @dataProvider dataInput
	 */
	public function testCreateFromString(array $input, array $assertion)
	{
		$permissionSet = $this->permissionSet->createFromString(
			$input['uid'],
			$input['allow_cid'] ?? '',
			$input['allow_gid'] ?? '',
			$input['deny_cid'] ?? '',
			$input['deny_gid'] ?? ''
		);

		unset($assertion['id']);

		$this->assertPermissionSet($permissionSet, $assertion);
	}
}
