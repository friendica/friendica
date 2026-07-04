<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Friendica\Photoalbum;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Photoalbum\Update;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;

class UpdateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testEmpty(): void
	{
		$this->expectException(BadRequestException::class);
		(new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	public function testTooFewArgs(): void
	{
		$this->expectException(BadRequestException::class);
		(new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'album' => 'album_name',
			]);
	}

	public function testWrongUpdate(): void
	{
		$this->expectException(BadRequestException::class);
		(new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'album'     => 'album_name',
				'album_new' => 'album_name',
			]);
	}

	public function testWithoutAuthenticatedUser(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');
	}

	public function testValid(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$response = (new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'album'     => 'test_album',
				'album_new' => 'test_album_2',
			]);

		$json = $this->toJson($response);

		self::assertEquals('updated', $json->result);
		self::assertEquals('album `test_album` with all containing photos has been renamed to `test_album_2`.', $json->message);
	}
}
