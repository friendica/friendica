<?php
/**
 * ApiTest class.
 */

namespace Friendica\Test\legacy;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Security\BasicAuth;
use Friendica\Test\FixtureTest;
use Friendica\Util\Arrays;
use Friendica\Util\DateTimeFormat;
use Monolog\Handler\TestHandler;

require_once __DIR__ . '/../../include/api.php';

/**
 * Tests for the API functions.
 *
 * Functions that use header() need to be tested in a separate process.
 * @see https://phpunit.de/manual/5.7/en/appendixes.annotations.html#appendixes.annotations.runTestsInSeparateProcesses
 *
 * @backupGlobals enabled
 */
class ApiTest extends FixtureTest
{
	/**
	 * @var TestHandler Can handle log-outputs
	 */
	protected $logOutput;

	/** @var array */
	protected $selfUser;
	/** @var array */
	protected $friendUser;
	/** @var array */
	protected $otherUser;

	protected $wrongUserId;

	/** @var App */
	protected $app;

	/** @var IManageConfigValues */
	protected $config;

	/**
	 * Create variables used by tests.
	 */
	protected function setUp() : void
	{
		global $API, $called_api;
		$API = [];
		$called_api = [];

		parent::setUp();

		/** @var IManageConfigValues $config */
		$this->config = $this->dice->create(IManageConfigValues::class);

		$this->config->set('system', 'url', 'http://localhost');
		$this->config->set('system', 'hostname', 'localhost');
		$this->config->set('system', 'worker_dont_fork', true);

		// Default config
		$this->config->set('config', 'hostname', 'localhost');
		$this->config->set('system', 'throttle_limit_day', 100);
		$this->config->set('system', 'throttle_limit_week', 100);
		$this->config->set('system', 'throttle_limit_month', 100);
		$this->config->set('system', 'theme', 'system_theme');


		/** @var App app */
		$this->app = DI::app();

		DI::args()->setArgc(1);

		// User data that the test database is populated with
		$this->selfUser   = [
			'id'   => 42,
			'name' => 'Self contact',
			'nick' => 'selfcontact',
			'nurl' => 'http://localhost/profile/selfcontact'
		];
		$this->friendUser = [
			'id'   => 44,
			'name' => 'Friend contact',
			'nick' => 'friendcontact',
			'nurl' => 'http://localhost/profile/friendcontact'
		];
		$this->otherUser  = [
			'id'   => 43,
			'name' => 'othercontact',
			'nick' => 'othercontact',
			'nurl' => 'http://localhost/profile/othercontact'
		];

		// User ID that we know is not in the database
		$this->wrongUserId = 666;

		DI::session()->start();

		// Most API require login so we force the session
		$_SESSION = [
			'authenticated' => true,
			'uid'           => $this->selfUser['id']
		];
		BasicAuth::setCurrentUserID($this->selfUser['id']);
	}

	/**
	 * Assert that a list array contains expected keys.
	 *
	 * @param array $list List array
	 *
	 * @return void
	 */
	private function assertList(array $list = [])
	{
		self::assertIsString($list['name']);
		self::assertIsInt($list['id']);
		self::assertIsString('string', $list['id_str']);
		self::assertContains($list['mode'], ['public', 'private']);
		// We could probably do more checks here.
	}

	/**
	 * Assert that the string is XML and contain the root element.
	 *
	 * @param string $result       XML string
	 * @param string $root_element Root element name
	 *
	 * @return void
	 */
	private function assertXml($result = '', $root_element = '')
	{
		self::assertStringStartsWith('<?xml version="1.0"?>', $result);
		self::assertStringContainsString('<' . $root_element, $result);
		// We could probably do more checks here.
	}

	/**
	 * Test the api_user() function.
	 *
	 * @return void
	 */
	public function testApiUser()
	{
		self::assertEquals($this->selfUser['id'], BaseApi::getCurrentUserID());
	}



