<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Console;

use Friendica\App;
use Friendica\App\Mode;
use Friendica\Console\Lock;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Test\ConsoleTestCase;
use Mockery;
use Mockery\MockInterface;

class LockConsoleTest extends ConsoleTestCase
{
	/**
	 * @var App\Mode|MockInterface $appMode
	 */
	private $appMode;

	/**
	 * @var ICanLock|MockInterface
	 */
	private $lockMock;

	protected function setUp(): void
	{
		parent::setUp();

		Mockery::getConfiguration()->setConstantsMap([
			Mode::class => [
				'DBCONFIGAVAILABLE' => 0,
			],
		]);

		$this->appMode = Mockery::mock(App\Mode::class);
		$this->appMode->shouldReceive('has')
				->andReturn(true);

		$this->lockMock = Mockery::mock(ICanLock::class);
	}

	public function testList(): void
	{
		$this->lockMock
			->shouldReceive('getLocks')
			->andReturn(['test', 'test2'])
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'list');
		$txt = $this->dumpExecute($console);
		self::assertEquals("Listing all Locks:\ntest\ntest2\n2 locks found\n", $txt);
	}

	public function testListPrefix(): void
	{
		$this->lockMock
			->shouldReceive('getLocks')
			->with('test')
			->andReturn(['test', 'test2'])
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'list');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("Listing all Locks starting with \"test\":\ntest\ntest2\n2 locks found\n", $txt);
	}

	public function testDelLock(): void
	{
		$this->lockMock
			->shouldReceive('release')
			->with('test', true)
			->andReturn(true)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'del');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("Lock 'test' released.\n", $txt);
	}

	public function testDelUnknownLock(): void
	{
		$this->lockMock
			->shouldReceive('release')
			->with('test', true)
			->andReturn(false)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'del');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("Couldn't release Lock 'test'\n", $txt);
	}

	public function testSetLock(): void
	{
		$this->lockMock
			->shouldReceive('isLocked')
			->with('test')
			->andReturn(false)
			->once();
		$this->lockMock
			->shouldReceive('acquire')
			->with('test')
			->andReturn(true)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'set');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("Lock 'test' acquired.\n", $txt);
	}

	public function testSetLockIsLocked(): void
	{
		$this->lockMock
			->shouldReceive('isLocked')
			->with('test')
			->andReturn(true)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'set');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("[Error] 'test' is already set.\n", $txt);
	}

	public function testSetLockNotWorking(): void
	{
		$this->lockMock
			->shouldReceive('isLocked')
			->with('test')
			->andReturn(false)
			->once();
		$this->lockMock
			->shouldReceive('acquire')
			->with('test')
			->andReturn(false)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'set');
		$console->setArgument(1, 'test');
		$txt = $this->dumpExecute($console);
		self::assertEquals("[Error] Unable to lock 'test'.\n", $txt);
	}

	public function testReleaseAll(): void
	{
		$this->lockMock
			->shouldReceive('releaseAll')
			->andReturn(true)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'clear');
		$txt = $this->dumpExecute($console);
		self::assertEquals("Locks successfully cleared.\n", $txt);
	}

	public function testReleaseAllFailed(): void
	{
		$this->lockMock
			->shouldReceive('releaseAll')
			->andReturn(false)
			->once();

		$console = new Lock($this->appMode, $this->lockMock, $this->consoleArgv);
		$console->setArgument(0, 'clear');
		$txt = $this->dumpExecute($console);
		self::assertEquals("[Error] Unable to clear the locks.\n", $txt);
	}

	public function testGetHelp(): void
	{
		// Usable to purposely fail if new commands are added without taking tests into account
		$theHelp = <<<HELP
console lock - Manage node locks
Synopsis
	bin/console lock list [<prefix>] [-h|--help|-?] [-v]
	bin/console lock set <lock> [<timeout> [<ttl>]] [-h|--help|-?] [-v]
	bin/console lock del <lock> [-h|--help|-?] [-v]
	bin/console lock clear [-h|--help|-?] [-v]

Description
	bin/console lock list [<prefix>]
		List all locks, optionally filtered by a prefix

	bin/console lock set <lock> [<timeout> [<ttl>]]
		Sets manually a lock, optionally with the provided TTL (time to live) with a default of five minutes.

	bin/console lock del <lock>
		Deletes a lock.

	bin/console lock clear
		Clears all locks

Options
    -h|--help|-? Show help information
    -v           Show more debug information.

HELP;
		$console = new Lock($this->appMode, $this->lockMock, [$this->consoleArgv]);
		$console->setOption('help', true);

		$txt = $this->dumpExecute($console);

		self::assertEquals($txt, $theHelp);
	}
}
