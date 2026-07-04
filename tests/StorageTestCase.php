<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Core\Storage\Exception\ReferenceStorageException;

abstract class StorageTestCase extends MockedTestCase
{
	/** @return ICanWriteToStorage */
	abstract protected function getInstance();

	/**
	 * Test if the instance is "really" implementing the interface
	 */
	public function testInstance(): void
	{
		$instance = $this->getInstance();
		self::assertInstanceOf(ICanReadFromStorage::class, $instance);
	}

	/**
	 * Test basic put, get and delete operations
	 */
	public function testPutGetDelete(): void
	{
		$instance = $this->getInstance();

		$ref = $instance->put('data12345');
		self::assertNotEmpty($ref);

		self::assertEquals('data12345', $instance->get($ref));

		$instance->delete($ref);
	}

	/**
	 * Test a delete with an invalid reference
	 */
	public function testInvalidDelete(): void
	{
		self::expectException(ReferenceStorageException::class);

		$instance = $this->getInstance();

		$instance->delete(-1234456);
	}

	/**
	 * Test a get with an invalid reference
	 */
	public function testInvalidGet(): void
	{
		self::expectException(ReferenceStorageException::class);

		$instance = $this->getInstance();

		$instance->get(-123456);
	}

	/**
	 * Test an update with a given reference
	 */
	public function testUpdateReference(): void
	{
		$instance = $this->getInstance();

		$ref = $instance->put('data12345');
		self::assertNotEmpty($ref);

		self::assertEquals('data12345', $instance->get($ref));

		self::assertEquals($ref, $instance->put('data5432', $ref));
		self::assertEquals('data5432', $instance->get($ref));
	}

	/**
	 * Test that an invalid update results in an insert
	 */
	public function testInvalidUpdate(): void
	{
		$instance = $this->getInstance();

		self::assertEquals(-123, $instance->put('data12345', -123));
	}
}
