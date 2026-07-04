<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\PConfig;

use Friendica\Core\PConfig\Type\JitPConfig;
use Friendica\Test\PConfigTestCase;

class JitPConfigTest extends PConfigTestCase
{
	public function getInstance()
	{
		return new JitPConfig($this->configCache, $this->configModel);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataConfigLoad')]
	public function testLoad(int $uid, array $data, array $possibleCats, array $load): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true)
						  ->times(count($load));

		foreach ($load as $loadCat) {
			$this->configModel->shouldReceive('load')
							  ->with($uid, $loadCat)
							  ->andReturn([$loadCat => $data[$loadCat]])
							  ->once();
		}

		parent::testLoad($uid, $data, $possibleCats, $load);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataDoubleLoad')]
	public function testCacheLoadDouble(int $uid, array $data1, array $data2, array $expect): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true)
						  ->times(count($data1) + count($data2));

		foreach ($data1 as $cat => $data) {
			$this->configModel->shouldReceive('load')
							  ->with($uid, $cat)
							  ->andReturn([$cat => $data])
							  ->once();
		}


		foreach ($data2 as $cat => $data) {
			$this->configModel->shouldReceive('load')
							  ->with($uid, $cat)
							  ->andReturn([$cat => $data])
							  ->once();
		}

		parent::testCacheLoadDouble($uid, $data1, $data2, $expect);

		// Assert the expected categories
		foreach ($data2 as $cat => $data) {
			self::assertConfig($uid, $cat, $expect[$cat]);
		}
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testSetGetWithoutDB(int $uid, $data): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(false)
						  ->times(2);

		parent::testSetGetWithoutDB($uid, $data);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testSetGetWithDB(int $uid, $data): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true)
						  ->times(2);

		parent::testSetGetWithDB($uid, $data);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testGetWithRefresh(int $uid, $data): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true)
						  ->times(3);

		// mocking one get without result
		$this->configModel->shouldReceive('get')
						  ->with($uid, 'test', 'it')
						  ->andReturn(null)
						  ->once();

		// mocking the data get
		$this->configModel->shouldReceive('get')
						  ->with($uid, 'test', 'it')
						  ->andReturn($data)
						  ->once();

		// mocking second get
		$this->configModel->shouldReceive('get')
						  ->with($uid, 'test', 'not')
						  ->andReturn(null)
						  ->once();

		parent::testGetWithRefresh($uid, $data);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTests')]
	public function testDeleteWithoutDB(int $uid, $data): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(false)
						  ->times(3);

		parent::testDeleteWithoutDB($uid, $data);
	}

	public function testDeleteWithDB(): void
	{
		$this->configModel->shouldReceive('isConnected')
						  ->andReturn(true)
						  ->times(5);

		// mocking one get without result
		$this->configModel->shouldReceive('get')
						  ->with(42, 'test', 'it')
						  ->andReturn(null)
						  ->once();

		parent::testDeleteWithDB();
	}
}
