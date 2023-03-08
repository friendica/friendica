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

namespace Friendica\Test\src\Module\Api;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Twitter\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Test\MockedTest;
use Psr\Log\NullLogger;

class ApiResponseTest extends MockedTest
{
	public function testErrorWithJson()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'json');

		self::assertEquals('{"error":"error_message","code":"200 OK","request":""}', $response->getContent());
	}

	public function testErrorWithXml()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'xml');

		self::assertEquals(['Content-type' => 'text/xml', 'HTTP/1.1 200 OK'], $response->getHeaders());
		self::assertEquals('<?xml version="1.0"?>' . "\n" .
						   '<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
						   'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
						   'xmlns:georss="http://www.georss.org/georss">' . "\n" .
						   '  <error>error_message</error>' . "\n" .
						   '  <code>200 OK</code>' . "\n" .
						   '  <request/>' . "\n" .
						   '</status>' . "\n",
			$response->getContent());
	}

	public function testErrorWithRss()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'rss');

		self::assertEquals(['Content-type' => 'application/rss+xml', 'HTTP/1.1 200 OK'], $response->getHeaders());
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <error>error_message</error>' . "\n" .
			'  <code>200 OK</code>' . "\n" .
			'  <request/>' . "\n" .
			'</status>' . "\n",
			$response->getContent());
	}

	public function testErrorWithAtom()
	{
		$l10n = \Mockery::mock(L10n::class);
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->error(200, 'OK', 'error_message', 'atom');

		self::assertEquals(['Content-type' => 'application/atom+xml', 'HTTP/1.1 200 OK'], $response->getHeaders());
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <error>error_message</error>' . "\n" .
			'  <code>200 OK</code>' . "\n" .
			'  <request/>' . "\n" .
			'</status>' . "\n",
			$response->getContent());
	}

	public function testUnsupported()
	{
		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);
		$response->unsupported();

		self::assertEquals('{"error":"API endpoint %s %s is not implemented but might be in the future.","code":"501 Not Implemented","request":""}', $response->getContent());
	}

	/**
	 * Test the BaseApi::reformatXML() function.
	 *
	 * @return void
	 */
	public function testApiReformatXml()
	{
		$item = true;
		$key  = '';
		self::assertTrue(ApiResponse::reformatXML($item, $key));
		self::assertEquals('true', $item);
	}

	/**
	 * Test the BaseApi::reformatXML() function with a statusnet_api key.
	 *
	 * @return void
	 */
	public function testApiReformatXmlWithStatusnetKey()
	{
		$item = '';
		$key  = 'statusnet_api';
		self::assertTrue(ApiResponse::reformatXML($item, $key));
		self::assertEquals('statusnet:api', $key);
	}

	/**
	 * Test the BaseApi::reformatXML() function with a friendica_api key.
	 *
	 * @return void
	 */
	public function testApiReformatXmlWithFriendicaKey()
	{
		$item = '';
		$key  = 'friendica_api';
		self::assertTrue(ApiResponse::reformatXML($item, $key));
		self::assertEquals('friendica:api', $key);
	}

	/**
	 * Test the BaseApi::createXML() function.
	 *
	 * @return void
	 */
	public function testApiCreateXml()
	{
		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <data>some_data</data>' . "\n" .
			'</root_element>' . "\n",
			$response->createXML(['data' => ['some_data']], 'root_element')
		);
	}

	/**
	 * Test the BaseApi::createXML() function without any XML namespace.
	 *
	 * @return void
	 */
	public function testApiCreateXmlWithoutNamespaces()
	{
		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<ok>' . "\n" .
			'  <data>some_data</data>' . "\n" .
			'</ok>' . "\n",
			$response->createXML(['data' => ['some_data']], 'ok')
		);
	}

	/**
	 * Test the BaseApi::formatData() function.
	 *
	 * @return void
	 */
	public function testApiFormatData()
	{
		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);

		$data = ['some_data'];
		self::assertEquals($data, $response->formatData('root_element', 'json', $data));
	}

	/**
	 * Test the BaseApi::formatData() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiFormatDataWithXml()
	{
		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl     = \Mockery::mock(BaseURL::class);
		$twitterUser = \Mockery::mock(User::class);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);

		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <data>some_data</data>' . "\n" .
			'</root_element>' . "\n",
			$response->formatData('root_element', 'xml', ['data' => ['some_data']])
		);
	}

	/**
	 * Test the api_rss_extra() function.
	 *
	 * @return void
	 */
	public function testApiRssExtra()
	{
		self::markTestIncomplete('Cannot mock it yet.');

		/*
		$user_info = ['url' => 'user_url', 'lang' => 'en'];
		$userMock = \Mockery::mock(\Friendica\Object\Api\Twitter\User::class);
		$userMock->shouldReceive('toArray')->andReturn($user_info);

		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) {
			return $args;
		});
		$args = \Mockery::mock(Arguments::class);
		$args->shouldReceive('getQueryString')->andReturn('');
		$baseUrl = \Mockery::mock(BaseURL::class);
		$baseUrl->shouldReceive('__toString')->andReturn('https://friendica.local');
		$twitterUser = \Mockery::mock(User::class);
		$twitterUser->shouldReceive('createFromContactId')->with(1)->andReturn($userMock);

		$response = new ApiResponse($l10n, $args, new NullLogger(), $baseUrl, $twitterUser);

		$result = $response->formatData('root_element', 'rss', ['data' => ['some_data']], 1);

		print_r($result);

		self::assertEquals($user_info, $result['$user']);
		self::assertEquals($user_info['url'], $result['$rss']['alternate']);
		self::assertArrayHasKey('self', $result['$rss']);
		self::assertArrayHasKey('base', $result['$rss']);
		self::assertArrayHasKey('updated', $result['$rss']);
		self::assertArrayHasKey('atom_updated', $result['$rss']);
		self::assertArrayHasKey('language', $result['$rss']);
		self::assertArrayHasKey('logo', $result['$rss']);
		*/
	}

	/**
	 * Test the api_rss_extra() function without any user info.
	 *
	 * @return void
	 */
	public function testApiRssExtraWithoutUserInfo()
	{
		self::markTestIncomplete('Cannot mock it yet.');

		/*
		$result = api_rss_extra([], null);
		self::assertIsArray($result['$user']);
		self::assertArrayHasKey('alternate', $result['$rss']);
		self::assertArrayHasKey('self', $result['$rss']);
		self::assertArrayHasKey('base', $result['$rss']);
		self::assertArrayHasKey('updated', $result['$rss']);
		self::assertArrayHasKey('atom_updated', $result['$rss']);
		self::assertArrayHasKey('language', $result['$rss']);
		self::assertArrayHasKey('logo', $result['$rss']);
		*/
	}
}
