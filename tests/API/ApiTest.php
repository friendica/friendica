<?php

namespace Friendica\Test\API;

use Friendica\Model\Item;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\BBCodeMockTrait;
use Friendica\Test\Util\Mocks\ContactMockTrait;
use Friendica\Test\Util\Mocks\DBAMockTrait;
use Friendica\Test\Util\Mocks\ItemMockTrait;
use Friendica\Test\Util\Mocks\PConfigMockTrait;
use Friendica\Test\Util\Mocks\UserMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;

require_once __DIR__ . '/../../include/api.php';

abstract class ApiTest extends MockedTest
{
	use AppMockTrait;
	use ContactMockTrait;
	use DBAMockTrait;
	use PConfigMockTrait;
	use UserMockTrait;
	use VFSTrait;
	use ItemMockTrait;
	use BBCodeMockTrait;

	/**
	 * Create variables used by tests.
	 */
	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);

		$this->mockConfigGet('system', 'url', 'http://localhost');
		$this->mockConfigGet('system', 'hostname', 'localhost');
		$this->mockConfigGet('system', 'worker_dont_fork', true);

		// Default config
		$this->mockConfigGet('config', 'hostname', 'localhost');
		$this->mockConfigGet('system', 'throttle_limit_day', 100);
		$this->mockConfigGet('system', 'throttle_limit_week', 100);
		$this->mockConfigGet('system', 'throttle_limit_month', 100);
		$this->mockConfigGet('system', 'theme', 'system_theme');

		/// @todo not needed anymore with new Logging 2019.03
		$this->mockConfigGet('system', 'debugging', false);
		$this->mockConfigGet('system', 'logfile', 'friendica.log');
		$this->mockConfigGet('system', 'loglevel', '0');

		// setup DB mock
		$this->mockConnect();
		$this->mockConnected();
	}

	/**
	 * Cleanup variables used by tests.
	 */
	protected function tearDown()
	{
		parent::tearDown();

		$this->app->argc = 1;
		$this->app->argv = ['home'];
	}

	/**
	 * Mocking the login because we already test api_user() in other UnitTests
	 * @see LoginTest
	 *
	 * @param $uid
	 */
	protected function mockApiUser($uid = 1)
	{
		$_SESSION = [
			'allow_api' => true,
			'authenticated' => true,
			'uid' => $uid,
		];
	}

	/**
	 * Mocking the "default" way to get user data per api_get_user()
	 * We test this function already in other tests
	 * @see GetUserTest
	 *
	 * @param array $user
	 * @param int $times
	 */
	protected function mockApiGetUser($user, $times = 1)
	{
		$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $user['uid'] . " AND `contact`.`self` ";

		$this->mockP($stmt, [$user], $times);
		$this->mockIsResult([$user], true, $times);

		$this->mockSelectFirst('user', ['default-location'], ['uid' => $user['uid']], ['default-location' => $user['default-location']], $times);
		$this->mockSelectFirst('profile', ['about'], ['uid' => $user['uid'], 'is-default' => true], ['about' => $user['about']], $times);
		$this->mockSelectFirst('user', ['theme'], ['uid' => $user['uid']], ['theme' => $user['theme']], $times);
		$this->mockPConfigGet($user['uid'], 'frio', 'schema', $user['schema'], $times);
		$this->mockGetIdForURL($user['url'], 0, true, null, null, null, $times * 2);
		$this->mockConstants();
	}

	protected function mockDBAQ($sql, $return, $args = [], $times = 1)
	{
		$this->mockCleanQuery($sql, $times);
		$this->mockAnyValueFallback($sql, $times);
		$stmt = @vsprintf($sql, $args);
		$this->mockP($stmt, $return, $times);
		$this->mockColumnCount($return, 1, $times);
		$this->mockToArray($return, $return, 1);
	}

	/**
	 * Mocking the "default" way to show item data per api_status_show()
	 *
	 * @param $item
	 * @param int $times
	 */
	protected function mockApiStatusShow($item, $times = 1)
	{
		$this->mockItemConstants();
		$this->mockItemSelectFirst(Item::ITEM_FIELDLIST, [], [], $item, $times);
		$this->mockIsResult($item, true, $times);

		/// for mocking api_convert_item()
		$this->mockCleanPictureLinks($item['body'], $item['body'], $times);
		$this->mockGetAttachmentData($item['body'], [], $times * 2);
		$this->mockConvert("", "", false, false, false, $times * 3);
	}

	/**
	 * Mocking the "default" way to show item data per api_users_show()
	 *
	 * @param $item
	 * @param int $times
	 */
	protected function mockApiUsersShow($item, $times = 1)
	{
		$this->mockItemConstants();
		$this->mockItemSelectFirst(Item::ITEM_FIELDLIST, [], [], $item, $times);
		$this->mockIsResult($item, true, $times);

		/// for mocking api_convert_item()
		$this->mockCleanPictureLinks($item['body'], $item['body'], $times);
		$this->mockGetAttachmentData($item['body'], [], $times * 2);
		$this->mockConvert("", "", false, false, false, $times * 3);
	}

	/**
	 * Assert that an user array contains expected keys.
	 * @param array $user User array
	 * @param array $data DataSource array
	 * @return void
	 */
	protected function assertUser(array $user, array $data)
	{
		$this->assertEquals($data['uid'], $user['uid']);
		$this->assertEquals($data['uid'], $user['cid']);
		$this->assertEquals($data['self'], $user['self']);
		$this->assertEquals($data['location'], $user['location']);
		$this->assertEquals($data['name'], $user['name']);
		$this->assertEquals($data['nick'], $user['screen_name']);
		$this->assertEquals($data['network'], $user['network']);
		$this->assertTrue($user['verified']);
	}

	/**
	 * Assert that a status array contains expected keys.
	 * @param array $status Status array
	 * @return void
	 */
	protected function assertStatus(array $status)
	{
		$this->assertInternalType('string', $status['text']);
		$this->assertInternalType('int', $status['id']);
		// We could probably do more checks here.
	}

	/**
	 * Assert that a list array contains expected keys.
	 * @param array $list List array
	 * @return void
	 */
	protected function assertList(array $list)
	{
		$this->assertInternalType('string', $list['name']);
		$this->assertInternalType('int', $list['id']);
		$this->assertInternalType('string', $list['id_str']);
		$this->assertContains($list['mode'], ['public', 'private']);
		// We could probably do more checks here.
	}

	/**
	 * Assert that the string is XML and contain the root element.
	 * @param string $result       XML string
	 * @param string $root_element Root element name
	 * @return void
	 */
	protected function assertXml($result, $root_element)
	{
		$this->assertStringStartsWith('<?xml version="1.0"?>', $result);
		$this->assertContains('<'.$root_element, $result);
		// We could probably do more checks here.
	}

	/**
	 * Get the path to a temporary empty PNG image.
	 * @return string Path
	 */
	protected function getTempImage()
	{
		$tmpFile = tempnam(sys_get_temp_dir(), 'tmp_file');
		file_put_contents(
			$tmpFile,
			base64_decode(
				// Empty 1x1 px PNG image
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=='
			)
		);

		return $tmpFile;
	}
}
