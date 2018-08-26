<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Database\DBA;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires PHP 7.0
 */
class ConfigConsoleTest extends ConsoleTest
{
	public function tearDown()
	{
		DBA::delete('config', ['k' => 'test']);

		parent::tearDown();
	}

	private function assertGet($family, $key, $value) {
		$config = $this->execute(['config', $family, $key]);
		$this->assertEquals($family . "." . $key . " => " . $value . "\n", $config);
	}

	private function assertSet($family, $key, $value) {
		$config = $this->execute(['config', $family, $key, $value]);
		$this->assertEquals($family . "." . $key . " <= " . $value . "\n", $config);
	}

	function testSetGetKeyValue() {
		$this->assertSet( 'config', 'test', 'now');
		$this->assertGet('config', 'test', 'now');
		$this->assertSet('config', 'test', '');
		$this->assertGet('config', 'test', '');
		DBA::delete('config', ['k' => 'test']);
		$this->assertGet('config', 'test', null);
	}

	function testSetArrayValue() {
		$testArray = [1, 2, 3];
		DBA::insert('config', ['cat' => 'config', 'k' => 'test', 'v' => serialize($testArray)]);

		$txt = $this->execute(['config', 'config', 'test', 'now']);

		$this->assertEquals("[Error] config.test is an array and can't be set using this command.\n", $txt);
	}

	function testTooManyArguments() {
		$txt = $this->execute(['config', 'config', 'test', 'it', 'now']);
		$assertion = '[Warning] Too many arguments';
		$firstline = substr($txt, 0, strlen($assertion));

		$this->assertEquals($assertion, $firstline);
	}

	function testVerbose() {
		$this->assertSet('test', 'it', 'now');
		$executable = $this->getExecutablePath();
		$assertion = <<<CONF
Executable: {$executable}
Arguments: array (
  0 => 'config',
  1 => 'test',
)
Options: array (
  'v' => 1,
)
Command: config
Executable: {$executable}
Class: Friendica\Core\Console\Config
Arguments: array (
  0 => 'test',
)
Options: array (
  'v' => 1,
)
[test]
it => now

CONF;
		$txt = $this->execute(['config', 'test', '-v']);

		$this->assertEquals($assertion, $txt);
	}
}
