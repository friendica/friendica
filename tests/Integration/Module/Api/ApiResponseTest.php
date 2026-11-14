<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api;

use Friendica\DI;
use Friendica\Module\Api\ApiResponse;
use Friendica\Test\ApiTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ApiResponseTest extends ApiTestCase
{
	/** Public contact fixture, see tests/Fixtures/api.fixture.php */
	private const CONTACT_ID = 43;

	private function createApiResponse(array $server = [], string $jsonpCallback = '', ?LoggerInterface $logger = null): ApiResponse
	{
		return new ApiResponse(DI::l10n(), DI::args(), $logger ?? DI::logger(), DI::baseUrl(), DI::twitterUser(), DI::config(), $server, $jsonpCallback);
	}

	public function testErrorWithJson(): void
	{
		$response = $this->createApiResponse();
		$response->error(200, 'OK', 'error_message', 'json');

		self::assertEquals('{"error":"error_message","code":"200 OK","request":""}', $response->getContent());
	}

	public function testErrorWithXml(): void
	{
		$response = $this->createApiResponse();
		$response->error(200, 'OK', 'error_message', 'xml');

		self::assertEquals(['Content-type' => 'text/xml', 'HTTP/1.1 200 OK'], $response->getHeaders());
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n"
			. '<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '
			. 'xmlns:friendica="http://friendi.ca/schema/api/1/" '
			. 'xmlns:georss="http://www.georss.org/georss">' . "\n"
			. '  <error>error_message</error>' . "\n"
			. '  <code>200 OK</code>' . "\n"
			. '  <request/>' . "\n"
			. '</status>' . "\n",
			$response->getContent(),
		);
	}

	public function testErrorWithRss(): void
	{
		$response = $this->createApiResponse();
		$response->error(200, 'OK', 'error_message', 'rss');

		self::assertEquals(['Content-type' => 'application/rss+xml', 'HTTP/1.1 200 OK'], $response->getHeaders());
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n"
			. '<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '
			. 'xmlns:friendica="http://friendi.ca/schema/api/1/" '
			. 'xmlns:georss="http://www.georss.org/georss">' . "\n"
			. '  <error>error_message</error>' . "\n"
			. '  <code>200 OK</code>' . "\n"
			. '  <request/>' . "\n"
			. '</status>' . "\n",
			$response->getContent(),
		);
	}

	public function testErrorWithAtom(): void
	{
		$response = $this->createApiResponse();
		$response->error(200, 'OK', 'error_message', 'atom');

		self::assertEquals(['Content-type' => 'application/atom+xml', 'HTTP/1.1 200 OK'], $response->getHeaders());
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n"
			. '<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '
			. 'xmlns:friendica="http://friendi.ca/schema/api/1/" '
			. 'xmlns:georss="http://www.georss.org/georss">' . "\n"
			. '  <error>error_message</error>' . "\n"
			. '  <code>200 OK</code>' . "\n"
			. '  <request/>' . "\n"
			. '</status>' . "\n",
			$response->getContent(),
		);
	}

	public function testUnsupported(): void
	{
		$logger = \Mockery::mock(NullLogger::class);
		$logger->shouldReceive('info')->withArgs(['Unimplemented API call', ['method' => 'all', 'path' => '', 'agent' => '', 'request' => []]]);

		$response = $this->createApiResponse([], '', $logger);
		$response->unsupported();

		self::assertEquals('{"error":"API endpoint ALL  is not implemented but might be in the future.","code":"501 Not Implemented","request":""}', $response->getContent());
	}

	public function testUnsupportedUserAgent(): void
	{
		$logger = \Mockery::mock(NullLogger::class);
		$logger->shouldReceive('info')->withArgs(['Unimplemented API call', ['method' => 'all', 'path' => '', 'agent' => 'PHPUnit', 'request' => []]]);

		$response = $this->createApiResponse(['HTTP_USER_AGENT' => 'PHPUnit'], '', $logger);
		$response->unsupported();

		self::assertEquals('{"error":"API endpoint ALL  is not implemented but might be in the future.","code":"501 Not Implemented","request":""}', $response->getContent());
	}

	public function testApiReformatXml(): void
	{
		$item = true;
		$key  = '';
		self::assertTrue(ApiResponse::reformatXML($item, $key));
		self::assertEquals('true', $item);
	}

	public function testApiReformatXmlWithStatusnetKey(): void
	{
		$item = '';
		$key  = 'statusnet_api';
		self::assertTrue(ApiResponse::reformatXML($item, $key));
		self::assertEquals('statusnet:api', $key);
	}

	public function testApiReformatXmlWithFriendicaKey(): void
	{
		$item = '';
		$key  = 'friendica_api';
		self::assertTrue(ApiResponse::reformatXML($item, $key));
		self::assertEquals('friendica:api', $key);
	}

	public function testApiCreateXml(): void
	{
		$response = $this->createApiResponse();

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n"
			. '<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '
			. 'xmlns:friendica="http://friendi.ca/schema/api/1/" '
			. 'xmlns:georss="http://www.georss.org/georss">' . "\n"
			. '  <data>some_data</data>' . "\n"
			. '</root_element>' . "\n",
			$response->createXML(['data' => ['some_data']], 'root_element'),
		);
	}

	public function testApiCreateXmlWithoutNamespaces(): void
	{
		$response = $this->createApiResponse();

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n"
			. '<ok>' . "\n"
			. '  <data>some_data</data>' . "\n"
			. '</ok>' . "\n",
			$response->createXML(['data' => ['some_data']], 'ok'),
		);
	}

	public function testApiFormatData(): void
	{
		$response = $this->createApiResponse();

		$data = ['some_data'];
		self::assertEquals($data, $response->formatData('root_element', 'json', $data));
	}

	public function testApiExitWithJson(): void
	{
		$response = $this->createApiResponse();
		$response->addJsonContent(['some_data']);

		self::assertEquals('["some_data"]', $response->getContent());
	}

	public function testApiExitWithJsonP(): void
	{
		$response = $this->createApiResponse([], 'JsonPCallback');
		$response->addJsonContent(['some_data']);

		self::assertEquals('JsonPCallback(["some_data"])', $response->getContent());
	}

	public function testApiFormatDataWithXml(): void
	{
		$response = $this->createApiResponse();

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n"
			. '<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '
			. 'xmlns:friendica="http://friendi.ca/schema/api/1/" '
			. 'xmlns:georss="http://www.georss.org/georss">' . "\n"
			. '  <data>some_data</data>' . "\n"
			. '</root_element>' . "\n",
			$response->formatData('root_element', 'xml', ['data' => ['some_data']]),
		);
	}

	public function testApiRssExtra(): void
	{
		$response = $this->createApiResponse();

		$result = $response->formatData('root_element', 'rss', ['data' => ['key' => 'some_data']], self::CONTACT_ID);

		self::assertStringContainsString('<alternate>https://friendica.local/profile/othercontact</alternate>', $result);
		self::assertStringContainsString('<base>https://friendica.local</base>', $result);
		self::assertStringContainsString('<logo>https://friendica.local/images/friendica-32.png</logo>', $result);
		self::assertStringContainsString('<updated>', $result);
		self::assertStringContainsString('<atom_updated>', $result);
	}

	public function testApiRssExtraWithoutUserInfo(): void
	{
		$response = $this->createApiResponse();

		$result = $response->formatData('root_element', 'rss', ['data' => ['key' => 'some_data']], 0);

		self::assertStringContainsString('<key>some_data</key>', $result);
		self::assertStringNotContainsString('<alternate>', $result);
		self::assertStringNotContainsString('<base>', $result);
		self::assertStringNotContainsString('<logo>', $result);
		self::assertStringNotContainsString('<language>', $result);
	}
}
