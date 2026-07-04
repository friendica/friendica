<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Profile\ProfileField\Entity;

use Friendica\Profile\ProfileField\Entity\ProfileField;
use Friendica\Profile\ProfileField\Exception\ProfileFieldNotFoundException;
use Friendica\Profile\ProfileField\Exception\UnexpectedPermissionSetException;
use Friendica\Profile\ProfileField\Factory\ProfileField as ProfileFieldFactory;
use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Test\MockedTestCase;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

class ProfileFieldTest extends MockedTestCase
{
	/** @var MockInterface|PermissionSetRepository */
	protected $permissionSetRepository;
	/** @var ProfileFieldFactory */
	protected $profileFieldFactory;
	/** @var MockInterface|PermissionSetFactory */
	protected $permissionSetFactory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->permissionSetRepository = \Mockery::mock(PermissionSetRepository::class);
		$this->permissionSetFactory    = new PermissionSetFactory(new NullLogger(), new ACLFormatter());
		$this->profileFieldFactory     = new ProfileFieldFactory(new NullLogger(), $this->permissionSetFactory);
	}

	public static function dataEntity()
	{
		return [
			'default' => [
				'uid'           => 23,
				'order'         => 1,
				'psid'          => 2,
				'label'         => 'test',
				'value'         => 'more',
				'created'       => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'edited'        => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'permissionSet' => [
					'uid'       => 23,
					'allow_cid' => "<1>",
					'allow_gid' => "<~>",
					'deny_cid'  => '<2>',
					'deny_gid'  => '<3>',
					'id'        => 2,
				],
			],
			'withId' => [
				'uid'           => 23,
				'order'         => 1,
				'psid'          => 2,
				'label'         => 'test',
				'value'         => 'more',
				'created'       => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'edited'        => new \DateTime('2021-10-10T21:12:00.000000+0000', new \DateTimeZone('UTC')),
				'permissionSet' => [
					'uid'       => 23,
					'allow_cid' => "<1>",
					'allow_gid' => "<~>",
					'deny_cid'  => '<2>',
					'deny_gid'  => '<3>',
					'id'        => 2,
				],
				'id' => 54,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataEntity')]
	public function testEntity(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, array $permissionSet, $id = null): void
	{
		$entity = new ProfileField($uid, $order, $label, $value, $created, $edited, $this->permissionSetFactory->createFromTableRow($permissionSet), $id);

		self::assertEquals($uid, $entity->uid);
		self::assertEquals($order, $entity->order);
		self::assertEquals($psid, $entity->permissionSet->id);
		self::assertEquals($label, $entity->label);
		self::assertEquals($value, $entity->value);
		self::assertEquals($created, $entity->created);
		self::assertEquals($edited, $entity->edited);
		self::assertEquals($id, $entity->id);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataEntity')]
	public function testUpdate(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, array $permissionSet, $id = null): void
	{
		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => 2, 'id' => $psid]);

		$entity = $this->profileFieldFactory->createFromTableRow([
			'uid'     => $uid,
			'order'   => $order,
			'psid'    => $psid,
			'label'   => $label,
			'value'   => $value,
			'created' => $created->format(DateTimeFormat::MYSQL),
			'edited'  => $edited->format(DateTimeFormat::MYSQL),
			'id'      => $id,
		], $permissionSet);

		$permissionSetNew = $this->permissionSetFactory->createFromTableRow([
			'uid'       => 2,
			'allow_cid' => '<1>',
			'id'        => 23,
		]);

		$entity->update('updatedValue', 2345, $permissionSetNew);

		self::assertEquals($uid, $entity->uid);
		self::assertEquals(2345, $entity->order);
		self::assertEquals(23, $entity->permissionSet->id);
		self::assertEquals($label, $entity->label);
		self::assertEquals('updatedValue', $entity->value);
		self::assertEquals($created, $entity->created);
		self::assertGreaterThan($edited, $entity->edited);
		self::assertEquals($id, $entity->id);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataEntity')]
	public function testSetOrder(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, array $permissionSet, $id = null): void
	{
		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => 2, 'id' => $psid]);

		$entity = $this->profileFieldFactory->createFromTableRow([
			'uid'     => $uid,
			'order'   => $order,
			'psid'    => $psid,
			'label'   => $label,
			'value'   => $value,
			'created' => $created->format(DateTimeFormat::MYSQL),
			'edited'  => $edited->format(DateTimeFormat::MYSQL),
			'id'      => $id,
		], $permissionSet);

		$entity->setOrder(2345);

		self::assertEquals($uid, $entity->uid);
		self::assertEquals(2345, $entity->order);
		self::assertEquals($psid, $entity->permissionSet->id);
		self::assertEquals($label, $entity->label);
		self::assertEquals($value, $entity->value);
		self::assertEquals($created, $entity->created);
		self::assertGreaterThan($edited, $entity->edited);
		self::assertEquals($id, $entity->id);
	}

	/**
	 * Test the exception because of a wrong property
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataEntity')]
	public function testWrongGet(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, array $permissionSet, $id = null): void
	{
		$entity = new ProfileField($uid, $order, $label, $value, $created, $edited, $this->permissionSetFactory->createFromTableRow($permissionSet), $id);

		self::expectException(ProfileFieldNotFoundException::class);
		$entity->wrong; // @phpstan-ignore property.notFound, expr.resultUnused
	}

	/**
	 * Test gathering the permissionset
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataEntity')]
	public function testPermissionSet(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, array $permissionSet, $id = null): void
	{
		$entity = new ProfileField($uid, $order, $label, $value, $created, $edited, $this->permissionSetFactory->createFromTableRow($permissionSet), $id);

		$permissionSet = $this->permissionSetFactory->createFromTableRow(['uid' => $uid, 'id' => $psid]);

		$this->permissionSetRepository->shouldReceive('selectOneById')->with($psid, $uid)->andReturns($permissionSet);

		self::assertEquals($psid, $entity->permissionSet->id);
	}

	/**
	 * Test the exception because the factory cannot find a permissionSet ID, nor the permissionSet itself
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataEntity')]
	public function testMissingPermissionFactory(int $uid, int $order, int $psid, string $label, string $value, \DateTime $created, \DateTime $edited, array $permissionSet, $id = null): void
	{
		self::expectException(UnexpectedPermissionSetException::class);
		self::expectExceptionMessage('Either set the PermissionSet fields (join) or the PermissionSet itself');

		$entity = $this->profileFieldFactory->createFromTableRow([
			'uid'     => $uid,
			'order'   => $order,
			'label'   => $label,
			'value'   => $value,
			'created' => $created->format(DateTimeFormat::MYSQL),
			'edited'  => $edited->format(DateTimeFormat::MYSQL),
			'id'      => $id,
		]);
	}
}
