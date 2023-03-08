<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Network\HTTPClient\Response;

use Friendica\Network\HTTPClient\Response\CurlResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CurlResultTest extends TestCase
{
	/**
	 * @small
	 */
	public function testNormal()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.head');
		$headerArray = include(__DIR__ . '/../../../../datasets/curl/about.head.php');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');


		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(),'https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);

		self::assertTrue($curlResult->isSuccess());
		self::assertFalse($curlResult->isTimeout());
		self::assertFalse($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBody());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local', $curlResult->getUrl());
		self::assertSame('https://test.local', $curlResult->getRedirectUrl());
	}

	/**
	 * @small
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testRedirect()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.head');
		$headerArray = include(__DIR__ . '/../../../../datasets/curl/about.head.php');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');


		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(),'https://test.local/test/it', $header . $body, [
			'http_code' => 301,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local/test/it',
			'redirect_url' => 'https://test.other'
		]);

		self::assertTrue($curlResult->isSuccess());
		self::assertFalse($curlResult->isTimeout());
		self::assertTrue($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBody());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local/test/it', $curlResult->getUrl());
		self::assertSame('https://test.other/test/it', $curlResult->getRedirectUrl());
	}

	/**
	 * @small
	 */
	public function testTimeout()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.head');
		$headerArray = include(__DIR__ . '/../../../../datasets/curl/about.head.php');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');


		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(),'https://test.local/test/it', $header . $body, [
			'http_code' => 500,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local/test/it',
			'redirect_url' => 'https://test.other'
		], CURLE_OPERATION_TIMEDOUT, 'Tested error');

		self::assertFalse($curlResult->isSuccess());
		self::assertTrue($curlResult->isTimeout());
		self::assertFalse($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBody());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local/test/it', $curlResult->getRedirectUrl());
		self::assertSame('Tested error', $curlResult->getError());
	}

	/**
	 * @small
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testRedirectHeader()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.redirect');
		$headerArray = include(__DIR__ . '/../../../../datasets/curl/about.redirect.php');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');


		$curlResult = new CurlResult(new NullLogger(),'https://test.local/test/it?key=value', $header . $body, [
			'http_code' => 301,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local/test/it?key=value',
		]);

		self::assertTrue($curlResult->isSuccess());
		self::assertFalse($curlResult->isTimeout());
		self::assertTrue($curlResult->isRedirectUrl());
		self::assertSame($headerArray, $curlResult->getHeaders());
		self::assertSame($body, $curlResult->getBody());
		self::assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		self::assertSame('https://test.local/test/it?key=value', $curlResult->getUrl());
		self::assertSame('https://test.other/some/?key=value', $curlResult->getRedirectUrl());
	}

	/**
	 * @small
	 */
	public function testInHeader()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');

		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(),'https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);
		self::assertTrue($curlResult->inHeader('vary'));
		self::assertFalse($curlResult->inHeader('wrongHeader'));
	}

	 /**
	 * @small
	 */
	public function testGetHeaderArray()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');

		$curlResult = new \Friendica\Network\HTTPClient\Response\CurlResult(new NullLogger(), 'https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);

		$headers = $curlResult->getHeaderArray();

		self::assertNotEmpty($headers);
		self::assertArrayHasKey('vary', $headers);
	}

	 /**
	 * @small
	 */
	public function testGetHeaderWithParam()
	{
		$header = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../../../datasets/curl/about.body');

		$curlResult = new CurlResult(new NullLogger(),'https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);

		self::assertNotEmpty($curlResult->getHeaders());
		self::assertEmpty($curlResult->getHeader('wrongHeader'));
	}
}
