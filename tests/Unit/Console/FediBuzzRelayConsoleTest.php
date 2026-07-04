<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Console;

use Friendica\App\Mode;
use Friendica\Console\FediBuzzRelay;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Protocol\ActivityPub\Firehose;
use Friendica\System\Daemon as SysDaemon;
use Friendica\Test\ConsoleTestCase;
use Mockery;
use Mockery\MockInterface;

class FediBuzzRelayConsoleTest extends ConsoleTestCase
{
	private Mode|MockInterface $mode;
	private IManageConfigValues|MockInterface $config;
	private IManageKeyValuePairs|MockInterface $keyValue;
	private SysDaemon|MockInterface $daemon;
	private Firehose|MockInterface $firehose;

	private const PIDFILE = '/tmp/fedibuzz-test.pid';

	protected function setUp(): void
	{
		parent::setUp();

		$this->mode     = Mockery::mock(Mode::class);
		$this->config   = Mockery::mock(IManageConfigValues::class);
		$this->keyValue = Mockery::mock(IManageKeyValuePairs::class);
		$this->daemon   = Mockery::mock(SysDaemon::class);
		$this->firehose = Mockery::mock(Firehose::class);
	}

	private function createConsole(array $extraArgs = []): FediBuzzRelay
	{
		return new FediBuzzRelay(
			$this->mode,
			$this->config,
			$this->keyValue,
			$this->daemon,
			$this->firehose,
			array_merge($this->consoleArgv, $extraArgs),
		);
	}

	private function setupReady(string $pidfile = self::PIDFILE): void
	{
		$this->mode->shouldReceive('isInstall')->andReturn(false)->once();
		$this->config->shouldReceive('reload')->once();
		$this->config->shouldReceive('get')->with('fedibuzzrelay', 'pidfile')->andReturn($pidfile);
	}

	public function testErrorWhenNotInstalled(): void
	{
		$this->mode->shouldReceive('isInstall')->andReturn(true)->once();

		$txt = $this->dumpExecute($this->createConsole(['start']));
		self::assertStringContainsString("Friendica isn't properly installed yet", $txt);
	}

	public function testErrorWhenPidfileMissing(): void
	{
		$this->mode->shouldReceive('isInstall')->andReturn(false)->once();
		$this->config->shouldReceive('reload')->once();
		$this->config->shouldReceive('get')->with('fedibuzzrelay', 'pidfile')->andReturn('');

		$txt = $this->dumpExecute($this->createConsole(['start']));
		self::assertStringContainsString('Please set fedibuzzrelay.pidfile', $txt);
	}

	public function testErrorWhenNoSubcommand(): void
	{
		$this->mode->shouldReceive('isInstall')->andReturn(false)->once();
		$this->config->shouldReceive('reload')->once();
		$this->config->shouldReceive('get')->with('fedibuzzrelay', 'pidfile')->andReturn(self::PIDFILE);

		$txt = $this->dumpExecute($this->createConsole([]));
		self::assertStringContainsString("Please use either 'start', 'stop' or 'status'", $txt);
	}

	public function testStatusWhenRunning(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(true)->once();
		$this->daemon->shouldReceive('getPid')->andReturn(12345);
		$this->daemon->shouldReceive('getPidfile')->andReturn(self::PIDFILE);

		$txt = $this->dumpExecute($this->createConsole(['status']));
		self::assertStringContainsString('is running', $txt);
	}

	public function testStatusWhenNotRunning(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(false)->once();
		$this->daemon->shouldReceive('getPid')->andReturn(null);
		$this->daemon->shouldReceive('getPidfile')->andReturn(self::PIDFILE);

		$txt = $this->dumpExecute($this->createConsole(['status']));
		self::assertStringContainsString("isn't running", $txt);
	}

	public function testStopWhenNotRunning(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(false)->once();
		$this->daemon->shouldReceive('getPid')->andReturn(null);
		$this->daemon->shouldReceive('getPidfile')->andReturn(self::PIDFILE);

		$txt = $this->dumpExecute($this->createConsole(['stop']));
		self::assertStringContainsString("isn't running", $txt);
	}

	public function testStopWhenRunning(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(true)->once();
		$this->daemon->shouldReceive('stop')->andReturn(true)->once();
		$this->daemon->shouldReceive('getPid')->andReturn(12345);
		$this->daemon->shouldReceive('getPidfile')->andReturn(self::PIDFILE);
		$this->keyValue->shouldReceive('set')->with('fedibuzzrelay_daemon_mode', false)->once();

		$txt = $this->dumpExecute($this->createConsole(['stop']));
		self::assertStringContainsString('was killed', $txt);
	}

	public function testStopFailsReturnsNonZero(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(true)->once();
		$this->daemon->shouldReceive('stop')->andReturn(false)->once();

		$result = $this->createConsole(['stop'])->execute();
		self::assertSame(1, $result);
	}

	public function testStartWhenAlreadyRunning(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(true)->once();
		$this->daemon->shouldReceive('getPid')->andReturn(12345);
		$this->daemon->shouldReceive('getPidfile')->andReturn(self::PIDFILE);

		$txt = $this->dumpExecute($this->createConsole(['start']));
		self::assertStringContainsString('is already running', $txt);
	}

	public function testStartForeground(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(false)->once();
		$this->daemon->shouldReceive('start')->with(Mockery::type('callable'), true)->once();

		$txt = $this->dumpExecute($this->createConsole(['start', '-f']));
		self::assertStringContainsString('Starting FediBuzz relay daemon', $txt);
	}

	public function testStartBackground(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(false)->once();
		$this->daemon->shouldReceive('start')->with(Mockery::type('callable'), false)->once();

		$txt = $this->dumpExecute($this->createConsole(['start']));
		self::assertStringContainsString('Starting FediBuzz relay daemon', $txt);
	}

	public function testInvalidSubcommand(): void
	{
		$this->setupReady();
		$this->daemon->shouldReceive('init')->with(self::PIDFILE)->once();
		$this->daemon->shouldReceive('isRunning')->andReturn(false)->once();

		$txt = $this->dumpExecute($this->createConsole(['invalid']));
		self::assertStringContainsString('Invalid command', $txt);
	}
}
