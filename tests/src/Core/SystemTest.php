<?php

namespace Friendica\Test\src\Core;

use Friendica\Core\System;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function assertGuid($guid, $length, $prefix = '')
	{
		print $guid;
		$length -= strlen($prefix);
		$this->assertRegExp("/^" . $prefix . "[a-z0-9]{" . $length . "}?$/", $guid);
	}

	function testGuidWithoutParameter()
	{
		$guid = System::createGUID();
		$this->assertGuid($guid, 16);
	}

	function testGuidWithSize32() {
		$guid = System::createGUID(32);
		$this->assertGuid($guid, 32);
	}

	function testGuidWithSize64() {
		$guid = System::createGUID(64);
		$this->assertGuid($guid, 64);
	}

	function testGuidWithPrefix() {
		$guid = System::createGUID(23, 'test');
		$this->assertGuid($guid, 23, 'test');
	}
}