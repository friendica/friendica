<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserDatasetTrait;

class RssExtraTest extends ApiTest
{
	use ApiUserDatasetTrait;

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
		$this->mockApiUser($data['uid']);
		$this->mockApiGetUser($data);

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
