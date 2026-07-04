<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Console;

use Friendica\App\Mode;
use Friendica\Console\Config;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\ConsoleTestCase;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;

class ConfigConsoleTest extends ConsoleTestCase
{
	/** @var IManageConfigValues|LegacyMockInterface|MockInterface */
	private $configMock;

	protected function setUp(): void
	{
		parent::setUp();

		Mockery::getConfiguration()->setConstantsMap([
			Mode::class => [
				'DBCONFIGAVAILABLE' => 0,
			],
		]);

		$this->configMock = Mockery::mock(IManageConfigValues::class);
	}

	public function testSetGetKeyValue(): void
	{
		$this->configMock
			->shouldReceive('set')
			->with('config', 'test', 'now')
			->andReturn(true)
			->once();
		$this->configMock
			->shouldReceive('get')
			->with('config', 'test')
			->andReturn('old')
			->twice();
		$this->configMock
			->shouldReceive('get')
			->with('config', 'test')
			->andReturn('now')
			->once();

		$console = new Config($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);
		self::assertEquals("config.test <= now\n", $txt);

		$this->configMock
			->shouldReceive('get')
			->with('config', 'test')
			->andReturn('now')
			->once();

		$console = new Config($this->configMock, [$this->consoleArgv]);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("config.test => now\n", $txt);

		$this->configMock
			->shouldReceive('get')
			->with('config', 'test')
			->andReturn(null)
			->once();

		$console = new Config($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("config.test => NULL\n", $txt);
	}

	public function testSetArrayValue(): void
	{
		$testArray = [1, 2, 3];
		$this->configMock
			->shouldReceive('get')
			->with('config', 'test')
			->andReturn($testArray)
			->once();

		$console = new Config($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);

		self::assertEquals("[Error] config.test is an array and can't be set using this command.\n", $txt);
	}

	public function testSetExistingValue(): void
	{
		$this->configMock
			->shouldReceive('get')
			->with('config', 'test')
			->andReturn('now')
			->twice();

		$console = new Config($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);

		self::assertEquals("[Error] config.test already set to now.\n", $txt);
	}

	public function testTooManyArguments(): void
	{
		$console = new Config($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'config');
		$console->setArgument(1, 'test');
		$console->setArgument(2, 'it');
		$console->setArgument(3, 'now');
		$txt       = $this->dumpExecute($console);
		$assertion = '[Warning] Too many arguments';
		$firstline = substr($txt, 0, strlen($assertion));
		self::assertEquals($assertion, $firstline);
	}

	public function testVerbose(): void
	{
		$this->configMock
			->shouldReceive('get')
			->with('test', 'it')
			->andReturn('now')
			->once();
		$console = new Config($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'test');
		$console->setArgument(1, 'it');
		$console->setOption('v', 1);
		$executable = $this->consoleArgv[0];
		$assertion  = <<<CONF
Executable: {$executable}
Class: Friendica\Console\Config
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
		self::assertEquals($assertion, $txt);
	}

	public function testUnableToSet(): void
	{
		$this->configMock
			->shouldReceive('set')
			->with('test', 'it', 'now')
			->andReturn(false)
			->once();
		$this->configMock
			->shouldReceive('get')
			->with('test', 'it')
			->andReturn(null)
			->twice();
		$console = new Config($this->configMock, [$this->consoleArgv]);
		$console->setArgument(0, 'test');
		$console->setArgument(1, 'it');
		$console->setArgument(2, 'now');
		$txt = $this->dumpExecute($console);
		self::assertSame("Unable to set test.it\n", $txt);
	}

	public function testGetHelp(): void
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
		$console = new Config($this->configMock, [$this->consoleArgv]);
		$console->setOption('help', true);

		$txt = $this->dumpExecute($console);

		self::assertEquals($txt, $theHelp);
	}
}
