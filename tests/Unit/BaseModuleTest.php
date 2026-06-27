<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit;

use Friendica\App\Arguments;
use Friendica\App\Request;
use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Event\ModuleContentEvent;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class BaseModuleTest extends TestCase
{
	public function testGetServerRequestReturnsPreviousSetRequest(): void
	{
		$module = (new \ReflectionClass(BaseModuleTestModule::class))->newInstanceWithoutConstructor();

		$request = $this->createStub(Request::class);

		$appRequestProp = new \ReflectionProperty(BaseModule::class, 'appRequest');
		$appRequestProp->setValue($module, $request);

		$method = new \ReflectionMethod(BaseModule::class, 'getServerRequest');
		$this->assertSame($request, $method->invoke($module));
	}

	public function testGetServerRequestThrowsWhenUnset(): void
	{
		$module = (new \ReflectionClass(BaseModuleTestModule::class))->newInstanceWithoutConstructor();

		$method = new \ReflectionMethod(BaseModule::class, 'getServerRequest');

		$this->expectException(\RuntimeException::class);
		$method->invoke($module);
	}

	public function testHandleRequestDispatchesToGet(): void
	{
		$module = $this->createModuleWithMocks();

		$request = $this->createMock(Request::class);
		$request->method('getAllInput')->willReturn(['key' => 'value']);

		$module->handleRequest($request);

		$this->assertSame(['key' => 'value'], $module->receivedInput);
	}

	public function testHandleRequestDispatchesToPost(): void
	{
		$module = $this->createModuleWithMocks(Router::POST);

		$request = $this->createMock(Request::class);
		$request->method('getAllInput')->willReturn(['post_key' => 'post_val']);

		$module->handleRequest($request);

		$this->assertSame(['post_key' => 'post_val'], $module->receivedInput);
	}

	public function testHandleRequestSetsServerRequest(): void
	{
		$module = $this->createModuleWithMocks();

		$request = $this->createMock(Request::class);
		$request->method('getAllInput')->willReturn([]);

		$module->handleRequest($request);

		$method = new \ReflectionMethod(BaseModule::class, 'getServerRequest');
		$this->assertSame($request, $method->invoke($module));
	}

	public function testRunBcPathStillWorks(): void
	{
		$module = $this->createModuleWithMocks();

		$httpException = $this->createStub(\Friendica\Module\Special\HTTPException::class);

		$requestArray = ['key' => 'value'];
		$module->run($httpException, $requestArray);

		$this->assertSame($requestArray, $module->receivedInput);
	}

	public function testRunBcPathDoesNotSetServerRequest(): void
	{
		$module = $this->createModuleWithMocks();

		$httpException = $this->createStub(\Friendica\Module\Special\HTTPException::class);
		$module->run($httpException, ['key' => 'value']);

		$method = new \ReflectionMethod(BaseModule::class, 'getServerRequest');

		$this->expectException(\RuntimeException::class);
		$method->invoke($module);
	}

	/**
	 * @return BaseModuleTestModule
	 */
	private function createModuleWithMocks(string $method = Router::GET): BaseModuleTestModule
	{
		/** @var BaseModuleTestModule $module */
		$module = (new \ReflectionClass(BaseModuleTestModule::class))->newInstanceWithoutConstructor();

		$args = $this->createMock(Arguments::class);
		$args->method('getMethod')->willReturn($method);
		$args->method('getModuleName')->willReturn('test');
		$args->method('getCommand')->willReturn('test');
		$args->method('getQueryString')->willReturn('test');

		$response = $this->createMock(Response::class);
		$response->method('setHeader');
		$response->method('addContent');
		$response->method('generate')->willReturn($this->createStub(ResponseInterface::class));

		$profiler = $this->createMock(Profiler::class);

		$eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$eventDispatcher->method('dispatch')->willReturnArgument(0);

		$this->setModuleProperty($module, 'args', $args);
		$this->setModuleProperty($module, 'response', $response);
		$this->setModuleProperty($module, 'profiler', $profiler);
		$this->setModuleProperty($module, 'eventDispatcher', $eventDispatcher);

		return $module;
	}

	private function setModuleProperty(BaseModule $module, string $name, mixed $value): void
	{
		$prop = new \ReflectionProperty(BaseModule::class, $name);
		$prop->setValue($module, $value);
	}
}

class BaseModuleTestModule extends BaseModule
{
	public array $receivedInput = [];

	protected function get(array $request = []): void
	{
		$this->receivedInput = $request;
	}

	protected function post(array $request = []): void
	{
		$this->receivedInput = $request;
	}

	protected function content(array $request = []): string
	{
		return 'test';
	}
}
