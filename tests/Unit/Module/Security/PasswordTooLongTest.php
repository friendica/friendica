<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Module\Security;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Request;
use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Module\Security\PasswordTooLong;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class PasswordTooLongTest extends TestCase
{
	#[DoesNotPerformAssertions]
	public function testGetDispatchesContentWithQueryString(): void
	{
		$module = $this->createPasswordTooLongModule(
			$this->createStub(SystemMessages::class),
			$this->createStub(L10n::class),
			$this->createStub(BaseURL::class),
			Router::GET
		);

		$request = $this->createMock(Request::class);
		$request->method('getAllInput')->willReturn([]);
		$request->method('getQueryString')->willReturn('');

		$module->handleRequest($request);
	}

	public function testPostReadsPasswordFromTypedGetter(): void
	{
		$notices = [];
		$sysmsg = $this->createMock(SystemMessages::class);
		$sysmsg->expects($this->atLeastOnce())
			->method('addNotice')
			->willReturnCallback(function (string $message) use (&$notices): void {
				$notices[] = $message;
			});

		$l10n = $this->createMock(L10n::class);
		$l10n->method('t')->willReturnArgument(0);

		$baseUrl = $this->createMock(BaseURL::class);
		$baseUrl->expects($this->never())->method('redirect');

		$module = $this->createPasswordTooLongModule($sysmsg, $l10n, $baseUrl);

		$request = $this->createMock(Request::class);
		$request->method('getAllInput')->willReturn([]);
		$request->method('getBodyString')->willReturnMap([
			['password', '', 'newpass_abc'],
			['password_confirm', '', 'newpass_XYZ'],
		]);

		$module->handleRequest($request);

		$this->assertContains('Passwords do not match.', $notices);
	}

	/**
	 * @return PasswordTooLong&MockObject
	 */
	private function createPasswordTooLongModule(SystemMessages $sysmsg, L10n $l10n, BaseURL $baseUrl, string $method = Router::POST): PasswordTooLong
	{
		/** @var PasswordTooLong&MockObject $module */
		$module = $this->getMockBuilder(PasswordTooLong::class)
			->disableOriginalConstructor()
			->onlyMethods(['content'])
			->getMock();

		$module->method('content')->willReturn('');

		$args = $this->createMock(Arguments::class);
		$args->method('getMethod')->willReturn($method);
		$args->method('getModuleName')->willReturn('security/password_too_long');
		$args->method('getCommand')->willReturn('security/password_too_long');
		$args->method('getQueryString')->willReturn('test');

		$response = $this->createMock(Response::class);
		$response->method('setHeader');
		$response->method('addContent');
		$response->method('generate')->willReturn($this->createStub(ResponseInterface::class));

		$profiler = $this->createMock(Profiler::class);

		$eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$eventDispatcher->method('dispatch')->willReturnArgument(0);

		$logger   = $this->createMock(LoggerInterface::class);
		$userSession = $this->createMock(IHandleUserSessions::class);

		$this->setModuleProperty($module, 'baseUrl', $baseUrl);
		$this->setModuleProperty($module, 'args', $args);
		$this->setModuleProperty($module, 'response', $response);
		$this->setModuleProperty($module, 'profiler', $profiler);
		$this->setModuleProperty($module, 'eventDispatcher', $eventDispatcher);
		$this->setModuleProperty($module, 'l10n', $l10n);
		$this->setModuleProperty($module, 'logger', $logger);
		$this->setModuleProperty($module, 'server', []);
		$this->setModuleProperty($module, 'parameters', []);

		$this->setModuleProperty($module, 'sysmsg', $sysmsg);
		$this->setModuleProperty($module, 'userSession', $userSession);

		return $module;
	}

	private function setModuleProperty(object $module, string $name, mixed $value): void
	{
		foreach ([PasswordTooLong::class, BaseModule::class] as $class) {
			if ((new \ReflectionClass($class))->hasProperty($name)) {
				$prop = new \ReflectionProperty($class, $name);
				$prop->setValue($module, $value);
				return;
			}
		}
		throw new \InvalidArgumentException(sprintf('Property %s not found on %s or BaseModule', $name, $module::class));
	}
}
