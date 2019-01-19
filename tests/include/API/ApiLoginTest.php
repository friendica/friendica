<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;

class ApiLoginTest extends ApiTest
{
	public function setUp()
	{
		parent::setUp();

		$_SESSION['allow_api'] = false;
	}

	/**
	 * Test the api_login() function with a correct login.
	 * @dataProvider dataApiUser
	 * @@runInSeparateProcess
	 */
	public function testWithCorrectLogin($data)
	{
		$this->mockAuthenticate($data['username'], $data['password'], $data['uid'], 1);
		$this->mockSelectFirst('user', [], ['uid' => $data['uid']], $data, 1);
		$this->mockIsResult($data, true, 1);

		/// This mock is
		$this->mockPConfigGet($data['uid'], 'system', 'mobile_theme', 'anything', 1);

		$this->mockIdentites($data['uid'], 1);

		$this->mockSelectFirst('contact', [], ['uid' => $data['uid'], 'self' => true], [], 1);
		$this->mockIsResult([], false, 1);

		$_SERVER['PHP_AUTH_USER'] = $data['username'];
		$_SERVER['PHP_AUTH_PW'] = $data['password'];
		api_login($this->app);

		$this->assertTrue($_SESSION['allow_api']);
	}

	/**
	 * Test the api_login() function without any login.
	 * @return void
	 * @runInSeparateProcess
	 * @expectedException Friendica\Network\HTTPException\UnauthorizedException
	 */
	public function testWithoutLogin()
	{
		api_login($this->app);
	}

	/**
	 * Test the api_login() function with a bad login.
	 * @return void
	 * @runInSeparateProcess
	 * @expectedException Friendica\Network\HTTPException\UnauthorizedException
	 */
	public function testWithBadLogin()
	{
		$this->mockIsResult(\Mockery::any(), false);
		$_SERVER['PHP_AUTH_USER'] = 'user@server';
		api_login($this->app);
	}

	/**
	 * Test the api_login() function with oAuth.
	 * @return void
	 */
	public function testWithOauth()
	{
		$this->markTestIncomplete('Can we test this easily?');
	}

	/**
	 * Test the api_login() function with authentication provided by an addon.
	 * @return void
	 */
	public function testWithAddonAuth()
	{
		$this->markTestIncomplete('Can we test this easily?');
	}

	/**
	 * Test the api_login() function with a remote user.
	 * @runInSeparateProcess
	 * @expectedException Friendica\Network\HTTPException\UnauthorizedException
	 */
	public function testWithRemoteUser()
	{
		$this->mockAuthenticate('user', 'password', false, 1);
		$this->mockIsResult(null, false, 1);

		$_SERVER['REDIRECT_REMOTE_USER'] = '123456dXNlcjpwYXNzd29yZA==';
		api_login($this->app);
	}
}
