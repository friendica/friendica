<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\Media;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Media\Upload;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class UploadTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
		$_FILES = [];
	}

	protected function tearDown(): void
	{
		$_FILES = [];
		parent::tearDown();
	}

	public function testApiMediaUpload(): void
	{
		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('POST', 'https://friendica.local/api/media/upload');

		$this->expectException(BadRequestException::class);

		$module->handleRequest($request);
	}

	public function testApiMediaUploadWithoutAuthenticatedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('POST', 'https://friendica.local/api/media/upload');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());
		}
	}

	public function testApiMediaUploadWithInvalidMedia(): void
	{
		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$_FILES['media'] = [
			'id'       => 666,
			'tmp_name' => 'tmp_name',
		];

		$request = new ServerRequest('POST', 'https://friendica.local/api/media/upload');

		$this->expectException(InternalServerErrorException::class);

		$module->handleRequest($request);
	}

	public function testApiMediaUploadWithValidMedia(): void
	{
		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$_FILES['media'] = [
			'id'       => 666,
			'size'     => 666,
			'width'    => 666,
			'height'   => 666,
			'tmp_name' => $this->getTempImage(),
			'name'     => 'spacer.png',
			'type'     => 'image/png',
		];

		$request = new ServerRequest('POST', 'https://friendica.local/api/media/upload');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$media = $this->toJson($response);

		self::assertEquals('image/png', $media->image->image_type);
		self::assertEquals(1, $media->image->w);
		self::assertEquals(1, $media->image->h);
		self::assertNotEmpty($media->image->friendica_preview_url);
	}
}
