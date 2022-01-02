<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\Type\PreloadConfig;

class PreloadConfigTest extends ConfigTest
{
	public function getInstance()
	{
		return new PreloadConfig($this->configCache, $this->configModel);
	}

	/**
	 * @dataProvider dataConfigLoad
	 */
	public function testSetUp(array $data)
	{
		$this->configModel->shouldReceive('load')
		                  ->andReturn($data)
		                  ->once();

		parent::testSetUp($data);
	}

	/**
	 * @dataProvider dataConfigLoad
	 *
	 * @param array $data
	 * @param array $load
	 */
	public function testLoad(array $data, array $load)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->once();

		$this->configModel->shouldReceive('load')
		                  ->andReturn($data)
		                  ->once();

		parent::testLoad($data, $load);

		// Assert that every category is loaded everytime
		foreach ($data as $cat => $values) {
			self::assertConfig($cat, $values);
		}
	}

	/**
	 * @dataProvider dataDoubleLoad
	 *
	 * @param array $data1
	 * @param array $data2
	 */
	public function testCacheLoadDouble(array $data1, array $data2, array $expect = [])
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->once();

		$this->configModel->shouldReceive('load')
		                  ->andReturn($data1)
		                  ->once();

		parent::testCacheLoadDouble($data1, $data2);

		// Assert that every category is loaded everytime and is NOT overwritten
		foreach ($data1 as $cat => $values) {
			self::assertConfig($cat, $values);
		}
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(2);

		$this->configModel->shouldReceive('load')->andReturn(['config' => []])->once();

		parent::testSetGetWithDB($data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(2);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn($data)
		                  ->once();

		parent::testGetWithRefresh($data);
	}


	public function testGetWrongWithoutDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(2);

		parent::testGetWrongWithoutDB();
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(2);

		parent::testDeleteWithoutDB($data);
	}

	public function testDeleteWithDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(5);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->andReturn(['config' => []])
		                  ->once();

		parent::testDeleteWithDB();
	}


	public function testSetGetHighPrio()
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true);

		// constructor loading
		$this->configModel->shouldReceive('load')
						  ->andReturn(['config' => []])
						  ->once();

		$this->configModel->shouldReceive('set')
						  ->with('config', 'test', '123')
						  ->andReturn(true)
						  ->once();

		$this->configModel->shouldReceive('get')
						  ->with('config', 'test')
						  ->andReturn('123')
						  ->once();

		parent::testSetGetHighPrio();
	}

	public function testSetGetLowPrio()
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true);

		// constructor loading
		$this->configModel->shouldReceive('load')
						  ->andReturn(['config' => ['test' => 'it']])
						  ->once();

		$this->configModel->shouldReceive('set')
						  ->with('config', 'test', '123')
						  ->andReturn(true)
						  ->once();

		// mocking one get without result
		$this->configModel->shouldReceive('get')
						  ->with('config', 'test')
						  ->andReturn('it')
						  ->once();

		parent::testSetGetLowPrio();
	}
}
