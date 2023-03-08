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

namespace Friendica\Test\src\Model\User;

use Friendica\App\BaseURL;
use Friendica\App\Request;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Model\User\Cookie;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\StaticCookie;
use Mockery\MockInterface;

class CookieTest extends MockedTest
{
	/** @var MockInterface|IManageConfigValues */
	private $config;
	/** @var MockInterface|BaseURL */
	private $baseUrl;

	const SERVER_ARRAY = ['REMOTE_ADDR' => '1.2.3.4'];

	protected function setUp(): void
	{
		StaticCookie::clearStatic();

		parent::setUp();

		$this->config  = \Mockery::mock(IManageConfigValues::class);
		$this->baseUrl = \Mockery::mock(BaseURL::class);
	}

	protected function tearDown(): void
	{
		StaticCookie::clearStatic();

		parent::tearDown();
	}

	/**
	 * Test if we can create a basic cookie instance
	 */
	public function testInstance()
	{
		$this->baseUrl->shouldReceive('getScheme')->andReturn('https')->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn('1235')->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();
		$this->config->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn('')->once();

		$request = new Request($this->config,static::SERVER_ARRAY);

		$cookie = new Cookie($request, $this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);
	}

	public function dataGet()
	{
		return [
			'default'    => [
				'cookieData' => [
					Cookie::NAME => json_encode([
						'uid'  => -1,
						'hash' => 12345,
						'ip'   => '127.0.0.1',
					])
				],
				'hasValues'  => true,
				'uid'        => -1,
				'hash'       => 12345,
				'ip'         => '127.0.0.1',
			],
			'missing'    => [
				'cookieData' => [

				],
				'hasValues'  => false,
				'uid'        => null,
				'hash'       => null,
				'ip'         => null,
			],
			'invalid'    => [
				'cookieData' => [
					Cookie::NAME => 'test',
				],
				'hasValues'  => false,
				'uid'        => null,
				'hash'       => null,
				'ip'         => null,
			],
			'incomplete' => [
				'cookieData' => [
					Cookie::NAME => json_encode([
						'uid'  => -1,
						'hash' => 12345,
					])
				],
				'hasValues'  => true,
				'uid'        => -1,
				'hash'       => 12345,
				'ip'         => null,
			],
		];
	}

	/**
	 * Test the get() method of the cookie class
	 *
	 * @dataProvider dataGet
	 */
	public function testGet(array $cookieData, bool $hasValues, $uid, $hash, $ip)
	{
		$this->baseUrl->shouldReceive('getScheme')->andReturn('https')->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn('1235')->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();
		$this->config->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn('')->once();

		$request = new Request($this->config, static::SERVER_ARRAY);

		$cookie = new Cookie($request, $this->config, $this->baseUrl, $cookieData);
		self::assertInstanceOf(Cookie::class, $cookie);

		if (isset($uid)) {
			self::assertEquals($uid, $cookie->get('uid'));
		} else {
			self::assertNull($cookie->get('uid'));
		}
		if (isset($hash)) {
			self::assertEquals($hash, $cookie->get('hash'));
		} else {
			self::assertNull($cookie->get('hash'));
		}
		if (isset($ip)) {
			self::assertEquals($ip, $cookie->get('ip'));
		} else {
			self::assertNull($cookie->get('ip'));
		}
	}

