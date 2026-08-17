<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Media;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Media\Upload;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;

class UploadTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Test the \Friendica\Module\Api\Twitter\Media\Upload module.
	 */
	public function testApiMediaUpload(): void
	{
		$this->expectException(BadRequestException::class);

		// @phpstan-ignore method.deprecated
		(new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the \Friendica\Module\Api\Twitter\Media\Upload module without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiMediaUploadWithoutAuthenticatedUser(): void
	{
		$this->expectException(UnauthorizedException::class);
		AuthTestConfig::$authenticated = false;

		// @phpstan-ignore method.deprecated
		(new class (DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []) extends Upload {
			public function earlyJsonError(int $httpCode, mixed $content, string $contentType = 'application/json'): never
			{
				if ($httpCode === 401) {
					throw new UnauthorizedException(json_encode($content));
				}

				parent::earlyJsonError($httpCode, $content, $contentType);
			}
		})->run($this->httpExceptionMock);
	}

	/**
	 * Test the \Friendica\Module\Api\Twitter\Media\Upload module with an invalid uploaded media.
	 *
	 * @return void
	 */
	public function testApiMediaUploadWithMedia(): void
	{
		$this->expectException(InternalServerErrorException::class);
		$_FILES = [
			'media' => [
				'id'       => 666,
				'tmp_name' => 'tmp_name',
			],
		];

		(new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the \Friendica\Module\Api\Twitter\Media\Upload module with an valid uploaded media.
	 *
	 * @return void
	 */
	public function testApiMediaUploadWithValidMedia(): void
	{
		$_FILES = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png',
			],
		];

		// @phpstan-ignore method.deprecated
		// @phpstan-ignore method.deprecated
		$response = (new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$media = $this->toJson($response);

		self::assertEquals('image/png', $media->image->image_type);
		self::assertEquals(1, $media->image->w);
		self::assertEquals(1, $media->image->h);
		self::assertNotEmpty($media->image->friendica_preview_url);
	}
}
