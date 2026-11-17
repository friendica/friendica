<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Friendica\Photo;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Photo\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class DeleteTest extends ApiTestCase
{
	protected const PHOTO_RESOURCE_ID = '709057080661a283a6aa598501504178';

	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiPhotoDeleteWithoutPhotoId(): void
	{
		$this->expectException(BadRequestException::class);

		$request = new ServerRequest('POST', 'https://friendica.local/api/friendica/photo/delete');

		$this->createModule()->handleRequest($request);
	}

	public function testApiPhotoDeleteWithWrongPhotoId(): void
	{
		$this->expectException(BadRequestException::class);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photo/delete'))
			->withParsedBody([
				'photo_id' => 1,
			]);

		$this->createModule()->handleRequest($request);
	}

	public function testApiPhotoDeleteValid(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photo/delete'))
			->withParsedBody([
				'photo_id' => self::PHOTO_RESOURCE_ID,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('photo with id `' . self::PHOTO_RESOURCE_ID . '` has been deleted from server.', $json->message);

		self::assertFalse(DI::dba()->exists('photo', ['resource-id' => self::PHOTO_RESOURCE_ID, 'uid' => 42]));
	}

	public function testApiPhotoDeleteWithRawJsonBody(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photo/delete'))
			->withParsedBody([
				'photo_id' => self::PHOTO_RESOURCE_ID,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$responseText = (string) $response->getBody();

		self::assertJson($responseText);

		$json = json_decode($responseText);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('photo with id `' . self::PHOTO_RESOURCE_ID . '` has been deleted from server.', $json->message);
	}

	public function testApiPhotoDeleteWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photo/delete'))
			->withParsedBody([
				'photo_id' => self::PHOTO_RESOURCE_ID,
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

	private function createModule(array $parameters = []): Delete
	{
		return new Delete(
			DI::postDeletion(),
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
