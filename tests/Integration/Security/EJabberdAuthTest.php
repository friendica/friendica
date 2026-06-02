<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Integration\Security;

use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Security\EJabberdAuth;
use Friendica\Test\FixtureTestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * Integration tests for the {@see EJabberdAuth} service.
 *
 * Tests the auth business logic (user existence, password verification, remote fallback)
 * against the real fixture database, independently of the binary wire-protocol layer
 * ({@see \Friendica\Console\EJabberdAuthDaemon}). Only the outgoing HTTP client is mocked,
 * so the remote /noscrape and verify_credentials fallbacks can be exercised without network.
 *
 * Extends FixtureTestCase so the database transaction is rolled back and Mockery
 * expectations are verified/closed after every test (in that order), preventing any
 * state from leaking between tests.
 */
class EJabberdAuthTest extends FixtureTestCase
{
	/** @var ICanSendHttpRequests|MockInterface */
	private $httpClient;

	/** @var int Configured HTTP timeout */
	private const TIMEOUT = 5;

	protected function setUp(): void
	{
		parent::setUp();

		$this->loadFixture(__DIR__ . '/../../Fixtures/ejabberd/fixture.php', DI::dba());

		$this->httpClient = Mockery::mock(ICanSendHttpRequests::class);
	}

	private function newService(): EJabberdAuth
	{
		return new EJabberdAuth(
			DI::config(),
			DI::pConfig(),
			DI::dba(),
			DI::baseUrl(),
			$this->httpClient,
		);
	}

	// ------------------------------------------------------------------
	// isUser: local host
	// ------------------------------------------------------------------

	public function testIsUserLocalKnownUser(): void
	{
		$this->assertTrue($this->newService()->isUser('admin', 'friendica.local'));
	}

	public function testIsUserLocalUnknownUser(): void
	{
		// The local database is authoritative for our own host: an unknown user must not
		// trigger a remote self-lookup (ejabberd calls isuser very frequently).
		$this->httpClient->shouldNotReceive('get');

		$this->assertFalse($this->newService()->isUser('nobody', 'friendica.local'));
	}

	// ------------------------------------------------------------------
	// isUser: remote host via /noscrape
	// ------------------------------------------------------------------

