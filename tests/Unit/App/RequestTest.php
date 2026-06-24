<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\App;

use Friendica\App\Request;
use Friendica\Core\Config\Capability\IManageConfigValues;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
	public static function dataServerArray(): array
	{
		return [
			'default' => [
				'server' => ['REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '',
					'forwarded_for_headers' => '',
				],
				'assertion' => '1.2.3.4',
			],
			'proxy_1' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_2' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '4.5.6.7, 1.2.3.4', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_CIDR_multiple_proxies' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '4.5.6.7, 1.2.3.4', 'REMOTE_ADDR' => '10.0.1.1'],
				'config' => [
					'trusted_proxies'       => '10.0.0.0/16, 1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_wrong_CIDR' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '4.5.6.7, 1.2.3.4', 'REMOTE_ADDR' => '10.1.0.1'],
				'config' => [
					'trusted_proxies'       => '10.0.0.0/24, 1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '10.1.0.1',
			],
			'proxy_3' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_multiple_header_1' => [
				'server' => ['HTTP_X_FORWARDED' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_multiple_header_2' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4', 'HTTP_X_FORWARDED' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_multiple_header_wrong' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4', 'HTTP_X_FORWARDED' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => '',
				],
				'assertion' => '1.2.3.4',
			],
			'no_remote_addr' => [
				'server' => [],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => '',
				],
				'assertion' => '0.0.0.0',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataServerArray')]
	public function testRemoteAddress(array $server, array $config, string $assertion): void
	{
		$configClass = self::createMock(IManageConfigValues::class);
		$configClass->expects(self::atLeast(1))->method('get')->willReturnMap([
			['proxy', 'trusted_proxies', '', $config['trusted_proxies']],
			['proxy', 'forwarded_for_headers', Request::DEFAULT_FORWARD_FOR_HEADER, $config['forwarded_for_headers']],
		]);

		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', $server);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame($assertion, $request->getRemoteAddress());
	}

	public function testPsr7Delegation(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('POST', 'http://example.com/test', ['X-Custom' => 'val'], 'body', '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$psr7Request = $psr7Request->withQueryParams(['foo' => 'bar']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('POST', $request->getMethod());
		self::assertSame('/test', $request->getRequestTarget());
		self::assertSame('1.1', $request->getProtocolVersion());
		self::assertTrue($request->hasHeader('X-Custom'));
		self::assertSame(['val'], $request->getHeader('X-Custom'));
		self::assertSame('val', $request->getHeaderLine('X-Custom'));
		self::assertSame(['foo' => 'bar'], $request->getQueryParams());
		self::assertSame('body', (string) $request->getBody());
	}

	public function testGetQueryParam(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$psr7Request = $psr7Request->withQueryParams(['foo' => 'bar', 'num' => '42', 'flag' => '1', 'price' => '9.99']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('bar', $request->getQueryParam('foo'));
		self::assertNull($request->getQueryParam('nonexistent'));
		self::assertSame('default', $request->getQueryParam('nonexistent', 'default'));
		self::assertSame('bar', $request->getQueryString('foo'));
		self::assertSame('', $request->getQueryString('nonexistent'));
		self::assertSame(42, $request->getQueryInt('num'));
		self::assertSame(0, $request->getQueryInt('nonexistent'));
		self::assertSame(9.99, $request->getQueryFloat('price'));
		self::assertSame(0.0, $request->getQueryFloat('nonexistent'));
		self::assertTrue($request->getQueryBool('flag'));
		self::assertFalse($request->getQueryBool('nonexistent'));
	}

	public function testGetBodyParam(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('POST', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$psr7Request = $psr7Request->withParsedBody(['name' => 'Alice', 'age' => '30', 'score' => '9.5', 'active' => '1']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('Alice', $request->getBodyParam('name'));
		self::assertNull($request->getBodyParam('nonexistent'));
		self::assertSame('Alice', $request->getBodyString('name'));
		self::assertSame(30, $request->getBodyInt('age'));
		self::assertSame(9.5, $request->getBodyFloat('score'));
		self::assertTrue($request->getBodyBool('active'));
	}

	public function testGetInputMerged(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('POST', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$psr7Request = $psr7Request->withQueryParams(['query_key' => 'qv', 'overlap' => 'query_val']);
		$psr7Request = $psr7Request->withParsedBody(['body_key' => 'bv', 'overlap' => 'body_wins']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('qv', $request->getInput('query_key'));
		self::assertSame('bv', $request->getInput('body_key'));
		self::assertSame('body_wins', $request->getInput('overlap'));
		self::assertNull($request->getInput('nonexistent'));
	}

	public function testGetInputWithHttpInput(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('POST', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);
		$request     = $request->withHttpInput(['http_key' => 'hv']);

		self::assertSame('hv', $request->getInput('http_key'));
	}

	public function testGetServerParam(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4', 'HTTP_HOST' => 'example.com']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('1.2.3.4', $request->getServerParam('REMOTE_ADDR'));
		self::assertSame('example.com', $request->getServerParam('HTTP_HOST'));
		self::assertNull($request->getServerParam('nonexistent'));
	}

	public function testIsMethod(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertTrue($request->isGet());
		self::assertTrue($request->isMethod('GET'));
		self::assertFalse($request->isPost());
		self::assertFalse($request->isPut());
		self::assertFalse($request->isPatch());
		self::assertFalse($request->isDelete());

		$postRequest = new Request($psr7Request->withMethod('POST'), $configClass);
		self::assertTrue($postRequest->isPost());
	}

	public function testWithMethodReturnsNewInstance(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		$newRequest = $request->withMethod('POST');

		self::assertNotSame($request, $newRequest);
		self::assertTrue($request->isGet());
		self::assertTrue($newRequest->isPost());
	}

	public function testWithQueryParamsReturnsNewInstance(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		$newRequest = $request->withQueryParams(['new' => 'value']);

		self::assertNotSame($request, $newRequest);
		self::assertSame(['new' => 'value'], $newRequest->getQueryParams());
		self::assertSame([], $request->getQueryParams());
	}

	public function testWithServerParamsUpdatesRemoteAddress(): void
	{
		$configClass = self::createMock(IManageConfigValues::class);
		$configClass->expects(self::atLeast(2))->method('get')->willReturnMap([
			['proxy', 'trusted_proxies', '', ''],
			['proxy', 'forwarded_for_headers', Request::DEFAULT_FORWARD_FOR_HEADER, Request::DEFAULT_FORWARD_FOR_HEADER],
		]);

		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$request     = new Request($psr7Request, $configClass);

		$newRequest = $request->withServerParams(['REMOTE_ADDR' => '5.6.7.8']);

		self::assertNotSame($request, $newRequest);
		self::assertSame('5.6.7.8', $newRequest->getRemoteAddress());
	}

	public function testWithHttpInputReturnsNewInstance(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		$newRequest = $request->withHttpInput(['key' => 'val']);

		self::assertNotSame($request, $newRequest);
		self::assertSame('val', $newRequest->getInput('key'));
		self::assertNull($request->getInput('key'));
	}

	public function testGetCookieParam(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$psr7Request = $psr7Request->withCookieParams(['session' => 'abc123']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('abc123', $request->getCookieParam('session'));
		self::assertNull($request->getCookieParam('nonexistent'));
	}

	public function testTypedGettersDefaultValues(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('', $request->getQueryString('nonexistent'));
		self::assertSame(0, $request->getQueryInt('nonexistent'));
		self::assertSame(0.0, $request->getQueryFloat('nonexistent'));
		self::assertFalse($request->getQueryBool('nonexistent'));
		self::assertSame([], $request->getQueryArray('nonexistent'));

		self::assertSame('', $request->getBodyString('nonexistent'));
		self::assertSame(0, $request->getBodyInt('nonexistent'));
		self::assertSame(0.0, $request->getBodyFloat('nonexistent'));
		self::assertFalse($request->getBodyBool('nonexistent'));
		self::assertSame([], $request->getBodyArray('nonexistent'));

		self::assertSame('', $request->getInputString('nonexistent'));
		self::assertSame(42, $request->getInputInt('nonexistent', 42));
		self::assertSame(1.5, $request->getInputFloat('nonexistent', 1.5));
		self::assertTrue($request->getInputBool('nonexistent', true));
		self::assertSame(['default'], $request->getInputArray('nonexistent', ['default']));
	}

	public function testTypeCastingEdgeCases(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('GET', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$psr7Request = $psr7Request->withQueryParams(['str' => 'hello', 'int' => '42abc', 'float' => '3.14extra', 'bool_true' => '1', 'bool_false' => '0', 'arr' => ['1', '2']]);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		self::assertSame('hello', $request->getQueryString('str'));
		self::assertSame(0, $request->getQueryInt('int'), '42abc should not validate as int');
		self::assertSame(0.0, $request->getQueryFloat('float'), '3.14extra should not validate as float');
		self::assertTrue($request->getQueryBool('bool_true'));
		self::assertFalse($request->getQueryBool('bool_false'));
		self::assertSame(['1', '2'], $request->getQueryArray('arr'));
	}

	public function testGetUploadedFile(): void
	{
		$psr7Request = new \GuzzleHttp\Psr7\ServerRequest('POST', 'http://example.com', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);
		$upload      = new \GuzzleHttp\Psr7\UploadedFile(\GuzzleHttp\Psr7\Utils::streamFor('test'), 4, UPLOAD_ERR_OK, 'file.txt');
		$psr7Request = $psr7Request->withUploadedFiles(['avatar' => $upload]);
		$configClass = self::createMock(IManageConfigValues::class);
		$request     = new Request($psr7Request, $configClass);

		$result = $request->getUploadedFile('avatar');
		self::assertNotNull($result);
		self::assertSame('file.txt', $result->getClientFilename());

		self::assertNull($request->getUploadedFile('nonexistent'));
	}
}
