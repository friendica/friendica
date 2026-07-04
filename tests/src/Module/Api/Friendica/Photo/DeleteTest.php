<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Friendica\Photo;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Photo\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class DeleteTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testEmpty(): void
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run($this->httpExceptionMock);
	}

	public function testWithoutAuthenticatedUser(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');
	}

	public function testWrong(): void
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run($this->httpExceptionMock, ['photo_id' => 1]);
	}

	public function testValidWithPost(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$response = (new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'photo_id' => '709057080661a283a6aa598501504178',
			]);

		$json = $this->toJson($response);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('photo with id `709057080661a283a6aa598501504178` has been deleted from server.', $json->message);
	}

	public function testValidWithDelete(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$response = (new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'photo_id' => '709057080661a283a6aa598501504178',
			]);

		$responseText = (string) $response->getBody();

		self::assertJson($responseText);

		$json = json_decode($responseText);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('photo with id `709057080661a283a6aa598501504178` has been deleted from server.', $json->message);
	}
}
