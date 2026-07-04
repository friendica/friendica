<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\App;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Network\HTTPException\InternalServerErrorException;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BaseURLTest extends TestCase
{
	public static function provideInputTestData(): array
	{
		return [
			'default' => [
				'url'    => 'https://friendica.local',
				'expect' => 'https://friendica.local',
			],
			'subPath' => [
				'url'    => 'https://friendica.local/subpath',
				'expect' => 'https://friendica.local/subpath',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('provideInputTestData')]
	#[BackupGlobals(true)]
	public function testDetermineWithConfigReturnsCorrectUrl(string $url, string $expect): void
	{
		$config = self::createStub(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(function (string $category, string $key, mixed $default) use ($url): mixed {
			if ($category === 'system' && $key === 'url') {
				return $url;
			}

			return $default;
		});

		$baseUrl = new BaseURL($config, self::createStub(LoggerInterface::class));

		self::assertEquals($expect, (string) $baseUrl);
	}

	public static function provideServerTestData(): array
	{
		return [
			'serverArrayStandard' => [
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'SERVER_PORT'  => 443,
					'REQUEST_URI'  => '/test/it?with=query',
					'QUERY_STRING' => 'pagename=test/it',
				],
				'expect' => 'https://friendica.server',
			],
			'serverArraySubPath' => [
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'SERVER_PORT'  => 443,
					'REQUEST_URI'  => '/test/it/now?with=query',
					'QUERY_STRING' => 'pagename=it/now',
				],
				'expect' => 'https://friendica.server/test',
			],
			'serverArraySubPath2' => [
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'SERVER_PORT'  => 443,
					'REQUEST_URI'  => '/test/it/now?with=query',
					'QUERY_STRING' => 'pagename=now',
				],
				'expect' => 'https://friendica.server/test/it',
			],
			'serverArraySubPath3' => [
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'SERVER_PORT'  => 443,
					'REQUEST_URI'  => '/test/it/now?with=query',
					'QUERY_STRING' => 'pagename=test/it/now',
				],
				'expect' => 'https://friendica.server',
			],
			'serverArrayWithoutQueryString1' => [
				'server' => [
					'HTTPS'       => 'on',
					'HTTP_HOST'   => 'friendica.server',
					'SERVER_PORT' => 443,
					'REQUEST_URI' => '/test/it/now?with=query',
				],
				'expect' => 'https://friendica.server/test/it/now',
			],
			'serverArrayWithoutQueryString2' => [
				'server' => [
					'HTTPS'       => 'on',
					'HTTP_HOST'   => 'friendica.server',
					'SERVER_PORT' => 443,
					'REQUEST_URI' => '',
				],
				'expect' => 'https://friendica.server',
			],
			'serverArrayWithoutQueryString3' => [
				'server' => [
					'HTTPS'       => 'on',
					'HTTP_HOST'   => 'friendica.server',
					'SERVER_PORT' => 443,
					'REQUEST_URI' => '/',
				],
				'expect' => 'https://friendica.server',
			],
		];
	}


	#[\PHPUnit\Framework\Attributes\DataProvider('provideServerTestData')]
	#[BackupGlobals(true)]
	public function testDetermineWithServerArrayReturnsCorrectUrl(array $server, string $expect): void
	{
		$_SERVER = array_merge($_SERVER, $server);

		$baseUrl = new BaseURL(
			self::createStub(IManageConfigValues::class),
			self::createStub(LoggerInterface::class),
			$server,
		);

		self::assertEquals($expect, (string) $baseUrl);
	}

	#[BackupGlobals(true)]
	public function testDetermineWithGlobalsReturnsCorrectUrl(): void
	{
		$_SERVER['HTTP_HOST']   = 'localhost';
		$_SERVER['SERVER_PORT'] = 80;

		$baseUrl = new BaseURL(
			self::createStub(IManageConfigValues::class),
			self::createStub(LoggerInterface::class),
			[],
		);

		self::assertEquals('http://localhost', (string) $baseUrl);
	}

	public static function provideRemoveTestData(): array
	{
		return [
			'same' => [
				'url'     => 'https://friendica.local',
				'origUrl' => 'https://friendica.local/test/picture.png',
				'expect'  => 'test/picture.png',
			],
			'other' => [
				'url'     => 'https://friendica.local',
				'origUrl' => 'https://friendica.other/test/picture.png',
				'expect'  => 'https://friendica.other/test/picture.png',
			],
			'samSubPath' => [
				'url'     => 'https://friendica.local/test',
				'origUrl' => 'https://friendica.local/test/picture.png',
				'expect'  => 'picture.png',
			],
			'otherSubPath' => [
				'url'     => 'https://friendica.local/test',
				'origUrl' => 'https://friendica.other/test/picture.png',
				'expect'  => 'https://friendica.other/test/picture.png',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('provideRemoveTestData')]
	public function testRemove(string $url, string $origUrl, string $expect): void
	{
		$config = self::createStub(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(function (string $category, string $key, mixed $default) use ($url): mixed {
			if ($category === 'system' && $key === 'url') {
				return $url;
			}

			return $default;
		});

		$baseUrl = new BaseURL($config, self::createStub(LoggerInterface::class));

		self::assertEquals($expect, $baseUrl->remove($origUrl));
	}

	/**
	 * Test that redirect to external domains fails
	 */
	public function testRedirectWithExternalDomainThrowsException(): void
	{
		$config = self::createConfiguredStub(IManageConfigValues::class, [
			'get' => 'https://friendica.local',
		]);

		$baseUrl = new BaseURL($config, $this->createStub(LoggerInterface::class));

		self::expectException(InternalServerErrorException::class);
		self::expectExceptionMessage('https://friendica.other is not a relative path, please use System::externalRedirect');

		$baseUrl->redirect('https://friendica.other');
	}
}
