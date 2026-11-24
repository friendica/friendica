<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Security\PermissionSet\Repository;

use Friendica\Database\Database;
use Friendica\Security\PermissionSet\Collection\PermissionSets;
use Friendica\Security\PermissionSet\Exception\PermissionSetNotFoundException;
use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\FixtureTestCase;
use Friendica\DI;

class PermissionSetTest extends FixtureTestCase
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

	public function testSelectOneByIdPublic(): void
	{
		$permissionSet = $this->repository->selectPublicForUser(1001);

		$this->assertInstanceOf(PermissionSet::class, $permissionSet);
		self::assertEmpty($permissionSet->allow_cid);
		self::assertEmpty($permissionSet->allow_gid);
		self::assertEmpty($permissionSet->deny_cid);
		self::assertEmpty($permissionSet->deny_gid);
		self::assertSame(PermissionSetRepository::PUBLIC, $permissionSet->id);
		self::assertEquals(1001, $permissionSet->uid);
	}

	/**
	 * Test create/update PermissionSets
	 */
	public function testSaving(): void
	{
		$permissionSet = $this->factory->createFromString(1042, '', '<~>');

		$permissionSet = $this->repository->selectOrCreate($permissionSet);

		self::assertNotNull($permissionSet->id);

		$permissionSetSelected = $this->repository->selectOneById($permissionSet->id, 1042);

		self::assertEquals($permissionSet, $permissionSetSelected);

		$newPermissionSet   = $permissionSet->withAllowedContacts(['3001', '3002']);
		$savedPermissionSet = $this->repository->save($newPermissionSet);

		self::assertNotNull($savedPermissionSet->id);
		self::assertNull($newPermissionSet->id);

		$permissionSetSavedSelected = $this->repository->selectOneById($savedPermissionSet->id, 1042);

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
					$actualPermissionSet->uid == $outputPermissionSet->uid
					&& $actualPermissionSet->allow_cid == $outputPermissionSet->allow_cid
					&& $actualPermissionSet->allow_gid == $outputPermissionSet->allow_gid
					&& $actualPermissionSet->deny_cid == $outputPermissionSet->deny_cid
					&& $actualPermissionSet->deny_gid == $outputPermissionSet->deny_gid
				);
			}), 'PermissionSet not found: ' . print_r($outputPermissionSet, true));
		}
	}

	public static function dataSet()
	{
		return [
			'standard' => [
				'group_member'   => [],
				'permissionSets' => [
					[
						'uid'       => 1042,
						'allow_cid' => '<3043>',
						'allow_gid' => '',
						'deny_cid'  => '<3044>',
						'deny_gid'  => '',
					],
					[
						'uid'       => 1042,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
					[
						'uid'       => 1042,
						'allow_cid' => '<3044>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 3043,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, ['3043'], [], ['3044'], []),
							new PermissionSet(1042, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 3044,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], []),
							new PermissionSet(1042, ['3044'], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 3047,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], []),
						]),
					],
				],
			],
			'empty' => [
				'group_member'   => [],
				'permissionSets' => [
					[
						'uid'       => 1042,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 3043,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 3044,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], []),
						]),
					],
					[
						'input' => [
							'cid' => 3047,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], []),
						]),
					],
				],
			],
			'nothing' => [
				'group_member'   => [],
				'permissionSets' => [
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 3043,
							'uid' => 1042,
						],
						'output' => new PermissionSets(),
					],
					[
						'input' => [
							'cid' => 3044,
							'uid' => 1042,
						],
						'output' => new PermissionSets(),
					],
					[
						'input' => [
							'cid' => 3047,
							'uid' => 1042,
						],
						'output' => new PermissionSets(),
					],
				],
			],
			'with_groups' => [
				'group_member' => [
					[
						'id'         => 801,
						'gid'        => 701,
						'contact-id' => 3047,
					],
					[
						'id'         => 802,
						'gid'        => 701,
						'contact-id' => 3042,
					],
					[
						'id'         => 803,
						'gid'        => 702,
						'contact-id' => 3043,
					],
				],
				'permissionSets' => [
					[
						'uid'       => 1042,
						'allow_cid' => '<3043>',
						'allow_gid' => '<703>',
						'deny_cid'  => '<3044>,<3046>',
						'deny_gid'  => '',
					],
					[
						'uid'       => 1042,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '<702>',
					],
					[
						'uid'       => 1042,
						'allow_cid' => '<3044>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '',
					],
					[
						'uid'       => 1042,
						'allow_cid' => '',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '<701>',
					],
					[
						'uid'       => 1042,
						'allow_cid' => '<3045>',
						'allow_gid' => '',
						'deny_cid'  => '',
						'deny_gid'  => '<701><702>',
					],
				],
				'assertions' => [
					[
						'input' => [
							'cid' => 3042,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], ['702']),
						]),
					],
					[
						'input' => [
							'cid' => 3043,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, ['3043'], ['703'], ['3044', '3046'], []),
							new PermissionSet(1042, [], [], [], ['702']),
							new PermissionSet(1042, [], [], [], ['701']),
						]),
					],
					[
						'input' => [
							'cid' => 3044,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], ['702']),
							new PermissionSet(1042, ['3044'], [], [], []),
							new PermissionSet(1042, [], [], [], ['701']),
							new PermissionSet(1042, ['3045'], [], [], ['701', '702']),
						]),
					],
					[
						'input' => [
							'cid' => 3045,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], ['702']),
							new PermissionSet(1042, ['3044'], [], [], []),
							new PermissionSet(1042, [], [], [], ['701']),
							new PermissionSet(1042, ['3045'], [], [], ['701', '702']),
						]),
					],
					[
						'input' => [
							'cid' => 3046,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], ['702']),
							new PermissionSet(1042, [], [], [], ['701']),
						]),
					],
					[
						'input' => [
							'cid' => 3047,
							'uid' => 1042,
						],
						'output' => new PermissionSets([
							new PermissionSet(1042, [], [], [], ['702']),
							new PermissionSet(1042, [], [], [], ['701']),
						]),
					],
				],
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSet')]
	public function testSelectContactId(array $group_member, array $permissionSets, array $assertions): void
	{
		/** @var Database $db */
		$db = $this->dice->create(Database::class);

		foreach ($group_member as $gmember) {
			$db->insert('group_member', $gmember, Database::INSERT_UPDATE);
		}

		foreach ($permissionSets as $inputPermissionSet) {
			$db->insert('permissionset', $inputPermissionSet, Database::INSERT_UPDATE);
		}

		foreach ($assertions as $assertion) {
			$permissionSets = $this->repository->selectByContactId($assertion['input']['cid'], $assertion['input']['uid']);
			self::assertInstanceOf(PermissionSets::class, $permissionSets);
			self::assertEqualPermissionSets($assertion['output'], $permissionSets);
		}
	}

	public function testSelectOneByIdInvalid(): void
	{
		self::expectException(PermissionSetNotFoundException::class);
		self::expectExceptionMessage('PermissionSet with id -1 for user 1042 doesn\'t exist.');

		$this->repository->selectOneById(-1, 1042);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSet')]
	public function testSelectOneById(array $group_member, array $permissionSets, array $assertions): void
	{
		if (count($permissionSets) === 0) {
			self::markTestSkipped('Nothing to assert.');
		}

		/** @var Database $db */
		$db = $this->dice->create(Database::class);

		foreach ($permissionSets as $inputPermissionSet) {
			$db->insert('permissionset', $inputPermissionSet);
			$id = $db->lastInsertId();

			self::assertInstanceOf(PermissionSet::class, $this->repository->selectOneById($id, $inputPermissionSet['uid']));
		}
	}
}
