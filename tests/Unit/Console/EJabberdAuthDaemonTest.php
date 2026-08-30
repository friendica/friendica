<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Console;

use Friendica\App\Mode;
use Friendica\Console\EJabberdAuthDaemon;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Security\EJabberdAuth;
use Friendica\Test\ConsoleTestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for the {@see EJabberdAuthDaemon} console command.
 *
 * These tests cover only the binary wire-protocol layer the command is responsible for:
 * frame parsing, command dispatch, response encoding, and the daemon lifecycle (wrong mode,
 * lost database connection). The authentication business logic is mocked out via
 * {@see EJabberdAuth} and tested separately in {@see \Friendica\Test\Integration\Security\EJabberdAuthTest}.
 *
 * No database is required: every collaborator is injected as a mock.
 */
class EJabberdAuthDaemonTest extends ConsoleTestCase
{
	private $inputStream;
	private $outputStream;

	/** @var Mode|MockInterface */
	private $mode;
	/** @var IManageConfigValues|MockInterface */
	private $config;
	/** @var EJabberdAuth|MockInterface */
	private $auth;

	protected function setUp(): void
	{
		parent::setUp();

		$this->mode   = Mockery::mock(Mode::class);
		$this->config = Mockery::mock(IManageConfigValues::class);
		$this->auth   = Mockery::mock(EJabberdAuth::class);

		// The command only reads the debug verbosity from the config.
		$this->config->shouldReceive('get')->with('jabber', 'debug')->andReturn(0)->byDefault();

		// By default the database is connected; individual tests can override this.
		$this->auth->shouldReceive('isConnected')->andReturn(true)->byDefault();

		$this->inputStream  = fopen('php://memory', 'r+');
		$this->outputStream = fopen('php://memory', 'w+');
	}

	protected function tearDown(): void
	{
		Mockery::close();
		parent::tearDown();
	}

	private function sendInput(string $payload): void
	{
		fwrite($this->inputStream, pack('n', strlen($payload)) . $payload);
	}

	private function assertSuccess(): void
	{
		$this->assertEquals(pack('nn', 2, 1), fread($this->outputStream, 4), 'Expected success response');
	}

	private function assertFailed(): void
	{
		$this->assertEquals(pack('nn', 2, 0), fread($this->outputStream, 4), 'Expected failed response');
	}

	private function newConsole(): EJabberdAuthDaemon
	{
		return new EJabberdAuthDaemon(
			$this->mode,
			$this->config,
			$this->auth,
			$this->consoleArgv,
			$this->inputStream,
			$this->outputStream,
		);
	}

	public function testWrongMode(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(false)->once();

		$txt = $this->dumpExecute($this->newConsole());

		// The node bails out before reading any frame, so it must NOT emit a response frame
		// (ejabberd has not asked anything yet); it only fails with a non-zero exit code.
		rewind($this->outputStream);
		$this->assertSame('', stream_get_contents($this->outputStream), 'No protocol frame before the first request');
		$this->assertSame(1, $this->consoleExecReturn);
		$this->assertEquals("[Error] The node isn't ready.\n", $txt);
	}

