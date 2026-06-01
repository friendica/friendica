<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Integration\Console;

use Friendica\App\Mode;
use Friendica\Console\EJabberdAuthDaemon;
use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Security\EJabberdAuth;
use Friendica\Test\FixtureTestCase;
use Mockery;

/**
 * End-to-end integration test for the ejabberd external auth daemon.
 *
 * Unlike {@see \Friendica\Test\Unit\Console\EJabberdAuthDaemonTest} (which mocks the auth service) and
 * {@see \Friendica\Test\Integration\Security\EJabberdAuthTest} (which tests the service in isolation),
 * this test wires the REAL command, the REAL
 * {@see EJabberdAuth} service and the REAL fixture database together and drives them through
 * the genuine binary wire protocol over in-memory streams.
 *
 * What this covers end to end:
 *   - the 2-byte length-prefixed frame parsing
 *   - command dispatch
 *   - the real auth service against the real (fixture) database
 *   - the binary response encoding ejabberd expects
 *   - the read loop processing multiple requests until STDIN EOF
 *
 * Only the local auth paths are exercised here (no network): the remote /noscrape and
 * verify_credentials fallbacks are covered with mocked HTTP in EJabberdAuthTest.
 * It therefore requires a running database and runs in the "integration" test suite.
 */
class EJabberdAuthDaemonIntegrationTest extends FixtureTestCase
{
	private $inputStream;
	private $outputStream;

	/** @var Mode */
	private $mode;

	protected function setUp(): void
	{
		parent::setUp();

		$this->loadFixture(__DIR__ . '/../../Fixtures/ejabberd/fixture.php', DI::dba());

		// Real "normal" mode so the daemon actually serves requests.
		$this->mode = Mockery::mock(Mode::class);
		$this->mode->shouldReceive('isNormal')->andReturn(true);

		$this->inputStream  = fopen('php://memory', 'r+');
		$this->outputStream = fopen('php://memory', 'w+');
	}

	/**
	 * Sends one or more real protocol frames through the daemon and returns the decoded
	 * boolean results (true = authenticated/found, false = rejected).
	 *
	 * @param string[] $payloads
	 *
	 * @return bool[]
	 */
	private function exchange(array $payloads): array
	{
		foreach ($payloads as $payload) {
			fwrite($this->inputStream, pack('n', strlen($payload)) . $payload);
		}
		rewind($this->inputStream);

		// This test only drives the LOCAL auth paths, so the HTTP client must never be touched.
		// Injecting a strict mock instead of the real DI::httpClient() makes that contract an
		// assertion AND avoids building the real client, whose construction reads the DB config
		// and would otherwise contend for a lock with this test's own (fixture) transaction.
		$httpClient = Mockery::mock(ICanSendHttpRequests::class);
		$httpClient->shouldNotReceive('get');
		$httpClient->shouldNotReceive('head');

		// Build the command with the REAL auth service backed by the REAL fixture database.
		$auth = new EJabberdAuth(
			DI::config(),
			DI::pConfig(),
			DI::dba(),
			DI::baseUrl(),
			$httpClient,
		);

		$console = new EJabberdAuthDaemon(
			$this->mode,
			DI::config(),
			$auth,
			['consoleTest.php'],
			$this->inputStream,
			$this->outputStream,
		);

		$this->assertSame(0, $console->execute());

		rewind($this->outputStream);
		$raw = stream_get_contents($this->outputStream);

		// Decode the stream of 4-byte response frames (2-byte length + 2-byte result).
		$results = [];
		for ($offset = 0; $offset + 4 <= strlen($raw); $offset += 4) {
			$frame     = substr($raw, $offset, 4);
			$results[] = ($frame === pack('nn', 2, 1));
		}

		return $results;
	}

	public function testLocalValidPasswordIsAccepted(): void
	{
		$this->assertSame([true], $this->exchange(['auth:admin:friendica.local:admin']));
	}

	public function testLocalWrongPasswordIsRejected(): void
	{
		$this->assertSame([false], $this->exchange(['auth:admin:friendica.local:wrongpassword']));
	}

	public function testLocalUnknownUserIsRejected(): void
	{
		$this->assertSame([false], $this->exchange(['auth:nobody:friendica.local:whatever']));
	}

	public function testIsUserKnownAndUnknown(): void
	{
		$this->assertSame(
			[true, false],
			$this->exchange([
				'isuser:admin:friendica.local',
				'isuser:nobody:friendica.local',
			]),
		);
	}

	public function testXmppApplicationPasswordIsAccepted(): void
	{
		DI::pConfig()->set(51, 'xmpp', 'password', 'xmpp-secret');

		$this->assertSame([true], $this->exchange(['auth:admin:friendica.local:xmpp-secret']));
	}

	public function testSetpassIsAlwaysRejected(): void
	{
		$this->assertSame([false], $this->exchange(['setpass:admin:friendica.local:newpass']));
	}

	/**
	 * The decisive end-to-end scenario: a full session with multiple mixed requests, served
	 * sequentially by one daemon instance until STDIN is closed - exactly how ejabberd drives
	 * a pooled worker.
	 */
	public function testMixedSessionIsServedSequentially(): void
	{
		$this->assertSame(
			[true, false, true, false, true],
			$this->exchange([
				'auth:admin:friendica.local:admin',          // valid
				'auth:admin:friendica.local:wrongpassword',  // wrong password
				'isuser:admin:friendica.local',              // known user
				'isuser:nobody:friendica.local',             // unknown user
				'auth:user:friendica.local:user',            // second valid account
			]),
		);
	}

	public function testMalformedFrameDoesNotBreakSession(): void
	{
		$this->assertSame(
			[false, true],
			$this->exchange([
				'notacommand:foo:bar',               // unknown command -> rejected
				'auth:admin:friendica.local:admin',  // daemon keeps serving -> valid
			]),
		);
	}

	/**
	 * A payload containing a newline byte must be parsed correctly: the framing is binary and
	 * must not be split on \n (this is the fread-vs-fgets regression guard).
	 */
	public function testPayloadWithNewlineByteIsHandled(): void
	{
		// A wrong password that happens to contain a newline; must be cleanly rejected, not truncated.
		$this->assertSame(
			[false, true],
			$this->exchange([
				"auth:admin:friendica.local:wrong\npart",  // newline inside payload
				'auth:admin:friendica.local:admin',        // next frame still parses correctly
			]),
		);
	}
}
