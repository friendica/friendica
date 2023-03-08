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

namespace Friendica\Test\src\Security\PermissionSet\Repository;

use Friendica\Database\Database;
use Friendica\Security\PermissionSet\Collection\PermissionSets;
use Friendica\Security\PermissionSet\Exception\PermissionSetNotFoundException;
use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\FixtureTest;
use Friendica\DI;

class PermissionSetTest extends FixtureTest
{
	/** @var PermissionSetRepository */
	private $repository;
	/** @var PermissionSetFactory */
	private $factory;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = DI::permissionSet();
		$this->factory    = DI::permissionSetFactory();
	}

	public function testSelectOneByIdPublic()
	{
		$permissionSet = $this->repository->selectPublicForUser(1);

		$this->assertInstanceOf(PermissionSet::class, $permissionSet);
		self::assertEmpty($permissionSet->allow_cid);
		self::assertEmpty($permissionSet->allow_gid);
		self::assertEmpty($permissionSet->deny_cid);
		self::assertEmpty($permissionSet->deny_gid);
		self::assertEmpty(PermissionSetRepository::PUBLIC, $permissionSet->id);
		self::assertEquals(1, $permissionSet->uid);
	}

	/**
	 * Test create/update PermissionSets
	 */
	public function testSaving()
	{
		$permissionSet = $this->factory->createFromString(42, '', '<~>');

		$permissionSet = $this->repository->selectOrCreate($permissionSet);

		self::assertNotNull($permissionSet->id);

		$permissionSetSelected = $this->repository->selectOneById($permissionSet->id, 42);

		self::assertEquals($permissionSet, $permissionSetSelected);

		$newPermissionSet   = $permissionSet->withAllowedContacts(['1', '2']);
		$savedPermissionSet = $this->repository->save($newPermissionSet);

		self::assertNotNull($savedPermissionSet->id);
		self::assertNull($newPermissionSet->id);

		$permissionSetSavedSelected = $this->repository->selectOneById($savedPermissionSet->id, 42);

		self::assertEquals($savedPermissionSet, $permissionSetSavedSelected);
	}

	/**
	 * Asserts that the actual permissionset is equal to the expected permissionset
	 *   --> It skips the "id" fields
	 *
	 * @param PermissionSets $expected
	 * @param PermissionSets $actual
	 */
	public static function assertEqualPermissionSets(PermissionSets $expected, PermissionSets $actual)
	{
		self::assertEquals($expected->count(), $actual->count(), 'PermissionSets not even ' . PHP_EOL . 'expected: ' . print_r($expected, true) . 'actual: ' . print_r($actual, true));

		foreach ($expected as $outputPermissionSet) {
			self::assertCount(1, $actual->filter(function (PermissionSet $actualPermissionSet) use ($outputPermissionSet) {
				return (
					$actualPermissionSet->uid == $outputPermissionSet->uid &&
					$actualPermissionSet->allow_cid == $outputPermissionSet->allow_cid &&
					$actualPermissionSet->allow_gid == $outputPermissionSet->allow_gid &&
					$actualPermissionSet->deny_cid == $outputPermissionSet->deny_cid &&
					$actualPermissionSet->deny_gid == $outputPermissionSet->deny_gid
				);
			}), 'PermissionSet not found: ' . print_r($outputPermissionSet, true));
		}
	}

	public function dataSet()
	{
		return [
			'standard' => [
				'group_member'   => [],
				'permissionSets' => [
					[
						'uid'       => 42,
						'allow_cid' => '<43>',
						'allow_gid' => '',
						'deny_cid'  => '<44>',
						'deny_gid'  => '',
					],
					[
						'uid'       => 42,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
					[
						'uid'       => 42,
						'allow_cid' => '<44>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 43,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [43], [], [44], []),
							new PermissionSet(42, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 44,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
							new PermissionSet(42, [44], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 47,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
				],
			],
			'empty' => [
				'group_member'   => [],
				'permissionSets' => [
					[
						'uid'       => 42,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 43,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 44,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 47,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], []),
						]),
					],
				]
			],
			'nothing' => [
				'group_member'   => [],
				'permissionSets' => [
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 43,
							'uid' => 42,
						],
						'output' => new PermissionSets(),
					],
					[
						'input' => [
							'cid' => 44,
							'uid' => 42,
						],
						'output' => new PermissionSets(),
					],
					[
						'input' => [
							'cid' => 47,
							'uid' => 42,
						],
						'output' => new PermissionSets(),
					],
				]
			],
			'with_groups' => [
				'group_member' => [
					[
						'id'         => 1,
						'gid'        => 1,
						'contact-id' => 47,
					],
					[
						'id'         => 2,
						'gid'        => 1,
						'contact-id' => 42,
					],
					[
						'id'         => 3,
						'gid'        => 2,
						'contact-id' => 43,
					],
				],
				'permissionSets' => [
					[
						'uid'       => 42,
						'allow_cid' => '<43>',
						'allow_gid' => '<3>',
						'deny_cid'  => '<44>,<46>',
						'deny_gid'  => '',
					],
					[
						'uid'       => 42,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '<2>',
					],
					[
						'uid'       => 42,
						'allow_cid' => '<44>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
					[
						'uid'       => 42,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '<1>',
					],
					[
						'uid'       => 42,
						'allow_cid' => '<45>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '<1><2>',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 42,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], [2]),
						]),
					],
					[
						'input' => [
							'cid' => 43,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [43], [3], [44, 46], []),
							new PermissionSet(42, [], [], [], [2]),
							new PermissionSet(42, [], [], [], [1]),
						]),
					],
					[
						'input' => [
							'cid' => 44,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], [2]),
							new PermissionSet(42, [44], [], [], []),
							new PermissionSet(42, [], [], [], [1]),
							new PermissionSet(42, [45], [], [], [1, 2]),
						]),
					],
					[
						'input' => [
							'cid' => 45,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], [2]),
							new PermissionSet(42, [44], [], [], []),
							new PermissionSet(42, [], [], [], [1]),
							new PermissionSet(42, [45], [], [], [1, 2]),
						]),
					],
					[
						'input' => [
							'cid' => 46,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], [2]),
							new PermissionSet(42, [], [], [], [1]),
						]),
					],
					[
						'input' => [
							'cid' => 47,
							'uid' => 42,
						],
						'output' => new PermissionSets([
							new PermissionSet(42, [], [], [], [2]),
							new PermissionSet(42, [], [], [], [1]),
						]),
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataSet
	 */
	public function testSelectContactId(array $group_member, array $inputPermissionSets, array $assertions)
	{
		/** @var Database $db */
		$db = $this->dice->create(Database::class);

		foreach ($group_member as $gmember) {
			$db->insert('group_member', $gmember, true);
		}

		foreach ($inputPermissionSets as $inputPermissionSet) {
			$db->insert('permissionset', $inputPermissionSet, true);
		}

		foreach ($assertions as $assertion) {
			$permissionSets = $this->repository->selectByContactId($assertion['input']['cid'], $assertion['input']['uid']);
			self::assertInstanceOf(PermissionSets::class, $permissionSets);
			self::assertEqualPermissionSets($assertion['output'], $permissionSets);
		}
	}

	public function testSelectOneByIdInvalid()
	{
		self::expectException(PermissionSetNotFoundException::class);
		self::expectExceptionMessage('PermissionSet with id -1 for user 42 doesn\'t exist.');

		$this->repository->selectOneById(-1, 42);
	}

	/**
	 * @dataProvider dataSet
	 */
	public function testSelectOneById(array $group_member, array $inputPermissionSets, array $assertions)
	{
		if (count($inputPermissionSets) === 0) {
			self::markTestSkipped('Nothing to assert.');
		}

		/** @var Database $db */
		$db = $this->dice->create(Database::class);

		foreach ($inputPermissionSets as $inputPermissionSet) {
			$db->insert('permissionset', $inputPermissionSet);
			$id = $db->lastInsertId();

			self::assertInstanceOf(PermissionSet::class, $this->repository->selectOneById($id, $inputPermissionSet['uid']));
		}
	}
}
