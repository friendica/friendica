<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;
use Friendica\Test\Util\Mocks\AuhtenticationMockTrait;
use Friendica\Util\Strings;

class ApiGetUserTest extends ApiTest
{
	use AuhtenticationMockTrait;

	/**
	 * Test the api_get_user() function.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testDefault($data)
	{
		$this->mockLogin($data['uid']);

		$stmt = @vsprintf(
			"SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = %d AND `contact`.`self` "
			, $data['uid']);

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', '---', 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'nav_bg', $data['nav_bg'], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'link_color', $data['link_color'], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'background_color', $data['background_color'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$user = api_get_user($this->app);

		$this->assertUser($user, $data);
		$this->assertEquals(str_replace('#', '', $data['nav_bg']), $user['profile_sidebar_fill_color']);
		$this->assertEquals(str_replace('#', '', $data['link_color']), $user['profile_link_color']);
		$this->assertEquals(str_replace('#', '', $data['background_color']), $user['profile_background_color']);
	}

	/**
	 * Test the api_get_user() function with a Frio schema.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithFrioSchema($data)
	{
		$this->mockLogin($data['uid']);

		$stmt = @vsprintf(
			"SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = %d AND `contact`.`self` "
			, $data['uid']);

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$user = api_get_user($this->app);
		$this->assertUser($user, $data);
		$this->assertEquals('708fa0', $user['profile_sidebar_fill_color']);
		$this->assertEquals('6fdbe8', $user['profile_link_color']);
		$this->assertEquals('ededed', $user['profile_background_color']);
	}

	/**
	 * Test the api_get_user() function with an user that is not allowed to use the API.
	 * @return void
	 * @dataProvider dataApiUserFull
	 * @runInSeparateProcess
	 */
	public function testWithoutApiUser($data)
	{
		$this->mockAuthenticate($data['username'], $data['password'], $data['uid'], 1);
		$this->mockSelectFirst('user', [], ['uid' => $data['uid']], $data, 1);
		$this->mockIsResult($data, true, 1);
		$this->mockPConfigGet($data['uid'], 'system', 'mobile_theme', $data['mobile_theme']);
		$this->mockIdentites($data['uid'], [$data], 1);
		$this->mockSelectFirst('contact', [], ['uid' => $data['uid'], 'self' => true], ['id' => $data['uid']], 1);
		$this->mockIsResult(['id' => $data['uid']], true, 1);

		$_SERVER['PHP_AUTH_USER'] = $data['username'];
		$_SERVER['PHP_AUTH_PW'] = $data['password'];
		$_SESSION['allow_api'] = false;
		$this->assertFalse(api_get_user($this->app));
	}

	/**
	 * Test the api_get_user() function with an user ID in a GET parameter.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithGetId($data)
	{
		$this->mockLogin($data['uid']);

		$this->mockSelectFirst('contact', ['nurl'], ['id' => $data['uid']], ['nurl' => $data['url']], 1);
		$this->mockIsResult(['nurl' => $data['url']], true, 1);
		$this->mockEscape($data['url'], 1);
		$stmt = @vsprintf(
			"SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nurl` = '%s' AND `contact`.`uid`=" . $data['uid'], $data['url']);

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$_GET['user_id'] = $data['uid'];
		$this->assertUser(api_get_user($this->app), $data);
	}

	/**
	 * Test the api_get_user() function with a wrong user ID in a GET parameter.
	 * @dataProvider dataApiUserFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithWrongGetId($data)
	{
		$this->mockSelectFirst('contact', ['nurl'], ['id' => $data['uid']], [], 1);
		$this->mockIsResult([], false, 1);
		$this->mockEscape(false, 1);

		$_GET['user_id'] = $data['uid'];
		$this->assertUser(api_get_user($this->app), $data);
	}

	/**
	 * Test the api_get_user() function with an user name in a GET parameter.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithGetName($data)
	{
		$this->mockLogin($data['uid']);

		$this->mockEscape($data['nick'], 1);
		$stmt = @vsprintf(
			"SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nick` = '%s' AND `contact`.`uid`=" . $data['uid'], $data['nick']);

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$_GET['screen_name'] = $data['nick'];
		$this->assertUser(api_get_user($this->app), $data);
	}

	/**
	 * Test the api_get_user() function with a profile URL in a GET parameter.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithGetUrl($data)
	{
		$this->mockLogin($data['uid']);

		$url = Strings::normaliseLink($data['url']);
		$this->mockEscape($url, 1);
		$stmt = @vsprintf(
			"SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nurl` = '%s' AND `contact`.`uid`=" . $data['uid'], $url);

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$_GET['profileurl'] = $data['url'];
		$this->assertUser(api_get_user($this->app), $data);
	}

	/**
	 * Test the api_get_user() function with an user ID in the API path.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithNumericCalledApi($data)
	{
		$this->mockLogin($data['uid']);

		$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $data['uid'] . " AND `contact`.`self` ";

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		global $called_api;
		$called_api = ['api_path'];
		$this->app->argv[1] = $data['uid'].'.json';
		$this->assertUser(api_get_user($this->app), $data);
	}

	/**
	 * Test the api_get_user() function with the $called_api global variable.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithCalledApi($data)
	{
		$this->mockLogin($data['uid']);

		$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $data['uid'] . " AND `contact`.`self` ";

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		global $called_api;
		$called_api = ['api', 'api_path'];
		$this->assertUser(api_get_user($this->app), $data);
	}

	/**
	 * Test the api_get_user() function with a valid user.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithCorrectUser($data)
	{
		$this->mockLogin($data['uid']);

		$this->mockSelectFirst('contact', ['nurl'], ['id' => $data['uid']], ['nurl' => $data['url']], 1);
		$this->mockIsResult(['nurl' => $data['url']], true, 1);
		$this->mockEscape($data['url'], 1);

		$stmt = @vsprintf(
			"SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nurl` = '%s' AND `contact`.`uid`=" . $data['uid'], $data['url']);

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		$this->assertUser(api_get_user($this->app, $data['uid']), $data);
	}

	/**
	 * Test the api_get_user() function with a wrong user ID.
	 * @dataProvider dataApiUserFull
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithWrongUser($data)
	{
		$this->mockSelectFirst('contact', ['nurl'], ['id' => $data['uid']], false, 1);
		$this->mockIsResult(false, false, 1);
		$this->mockEscape(false, 1);

		$this->assertUser(api_get_user($this->app, $data['uid']), $data);
	}

	/**
	 * Test the api_get_user() function with a 0 user ID.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithZeroUser($data)
	{
		$this->mockLogin($data['uid']);

		$this->mockEscape(false, 1);

		$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $data['uid'] . " AND `contact`.`self` ";

		$this->mockP($stmt, [$data], 1);
		$this->mockIsResult([$data], true, 1);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $data['uid']], ['default-location' => $data['default-location']], 1);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $data['uid'], 'is-default' => true], ['about' => $data['about']], 1);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $data['uid']], ['theme' => $data['theme']], 1);
		$this->mockPConfigGet($data['uid'], 'frio', 'schema', $data['schema'], 1);
		$this->mockGetIdForURL($data['url'], 0, true, null, null, null, 2);
		$this->mockConstants();

		/// @todo special data constelation => new dataProvider
		$this->assertUser(api_get_user($this->app, 0), $data);
	}
}
