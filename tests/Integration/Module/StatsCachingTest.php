<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module;

use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\Lock\Type\DatabaseLock;
use Friendica\DI;
use Friendica\Module\StatsCaching;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class StatsCachingTest extends ApiTestCase
{
	public function testStatsCachingNotAllowed(): void
	{
		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new ArrayCache('localhost');
		$lock  = new CacheLock($cache);

		$module = new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock, []);

		$this->expectException(\Friendica\Network\HTTPException\NotFoundException::class);

		$module->handleRequest(new ServerRequest('GET', 'https://friendica.local/stats/caching'));
	}

	public function testStatsCachingWithArrayCache(): void
	{
		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new ArrayCache('localhost');
		$lock  = new CacheLock($cache);

		$module = new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock, []);

		$response = $module->handleRequest((new ServerRequest('GET', 'https://friendica.local/stats/caching'))->withQueryParams(['key' => 'my-secret-key']));

		self::assertJson((string) $response->getBody());
		self::assertEquals(200, $response->getStatusCode());

		$json = json_decode((string) $response->getBody(), true);

		self::assertArrayHasKey('opcache', $json);
		self::assertArrayHasKey('cache', $json);
		self::assertArrayHasKey('lock', $json);
		self::assertEquals('array', $json['cache']['type']);
		self::assertEquals('array', $json['lock']['type']);
	}

	public function testStatsCachingWithDatabaseCache(): void
	{
		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock, []);

		$response = $module->handleRequest((new ServerRequest('GET', 'https://friendica.local/stats/caching'))->withQueryParams(['key' => 'my-secret-key']));

		self::assertJson((string) $response->getBody());
		self::assertEquals(200, $response->getStatusCode());

		$json = json_decode((string) $response->getBody(), true);

		self::assertArrayHasKey('opcache', $json);
		self::assertArrayHasKey('cache', $json);
		self::assertArrayHasKey('lock', $json);
		self::assertEquals('database', $json['cache']['type']);
		self::assertEquals('database', $json['lock']['type']);
	}

	public function testStatsCachingWithWrongKey(): void
	{
		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new ArrayCache('localhost');
		$lock  = new CacheLock($cache);

		$module = new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock, []);

		$this->expectException(\Friendica\Network\HTTPException\NotFoundException::class);

		$module->handleRequest((new ServerRequest('GET', 'https://friendica.local/stats/caching'))->withQueryParams(['key' => 'wrong-key']));
	}

	public function testStatsCachingWithOpcacheReturnsFalse(): void
	{
		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new StatsCaching(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock, []);

		$response = $module->handleRequest((new ServerRequest('GET', 'https://friendica.local/stats/caching'))->withQueryParams(['key' => 'my-secret-key']));

		self::assertJson((string) $response->getBody());
		self::assertEquals(200, $response->getStatusCode());

		$json = json_decode((string) $response->getBody(), true);

		self::assertEquals([
			'enabled'            => false,
			'hit_rate'           => null,
			'used_memory'        => null,
			'free_memory'        => null,
			'num_cached_scripts' => null,
		], $json['opcache']);
		self::assertEquals('database', $json['cache']['type']);
		self::assertEquals('database', $json['lock']['type']);
	}

	public function testStatsCachingWithOpcacheReturnsData(): void
	{
		$config = DI::config();
		$config->set('system', 'stats_key', 'my-secret-key');

		$cache = new DatabaseCache('localhost', DI::dba());
		$lock  = new DatabaseLock(DI::dba());

		$module = new class (DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], $config, $cache, $lock) extends StatsCaching {
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

		$response = $module->handleRequest((new ServerRequest('GET', 'https://friendica.local/stats/caching'))->withQueryParams(['key' => 'my-secret-key']));

		self::assertJson((string) $response->getBody());
		self::assertEquals(200, $response->getStatusCode());

		$json = json_decode((string) $response->getBody(), true);

		self::assertEquals([
			'enabled'            => true,
			'hit_rate'           => 1,
			'used_memory'        => 3,
			'free_memory'        => 4,
			'num_cached_scripts' => 2,
		], $json['opcache']);
		self::assertEquals('database', $json['cache']['type']);
		self::assertEquals('database', $json['lock']['type']);
	}
}
