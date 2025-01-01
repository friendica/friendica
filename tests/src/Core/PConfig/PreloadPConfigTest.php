<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\PConfig;

use Friendica\Core\PConfig\Type\PreloadPConfig;
use Friendica\Test\PConfigTestCase;

class PreloadPConfigTest extends PConfigTestCase
{
	public function getInstance()
	{
		return new PreloadPConfig($this->configCache, $this->configModel);
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
