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

use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Test\MockedTest;

abstract class StorageConfigTest extends MockedTest
{
	/** @return ICanConfigureStorage */
	abstract protected function getInstance();

	abstract protected function assertOption(ICanConfigureStorage $storage);

	/**
	 * Test if the "getOption" is asserted
	 */
	public function testGetOptions()
	{
		$instance = $this->getInstance();

		$this->assertOption($instance);
	}
}
