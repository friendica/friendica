<?php

namespace Friendica\Test\src\Network;

use Dice\Dice;
use Friendica\DI;
use Friendica\Network\CurlResult;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CurlResultTest extends TestCase
{
	protected function setUp()
	{
		parent::setUp();

		/** @var Dice|MockInterface $dice */
		$dice = \Mockery::mock(Dice::class)->makePartial();
		$dice = $dice->addRules(include __DIR__ . '/../../../static/dependencies.config.php');

		$logger = new NullLogger();
		$dice->shouldReceive('create')
		           ->with(LoggerInterface::class, [])
		           ->andReturn($logger);

		DI::init($dice);
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
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
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
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
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

	/**
	 * @small
	 */
	public function testInHeader()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');

		$curlResult = new CurlResult('https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);
		$this->assertTrue($curlResult->inHeader('vary'));
		$this->assertFalse($curlResult->inHeader('wrongHeader'));
	}

	 /**
	 * @small
	 */
	public function testGetHeaderArray()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');

		$curlResult = new CurlResult('https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);

		$headers = $curlResult->getHeaderArray();

		$this->assertNotEmpty($headers);
		$this->assertArrayHasKey('vary', $headers);
	}

	 /**
	 * @small
	 */
	public function testGetHeaderWithParam()
	{
		$header = file_get_contents(__DIR__ . '/../../datasets/curl/about.head');
		$body = file_get_contents(__DIR__ . '/../../datasets/curl/about.body');

		$curlResult = new CurlResult('https://test.local', $header . $body, [
			'http_code' => 200,
			'content_type' => 'text/html; charset=utf-8',
			'url' => 'https://test.local'
		]);

		$this->assertNotEmpty($curlResult->getHeader());
		$this->assertEmpty($curlResult->getHeader('wrongHeader'));
	}
}
