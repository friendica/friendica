<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;

class ApiRssExtraTest extends ApiTest
{
	/**
	 * Test the api_rss_extra() function.
	 * @return void
	 */
	public function testDefault()
	{
		$user_info = ['url' => 'user_url', 'lang' => 'en'];
		$result = api_rss_extra($this->app, [], $user_info);
		$this->assertEquals($user_info, $result['$user']);
		$this->assertEquals($user_info['url'], $result['$rss']['alternate']);
		$this->assertArrayHasKey('self', $result['$rss']);
		$this->assertArrayHasKey('base', $result['$rss']);
		$this->assertArrayHasKey('updated', $result['$rss']);
		$this->assertArrayHasKey('atom_updated', $result['$rss']);
		$this->assertArrayHasKey('language', $result['$rss']);
		$this->assertArrayHasKey('logo', $result['$rss']);
	}

	/**
	 * Test the api_rss_extra() function without any user info.
	 * @dataProvider dataApiUserFull
	 * @return void
	 */
	public function testWithoutUserInfo($data)
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
		$this->mockGetIdForURL($data['url'], 0, true);
		$this->mockConstants();

		$result = api_rss_extra($this->app, [], null);
		$this->assertInternalType('array', $result['$user']);
		$this->assertArrayHasKey('alternate', $result['$rss']);
		$this->assertArrayHasKey('self', $result['$rss']);
		$this->assertArrayHasKey('base', $result['$rss']);
		$this->assertArrayHasKey('updated', $result['$rss']);
		$this->assertArrayHasKey('atom_updated', $result['$rss']);
		$this->assertArrayHasKey('language', $result['$rss']);
		$this->assertArrayHasKey('logo', $result['$rss']);
	}
}
