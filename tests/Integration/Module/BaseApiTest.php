<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Integration\Module;

use Dice\Dice;
use Friendica\App;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Event\EventDispatcher;
use Friendica\Factory\Api\Mastodon\Error;
use Friendica\Factory\Api\Twitter\User as TwitterUser;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class BaseApiTest extends TestCase
{
	public function testHandleRequestGetReturnsResponse(): void
	{
		$module = $this->createModule(['getMethod' => 'GET']);

		$module->method('content')->willReturn('{"status":"ok"}');

		$request = new ServerRequest('GET', 'https://friendica.local/api/test');
		$result  = $module->handleRequest($request);

		$this->assertEquals(200, $result->getStatusCode());
		$this->assertJson((string) $result->getBody());
	}

	private function createModule(array $options = []): BaseApi&MockObject
	{
		$httpMethod = $options['getMethod'] ?? 'GET';

		$dice = new Dice();
		$dice = $dice->addRule(EventDispatcherInterface::class, [
			'instanceOf' => EventDispatcher::class,
			'shared'     => true,
		]);

		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturn(null);

		$diceMock = $this->createMock(Dice::class);
		$diceMock->method('create')->willReturnCallback(
			static fn (string $name, array $args = [], array $share = []) => $name === IManageConfigValues::class ? $config : $dice->create($name, $args, $share),
		);
		DI::init($diceMock, true);

		$args = $this->createMock(App\Arguments::class);
		$args->method('getQueryString')->willReturn('api/test');
		$args->method('getModuleName')->willReturn('Test');
		$args->method('getMethod')->willReturn($httpMethod);

		$apiResponse = new ApiResponse(
			$this->createStub(L10n::class),
			$args,
			$this->createStub(LoggerInterface::class),
			$this->createStub(App\BaseURL::class),
			$this->createStub(TwitterUser::class),
		);

		return $this->getMockBuilder(BaseApi::class)
			->setConstructorArgs([
				$this->createStub(Error::class),
				$this->createStub(AppHelper::class),
				$this->createStub(L10n::class),
				$this->createStub(App\BaseURL::class),
				$args,
				$this->createStub(LoggerInterface::class),
				$this->createStub(Profiler::class),
				$apiResponse,
				[],
				[],
			])
			->onlyMethods(['content'])
			->getMock();
	}
}
