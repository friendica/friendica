<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Profile;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\EarlyExitException;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Profile\Index;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

class IndexTest extends FixtureTestCase
{
	private const NICKNAME = 'selfcontact'; // Local user from api.fixture.php.

	private array $originalServer;

	protected function setUp(): void
	{
		parent::setUp();
		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);
		// Owner data is cached statically per uid, fixture changes would not be seen otherwise.
		(new \ReflectionProperty(User::class, 'owner'))->setValue(null, []);
		$this->originalServer = $_SERVER;
	}

	protected function tearDown(): void
	{
		$_SERVER = $this->originalServer;
		parent::tearDown();
	}

	public function testHeadDoesNotRenderTheWall(): void
	{
		$response = $this->handle('HEAD', self::NICKNAME);

		self::assertSame(200, $response->getStatusCode());
		self::assertSame(ICanCreateResponses::TYPE_HTML, $response->getHeaderLine(ICanCreateResponses::X_HEADER));
		self::assertSame('', (string) $response->getBody());
		self::assertSame([], DI::session()->get('last_updated', []), 'The wall query must not run.');
	}

	public function testGetRendersTheWall(): void
	{
		$response = $this->handle('GET', self::NICKNAME);

		self::assertSame(200, $response->getStatusCode());
		self::assertNotSame('', (string) $response->getBody());
		self::assertNotSame([], DI::session()->get('last_updated', []));
	}

	public function testHeadOnUnknownNicknameIsNotFound(): void
	{
		$this->expectException(NotFoundException::class);
		$this->handle('HEAD', 'nobody');
	}

	public function testHeadOnHiddenWallRedirects(): void
	{
		DI::dba()->update('user', ['hidewall' => true], ['nickname' => self::NICKNAME]);

		$this->expectException(FoundException::class);
		$this->handle('HEAD', self::NICKNAME);
	}

	public function testHeadOnBlockedPublicAccessShowsLogin(): void
	{
		DI::config()->set('system', 'block_public', true);

		$response = $this->handle('HEAD', self::NICKNAME);

		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('id="login-form"', (string) $response->getBody());
	}

	/** @return array<string, array{string}> */
	public static function methods(): array
	{
		return [
			'GET'  => ['GET'],
			'HEAD' => ['HEAD'],
		];
	}

	#[DataProvider('methods')]
	public function testActivityPubRequestReturnsJson(string $method): void
	{
		$_SERVER['HTTP_ACCEPT'] = 'application/activity+json';

		try {
			$this->handle($method, self::NICKNAME);
			self::fail('ActivityPub requests must exit early.');
		} catch (EarlyExitException $e) {
			$response = $e->getResponse();
		}

		self::assertSame(200, $response->getStatusCode());
		self::assertSame(ICanCreateResponses::TYPE_JSON, $response->getHeaderLine(ICanCreateResponses::X_HEADER));
		self::assertSame(DI::baseUrl() . '/profile/' . self::NICKNAME, json_decode((string) $response->getBody(), true)['id'] ?? null);
	}

	private function handle(string $method, string $nickname): ResponseInterface
	{
		// Arguments maps HEAD to GET, only the server array keeps the real method.
		$module = $this->dice->create(Index::class, [['REQUEST_METHOD' => $method], ['nickname' => $nickname]]);

		return $module->handleRequest(new ServerRequest($method, DI::baseUrl() . '/profile/' . $nickname));
	}
}
