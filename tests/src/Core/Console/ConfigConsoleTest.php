<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Core\Console\Config;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires PHP 7.0
 */
class ConfigConsoleTest extends ConsoleTest
{
	protected function setUp()
	{
		parent::setUp();

		\Mockery::getConfiguration()->setConstantsMap([
			'Friendica\App\Mode' => [
				'DBCONFIGAVAILABLE' => 0
			]
		]);

		$mode = \Mockery::mock('alias:Friendica\App\Mode');
		$mode
			->shouldReceive('has')
			->andReturn(true);

		$this->app
			->shouldReceive('getMode')
			->andReturn($mode);
	}

	function testSetGetKeyValue() {
		$this->mockConfigSetGet('config', 'test', 'now', 1);
		$console = new Config($this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);
		$this->assertEquals("config.test <= now\n", $txt);

		$this->mockConfigGet('config', 'test', 'now', 1);
		$console = new Config($this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		$this->assertEquals("config.test => now\n", $txt);

		$this->mockConfigGet('config', 'test', null, 1);
		$console = new Config($this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		$this->assertEquals("config.test => \n", $txt);
	}

	function testSetArrayValue() {
		$testArray = [1, 2, 3];
		$this->mockConfigGet('config', 'test', $testArray, 1);

		$console = new Config($this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);

		$this->assertEquals("[Error] config.test is an array and can't be set using this command.\n", $txt);
	}

	function testTooManyArguments() {
		$console = new Config($this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'it');
		$console->setArgument(3, 'now');
		$txt = $this->dumpExecute($console);
		$assertion = '[Warning] Too many arguments';
		$firstline = substr($txt, 0, strlen($assertion));
		$this->assertEquals($assertion, $firstline);
	}

	function testVerbose() {
		$this->mockConfigGet('test', 'it', 'now', 1);
		$console = new Config($this->consoleArgv);
		$console->setArgument(0, 'test');
		$console->setArgument(1, 'it');
		$console->setOption('v', 1);
		$executable = $this->consoleArgv[0];
		$assertion = <<<CONF
Executable: {$executable}
Class: Friendica\Core\Console\Config
Arguments: array (
  0 => 'test',
  1 => 'it',
)
Options: array (
  'v' => 1,
)
test.it => now

CONF;
		$txt = $this->dumpExecute($console);
		$this->assertEquals($assertion, $txt);
	}

	function testUnableToSet() {
		$this->mockConfigSetGet('test', 'it', 'now', 1, false);
		$console = new Config();
		$console->setArgument(0, 'test');
		$console->setArgument(1, 'it');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);
		$this->assertSame("Unable to set test.it\n", $txt);
	}

	public function testGetHelp()
	{
		// Usable to purposely fail if new commands are added without taking tests into account
		$theHelp = <<<HELP
console config - Manage site configuration
Synopsis
	bin/console config [-h|--help|-?] [-v]
	bin/console config <category> [-h|--help|-?] [-v]
	bin/console config <category> <key> [-h|--help|-?] [-v]
	bin/console config <category> <key> <value> [-h|--help|-?] [-v]

Description
	bin/console config
		Lists all config values

	bin/console config <category>
		Lists all config values in the provided category

	bin/console config <category> <key>
		Shows the value of the provided key in the category

	bin/console config <category> <key> <value>
		Sets the value of the provided key in the category

Notes:
	Setting config entries which are manually set in config/local.config.php may result in
	conflict between database settings and the manual startup settings.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.

HELP;
		$console = new Config($this->consoleArgv);
		$console->setOption('help', true);

		$txt = $this->dumpExecute($console);

		$this->assertEquals($txt, $theHelp);
	}
}