	#[\PHPUnit\Framework\Attributes\TestDox('A lost database connection answers the pending request and then exits')]
	public function testDatabaseDisconnected(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldReceive('isConnected')->andReturn(false)->once();
		// The request must not even be dispatched to the auth service.
		$this->auth->shouldNotReceive('authenticate');

		$this->sendInput('auth:admin:friendica.local:admin');
		rewind($this->inputStream);

		$txt = $this->dumpExecute($this->newConsole());

		$this->assertSame(1, $this->consoleExecReturn);
		$this->assertEquals("[Error] the database connection went down\n", $txt);
		// ejabberd is waiting for an answer to its frame: it must get a "failed" rather than
		// nothing (which would block it until its own 30s call timeout).
		rewind($this->outputStream);
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('isuser command is dispatched to the auth service and the response is encoded correctly')]
	public function testIsUserDispatch(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldReceive('isUser')->with('admin', 'friendica.local')->andReturn(true)->once();
		$this->auth->shouldReceive('isUser')->with('nobody', 'friendica.local')->andReturn(false)->once();

		$this->sendInput('isuser:admin:friendica.local');
		$this->sendInput('isuser:nobody:friendica.local');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertSuccess();
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('auth command is dispatched to the auth service and the response is encoded correctly')]
	public function testAuthDispatch(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldReceive('authenticate')->with('admin', 'friendica.local', 'admin')->andReturn(true)->once();
		$this->auth->shouldReceive('authenticate')->with('admin', 'friendica.local', 'wrong')->andReturn(false)->once();

		$this->sendInput('auth:admin:friendica.local:admin');
		$this->sendInput('auth:admin:friendica.local:wrong');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertSuccess();
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('A password containing a newline byte is parsed as one frame (fread, not fgets)')]
	public function testPasswordWithNewlineByteIsOneFrame(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		// The whole "wrong\npart" must arrive as a single password argument.
		$this->auth->shouldReceive('authenticate')->with('admin', 'friendica.local', "wrong\npart")->andReturn(false)->once();

		$this->sendInput("auth:admin:friendica.local:wrong\npart");
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('setpass is always rejected without calling the auth service')]
	public function testSetpassAlwaysRejected(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldNotReceive('isUser');
		$this->auth->shouldNotReceive('authenticate');

		$this->sendInput('setpass:admin:friendica.local:newpass');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('Frames with too few segments return failed without calling the auth service')]
	public function testTooShortFrames(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldNotReceive('isUser');
		$this->auth->shouldNotReceive('authenticate');

		$this->sendInput('isuser:onlyone');
		$this->sendInput('auth:only:two');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertFailed();
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('A zero-length frame is treated as end-of-stream: no dispatch, no response, clean stop')]
	public function testZeroLengthFrameStopsTheLoop(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldNotReceive('isUser');
		$this->auth->shouldNotReceive('authenticate');

		// A header announcing length 0 — ejabberd's way of saying there is nothing to process.
		fwrite($this->inputStream, pack('n', 0));
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		// The frame is not dispatched and no response is written; the read loop simply ends.
		rewind($this->outputStream);
		$this->assertSame('', stream_get_contents($this->outputStream));
	}

	#[\PHPUnit\Framework\Attributes\TestDox('Unknown commands return failed without calling the auth service')]
	public function testUnknownCommand(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldNotReceive('isUser');
		$this->auth->shouldNotReceive('authenticate');

		$this->sendInput('notacommand:foo:bar');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertFailed();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('Password has ":" ')]
	public function testPasswordWithColon(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		// The whole "wrong\npart" must arrive as a single password argument.
		$this->auth->shouldReceive('authenticate')->with('admin', 'friendica.local', "pass:code")->andReturn(false)->once();

		$this->sendInput("auth:admin:friendica.local:pass:code");
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertFailed();
	}


	#[\PHPUnit\Framework\Attributes\TestDox('An exception from the auth service is caught: the request fails but the daemon keeps serving')]
	public function testAuthServiceExceptionIsContainedPerRequest(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		// First request: the auth service blows up. The daemon must catch it, answer "failed"
		// for this single request, and NOT take the whole worker down.
		$this->auth->shouldReceive('isUser')->with('boom', 'friendica.local')->andThrow(new \RuntimeException('kaboom'))->once();
		// Second request on the SAME daemon instance must still be served normally.
		$this->auth->shouldReceive('isUser')->with('admin', 'friendica.local')->andReturn(true)->once();

		$this->sendInput('isuser:boom:friendica.local');
		$this->sendInput('isuser:admin:friendica.local');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertFailed();
		$this->assertSuccess();
	}

	#[\PHPUnit\Framework\Attributes\TestDox('With jabber.debug enabled the verbose log branch is taken while the request is still served correctly')]
	public function testDebugModeServesRequest(): void
	{
		// debug=1 flips the guard in writeLog() so the LOG_DEBUG branch is actually executed
		// (instead of returning early). The request itself must still be answered correctly.
		$this->config->shouldReceive('get')->with('jabber', 'debug')->andReturn(1)->once();
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$this->auth->shouldReceive('authenticate')->with('admin', 'friendica.local', 'admin')->andReturn(true)->once();

		$this->sendInput('auth:admin:friendica.local:admin');
		rewind($this->inputStream);

		$this->dumpExecute($this->newConsole());

		rewind($this->outputStream);
		$this->assertSuccess();
	}

	public function testGetHelp(): void
	{
		$theHelp = <<<HELP
auth_ejabberd - Daemon that communicates with the ejabberd server
Synopsis
	bin/console auth_ejabberd [-h|--help|-?] [-v]

Description
    ejabberd supports external authentication via a small daemon (script or binary)
    that communicates with the ejabberd server using STDIN and STDOUT and a binary protocol.

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.

Examples
	bin/console auth_ejabberd
		Starts the daemon and reads per STDIN

HELP;
		$console = $this->newConsole();
		$console->setOption('help', true);

		self::assertEquals($theHelp, $this->dumpExecute($console));
	}
}
