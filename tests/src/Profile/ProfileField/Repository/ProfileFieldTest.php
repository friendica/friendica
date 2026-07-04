<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Profile\ProfileField\Repository;

use Friendica\Profile\ProfileField\Collection\ProfileFields;
use Friendica\Profile\ProfileField\Repository\ProfileField as ProfileFieldRepository;
use Friendica\Profile\ProfileField\Exception\ProfileFieldPersistenceException;
use Friendica\Profile\ProfileField\Factory\ProfileField as ProfileFieldFactory;
use Friendica\Security\PermissionSet\Repository\PermissionSet;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;
use Friendica\Test\FixtureTestCase;
use Friendica\DI;

class ProfileFieldTest extends FixtureTestCase
{
	/** @var ProfileFieldRepository */
	private $depository;
	/** @var ProfileFieldFactory */
	private $factory;
	/** @var PermissionSetFactory */
	private $permissionSetFactory;
	/** @var PermissionSetRepository */
	private $permissionSetRepository;

	public function setUp(): void
	{
		parent::setUp();

		$this->depository              = DI::profileField();
		$this->factory                 = DI::profileFieldFactory();
		$this->permissionSetFactory    = DI::permissionSetFactory();
		$this->permissionSetRepository = DI::permissionSet();
	}

	/**
	 * Test create ProfileField without a valid PermissionSet
	 */
	public function testSavingWithoutPermissionSet(): void
	{
		self::expectExceptionMessage('PermissionSet needs to be saved first.');
		self::expectException(ProfileFieldPersistenceException::class);

		$profileField = $this->factory->createFromValues(42, 0, 'public', 'value', $this->permissionSetFactory->createFromString(42, '', '<~>'));

		self::assertEquals($profileField->uid, $profileField->permissionSet->uid);

		$this->depository->save($profileField);
	}

	/**
	 * Test saving a new entity
	 */
	public function testSaveNew(): void
	{
		$profileField = $this->factory->createFromValues(42, 0, 'public', 'value', $this->permissionSetRepository->save($this->permissionSetFactory->createFromString(42, '', '<~>')));

		self::assertEquals($profileField->uid, $profileField->permissionSet->uid);

		$savedProfileField = $this->depository->save($profileField);

		self::assertNotNull($savedProfileField->id);
		self::assertNull($profileField->id);

		$selectedProfileField = $this->depository->selectOneById($savedProfileField->id);

		self::assertEquals($savedProfileField, $selectedProfileField);

		$profileFields = new ProfileFields([$selectedProfileField]);
		$this->depository->deleteCollection($profileFields);
	}

	/**
	 * Test updating the order of a ProfileField
	 */
	public function testUpdateOrder(): void
	{
		$profileField = $this->factory->createFromValues(42, 0, 'public', 'value', $this->permissionSetRepository->save($this->permissionSetFactory->createFromString(42, '', '<~>')));

		self::assertEquals($profileField->uid, $profileField->permissionSet->uid);

		$savedProfileField = $this->depository->save($profileField);

		self::assertNotNull($savedProfileField->id);
		self::assertNull($profileField->id);

		$selectedProfileField = $this->depository->selectOneById($savedProfileField->id);

		self::assertEquals($savedProfileField, $selectedProfileField);

		$selectedProfileField->setOrder(66);

		$updatedOrderProfileField = $this->depository->save($selectedProfileField);

		self::assertEquals($selectedProfileField->id, $updatedOrderProfileField->id);
		self::assertEquals(66, $updatedOrderProfileField->order);

		// Even using the ID of the old, saved ProfileField returns the right instance
		$updatedFromOldProfileField = $this->depository->selectOneById($savedProfileField->id);
		self::assertEquals(66, $updatedFromOldProfileField->order);

		$profileFields = new ProfileFields([$updatedFromOldProfileField]);
		$this->depository->deleteCollection($profileFields);
	}

	/**
	 * Test updating a whole entity
	 */
	public function testUpdate(): void
	{
		$profileField = $this->factory->createFromValues(42, 0, 'public', 'value', $this->permissionSetRepository->save($this->permissionSetFactory->createFromString(42, '', '<~>')));

		self::assertEquals($profileField->uid, $profileField->permissionSet->uid);

		$savedProfileField = $this->depository->save($profileField);

		self::assertNotNull($savedProfileField->id);
		self::assertNull($profileField->id);

		$selectedProfileField = $this->depository->selectOneById($savedProfileField->id);

		self::assertEquals($savedProfileField, $selectedProfileField);

		$savedProfileField->update('another', 5, $this->permissionSetRepository->selectPublicForUser(42));
		self::assertEquals(PermissionSet::PUBLIC, $savedProfileField->permissionSet->id);

		$publicProfileField = $this->depository->save($savedProfileField);

		self::assertEquals($this->permissionSetRepository->selectPublicForUser(42), $publicProfileField->permissionSet);
		self::assertEquals('another', $publicProfileField->value);
		self::assertEquals(5, $publicProfileField->order);

		$profileFields = new ProfileFields([$publicProfileField]);
		$this->depository->deleteCollection($profileFields);
	}
}