	public function dataCheck()
	{
		return [
			'default'   => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => 'userkey',
				'password'         => 'test',
				'assertHash'       => 'e9b4eb16275a2907b5659d22905b248221d0517dde4a9d5c320b8fe051b1267b',
				'assertTrue'       => true,
			],
			'emptyUser' => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => '',
				'password'         => '',
				'assertHash'       => '',
				'assertTrue'       => false,
			],
			'invalid'   => [
				'serverPrivateKey' => 'serverkey',
				'userPrivateKey'   => 'bla',
				'password'         => 'nope',
				'assertHash'       => 'real wrong!',
				'assertTrue'       => false,
			]
		];
	}

	/**
	 * Test the check() method of the cookie class
	 *
	 * @dataProvider dataCheck
	 */
	public function testCheck(string $serverPrivateKey, string $userPrivateKey, string $password, string $assertHash, bool $assertTrue)
	{
		$this->baseUrl->shouldReceive('getScheme')->andReturn('https')->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverPrivateKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn('7')->once();
		$this->config->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn('')->once();

		$request = new Request($this->config, static::SERVER_ARRAY);

		$cookie = new Cookie($request, $this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);

		self::assertEquals($assertTrue, $cookie->comparePrivateDataHash($assertHash, $password, $userPrivateKey));
	}

	public function dataSet()
	{
		return [
			'default'         => [
				'serverKey'   => 23,
				'uid'         => 0,
				'password'    => '234',
				'privateKey'  => '124',
				'assertHash'  => 'b657a15cfe7ed1f7289c9aa51af14a9a26c966f4ddd74e495fba103d8e872a39',
				'remoteIp'    => '0.0.0.0',
				'serverArray' => [],
			],
			'withServerArray' => [
				'serverKey'   => 23,
				'uid'         => 0,
				'password'    => '234',
				'privateKey'  => '124',
				'assertHash'  => 'b657a15cfe7ed1f7289c9aa51af14a9a26c966f4ddd74e495fba103d8e872a39',
				'remoteIp'    => '1.2.3.4',
				'serverArray' => ['REMOTE_ADDR' => '1.2.3.4',],
			],
		];
	}

	public function assertCookie($uid, $hash, $remoteIp)
	{
		self::assertArrayHasKey(Cookie::NAME, StaticCookie::$_COOKIE);

		$data = json_decode(StaticCookie::$_COOKIE[Cookie::NAME]);

		self::assertObjectHasAttribute('uid', $data);
		self::assertEquals($uid, $data->uid);
		self::assertObjectHasAttribute('hash', $data);
		self::assertEquals($hash, $data->hash);
		self::assertObjectHasAttribute('ip', $data);
		self::assertEquals($remoteIp, $data->ip);

		self::assertLessThanOrEqual(time() + Cookie::DEFAULT_EXPIRE * 24 * 60 * 60, StaticCookie::$_EXPIRE);
	}

	/**
	 * Test the set() method of the cookie class
	 *
	 * @dataProvider dataSet
	 */
	public function testSet($serverKey, $uid, $password, $privateKey, $assertHash, $remoteIp, $serverArray)
	{
		$this->baseUrl->shouldReceive('getScheme')->andReturn('https')->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn(Cookie::DEFAULT_EXPIRE)->once();
		$this->config->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn('')->once();
		$this->config->shouldReceive('get')->with('proxy', 'forwarded_for_headers')->andReturn(Request::DEFAULT_FORWARD_FOR_HEADER);


		$request = new Request($this->config, $serverArray);

		$cookie = new StaticCookie($request, $this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);

		$cookie->setMultiple([
			'uid' => $uid,
			'hash' => $assertHash,
		]);

		self::assertCookie($uid, $assertHash, $remoteIp);
	}

	/**
	 * Test the set() method of the cookie class
	 *
	 * @dataProvider dataSet
	 */
	public function testDoubleSet($serverKey, $uid, $password, $privateKey, $assertHash, $remoteIp, $serverArray)
	{
		$this->baseUrl->shouldReceive('getScheme')->andReturn('https')->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn($serverKey)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn(Cookie::DEFAULT_EXPIRE)->once();
		$this->config->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn('')->once();
		$this->config->shouldReceive('get')->with('proxy', 'forwarded_for_headers')->andReturn(Request::DEFAULT_FORWARD_FOR_HEADER);

		$request = new Request($this->config, $serverArray);

		$cookie = new StaticCookie($request, $this->config, $this->baseUrl, $serverArray);
		self::assertInstanceOf(Cookie::class, $cookie);

		$cookie->set('uid', $uid);
		$cookie->set('hash', $assertHash);

		self::assertCookie($uid, $assertHash, $remoteIp);
	}

	/**
	 * Test the clear() method of the cookie class
	 */
	public function testClear()
	{
		StaticCookie::$_COOKIE = [
			Cookie::NAME => 'test'
		];

		$this->baseUrl->shouldReceive('getScheme')->andReturn('https')->once();
		$this->config->shouldReceive('get')->with('system', 'site_prvkey')->andReturn(24)->once();
		$this->config->shouldReceive('get')->with('system', 'auth_cookie_lifetime', Cookie::DEFAULT_EXPIRE)->andReturn(Cookie::DEFAULT_EXPIRE)->once();
		$this->config->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn('')->once();

		$request = new Request($this->config, static::SERVER_ARRAY);

		$cookie = new StaticCookie($request, $this->config, $this->baseUrl);
		self::assertInstanceOf(Cookie::class, $cookie);

		self::assertEquals('test', StaticCookie::$_COOKIE[Cookie::NAME]);
		self::assertEquals(null, StaticCookie::$_EXPIRE);

		$cookie->clear();

		self::assertEmpty(StaticCookie::$_COOKIE[Cookie::NAME]);
		self::assertEquals(-3600, StaticCookie::$_EXPIRE);
	}
}
