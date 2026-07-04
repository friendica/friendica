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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataConfigLoad')]
	public function testLoad(int $uid, array $data, array $possibleCats, array $load): void
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataDoubleLoad')]
	public function testCacheLoadDouble(int $uid, array $data1, array $data2, array $expect): void
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testSetGetWithoutDB(int $uid, $data): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(false)
						  ->times(3);

		parent::testSetGetWithoutDB($uid, $data);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testSetGetWithDB(int $uid, $data): void
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testGetWithRefresh(int $uid, $data): void
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testDeleteWithoutDB(int $uid, $data): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(false)
						  ->times(4);

		parent::testDeleteWithoutDB($uid, $data);
	}

	public function testDeleteWithDB(): void
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
