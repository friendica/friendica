<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Favorites;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Verb;
use Friendica\Module\Api\Twitter\Favorites\Create;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Protocol\Activity;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class CreateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiFavoritesCreateDestroyWithInvalidId(): void
	{
		$this->expectException(BadRequestException::class);

		$request = new ServerRequest('POST', 'https://friendica.local/api/favorites/create.json');

		$this->createModule()->handleRequest($request);
	}

	public function testApiFavoritesCreateDestroyWithCreateAction(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/favorites/create.json'))
			->withParsedBody(['id' => 3]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStatus($json);
		self::assertEquals(3, $json->id);
		self::assertTrue($json->favorited);

		self::assertTrue(DI::dba()->exists('post-user', [
			'uid'           => 42,
			'thr-parent-id' => 3,
			'vid'           => Verb::getID(Activity::LIKE),
			'gravity'       => Item::GRAVITY_ACTIVITY,
			'deleted'       => false,
		]));
	}

	public function testApiFavoritesCreateDestroyWithCreateActionAndRss(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/favorites/create.json'))
			->withParsedBody(['id' => 3]);

		$response = $this->createModule(['extension' => ICanCreateResponses::TYPE_RSS])->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'statuses');
	}

	public function testApiFavoritesCreateDestroyWithoutAuthenticatedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/favorites/create.json'))
			->withParsedBody(['id' => 3]);

		$module = $this->createModule();

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(array $parameters = []): Create
	{
		return new Create(
			DI::mstdnError(),
			DI::appHelper(),
			DI::l10n(),
			DI::baseUrl(),
			DI::args(),
			DI::logger(),
			DI::profiler(),
			DI::apiResponse(),
			[],
			$parameters,
		);
	}
}
