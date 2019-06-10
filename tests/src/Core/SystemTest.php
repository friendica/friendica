<?php

namespace Friendica\Test\src\Core;

use Friendica\Core\System;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;

class SystemTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);

		$this->app->shouldReceive('getHostName')->andReturn('localhost');
	}

	private function assertGuid($guid, $length, $prefix = '')
	{
		$length -= strlen($prefix);
		$this->assertRegExp("/^" . $prefix . "[a-z0-9]{" . $length . "}?$/", $guid);
	}

	function testGuidWithoutParameter()
	{
		$guid = System::createGUID();
		$this->assertGuid($guid, 16);
	}

	function testGuidWithSize32()
	{
		$guid = System::createGUID(32);
		$this->assertGuid($guid, 32);
	}

	function testGuidWithSize64()
	{
		$guid = System::createGUID(64);
		$this->assertGuid($guid, 64);
	}

	function testGuidWithPrefix()
	{
		$guid = System::createGUID(23, 'test');
		$this->assertGuid($guid, 23, 'test');
	}
}
