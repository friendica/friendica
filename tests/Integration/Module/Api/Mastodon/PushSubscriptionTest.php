<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\PushSubscription;
use Friendica\Security\BasicAuth;
use Friendica\Security\OAuth;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class PushSubscriptionTest extends ApiTestCase
{
	/** OAuth bearer token of the test application */
	private const ACCESS_TOKEN = 'testaccesstoken';

	protected function setUp(): void
	{
		parent::setUp();

		$this->resetAuthStatics();

		// Avoid scheduling worker tasks during the OAuth token lookup
		DI::pConfig()->set(1042, 'suggestion', 'last_update', time());
	}

	protected function tearDown(): void
	{
		$this->resetAuthStatics();

		parent::tearDown();
	}

	public function testApiAccountVerifyCredentials(): void
	{
		$applicationId = $this->createApplication();

		$this->useHttpMethod(Router::POST);
		$module = $this->createModule();

		$request = (new ServerRequest('POST', 'https://friendica.local/api/v1/push/subscription'))
			->withParsedBody([
				'subscription' => [
					'endpoint' => 'https://example.org/push',
					'keys'     => ['p256dh' => 'testpubkey', 'auth' => 'testsecret'],
				],
				'data' => [
					'alerts' => [
						'follow'         => true,
						'favourite'      => true,
						'reblog'         => true,
						'mention'        => true,
						'poll'           => true,
						'follow_request' => true,
						'status'         => true,
					],
				],
			]);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$subscription = $this->toJson($e->getResponse());

			self::assertEquals(200, $e->getResponse()->getStatusCode());
			self::assertEquals('https://example.org/push', $subscription->endpoint);
			self::assertIsString($subscription->server_key);
			self::assertNotEmpty($subscription->server_key);
			self::assertTrue($subscription->alerts->follow);
			self::assertTrue($subscription->alerts->favourite);
			self::assertTrue($subscription->alerts->reblog);
			self::assertTrue($subscription->alerts->mention);
			self::assertTrue($subscription->alerts->poll);

			self::assertTrue(DI::dba()->exists('subscription', ['application-id' => $applicationId, 'uid' => 1042]));
		}
	}

	public function testApiAccountVerifyCredentialsWithoutAuthenticatedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$this->useHttpMethod(Router::POST);
		$module = $this->createModule();

		$request = new ServerRequest('POST', 'https://friendica.local/api/v1/push/subscription');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());
		}
	}

	public function testApiPushSubscriptionGetWithoutSubscription(): void
	{
		$this->createApplication();

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/push/subscription');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(404, $e->getResponse()->getStatusCode());
		}
	}

	public function testApiPushSubscriptionGet(): void
	{
		$applicationId = $this->createApplication();
		$this->createSubscription($applicationId);

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/push/subscription');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$subscription = json_decode((string) $response->getBody());

		self::assertEquals('https://example.org/push', $subscription->endpoint);
		self::assertTrue($subscription->alerts->follow);
		self::assertFalse($subscription->alerts->favourite);
		self::assertTrue($subscription->alerts->reblog);
		self::assertFalse($subscription->alerts->mention);
		self::assertTrue($subscription->alerts->poll);
	}

	public function testApiPushSubscriptionPut(): void
	{
		$applicationId = $this->createApplication();
		$this->createSubscription($applicationId);

		$this->useHttpMethod(Router::PUT);
		$module = $this->createModule();

		$request = (new ServerRequest('PUT', 'https://friendica.local/api/v1/push/subscription'))
			->withParsedBody([
				'data' => [
					'alerts' => [
						'follow'  => 'true',
						'mention' => 'true',
					],
				],
			]);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$subscription = $this->toJson($e->getResponse());

			self::assertEquals(200, $e->getResponse()->getStatusCode());
			self::assertTrue($subscription->alerts->follow);
			self::assertFalse($subscription->alerts->favourite);
			self::assertFalse($subscription->alerts->reblog);
			self::assertTrue($subscription->alerts->mention);
			self::assertFalse($subscription->alerts->poll);

			self::assertTrue(DI::dba()->exists('subscription', ['application-id' => $applicationId, 'uid' => 1042]));
		}
	}

	public function testApiPushSubscriptionDelete(): void
	{
		$applicationId = $this->createApplication();
		$this->createSubscription($applicationId);

		$this->useHttpMethod(Router::DELETE);
		$module = $this->createModule();

		$request = new ServerRequest('DELETE', 'https://friendica.local/api/v1/push/subscription');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$subscription = $this->toJson($e->getResponse());

			self::assertEquals(200, $e->getResponse()->getStatusCode());
			self::assertSame([], $subscription);

			self::assertFalse(DI::dba()->exists('subscription', ['application-id' => $applicationId, 'uid' => 1042]));
		}
	}

	private function createApplication(): int
	{
		DI::dba()->insert('application', [
			'client_id'     => 'testclientid',
			'client_secret' => 'testclientsecret',
			'name'          => 'Test application',
			'redirect_uri'  => 'urn:ietf:wg:oauth:2.0:oob',
			'scopes'        => 'read write follow push',
			'read'          => true,
			'write'         => true,
			'follow'        => true,
			'push'          => true,
		]);

		$application = DI::dba()->selectFirst('application', ['id'], ['client_id' => 'testclientid']);

		DI::dba()->insert('application-token', [
			'application-id' => $application['id'],
			'uid'            => 1042,
			'code'           => 'testcode',
			'access_token'   => self::ACCESS_TOKEN,
			'created_at'     => '2020-01-01 12:00:00',
			'scopes'         => 'read write follow push',
			'read'           => true,
			'write'          => true,
			'follow'         => true,
			'push'           => true,
		]);

		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . self::ACCESS_TOKEN;

		return (int) $application['id'];
	}

	private function createSubscription(int $applicationId): void
	{
		DI::dba()->insert('subscription', [
			'application-id' => $applicationId,
			'uid'            => 1042,
			'endpoint'       => 'https://example.org/push',
			'pubkey'         => 'testpubkey',
			'secret'         => 'testsecret',
			'follow'         => true,
			'favourite'      => false,
			'reblog'         => true,
			'mention'        => false,
			'poll'           => true,
			'follow_request' => false,
			'status'         => true,
		]);
	}

	private function resetAuthStatics(): void
	{
		unset($_SERVER['HTTP_AUTHORIZATION']);

		BasicAuth::setCurrentUserID();

		(new \ReflectionProperty(OAuth::class, 'current_token'))->setValue(null, []);
		(new \ReflectionProperty(OAuth::class, 'current_user_id'))->setValue(null, 0);
		(new \ReflectionProperty(BasicAuth::class, 'current_token'))->setValue(null, []);
	}

	private function createModule(): PushSubscription
	{
		return new PushSubscription(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), DI::mstdnSubscription(), []);
	}
}
