<?php

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\System;
use Friendica\DI;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function useBaseUrl()
	{
		$baseUrl = \Mockery::mock(BaseURL::class);
		$baseUrl->shouldReceive('getHostname')->andReturn('friendica.local')->once();
		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(BaseURL::class, [])->andReturn($baseUrl);

		DI::init($dice);
	}

	private function assertGuid($guid, $length, $prefix = '')
	{
		$length -= strlen($prefix);
		$this->assertRegExp("/^" . $prefix . "[a-z0-9]{" . $length . "}?$/", $guid);
	}

	function testGuidWithoutParameter()
	{
		$this->useBaseUrl();
		$guid = System::createGUID();
		$this->assertGuid($guid, 16);
	}

	function testGuidWithSize32()
	{
		$this->useBaseUrl();
		$guid = System::createGUID(32);
		$this->assertGuid($guid, 32);
	}

	function testGuidWithSize64()
	{
		$this->useBaseUrl();
		$guid = System::createGUID(64);
		$this->assertGuid($guid, 64);
	}

	function testGuidWithPrefix()
	{
		$guid = System::createGUID(23, 'test');
		$this->assertGuid($guid, 23, 'test');
	}
}