	/**
	 * Test the api_source() function.
	 *
	 * @return void
	 */
	public function testApiSource()
	{
		self::assertEquals('api', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_source() function with a Twidere user agent.
	 *
	 * @return void
	 */
	public function testApiSourceWithTwidere()
	{
		$_SERVER['HTTP_USER_AGENT'] = 'Twidere';
		self::assertEquals('Twidere', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_source() function with a GET parameter.
	 *
	 * @return void
	 */
	public function testApiSourceWithGet()
	{
		$_REQUEST['source'] = 'source_name';
		self::assertEquals('source_name', BasicAuth::getCurrentApplicationToken()['name']);
	}

	/**
	 * Test the api_date() function.
	 *
	 * @return void
	 */
	public function testApiDate()
	{
		self::assertEquals('Wed Oct 10 00:00:00 +0000 1990', DateTimeFormat::utc('1990-10-10', DateTimeFormat::API));
	}

	/**
	 * Test the api_register_func() function.
	 *
	 * @return void
	 */
	public function testApiRegisterFunc()
	{
		global $API;
		self::assertNull(
			api_register_func(
				'api_path',
				function () {
				},
				true,
				'method'
			)
		);
		self::assertTrue(is_callable($API['api_path']['func']));
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function without any login.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @preserveGlobalState disabled
	 */
	public function testApiLoginWithoutLogin()
	{
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a bad login.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @preserveGlobalState disabled
	 */
	public function testApiLoginWithBadLogin()
	{
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		$_SERVER['PHP_AUTH_USER'] = 'user@server';
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with oAuth.
	 *
	 * @return void
	 */
	public function testApiLoginWithOauth()
	{
		$this->markTestIncomplete('Can we test this easily?');
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with authentication provided by an addon.
	 *
	 * @return void
	 */
	public function testApiLoginWithAddonAuth()
	{
		$this->markTestIncomplete('Can we test this easily?');
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a correct login.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @doesNotPerformAssertions
	 */
	public function testApiLoginWithCorrectLogin()
	{
		BasicAuth::setCurrentUserID();
		$_SERVER['PHP_AUTH_USER'] = 'Test user';
		$_SERVER['PHP_AUTH_PW']   = 'password';
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the BasicAuth::getCurrentUserID() function with a remote user.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiLoginWithRemoteUser()
	{
		BasicAuth::setCurrentUserID();
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		$_SERVER['REDIRECT_REMOTE_USER'] = '123456dXNlcjpwYXNzd29yZA==';
		BasicAuth::getCurrentUserID(true);
	}

	/**
	 * Test the api_call() function.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiCall()
	{
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'func'   => function () {
				return ['data' => ['some_data']];
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path';
		$_GET['callback']          = 'callback_name';

		self::assertEquals(
			'callback_name(["some_data"])',
			api_call('api_path', 'json')
		);
	}

	/**
	 * Test the api_call() function with the profiled enabled.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiCallWithProfiler()
	{
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'func'   => function () {
				return ['data' => ['some_data']];
			}
		];

		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path';

		$this->config->set('system', 'profiler', true);
		$this->config->set('rendertime', 'callstack', true);
		$this->app->callstack = [
			'database'       => ['some_function' => 200],
			'database_write' => ['some_function' => 200],
			'cache'          => ['some_function' => 200],
			'cache_write'    => ['some_function' => 200],
			'network'        => ['some_function' => 200]
		];

		self::assertEquals(
			'["some_data"]',
			api_call('api_path', 'json')
		);
	}

	/**
	 * Test the api_call() function with a JSON result.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiCallWithJson()
	{
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'func'   => function () {
				return ['data' => ['some_data']];
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path.json';

		self::assertEquals(
			'["some_data"]',
			api_call('api_path.json', 'json')
		);
	}

	/**
	 * Test the api_call() function with an XML result.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiCallWithXml()
	{
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'func'   => function () {
				return 'some_data';
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path.xml';

		$args = DI::args()->determine($_SERVER, $_GET);

		self::assertEquals(
			'some_data',
			api_call('api_path.xml', 'xml')
		);
	}

	/**
	 * Test the api_call() function with an RSS result.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiCallWithRss()
	{
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'func'   => function () {
				return 'some_data';
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path.rss';

		self::assertEquals(
			'<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
			'some_data',
			api_call('api_path.rss', 'rss')
		);
	}

	/**
	 * Test the api_call() function with an Atom result.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiCallWithAtom()
	{
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'func'   => function () {
				return 'some_data';
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path.atom';

		self::assertEquals(
			'<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
			'some_data',
			api_call('api_path.atom', 'atom')
		);
	}

	/**
	 * Test the Arrays::walkRecursive() function.
	 *
	 * @return void
	 */
	public function testApiWalkRecursive()
	{
		$array = ['item1'];
		self::assertEquals(
			$array,
			Arrays::walkRecursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}

	/**
	 * Test the Arrays::walkRecursive() function with an array.
	 *
	 * @return void
	 */
	public function testApiWalkRecursiveWithArray()
	{
		$array = [['item1'], ['item2']];
		self::assertEquals(
			$array,
			Arrays::walkRecursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}

	/**
	 * Test the api_lists_list() function.
	 *
	 * @return void
	 */
	public function testApiListsList()
	{
		$result = api_lists_list('json');
		self::assertEquals(['lists_list' => []], $result);
	}

	/**
	 * Test the api_lists_ownerships() function.
	 *
	 * @return void
	 */
	public function testApiListsOwnerships()
	{
		$result = api_lists_ownerships('json');
		foreach ($result['lists']['lists'] as $list) {
			self::assertList($list);
		}
	}

	/**
	 * Test the api_lists_ownerships() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiListsOwnershipsWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_lists_ownerships('json');
	}

	/**
	 * Test the api_fr_photos_list() function.
	 *
	 * @return void
	 */
	public function testApiFrPhotosList()
	{
		$result = api_fr_photos_list('json');
		self::assertArrayHasKey('photo', $result);
	}

	/**
	 * Test the api_fr_photos_list() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiFrPhotosListWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_fr_photos_list('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function.
	 */
	public function testApiFrPhotoCreateUpdate()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiFrPhotoCreateUpdateWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function with an album name.
	 *
	 * @return void
	 */
	public function testApiFrPhotoCreateUpdateWithAlbum()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		$_REQUEST['album'] = 'album_name';
		api_fr_photo_create_update('json');
	}

	/**
	 * Test the api_fr_photo_create_update() function with the update mode.
	 *
	 * @return void
	 */
	public function testApiFrPhotoCreateUpdateWithUpdate()
	{
		$this->markTestIncomplete('We need to create a dataset for this');
	}

	/**
	 * Test the api_fr_photo_create_update() function with an uploaded file.
	 *
	 * @return void
	 */
	public function testApiFrPhotoCreateUpdateWithFile()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_fr_photo_detail() function.
	 *
	 * @return void
	 */
	public function testApiFrPhotoDetail()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiFrPhotoDetailWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function with a photo ID.
	 *
	 * @return void
	 */
	public function testApiFrPhotoDetailWithPhotoId()
	{
		$this->expectException(\Friendica\Network\HTTPException\NotFoundException::class);
		$_REQUEST['photo_id'] = 1;
		api_fr_photo_detail('json');
	}

	/**
	 * Test the api_fr_photo_detail() function with a correct photo ID.
	 *
	 * @return void
	 */
	public function testApiFrPhotoDetailCorrectPhotoId()
	{
		$this->markTestIncomplete('We need to create a dataset for this.');
	}

	/**
	 * Test the api_account_update_profile_image() function.
	 *
	 * @return void
	 */
	public function testApiAccountUpdateProfileImage()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		api_account_update_profile_image('json');
	}

	/**
	 * Test the api_account_update_profile_image() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiAccountUpdateProfileImageWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_account_update_profile_image('json');
	}

	/**
	 * Test the api_account_update_profile_image() function with an uploaded file.
	 *
	 * @return void
	 */
	public function testApiAccountUpdateProfileImageWithUpload()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		$this->markTestIncomplete();
	}

	/**
	 * Test the check_acl_input() function.
	 *
	 * @return void
	 */
	public function testCheckAclInput()
	{
		$result = check_acl_input('<aclstring>', BaseApi::getCurrentUserID());
		// Where does this result come from?
		self::assertEquals(1, $result);
	}

	/**
	 * Test the check_acl_input() function with an empty ACL string.
	 *
	 * @return void
	 */
	public function testCheckAclInputWithEmptyAclString()
	{
		$result = check_acl_input(' ', BaseApi::getCurrentUserID());
		self::assertFalse($result);
	}

	/**
	 * Test the save_media_to_database() function.
	 *
	 * @return void
	 */
	public function testSaveMediaToDatabase()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the post_photo_item() function.
	 *
	 * @return void
	 */
	public function testPostPhotoItem()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the prepare_photo_data() function.
	 *
	 * @return void
	 */
	public function testPreparePhotoData()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_group_show() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaGroupShow()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_lists_destroy() function.
	 *
	 * @return void
	 */
	public function testApiListsDestroy()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the group_create() function.
	 *
	 * @return void
	 */
	public function testGroupCreate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_group_create() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaGroupCreate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_lists_create() function.
	 *
	 * @return void
	 */
	public function testApiListsCreate()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_lists_update() function.
	 *
	 * @return void
	 */
	public function testApiListsUpdate()
	{
		$this->markTestIncomplete();
	}
}
