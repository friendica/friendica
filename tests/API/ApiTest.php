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
use Friendica\Util\Strings;

require_once __DIR__ . '/../../include/api.php';

/**
 * Tests for the API functions.
 *
 * Functions that use header() need to be tested in a separate process.
 * @see https://phpunit.de/manual/5.7/en/appendixes.annotations.html#appendixes.annotations.runTestsInSeparateProcesses
 */
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
	protected function mockApiGetUser($user, $contact_id = null, $screen_name = null, $self = true, $allowed = true, $times = 1)
	{
		/// In case $contact_id is set
		if (isset($contact_id)) {

			/// in case $contact_id is a url
			if (intval($contact_id) == 0) {
				$nurl = Strings::normaliseLink($user['url']);
				$this->mockEscape($nurl, $times);

				/// in case $contact_id is 0
			} else {
				// api_unique_id_to_nurl()
				$nurl = $user['url'];
				$this->mockSelectFirst('contact', ['nurl'], ['id' => $contact_id], ['nurl' => $nurl], $times);
				$this->mockIsResult(['nurl' => $user['url']], true, $times);
				$this->mockEscape($user['url'], $times);
			}

			/// in case it is called by a known user
			if ($self && $allowed) {
				$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nurl` = '" . $nurl . "' AND `contact`.`uid`=" . $user['uid'];

				/// in case it is called by a unknown user
			} else {
				$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nurl` = '" . $nurl . "' ";
			}

			/// in case $screen_name is set
		} elseif (isset($screen_name)) {
			$this->mockEscape($screen_name, $times);

			/// In case it is called by a known user
			if ($self && $allowed) {
				$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nick` = '" . $screen_name . "' AND `contact`.`uid`=" . $user['uid'];

				/// in case it is called by a unknown user
			} else {
				$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`nick` = '" . $screen_name . "' ";
			}

			/// in case it is called without any argument
		} else {
			$stmt = "SELECT *, `contact`.`id` AS `cid` FROM `contact` WHERE 1 AND `contact`.`uid` = " . $user['uid'] . " AND `contact`.`self` ";
		}

		$this->mockP($stmt, [$user], $times);
		$this->mockIsResult([$user], true, $times);

		/// in case the user is allowed for the call, return all values needed
		if ($allowed) {
			$this->mockSelectFirst('user', ['default-location'], ['uid' => $user['uid']], ['default-location' => $user['default-location']], $times);
			$this->mockSelectFirst('profile', ['about'], ['uid' => $user['uid'], 'is-default' => true], ['about' => $user['about']], $times);

			if ($self) {
				$this->mockSelectFirst('user', ['theme'], ['uid' => $user['uid']], ['theme' => $user['theme']], $times);
				$this->mockPConfigGet($user['uid'], 'frio', 'schema', $user['schema'], $times);
			}

			$this->mockGetIdForURL($user['url'], 0, true, null, null, $user['id'], $times * 2);
			$this->mockConstants();
		} else {
			$this->mockSelectFirst('user', ['default-location'], ['uid' => false], null, $times);
			$this->mockSelectFirst('profile', ['about'], ['uid' => false, 'is-default' => true], null, $times);

			if ($self) {
				$this->mockSelectFirst('user', ['theme'], ['uid' => $user['uid']], ['theme' => $user['theme']], $times);
				$this->mockPConfigGet($user['uid'], 'frio', 'schema', $user['schema'], $times);
			}
			$this->mockGetIdForURL($user['url'], 0, true, null, null, $user['id'], $times * 2);
			$this->mockConstants();
		}
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
	 * @see api_status_show()
	 *
	 * @param $item
	 * @param int $times
	 */
	protected function mockApiStatusShow($item, $user, $times = 1)
	{
		$this->mockItemConstants();
		$this->mockItemSelectFirst(Item::ITEM_FIELDLIST, \Mockery::any() , ['order' => ['id' => true]], $item, $times);
		$this->mockIsResult($item, true, $times);

		$this->mockItemSelectFirst(['uri'], ['id' => $item['id']], [], ['uri' => $item['uri']], $times);
		$this->mockIsResult(['uri' => $item['uri']], true, $times);
		$this->mockItemSelectFirst(['id'], ['uri' => $item['uri'], 'uid' => [0, api_user()]], ['order' => ['uid' => true]], ['id' => $item['id']], $times);
		$this->mockIsResult(['id' => $item['id']], true, $times);
		$this->mockSelectForUser($user['uid'], [], ['id' => $item['id'], 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]], [], [$item], $times);
		$this->mockIsResult([$item], true, $times);
		$this->mockItemInArray([$item], $times);

		$this->mockApiGetUser($user, $item['author-id'], $times);

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
	 * @param bool  $full True, if uid and self should get checked too
	 * @return void
	 */
	protected function assertUser(array $user, array $data, $full = true)
	{
		if ($full) {
			$this->assertEquals($data['uid'], $user['uid']);
			$this->assertEquals($data['uid'], $user['cid']);
			$this->assertEquals($data['self'], $user['self']);
		}
		$this->assertEquals($data['location'], $user['location']);
		$this->assertEquals($data['name'], $user['name']);
		$this->assertEquals($data['nick'], $user['screen_name']);
		$this->assertEquals($data['network'], $user['network']);
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
