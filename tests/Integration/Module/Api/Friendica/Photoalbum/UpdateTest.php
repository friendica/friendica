<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Friendica\Photoalbum;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Photoalbum\Update;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class UpdateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiPhotoalbumUpdateWithoutData(): void
	{
		$this->expectException(BadRequestException::class);

		$request = new ServerRequest('POST', 'https://friendica.local/api/friendica/photoalbum/update');

		$this->createModule()->handleRequest($request);
	}

	public function testApiPhotoalbumUpdateWithOnlyAlbumName(): void
	{
		$this->expectException(BadRequestException::class);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photoalbum/update'))
			->withParsedBody([
				'album' => 'album_name',
			]);

		$this->createModule()->handleRequest($request);
	}

	public function testApiPhotoalbumUpdateWithNotExistingAlbum(): void
	{
		$this->expectException(BadRequestException::class);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photoalbum/update'))
			->withParsedBody([
				'album'     => 'album_name',
				'album_new' => 'album_name',
			]);

		$this->createModule()->handleRequest($request);
	}

	public function testApiPhotoalbumUpdateValid(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photoalbum/update'))
			->withParsedBody([
				'album'     => 'test_album',
				'album_new' => 'test_album_2',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('updated', $json->result);
		self::assertEquals('album `test_album` with all containing photos has been renamed to `test_album_2`.', $json->message);

		self::assertFalse(DI::dba()->exists('photo', ['uid' => 42, 'album' => 'test_album']));
		self::assertTrue(DI::dba()->exists('photo', ['uid' => 42, 'album' => 'test_album_2']));
	}

	public function testApiPhotoalbumUpdateWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photoalbum/update'))
			->withParsedBody([
				'album'     => 'test_album',
				'album_new' => 'test_album_2',
			]);

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

	private function createModule(array $parameters = []): Update
	{
		return new Update(
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
