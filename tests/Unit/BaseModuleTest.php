<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\EarlyExitException;
use Friendica\Core\L10n;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class BaseModuleTest extends TestCase
{
	public function testEarlyExitCarriesResponseContent(): void
	{
		$module = self::createModule(exitContent: 'custom content');

		try {
			$module->handleRequest(self::createStub(ServerRequestInterface::class));
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertSame('custom content', (string) $e->getResponse()->getBody());
		}
	}

	public function testEarlyJsonExitCarriesJsonBody(): void
	{
		$module = self::createModule(exitJson: ['key' => 'value']);

		try {
			$module->handleRequest(self::createStub(ServerRequestInterface::class));
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertJsonStringEqualsJsonString(
				'{"key":"value"}',
				(string) $e->getResponse()->getBody(),
			);
		}
	}

	public function testEarlyJsonErrorCarriesStatusCode(): void
	{
		$module = self::createModule(exitError: [404, ['error' => 'not found']]);

		try {
			$module->handleRequest(self::createStub(ServerRequestInterface::class));
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertSame(404, $e->getResponse()->getStatusCode());
		}
	}

	public function testEarlyJsonErrorCarriesJsonBody(): void
	{
		$module = self::createModule(exitError: [422, ['message' => 'unprocessable']]);

		try {
			$module->handleRequest(self::createStub(ServerRequestInterface::class));
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertJsonStringEqualsJsonString(
				'{"message":"unprocessable"}',
				(string) $e->getResponse()->getBody(),
			);
		}
	}

	public function testEarlyHttpErrorCarriesStatusCode(): void
	{
		$module = self::createModule(exitHttpError: [403, 'Forbidden']);

		try {
			$module->handleRequest(self::createStub(ServerRequestInterface::class));
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertSame(403, $e->getResponse()->getStatusCode());
		}
	}

	public function testEarlyHttpErrorCarriesBody(): void
	{
		$module = self::createModule(exitHttpError: [404, 'Not Found', 'Custom body']);

		try {
			$module->handleRequest(self::createStub(ServerRequestInterface::class));
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) {
			self::assertSame('Custom body', (string) $e->getResponse()->getBody());
		}
	}

	public function testHeadCallsHeadAndSkipsContent(): void
	{
		$calls  = new \ArrayObject();
		$module = self::createRecordingModule('HEAD', $calls);

		$response = $module->handleRequest(self::createStub(ServerRequestInterface::class));

		self::assertSame(['head'], $calls->getArrayCopy());
		self::assertSame('', (string) $response->getBody());
	}

	public function testGetCallsGetAndContent(): void
	{
		$calls  = new \ArrayObject();
		$module = self::createRecordingModule('GET', $calls);

		$response = $module->handleRequest(self::createStub(ServerRequestInterface::class));

		self::assertSame(['get', 'content'], $calls->getArrayCopy());
		self::assertStringContainsString('page', (string) $response->getBody());
	}

	/** @param \ArrayObject<int, string> $calls */
	private static function createRecordingModule(string $method, \ArrayObject $calls): BaseModule
	{
		$args = self::createStub(App\Arguments::class);
		$args->method('getMethod')->willReturn($method);
		$args->method('getModuleName')->willReturn('test');
		$args->method('getQueryString')->willReturn('');

		$eventDispatcher = self::createStub(EventDispatcherInterface::class);
		$eventDispatcher->method('dispatch')->willReturnCallback(fn ($event) => $event);

		return new class (
			self::createStub(L10n::class),
			self::createStub(App\BaseURL::class),
			$args,
			self::createStub(LoggerInterface::class),
			self::createStub(Profiler::class),
			new Response(),
			[],
			[],
			$eventDispatcher,
			$calls,
		) extends BaseModule {
			/** @param \ArrayObject<int, string> $calls */
			public function __construct(
				L10n $l10n,
				App\BaseURL $baseUrl,
				App\Arguments $args,
				LoggerInterface $logger,
				Profiler $profiler,
				Response $response,
				array $server,
				array $parameters,
				EventDispatcherInterface $eventDispatcher,
				private readonly \ArrayObject $calls,
			) {
				parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
			}

			protected function get(array $request = []): void
			{
				$this->calls[] = 'get';
			}

			protected function head(array $request = []): void
			{
				$this->calls[] = 'head';
			}

			protected function content(array $request = []): string
			{
				$this->calls[] = 'content';
				return 'page';
			}
		};
	}

	private static function createModule(?string $exitContent = null, mixed $exitJson = null, ?array $exitError = null, ?array $exitHttpError = null): BaseModule
	{
		$args = self::createStub(App\Arguments::class);
		$args->method('getMethod')->willReturn('GET');
		$args->method('getModuleName')->willReturn('test');
		$args->method('getQueryString')->willReturn('');

		$eventDispatcher = self::createStub(EventDispatcherInterface::class);
		$eventDispatcher->method('dispatch')->willReturnCallback(fn ($event) => $event);

		return new class (
			self::createStub(L10n::class),
			self::createStub(App\BaseURL::class),
			$args,
			self::createStub(LoggerInterface::class),
			self::createStub(Profiler::class),
			new Response(),
			[],
			[],
			$eventDispatcher,
			$exitContent,
			$exitJson,
			$exitError,
			$exitHttpError,
		) extends BaseModule {
			public function __construct(
				L10n $l10n,
				App\BaseURL $baseUrl,
				App\Arguments $args,
				LoggerInterface $logger,
				Profiler $profiler,
				Response $response,
				array $server,
				array $parameters = [],
				?EventDispatcherInterface $eventDispatcher = null,
				private readonly ?string $exitContent = null,
				private readonly mixed $exitJson = null,
				private ?array $exitError = null,
				private ?array $exitHttpError = null,
			) {
				parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
			}

			protected function get(array $request = []): void
			{
				if ($this->exitContent !== null) {
					$this->earlyHttpExit($this->exitContent);
				} elseif ($this->exitHttpError !== null) {
					$this->earlyHttpError($this->exitHttpError[0], $this->exitHttpError[1] ?? '', $this->exitHttpError[2] ?? '');
				} elseif ($this->exitError !== null) {
					$this->earlyJsonError($this->exitError[0], $this->exitError[1]);
				} elseif ($this->exitJson !== null) {
					$this->earlyJsonExit($this->exitJson);
				}
			}
		};
	}
}
