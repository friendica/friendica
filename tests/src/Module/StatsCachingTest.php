<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\Lock\Type\DatabaseLock;
use Friendica\DI;
use Friendica\Module\Special\HTTPException;
use Friendica\Module\StatsCaching;
use Friendica\Test\FixtureTestCase;
use Mockery\MockInterface;

class StatsCachingTest extends FixtureTestCase
{
	/** @var MockInterface|HTTPException */
	protected $httpExceptionMock;

	/** @var MockInterface|IManageConfigValues */
	protected $config;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = \Mockery::mock(HTTPException::class);
		$this->config            = \Mockery::mock(IManageConfigValues::class);
	}

	protected function getOpcacheDisabled(): StatsCaching
	{
		$config = $this->config;
		$cache  = new ArrayCache('localhost');
		$lock   = new CacheLock($cache);

		return new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock) extends StatsCaching {
			protected function getOpcacheStatus(): array|false
			{
				return false;
			}
		};
	}

	public function testStatsCachingNotAllowed(): void
	{
		$this->httpExceptionMock->shouldReceive('content')->andReturn('failed')->once();

		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new ArrayCache('localhost');
		$lock  = new CacheLock($cache);

		// @phpstan-ignore method.deprecated
		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $cache, $lock))
			->run($this->httpExceptionMock);

		self::assertEquals('404', $response->getStatusCode());
		self::assertEquals('Page not found', $response->getReasonPhrase());
		self::assertEquals('failed', $response->getBody());
	}

	public function testStatsCachingWitMinimumCache(): void
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$cache = new ArrayCache('localhost');
		$lock  = new CacheLock($cache);

		$module = new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $cache, $lock) extends StatsCaching {
			protected function getOpcacheStatus(): array|false
			{
				return false;
			}
		};

		// @phpstan-ignore method.deprecated
		$response = $module->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals([
			'type'  => 'array',
			'stats' => [],
		], $json['cache']);
		self::assertEquals([
			'type'  => 'array',
			'stats' => [],
		], $json['lock']);
	}

	public function testStatsCachingWithDatabase(): void
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $cache, $lock) extends StatsCaching {
			protected function getOpcacheStatus(): array|false
			{
				return false;
			}
		};

		// @phpstan-ignore method.deprecated
		$response = $module->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals([
			'enabled'            => false,
			'hit_rate'           => null,
			'used_memory'        => null,
			'free_memory'        => null,
			'num_cached_scripts' => null,
		], $json['opcache']);
		self::assertEquals(['type' => 'database'], $json['cache']);
		self::assertEquals(['type' => 'database'], $json['lock']);
	}

	public function testStatsCachingWithCache(): void
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $cache, $lock) extends StatsCaching {
			protected function getOpcacheStatus(): array|false
			{
				return false;
			}
		};

		// @phpstan-ignore method.deprecated
		$response = $module->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals([
			'enabled'            => false,
			'hit_rate'           => null,
			'used_memory'        => null,
			'free_memory'        => null,
			'num_cached_scripts' => null,
		], $json['opcache']);
		self::assertEquals(['type' => 'database'], $json['cache']);
		self::assertEquals(['type' => 'database'], $json['lock']);
	}

	public function testStatsCachingWithOpcacheAndNull(): void
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $cache, $lock) extends StatsCaching {
			protected function getOpcacheStatus(): array|false
			{
				return false;
			}
		};

		// @phpstan-ignore method.deprecated
		$response = $module->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals([
			'enabled'            => false,
			'hit_rate'           => null,
			'used_memory'        => null,
			'free_memory'        => null,
			'num_cached_scripts' => null,
		], $json['opcache']);
		self::assertEquals(['type' => 'database'], $json['cache']);
		self::assertEquals(['type' => 'database'], $json['lock']);
	}

	public function testStatsCachingWithOpcacheAndValues(): void
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $cache, $lock) extends StatsCaching {
			protected function getOpcacheStatus(): array
			{
				return [
					'opcache_enabled'    => true,
					'opcache_statistics' => [
						'opcache_hit_rate'   => 1,
						'num_cached_scripts' => 2,
					],
					'memory_usage' => [
						'used_memory' => 3,
						'free_memory' => 4,
					],
				];
			}
		};

		// @phpstan-ignore method.deprecated
		$response = $module->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals([
			'enabled'            => true,
			'hit_rate'           => 1,
			'used_memory'        => 3,
			'free_memory'        => 4,
			'num_cached_scripts' => 2,
		], $json['opcache']);
		self::assertEquals(['type' => 'database'], $json['cache']);
		self::assertEquals(['type' => 'database'], $json['lock']);
	}
}
