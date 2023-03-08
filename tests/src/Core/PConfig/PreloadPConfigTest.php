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

namespace Friendica\Test\src\Core\PConfig;

use Friendica\Core\PConfig\Type\PreloadPConfig;

class PreloadPConfigTest extends PConfigTest
{
	public function getInstance()
	{
		return new \Friendica\Core\PConfig\Type\PreloadPConfig($this->configCache, $this->configModel);
	}

	/**
	 * @dataProvider dataConfigLoad
	 */
	public function testLoad(int $uid, array $data, array $possibleCats, array $load)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->once();

		$this->configModel->shouldReceive('load')
		                  ->with($uid)
		                  ->andReturn($data)
		                  ->once();

		parent::testLoad($uid, $data, $possibleCats, $load);

		// Assert that every category is loaded everytime
		foreach ($data as $cat => $values) {
			self::assertConfig($uid, $cat, $values);
		}
	}

	/**
	 * @dataProvider dataDoubleLoad
	 */
	public function testCacheLoadDouble(int $uid, array $data1, array $data2, array $expect)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->once();

		$this->configModel->shouldReceive('load')
		                  ->with($uid)
		                  ->andReturn($data1)
		                  ->once();

		parent::testCacheLoadDouble($uid, $data1, $data2, $expect);

		// Assert that every category is loaded everytime and is NOT overwritten
		foreach ($data1 as $cat => $values) {
			self::assertConfig($uid, $cat, $values);
		}
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetGetWithoutDB(int $uid, $data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(3);

		parent::testSetGetWithoutDB($uid, $data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB(int $uid, $data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->twice();

		$this->configModel->shouldReceive('load')
		                  ->with($uid)
		                  ->andReturn(['config' => []])
		                  ->once();

		parent::testSetGetWithDB($uid, $data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh(int $uid, $data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(2);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->with($uid)
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get
		$this->configModel->shouldReceive('get')
		                  ->with($uid, 'test', 'it')
		                  ->andReturn($data)
		                  ->once();

		parent::testGetWithRefresh($uid, $data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB(int $uid, $data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(4);

		parent::testDeleteWithoutDB($uid, $data);
	}

	public function testDeleteWithDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(5);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->with(42)
		                  ->andReturn(['config' => []])
		                  ->once();

		parent::testDeleteWithDB();
	}
}
