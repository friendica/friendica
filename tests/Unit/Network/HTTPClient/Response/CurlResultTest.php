<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Network\HTTPClient\Response;

use Friendica\Network\HTTPClient\Response\CurlResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CurlResultTest extends TestCase
{
	public function testNormal(): void
	{
		$header      = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.head');
		$headerArray = include(__DIR__ . '/../../../../Fixtures/curl/about.head.php');
		$body        = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');


		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(), 'https://test.local', $header . $body, [
			'http_code'    => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local',
		]);

		self::assertTrue($curlResult->isSuccess());
		self::assertFalse($curlResult->isTimeout());
		self::assertFalse($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBodyString());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local', $curlResult->getUrl());
		self::assertSame('https://test.local', $curlResult->getRedirectUrl());
	}

	#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testRedirect(): void
	{
		$header      = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.head');
		$headerArray = include(__DIR__ . '/../../../../Fixtures/curl/about.head.php');
		$body        = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');


		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(), 'https://test.local/test/it', $header . $body, [
			'http_code'    => 301,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local/test/it',
			'redirect_url' => 'https://test.other',
		]);

		self::assertTrue($curlResult->isSuccess());
		self::assertFalse($curlResult->isTimeout());
		self::assertTrue($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBodyString());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local/test/it', $curlResult->getUrl());
		self::assertSame('https://test.other/test/it', $curlResult->getRedirectUrl());
	}

	public function testTimeout(): void
	{
		$header      = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.head');
		$headerArray = include(__DIR__ . '/../../../../Fixtures/curl/about.head.php');
		$body        = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');


		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(), 'https://test.local/test/it', $header . $body, [
			'http_code'    => 500,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local/test/it',
			'redirect_url' => 'https://test.other',
		], CURLE_OPERATION_TIMEDOUT, 'Tested error');

		self::assertFalse($curlResult->isSuccess());
		self::assertTrue($curlResult->isTimeout());
		self::assertFalse($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBodyString());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local/test/it', $curlResult->getRedirectUrl());
		self::assertSame('Tested error', $curlResult->getError());
	}

	#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
	#[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
	public function testRedirectHeader(): void
	{
		$header      = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.redirect');
		$headerArray = include(__DIR__ . '/../../../../Fixtures/curl/about.redirect.php');
		$body        = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');


		$curlResult = new CurlResult(new NullLogger(), 'https://test.local/test/it?key=value', $header . $body, [
			'http_code'    => 301,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local/test/it?key=value',
		]);

		self::assertTrue($curlResult->isSuccess());
		self::assertFalse($curlResult->isTimeout());
		self::assertTrue($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBodyString());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local/test/it?key=value', $curlResult->getUrl());
		self::assertSame('https://test.other/some/?key=value', $curlResult->getRedirectUrl());
	}

	public function testInHeader(): void
	{
		$header = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.head');
		$body   = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');

		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(), 'https://test.local', $header . $body, [
			'http_code'    => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local',
		]);
		self::assertTrue($curlResult->inHeader('vary'));
		self::assertFalse($curlResult->inHeader('wrongHeader'));
	}

	public function testGetHeaderArray(): void
	{
		$header = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.head');
		$body   = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');

		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(), 'https://test.local', $header . $body, [
			'http_code'    => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local',
		]);

		$headers = $curlResult->getHeaderArray();

		self::assertNotEmpty($headers);
		self::assertArrayHasKey('vary', $headers);
	}

	public function testGetHeaderWithParam(): void
	{
		$header = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.head');
		$body   = file_get_contents(__DIR__ . '/../../../../Fixtures/curl/about.body');

		$curlResult = new CurlResult(new NullLogger(), 'https://test.local', $header . $body, [
			'http_code'    => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url'          => 'https://test.local',
		]);

		self::assertNotEmpty($curlResult->getHeaders());
		self::assertEmpty($curlResult->getHeader('wrongHeader'));
	}
}
