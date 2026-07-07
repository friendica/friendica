<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
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

	protected function tearDown(): void
	{
		$_FILES = [];
		parent::tearDown();
	}

	/**
	 * Test the \Friendica\Module\Api\Twitter\Media\Upload module.
	 */
	public function testApiMediaUpload(): void
	{
		$this->expectException(BadRequestException::class);

		(new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [])) // @phpstan-ignore method.deprecated
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

		(new class (DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []) extends Upload { // @phpstan-ignore method.deprecated
			public function jsonError(int $httpCode, $content, string $content_type = 'application/json')
			{
				if ($httpCode === 401) {
					throw new UnauthorizedException(json_encode($content));
				}

				parent::jsonError($httpCode, $content, $content_type);
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

		(new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [])) // @phpstan-ignore method.deprecated
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

		$response = (new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [])) // @phpstan-ignore method.deprecated
			->run($this->httpExceptionMock);

		$media = $this->toJson($response);

		self::assertEquals('image/png', $media->image->image_type);
		self::assertEquals(1, $media->image->w);
		self::assertEquals(1, $media->image->h);
		self::assertNotEmpty($media->image->friendica_preview_url);
	}

	public function testHandleRequestMediaUploadThrowsBadRequestException(): void
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);

		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn([]);
		$request->method('getQueryString')->willReturn('');
		$module->handleRequest($request);
	}

	public function testHandleRequestMediaUploadWithImageReturnsMedia(): void
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

		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn(['media' => $this->getTempImage()]);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$media = $this->toJson($response);

		self::assertEquals('image/png', $media->image->image_type);
		self::assertEquals(1, $media->image->w);
		self::assertEquals(1, $media->image->h);
		self::assertNotEmpty($media->image->friendica_preview_url);
	}

	public function testHandleRequestMediaUploadWithOtherImageReturnsMedia(): void
	{
		$_FILES = [
			'media' => [
				'id'       => 667,
				'size'     => 667,
				'width'    => 667,
				'height'   => 667,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer2.png',
				'type'     => 'image/png',
			],
		];

		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn(['media' => $this->getTempImage()]);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$media = $this->toJson($response);

		self::assertEquals('image/png', $media->image->image_type);
		self::assertEquals(1, $media->image->w);
		self::assertEquals(1, $media->image->h);
		self::assertNotEmpty($media->image->friendica_preview_url);
	}

	public function testHandleRequestMediaUploadWithUrlReturnsMedia(): void
	{
		$_FILES = [
			'media' => [
				'id'       => 668,
				'size'     => 668,
				'width'    => 668,
				'height'   => 668,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer3.png',
				'type'     => 'image/png',
			],
		];

		$module = new Upload(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn(['media_url' => 'http://example.com/image.jpg']);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$media = $this->toJson($response);

		self::assertEquals('image/png', $media->image->image_type);
		self::assertEquals(1, $media->image->w);
		self::assertEquals(1, $media->image->h);
		self::assertNotEmpty($media->image->friendica_preview_url);
	}
}
