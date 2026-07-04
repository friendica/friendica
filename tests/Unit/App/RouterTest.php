<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\App;

use FastRoute\RouteCollector;
use Friendica\App\Arguments;
use Friendica\App\Router;
use Friendica\Core\Addon\AddonHelper;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Network\HTTPException\InternalServerErrorException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class RouterTest extends TestCase
{
	/**
	 * Test that constructor creates router instance with valid parameters
	 */
	public function testConstructorCreatesRouterInstanceWithValidParameters(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			self::createStub(EventDispatcherInterface::class),
			self::createStub(AddonHelper::class),
			$userSession,
		);

		self::assertInstanceOf(Router::class, $router); // @phpstan-ignore staticMethod.alreadyNarrowedType
	}

	/**
	 * Test that constructor creates router instance when user is local user
	 */
	public function testConstructorCreatesRouterInstanceWhenUserIsLocalUser(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(1);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			self::createStub(EventDispatcherInterface::class),
			self::createStub(AddonHelper::class),
			$userSession,
		);

		self::assertInstanceOf(Router::class, $router); // @phpstan-ignore staticMethod.alreadyNarrowedType
	}

	/**
	 * Test that constructor throws exception when base routes file does not exist
	 */
	public function testConstructorThrowsExceptionWhenBaseRoutesFileDoesNotExist(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);

		self::expectException(InternalServerErrorException::class);
		self::expectExceptionMessage("Routes file path does'n exist.");

		new Router(
			['REQUEST_METHOD' => 'GET'],
			'/non/existent/file.php',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			self::createStub(EventDispatcherInterface::class),
			self::createStub(AddonHelper::class),
			$userSession,
		);
	}

	/**
	 * Test that getRouteCollector returns a RouteCollector instance
	 */
	public function testGetRouteCollectorReturnsRouteCollectorInstance(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			self::createStub(EventDispatcherInterface::class),
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$routeCollector = $router->getRouteCollector();
		self::assertInstanceOf(RouteCollector::class, $routeCollector);
	}

	/**
	 * Test that getRouteCollector returns provided instance when instance is provided
	 */
	public function testGetRouteCollectorReturnsProvidedInstanceWhenInstanceIsProvided(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$providedCollector = self::createMock(RouteCollector::class);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			self::createStub(EventDispatcherInterface::class),
			self::createStub(AddonHelper::class),
			$userSession,
			$providedCollector,
		);

		self::assertSame($providedCollector, $router->getRouteCollector());
	}

	/**
	 * Test that getParameters returns array containing server data
	 */
	public function testGetParametersReturnsArrayWithServerData(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$server = ['REQUEST_METHOD' => 'GET'];

		$router = new Router(
			$server,
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			self::createStub(EventDispatcherInterface::class),
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$parameters = $router->getParameters();
		self::assertIsArray($parameters);
		self::assertCount(1, $parameters);
		self::assertSame($server, $parameters[0]);
	}

	/**
	 * Test that loadRoutes returns router instance with valid route
	 */
	public function testLoadRoutesReturnsRouterInstanceWithValidRoute(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$eventDispatcher = self::createMock(EventDispatcherInterface::class);
		$eventDispatcher->expects(self::once())->method('dispatch')->willReturnArgument(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			$eventDispatcher,
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$routes = [
			'/test' => ['Friendica\Module\Test', [Router::GET]],
		];

		$result = $router->loadRoutes($routes);
		self::assertSame($router, $result);
	}

	/**
	 * Test that loadRoutes returns router instance with empty routes array
	 */
	public function testLoadRoutesReturnsRouterInstanceWithEmptyRoutes(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$eventDispatcher = self::createMock(EventDispatcherInterface::class);
		$eventDispatcher->expects(self::once())->method('dispatch')->willReturnArgument(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			$eventDispatcher,
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$result = $router->loadRoutes([]);
		self::assertSame($router, $result);
	}

	/**
	 * Test that loadRoutes returns router instance with grouped routes
	 */
	public function testLoadRoutesReturnsRouterInstanceWithGroupedRoutes(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$eventDispatcher = self::createMock(EventDispatcherInterface::class);
		$eventDispatcher->expects(self::once())->method('dispatch')->willReturnArgument(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			$eventDispatcher,
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$routes = [
			'/api' => [
				'/test' => ['Friendica\Module\Api\Test', [Router::GET]],
			],
		];

		$result = $router->loadRoutes($routes);
		self::assertSame($router, $result);
	}

	/**
	 * Test that loadRoutes returns router instance with multiple HTTP methods
	 */
	public function testLoadRoutesReturnsRouterInstanceWithMultipleHttpMethods(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$eventDispatcher = self::createMock(EventDispatcherInterface::class);
		$eventDispatcher->expects(self::once())->method('dispatch')->willReturnArgument(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			$eventDispatcher,
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$routes = [
			'/test' => ['Friendica\Module\Test', [Router::GET, Router::POST, Router::PUT]],
		];

		$result = $router->loadRoutes($routes);
		self::assertSame($router, $result);
	}

	/**
	 * Test that loadRoutes returns router instance with single HTTP method
	 */
	public function testLoadRoutesReturnsRouterInstanceWithSingleHttpMethod(): void
	{
		$userSession = self::createMock(IHandleUserSessions::class);
		$userSession->expects(self::once())->method('getLocalUserId')->willReturn(0);
		$eventDispatcher = self::createMock(EventDispatcherInterface::class);
		$eventDispatcher->expects(self::once())->method('dispatch')->willReturnArgument(0);

		$router = new Router(
			['REQUEST_METHOD' => 'GET'],
			'',
			self::createStub(L10n::class),
			self::createStub(ICanCache::class),
			self::createStub(ICanLock::class),
			self::createStub(IManageConfigValues::class),
			self::createStub(Arguments::class),
			$eventDispatcher,
			self::createStub(AddonHelper::class),
			$userSession,
		);

		$routes = [
			'/test' => ['Friendica\Module\Test', [Router::POST]],
		];

		$result = $router->loadRoutes($routes);
		self::assertSame($router, $result);
	}

	/**
	 * Test that HTTP method constants and ALLOWED_METHODS are consistent
	 */
	public function testHttpMethodConstantsAndAllowedMethods(): void
	{
		$expectedMethods = ['DELETE', 'GET', 'PATCH', 'POST', 'PUT', 'OPTIONS'];

		foreach ($expectedMethods as $method) {
			self::assertContains($method, Router::ALLOWED_METHODS);
		}

		self::assertCount(count($expectedMethods), Router::ALLOWED_METHODS);
	}
}