	public static function dataIsUserRemote(): array
	{
		// returnCode values are strings on purpose: ICanHandleHttpResponses::getReturnCode()
		// returns a string, and the service must compare against that correctly.
		return [
			'found' => [
				'isSuccess'  => true,
				'returnCode' => '200',
				'body'       => json_encode(['nick' => 'someuser']),
				'expected'   => true,
			],
			'http-not-200' => [
				'isSuccess'  => true,
				'returnCode' => '202',
				'body'       => json_encode(['nick' => 'someuser']),
				'expected'   => false,
			],
			'http-failure' => [
				'isSuccess'  => false,
				'returnCode' => '500',
				'body'       => '',
				'expected'   => false,
			],
			'nick-mismatch' => [
				'isSuccess'  => true,
				'returnCode' => '200',
				'body'       => json_encode(['nick' => 'different']),
				'expected'   => false,
			],
			'empty-body' => [
				'isSuccess'  => true,
				'returnCode' => '200',
				'body'       => json_encode([]),
				'expected'   => false,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsUserRemote')]
	public function testIsUserRemote(bool $isSuccess, string $returnCode, string $body, bool $expected): void
	{
		$response = Mockery::mock(ICanHandleHttpResponses::class);
		$response->shouldReceive('isSuccess')->andReturn($isSuccess);
		$response->shouldReceive('getReturnCode')->andReturn($returnCode);
		$response->shouldReceive('getBodyString')->andReturn($body);

		$this->httpClient->shouldReceive('get')
			->with(
				'https://friendi.ca/noscrape/someuser',
				HttpClientAccept::JSON,
				[
					HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
					HttpClientOptions::TIMEOUT => self::TIMEOUT,
				],
			)
			->andReturn($response)
			->once();

		$this->assertSame($expected, $this->newService()->isUser('someuser', 'friendi.ca'));
	}

	// ------------------------------------------------------------------
	// authenticate: local host
	// ------------------------------------------------------------------

	public function testAuthenticateLocalValidPassword(): void
	{
		$this->assertTrue($this->newService()->authenticate('admin', 'friendica.local', 'admin'));
	}

	public function testAuthenticateLocalWrongPassword(): void
	{
		// A failed login for the local host must NOT trigger a redundant HTTP self-call
		// (the old implementation did, which was wasted load); it is simply rejected.
		$this->httpClient->shouldNotReceive('head');

		$this->assertFalse($this->newService()->authenticate('admin', 'friendica.local', 'wrongpassword'));
	}

	public function testAuthenticateLocalUnknownUser(): void
	{
		$this->httpClient->shouldNotReceive('head');

		$this->assertFalse($this->newService()->authenticate('nobody', 'friendica.local', 'whatever'));
	}

	public function testAuthenticateLocalIsCaseInsensitive(): void
	{
		// Friendica stores all nicknames as lowercase (User::create lowercases them).
		// ejabberd (or the XMPP client) may send the JID localpart in any case.
		// normaliseNick() must lowercase so the DB lookup matches.
		$this->assertTrue($this->newService()->authenticate('Admin', 'friendica.local', 'admin'));
	}

	public function testIsUserLocalIsCaseInsensitive(): void
	{
		$this->assertTrue($this->newService()->isUser('Admin', 'friendica.local'));
	}

	public function testAuthenticateLocalXmppAppPassword(): void
	{
		DI::pConfig()->set(51, 'xmpp', 'password', 'xmpp-secret');

		$this->assertTrue($this->newService()->authenticate('admin', 'friendica.local', 'xmpp-secret'));
	}

	public function testAuthenticateLocalWrongXmppAppPassword(): void
	{
		DI::pConfig()->set(51, 'xmpp', 'password', 'xmpp-secret');
		$this->httpClient->shouldNotReceive('head');

		$this->assertFalse($this->newService()->authenticate('admin', 'friendica.local', 'wrong-xmpp'));
	}

	// ------------------------------------------------------------------
	// authenticate: remote host via verify_credentials
	// ------------------------------------------------------------------

	public static function dataAuthenticateRemote(): array
	{
		// returnCode values are strings on purpose (see getReturnCode(): string).
		return [
			'valid' => [
				'isSuccess'  => true,
				'returnCode' => '200',
				'expected'   => true,
			],
			'wrong-password' => [
				'isSuccess'  => true,
				'returnCode' => '401',
				'expected'   => false,
			],
			'only-200-is-valid' => [
				'isSuccess'  => true,
				'returnCode' => '202',
				'expected'   => false,
			],
			'http-failure' => [
				'isSuccess'  => false,
				'returnCode' => '500',
				'expected'   => false,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataAuthenticateRemote')]
	public function testAuthenticateRemote(bool $isSuccess, string $returnCode, bool $expected): void
	{
		$response = Mockery::mock(ICanHandleHttpResponses::class);
		$response->shouldReceive('isSuccess')->andReturn($isSuccess);
		$response->shouldReceive('getReturnCode')->andReturn($returnCode);

		$this->httpClient->shouldReceive('head')
			->with(
				'https://friendi.ca/api/account/verify_credentials.json?skip_status=true',
				[
					HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
					HttpClientOptions::TIMEOUT => self::TIMEOUT,
					HttpClientOptions::AUTH    => ['someuser', 'somepass'],
				],
			)
			->andReturn($response)
			->once();

		$this->assertSame($expected, $this->newService()->authenticate('someuser', 'friendi.ca', 'somepass'));
	}

	// ------------------------------------------------------------------
	// HTTP timeout is taken from configuration
	// ------------------------------------------------------------------

	public function testConfiguredTimeoutIsUsed(): void
	{
		DI::config()->set('jabber', 'auth_http_timeout', 3);

		$response = Mockery::mock(ICanHandleHttpResponses::class);
		$response->shouldReceive('isSuccess')->andReturn(false);
		$response->shouldReceive('getReturnCode')->andReturn('404');
		$response->shouldReceive('getBodyString')->andReturn('');

		// The matcher only accepts the call if the configured timeout (3) is passed through;
		// if the service used any other value, Mockery would report an unmatched call.
		$this->httpClient->shouldReceive('get')
			->with(
				'https://friendi.ca/noscrape/someuser',
				HttpClientAccept::JSON,
				Mockery::on(fn ($opts) => $opts[HttpClientOptions::TIMEOUT] === 3),
			)
			->andReturn($response)
			->once();

		// The lookup must fail (404) AND must have gone through the timeout matcher above.
		$this->assertFalse($this->newService()->isUser('someuser', 'friendi.ca'));
	}

	// ------------------------------------------------------------------
	// A broken HTTP stack must never bubble up — it is treated as "no"
	// ------------------------------------------------------------------

	public function testIsUserRemoteHttpExceptionIsTreatedAsAbsent(): void
	{
		// A failing HTTP stack (DNS error, connection refused, timeout) must be swallowed:
		// the remote user is simply considered "not found", never an uncaught exception that
		// would crash the pooled ejabberd worker.
		$this->httpClient->shouldReceive('get')->andThrow(new \Exception('connection refused'))->once();

		$this->assertFalse($this->newService()->isUser('someuser', 'friendi.ca'));
	}

	public function testAuthenticateRemoteHttpExceptionIsTreatedAsRejected(): void
	{
		// Same contract for the credential check: a transport failure means "rejected", quietly.
		$this->httpClient->shouldReceive('head')->andThrow(new \Exception('timeout'))->once();

		$this->assertFalse($this->newService()->authenticate('someuser', 'friendi.ca', 'somepass'));
	}
}
