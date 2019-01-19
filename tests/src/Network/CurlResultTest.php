<?php

namespace Friendica\Test\src\Network;

use Friendica\Network\CurlResult;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;

class CurlResultTest extends MockedTest
{
	use AppMockTrait;
	use VFSTrait;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);

		/// @todo not needed anymore with new Logging 2019.03
		$this->mockConfigGet('system', 'debugging', false);
		$this->mockConfigGet('system', 'logfile', 'friendica.log');
		$this->mockConfigGet('system', 'loglevel', '0');
	}

	/**
	 * @small
	 */
	public function testNormal()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');


		$curlResult = new CurlResult('https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);

		$this->assertTrue($curlResult->isSuccess());
		$this->assertFalse($curlResult->isTimeout());
		$this->assertFalse($curlResult->isRedirectUrl());
		$this->assertSame($header, $curlResult->getHeader());
		$this->assertSame($body, $curlResult->getBody());
		$this->assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		$this->assertSame('https://test.local', $curlResult->getUrl());
		$this->assertSame('https://test.local', $curlResult->getRedirectUrl());
	}

	/**
	 * @small
	 */
	public function testRedirect()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');


		$curlResult = new CurlResult('https://test.local/test/it', $header . $body, [
			'http_code' => 301,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local/test/it',
			'redirect_url' => 'https://test.other'
		]);

		$this->assertTrue($curlResult->isSuccess());
		$this->assertFalse($curlResult->isTimeout());
		$this->assertTrue($curlResult->isRedirectUrl());
		$this->assertSame($header, $curlResult->getHeader());
		$this->assertSame($body, $curlResult->getBody());
		$this->assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		$this->assertSame('https://test.local/test/it', $curlResult->getUrl());
		$this->assertSame('https://test.other/test/it', $curlResult->getRedirectUrl());
	}

	/**
	 * @small
	 */
	public function testTimeout()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');


		$curlResult = new CurlResult('https://test.local/test/it', $header . $body, [
			'http_code' => 500,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local/test/it',
			'redirect_url' => 'https://test.other'
		], CURLE_OPERATION_TIMEDOUT, 'Tested error');

		$this->assertFalse($curlResult->isSuccess());
		$this->assertTrue($curlResult->isTimeout());
		$this->assertFalse($curlResult->isRedirectUrl());
		$this->assertSame($header, $curlResult->getHeader());
		$this->assertSame($body, $curlResult->getBody());
		$this->assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		$this->assertSame('https://test.local/test/it', $curlResult->getRedirectUrl());
		$this->assertSame('Tested error', $curlResult->getError());
	}

	/**
	 * @small
	 */
	public function testRedirectHeader()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.redirect');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');


		$curlResult = new CurlResult('https://test.local/test/it?key=value', $header . $body, [
			'http_code' => 301,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local/test/it?key=value',
		]);

		$this->assertTrue($curlResult->isSuccess());
		$this->assertFalse($curlResult->isTimeout());
		$this->assertTrue($curlResult->isRedirectUrl());
		$this->assertSame($header, $curlResult->getHeader());
		$this->assertSame($body, $curlResult->getBody());
		$this->assertSame('text/html; charset=utf-8', $curlResult->getContentType());
		$this->assertSame('https://test.local/test/it?key=value', $curlResult->getUrl());
		$this->assertSame('https://test.other/some/?key=value', $curlResult->getRedirectUrl());
	}
}
