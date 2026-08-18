<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\HTTPException;

use Friendica\App;
use Friendica\DI;
use Friendica\Module\HTTPException\PageNotFound;
use Friendica\Module\Response;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\FixtureTestCase;
use Mockery;
use Mockery\MockInterface;

class PageNotFoundTest extends FixtureTestCase
{
	/** @var MockInterface|ModuleHTTPException */
	protected $httpExceptionMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = Mockery::mock(ModuleHTTPException::class);
	}

	public function testPageNotFoundReturnsResponseForUnknownUrl(): void
	{
		$server = [
			'QUERY_STRING' => '',
			'REQUEST_URI'  => '/unknown',
		];

		$this->httpExceptionMock->shouldReceive('content')
			->once()
			->with(Mockery::type(NotFoundException::class))
			->andReturn('');

		$request      = new App\Request(DI::config(), $server);
		$pageNotFound = new PageNotFound(
			DI::l10n(),
			DI::baseUrl(),
			DI::args(),
			DI::logger(),
			DI::profiler(),
			new Response(),
			$request,
			$server,
		);

		// @phpstan-ignore method.deprecated
		$response = $pageNotFound->run($this->httpExceptionMock);

		self::assertEquals(404, $response->getStatusCode());
	}

	public function testJavaScriptTemplatePrefetchCallsExit(): void
	{
		self::markTestIncomplete(
			'Skipping JavaScript template prefetch test because System::exit() cannot be '
			. 'mocked with PHPUnit. See tests/src/Mod/ItemTest.php for prior art on this limitation.',
		);
	}
}
