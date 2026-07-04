<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Friendica\Photoalbum;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Photoalbum\Delete;
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
		(new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

	}

	public function testWrong(): void
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'album' => 'album_name',
			]);
	}

	public function testValidWithDelete(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());

		$response = (new Delete(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run(
				$this->httpExceptionMock,
				['album' => 'test_album'],
			);

		$json = $this->toJson($response);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('album `test_album` with all containing photos has been deleted.', $json->message);
	}
}
