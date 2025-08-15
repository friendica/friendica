<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\Lock\Type\DatabaseLock;
use Friendica\DI;
use Friendica\Module\Special\HTTPException;
use Friendica\Module\StatsCaching;
use Friendica\Test\FixtureTestCase;
use Mockery\MockInterface;
use phpmock\mockery\PHPMockery;

class StatsCachingTest extends FixtureTestCase
{
	/** @var MockInterface|HTTPException */
	protected $httpExceptionMock;

	protected ICanCache $cache;
	protected ICanLock $lock;

	/** @var MockInterface|IManageConfigValues */
	protected $config;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = \Mockery::mock(HTTPException::class);
		$this->config            = \Mockery::mock(IManageConfigValues::class);
		$this->cache             = new ArrayCache('localhost');
		$this->lock              = new CacheLock($this->cache);
	}

	public function testStatsCachingNotAllowed()
	{
		$this->httpExceptionMock->shouldReceive('content')->andReturn('failed')->once();

		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $this->cache, $this->lock, []))
			->run($this->httpExceptionMock);

		self::assertEquals('404', $response->getStatusCode());
		self::assertEquals('Page not found', $response->getReasonPhrase());
		self::assertEquals('failed', $response->getBody());
	}

	public function testStatsCachingWitMinimumCache()
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');
		PHPMockery::mock("Friendica\\Module", "function_exists")->with('opcache_get_status')->once()->andReturn(false);

		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $this->cache, $this->lock, []))
			->run($this->httpExceptionMock, $request);

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

	public function testStatsCachingWithDatabase()
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$this->cache = new DatabaseCache('localhost', DI::dba());
		$this->lock  = new DatabaseLock(DI::dba());
		PHPMockery::mock("Friendica\\Module", "function_exists")->with('opcache_get_status')->once()->andReturn(false);

		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $this->cache, $this->lock, []))
			->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals(['enabled' => false], $json['opcache']);
		self::assertEquals(['type' => 'database'], $json['cache']);
		self::assertEquals(['type' => 'database'], $json['lock']);
	}

	public function testStatsCachingWithCache()
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$this->cache = new DatabaseCache('localhost', DI::dba());
		$this->lock  = new DatabaseLock(DI::dba());
		PHPMockery::mock("Friendica\\Module", "function_exists")->with('opcache_get_status')->once()->andReturn(false);

		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $this->cache, $this->lock, []))
			->run($this->httpExceptionMock, $request);

		self::assertJson($response->getBody());
		self::assertEquals(['Content-type' => ['application/json; charset=utf-8'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());

		$json = json_decode($response->getBody(), true);

		self::assertEquals(['enabled' => false], $json['opcache']);
		self::assertEquals(['type' => 'database'], $json['cache']);
		self::assertEquals(['type' => 'database'], $json['lock']);
	}

	public function testStatsCachingWithOpcacheAndNull()
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$this->cache = new DatabaseCache('localhost', DI::dba());
		$this->lock  = new DatabaseLock(DI::dba());
		PHPMockery::mock("Friendica\\Module", "function_exists")->with('opcache_get_status')->once()->andReturn(true);
		PHPMockery::mock("Friendica\\Module", "opcache_get_status")->with(false)->once()->andReturn(false);

		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $this->cache, $this->lock, []))
			->run($this->httpExceptionMock, $request);

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

	public function testStatsCachingWithOpcacheAndValues()
	{
		$request = [
			'key' => '12345',
		];
		$this->config->shouldReceive('get')->with('system', 'stats_key')->twice()->andReturn('12345');

		$this->cache = new DatabaseCache('localhost', DI::dba());
		$this->lock  = new DatabaseLock(DI::dba());
		PHPMockery::mock("Friendica\\Module", "function_exists")->with('opcache_get_status')->once()->andReturn(true);
		PHPMockery::mock("Friendica\\Module", "opcache_get_status")->with(false)->once()->andReturn([
			'opcache_enabled'    => true,
			'opcache_statistics' => [
				'opcache_hit_rate'   => 1,
				'num_cached_scripts' => 2,
			],
			'memory_usage' => [
				'used_memory' => 3,
				'free_memory' => 4,
			]
		]);

		$response = (new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $this->config, $this->cache, $this->lock, []))
			->run($this->httpExceptionMock, $request);

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
