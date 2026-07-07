<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\GnuSocial\Help;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\GNUSocial\Help\Test;
use Friendica\Test\ApiTestCase;

class TestTest extends ApiTestCase
{
	public function testJson(): void
	{
		$response = (new Test(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json'])) // @phpstan-ignore method.deprecated
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());
		self::assertEquals('ok', $json);
	}

	public function testXml(): void
	{
		$response = (new Test(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml'])) // @phpstan-ignore method.deprecated
			->run($this->httpExceptionMock);

		self::assertEquals([
			'Content-type'                => ['text/xml'],
			ICanCreateResponses::X_HEADER => ['xml'],
		], $response->getHeaders());
		self::assertXml($response->getBody(), 'ok');
	}

	public function testHandleRequestGnusocialHelpTestReturnsResult(): void
	{
		$module = new Test(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn([]);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$json = $this->toJson($response);

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());
		self::assertEquals('ok', $json);
	}

	public function testHandleRequestGnusocialHelpTestWithTextReturnsResult(): void
	{
		$module = new Test(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = $this->createMock(\Friendica\App\Request::class);
		$request->method('getAllInput')->willReturn(['text' => 'test']);
		$request->method('getQueryString')->willReturn('');
		$response = $module->handleRequest($request);

		$json = $this->toJson($response);

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());
		self::assertEquals('ok', $json);
	}
}
