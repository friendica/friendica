<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Test\src\Util;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MockedTest;

class BaseURLTest extends MockedTest
{
	public function dataDefault()
	{
		return [
			'null' => [
				'server' => [],
				'input' => [
				'hostname' => null,
				'urlPath' => null,
				'sslPolicy' => null,
				'url' => null,
					],
				'assert' => [
					'hostname'  => '',
					'urlPath'   => '',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://',
					'scheme'    => 'http',
				],
			],
			'WithSubDirectory' => [
				'server' => [
					'SERVER_NAME'  => 'friendica.local',
					'REDIRECT_URI' => 'test/module/more',
					'QUERY_STRING' => 'module/more',
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.local/test',
					'scheme'    => 'http',
				],
			],
			'input' => [
				'server' => [],
				'input' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'http://friendica.local/test',
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'http://friendica.local/test',
					'scheme'    => 'http',
				],
			],
			'WithHttpsScheme' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'REDIRECT_URI'   => 'test/module/more',
					'QUERY_STRING'   => 'module/more',
					'HTTPS'          => true,
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/test',
					'scheme'    => 'https',
				],
			],
			'WithoutQueryString' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'REDIRECT_URI'   => 'test/more',
					'HTTPS'          => true,
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/test/more',
					'scheme'    => 'https',
				],
			],
			'WithPort' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'SERVER_PORT'    => '1234',
					'REDIRECT_URI'   => 'test/more',
					'HTTPS'          => true,
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local:1234',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local:1234/test/more',
					'scheme'    => 'https',
				],
			],
			'With443Port' => [
				'server' => [
					'SERVER_NAME'    => 'friendica.local',
					'SERVER_PORT'    => '443',
					'REDIRECT_URI'   => 'test/more',
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
					'url'       => 'https://friendica.local/test/more',
					'scheme'    => 'https',
				],
			],
			'With80Port' => [
				'server' => [
					'SERVER_NAME'  => 'friendica.local',
					'SERVER_PORT'  => '80',
					'REDIRECT_URI' => 'test/more',
				],
				'input' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
					'url'       => null,
				],
				'assert' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'test/more',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.local/test/more',
					'scheme'    => 'http',
				],
			],
		];
	}

	/**
	 * Test the default config determination
	 * @dataProvider dataDefault
	 */
	public function testCheck($server, $input, $assert)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn($input['hostname']);
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn($input['urlPath']);
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($input['sslPolicy']);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($input['url']);

		$savable = false;

		// If we don't have an urlPath as an input, we assert it, we will save it to the DB for the next time
		if (!isset($input['urlPath']) && isset($assert['urlPath'])) {
			$configMock->shouldReceive('set')->with('system', 'urlpath', $assert['urlPath'], false)->once();
			$savable = true;
		}

		// If we don't have the ssl_policy as an input, we assert it, we will save it to the DB for the next time
		if (!isset($input['sslPolicy']) && isset($assert['sslPolicy'])) {
			$configMock->shouldReceive('set')->with('system', 'ssl_policy', $assert['sslPolicy'], false)->once();
			$savable = true;
		}

		// If we don't have the hostname as an input, we assert it, we will save it to the DB for the next time
		if (empty($input['hostname']) && !empty($assert['hostname'])) {
			$configMock->shouldReceive('set')->with('config', 'hostname', $assert['hostname'], false)->once();
			$savable = true;
		}

		// If we don't have an URL at first, but we assert it, we will save it to the DB for the next time
		if (empty($input['url']) && !empty($assert['url'])) {
			$configMock->shouldReceive('set')->with('system', 'url', $assert['url'], false)->once();
			$savable = true;
		}

		if ($savable) {
			$configMock->shouldReceive('save')->once();
		}

		$baseUrl = new BaseURL($configMock, $server);

		self::assertEquals($assert['hostname'], $baseUrl->getHostname());
		self::assertEquals($assert['urlPath'], $baseUrl->getUrlPath());
		self::assertEquals($assert['sslPolicy'], $baseUrl->getSSLPolicy());
		self::assertEquals($assert['scheme'], $baseUrl->getScheme());
		self::assertEquals($assert['url'], $baseUrl->get());
	}

	public function dataSave()
	{
		return [
			'default' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => 'new/path',
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				],
				'url' => 'https://friendica.local/new/path',
			],
			'null' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => null,
				],
				'url' => 'http://friendica.old/is/old/path',
			],
			'changeHostname' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => 'friendica.local',
					'urlPath'   => null,
					'sslPolicy' => null,
				],
				'url' => 'http://friendica.local/is/old/path',
			],
			'changeUrlPath' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => null,
					'urlPath'   => 'new/path',
					'sslPolicy' => null,
				],
				'url' => 'http://friendica.old/new/path',
			],
			'changeSSLPolicy' => [
				'input' => [
					'hostname'  => 'friendica.old',
					'urlPath'   => 'is/old/path',
					'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
					'url'       => 'http://friendica.old/is/old/path',
					'force_ssl' => true,
				],
				'save' => [
					'hostname'  => null,
					'urlPath'   => null,
					'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				],
				'url' => 'https://friendica.old/is/old/path',
			],
		];
	}

	/**
	 * Test the save() method
	 * @dataProvider dataSave
	 */
	public function testSave($input, $save, $url)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn($input['hostname']);
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn($input['urlPath']);
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($input['sslPolicy']);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($input['url']);
		$configMock->shouldReceive('get')->with('system', 'force_ssl')->andReturn($input['force_ssl']);

		$baseUrl = new BaseURL($configMock, []);

		if (isset($save['hostname'])) {
			$configMock->shouldReceive('set')->with('config', 'hostname', $save['hostname'], false)->andReturn(true)->once();
		}

		if (isset($save['urlPath'])) {
			$configMock->shouldReceive('set')->with('system', 'urlpath', $save['urlPath'], false)->andReturn(true)->once();
		}

		if (isset($save['sslPolicy'])) {
			$configMock->shouldReceive('set')->with('system', 'ssl_policy', $save['sslPolicy'], false)->andReturn(true)->once();
		}

		$configMock->shouldReceive('set')->with('system', 'url', $url, false)->andReturn(true)->once();

		$configMock->shouldReceive('save')->once();

		$baseUrl->save($save['hostname'], $save['sslPolicy'], $save['urlPath']);

		self::assertEquals($url, $baseUrl->get());
	}

	/**
	 * Test the saveByUrl() method
	 * @dataProvider dataSave
	 *
	 * @param $input
	 * @param $save
	 * @param $url
	 */
	public function testSaveByUrl($input, $save, $url)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn($input['hostname']);
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn($input['urlPath']);
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($input['sslPolicy']);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($input['url']);
		$configMock->shouldReceive('get')->with('system', 'force_ssl')->andReturn($input['force_ssl']);

		$baseUrl = new BaseURL($configMock, []);

		if (isset($save['hostname'])) {
			$configMock->shouldReceive('set')->with('config', 'hostname', $save['hostname'], false)->andReturn(true)->once();
		}

		if (isset($save['urlPath'])) {
			$configMock->shouldReceive('set')->with('system', 'urlpath', $save['urlPath'], false)->andReturn(true)->once();
		}

		if (isset($save['sslPolicy'])) {
			$configMock->shouldReceive('set')->with('system', 'ssl_policy', $save['sslPolicy'], false)->andReturn(true)->once();
		}

		$configMock->shouldReceive('set')->with('system', 'url', $url, false)->andReturn(true)->once();

		$configMock->shouldReceive('save')->once();

		$baseUrl->saveByURL($url);

		self::assertEquals($url, $baseUrl->get());
	}

	public function dataGetBaseUrl()
	{
		return [
			'default'           => [
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'ssl'       => false,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
			'DefaultWithSSL'    => [
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'ssl'       => true,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'https://friendica.local/new/test',
			],
			'SSLFullWithSSL'    => [
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'ssl'       => true,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
			'SSLFullWithoutSSL' => [
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'ssl'       => false,
				'url'       => 'https://friendica.local/new/test',
				'assert'    => 'https://friendica.local/new/test',
			],
			'NoSSLWithSSL'      => [
				'sslPolicy' => BaseURL::SSL_POLICY_NONE,
				'ssl'       => true,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
			'NoSSLWithoutSSL'   => [
				'sslPolicy' => BaseURL::SSL_POLICY_NONE,
				'ssl'       => false,
				'url'       => 'http://friendica.local/new/test',
				'assert'    => 'http://friendica.local/new/test',
			],
		];
	}

	/**
	 * Test the get() method
	 * @dataProvider dataGetBaseUrl
	 */
	public function testGetURL($sslPolicy, $ssl, $url, $assert)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn('friendica.local');
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn('new/test');
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($sslPolicy);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($url);

		$baseUrl = new BaseURL($configMock, []);

		self::assertEquals($assert, $baseUrl->get($ssl));
	}

	public function dataCheckRedirectHTTPS()
	{
		return [
			'default' => [
				'server' => [
					'REQUEST_METHOD' => 'GET',
					'HTTPS' => true,
				],
				'forceSSL'  => false,
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'url'       => 'https://friendica.local',
				'redirect'  => false,
			],
			'forceSSL' => [
				'server' => [
					'REQUEST_METHOD' => 'GET',
				],
				'forceSSL'  => true,
				'sslPolicy' => BaseURL::DEFAULT_SSL_SCHEME,
				'url'       => 'https://friendica.local',
				'redirect'  => false,
			],
			'forceSSLWithSSLPolicy' => [
				'server' => [],
				'forceSSL'  => true,
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'url'       => 'https://friendica.local',
				'redirect'  => false,
			],
			'forceSSLWithSSLPolicyAndGet' => [
				'server' => [
					'REQUEST_METHOD' => 'GET',
				],
				'forceSSL'  => true,
				'sslPolicy' => BaseURL::SSL_POLICY_FULL,
				'url'       => 'https://friendica.local',
				'redirect'  => true,
			],
		];
	}

	/**
	 * Test the checkRedirectHTTPS() method
	 * @dataProvider dataCheckRedirectHTTPS
	 */
	public function testCheckRedirectHTTPS($server, $forceSSL, $sslPolicy, $url, $redirect)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn('friendica.local');
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn('new/test');
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn($sslPolicy);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn($url);
		$configMock->shouldReceive('get')->with('system', 'force_ssl')->andReturn($forceSSL);

		$baseUrl = new BaseURL($configMock, $server);

		self::assertEquals($redirect, $baseUrl->checkRedirectHttps());
	}

	public function dataWrongSave()
	{
		return [
			'wrongHostname' => [
				'fail' => 'hostname',
			],
			'wrongSSLPolicy' => [
				'fail' => 'sslPolicy',
			],
			'wrongURLPath' => [
				'fail' => 'urlPath',
			],
			'wrongURL' => [
				'fail' => 'url',
			],
		];
	}

	/**
	 * Test the save() method with wrong parameters
	 * @dataProvider dataWrongSave
	 */
	public function testWrongSave($fail)
	{
		$configMock = \Mockery::mock(IManageConfigValues::class);
		$configMock->shouldReceive('get')->with('config', 'hostname')->andReturn('friendica.local');
		$configMock->shouldReceive('get')->with('system', 'urlpath')->andReturn('new/test');
		$configMock->shouldReceive('get')->with('system', 'ssl_policy')->andReturn(BaseURL::DEFAULT_SSL_SCHEME);
		$configMock->shouldReceive('get')->with('system', 'url')->andReturn('http://friendica.local/new/test');

		switch ($fail) {
			case 'hostname':
				$configMock->shouldReceive('set')->with('config', 'hostname', \Mockery::any(), false)->andReturn(false)->once();
				break;
			case 'sslPolicy':
				$configMock->shouldReceive('set')->with('config', 'hostname', \Mockery::any(), false)->andReturn(true)->twice();
				$configMock->shouldReceive('set')->with('system', 'ssl_policy', \Mockery::any(), false)->andReturn(false)->once();
				$configMock->shouldReceive('save')->once();
				break;
			case 'urlPath':
				$configMock->shouldReceive('set')->with('config', 'hostname', \Mockery::any(), false)->andReturn(true)->twice();
				$configMock->shouldReceive('set')->with('system', 'ssl_policy', \Mockery::any(), false)->andReturn(true)->twice();
				$configMock->shouldReceive('set')->with('system', 'urlpath', \Mockery::any(), false)->andReturn(false)->once();
				$configMock->shouldReceive('save')->once();
				break;
			case 'url':
				$configMock->shouldReceive('set')->with('config', 'hostname', \Mockery::any(), false)->andReturn(true)->twice();
				$configMock->shouldReceive('set')->with('system', 'ssl_policy', \Mockery::any(), false)->andReturn(true)->twice();
				$configMock->shouldReceive('set')->with('system', 'urlpath', \Mockery::any(), false)->andReturn(true)->twice();
				$configMock->shouldReceive('set')->with('system', 'url', \Mockery::any(), false)->andReturn(false)->once();
				$configMock->shouldReceive('save')->once();
				break;
		}

		$baseUrl = new BaseURL($configMock, []);
		self::assertFalse($baseUrl->save('test', 10, 'nope'));

		// nothing should have changed because we never successfully saved anything
		self::assertEquals('friendica.local', $baseUrl->getHostname());
		self::assertEquals('new/test', $baseUrl->getUrlPath());
		self::assertEquals(BaseURL::DEFAULT_SSL_SCHEME, $baseUrl->getSSLPolicy());
		self::assertEquals('http://friendica.local/new/test', $baseUrl->get());
	}
}
