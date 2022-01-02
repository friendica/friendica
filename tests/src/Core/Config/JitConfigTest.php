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

use Friendica\Core\Config\Type\JitConfig;

class JitConfigTest extends ConfigTest
{
	public function getInstance()
	{
		return new JitConfig($this->configCache, $this->configModel);
	}

	/**
	 * @dataProvider dataConfigLoad
	 */
	public function testSetUp(array $data)
	{
		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => $data['config']])
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
		                  ->times(count($load) + 1);

		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => $data['config']])
		                  ->once();

		foreach ($load as $loadCat) {
			$this->configModel->shouldReceive('load')
			                  ->with($loadCat)
			                  ->andReturn([$loadCat => $data[$loadCat]])
			                  ->once();
		}

		parent::testLoad($data, $load);
	}

	/**
	 * @dataProvider dataDoubleLoad
	 */
	public function testCacheLoadDouble(array $data1, array $data2, array $expect = [])
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(count($data1) + count($data2) + 1);

		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => $data1['config']])
		                  ->once();

		foreach ($data1 as $cat => $data) {
			$this->configModel->shouldReceive('load')
			                  ->with($cat)
			                  ->andReturn([$cat => $data])
			                  ->once();
		}


		foreach ($data2 as $cat => $data) {
			$this->configModel->shouldReceive('load')
			                  ->with($cat)
			                  ->andReturn([$cat => $data])
			                  ->once();
		}

		parent::testCacheLoadDouble($data1, $data2);

		// Assert the expected categories
		foreach ($data2 as $cat => $data) {
			self::assertConfig($cat, $expect[$cat]);
		}
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(3);

		$this->configModel->shouldReceive('load')->with('config')->andReturn(['config' => []])->once();

		parent::testSetGetWithDB($data);
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(4);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get without result
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn(null)
		                  ->once();

		// mocking the data get
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn($data)
		                  ->once();

		// mocking second get
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'not')
		                  ->andReturn(null)
		                  ->once();

		parent::testGetWithRefresh($data);
	}

	public function testGetWrongWithoutDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(4);

		parent::testGetWrongWithoutDB();
	}

	/**
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(false)
		                  ->times(4);

		parent::testDeleteWithoutDB($data);
	}

	public function testDeleteWithDB()
	{
		$this->configModel->shouldReceive('isConnected')
		                  ->andReturn(true)
		                  ->times(6);

		// constructor loading
		$this->configModel->shouldReceive('load')
		                  ->with('config')
		                  ->andReturn(['config' => []])
		                  ->once();

		// mocking one get without result
		$this->configModel->shouldReceive('get')
		                  ->with('test', 'it')
		                  ->andReturn(null)
		                  ->once();

		parent::testDeleteWithDB();
	}

	public function testSetGetHighPrio()
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true);

		// constructor loading
		$this->configModel->shouldReceive('load')
						  ->with('config')
						  ->andReturn(['config' => []])
						  ->once();

		$this->configModel->shouldReceive('get')
						  ->with('config', 'test')
						  ->andReturn('prio')
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
						  ->with('config')
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
