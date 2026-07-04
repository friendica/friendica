<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Special;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Special\HTTPException;
use Friendica\Module\Special\Options;
use Friendica\Test\FixtureTestCase;
use Mockery\MockInterface;

class OptionsTest extends FixtureTestCase
{
	/** @var MockInterface|HTTPException */
	protected $httpExceptionMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = \Mockery::mock(HTTPException::class);
	}

	public function testOptionsAll(): void
	{
		$this->useHttpMethod(Router::OPTIONS);

		$response = (new Options(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run($this->httpExceptionMock);

		self::assertEmpty((string) $response->getBody());
		self::assertEquals(204, $response->getStatusCode());
		self::assertEquals('No Content', $response->getReasonPhrase());
		self::assertEquals([
			'Allow'                       => [implode(',', Router::ALLOWED_METHODS)],
			ICanCreateResponses::X_HEADER => ['blank'],
		], $response->getHeaders());
		self::assertEquals(implode(',', Router::ALLOWED_METHODS), $response->getHeaderLine('Allow'));
	}

	public function testOptionsSpecific(): void
	{
		$this->useHttpMethod(Router::OPTIONS);

		$response = (new Options(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'AllowedMethods' => [Router::GET, Router::POST],
		]))->run($this->httpExceptionMock);

		self::assertEmpty((string) $response->getBody());
		self::assertEquals(204, $response->getStatusCode());
		self::assertEquals('No Content', $response->getReasonPhrase());
		self::assertEquals([
			'Allow'                       => [implode(',', [Router::GET, Router::POST])],
			ICanCreateResponses::X_HEADER => ['blank'],
		], $response->getHeaders());
		self::assertEquals(implode(',', [Router::GET, Router::POST]), $response->getHeaderLine('Allow'));
	}
}
