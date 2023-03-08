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

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Test\MockedTest;

abstract class StorageTest extends MockedTest
{
	/** @return ICanWriteToStorage */
	abstract protected function getInstance();

	/**
	 * Test if the instance is "really" implementing the interface
	 */
	public function testInstance()
	{
		$instance = $this->getInstance();
		self::assertInstanceOf(ICanReadFromStorage::class, $instance);
	}

	/**
	 * Test basic put, get and delete operations
	 */
	public function testPutGetDelete()
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
	public function testInvalidDelete()
	{
		self::expectException(ReferenceStorageException::class);

		$instance = $this->getInstance();

		$instance->delete(-1234456);
	}

	/**
	 * Test a get with an invalid reference
	 */
	public function testInvalidGet()
	{
		self::expectException(ReferenceStorageException::class);

		$instance = $this->getInstance();

		$instance->get(-123456);
	}

	/**
	 * Test an update with a given reference
	 */
	public function testUpdateReference()
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
	public function testInvalidUpdate()
	{
		$instance = $this->getInstance();

		self::assertEquals(-123, $instance->put('data12345', -123));
	}
}
