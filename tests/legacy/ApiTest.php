<?php
/**
 * ApiTest class.
 */

namespace Friendica\Test\legacy;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Module\Api\ApiResponse;
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

		$this->config->set('system', 'allowed_link_protocols', []);

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
	 * Assert that an user array contains expected keys.
	 *
	 * @param array $user User array
	 *
	 * @return void
	 */
	private function assertSelfUser(array $user)
	{
		self::assertEquals($this->selfUser['id'], $user['uid']);
		self::assertEquals($this->selfUser['id'], $user['cid']);
		self::assertEquals(1, $user['self']);
		self::assertEquals('DFRN', $user['location']);
		self::assertEquals($this->selfUser['name'], $user['name']);
		self::assertEquals($this->selfUser['nick'], $user['screen_name']);
		self::assertEquals('dfrn', $user['network']);
		self::assertTrue($user['verified']);
	}

	/**
	 * Assert that an user array contains expected keys.
	 *
	 * @param array $user User array
	 *
	 * @return void
	 */
	private function assertOtherUser(array $user = [])
	{
		self::assertEquals($this->otherUser['id'], $user['id']);
		self::assertEquals($this->otherUser['id'], $user['id_str']);
		self::assertEquals($this->otherUser['name'], $user['name']);
		self::assertEquals($this->otherUser['nick'], $user['screen_name']);
		self::assertFalse($user['verified']);
	}

	/**
	 * Assert that a status array contains expected keys.
	 *
	 * @param array $status Status array
	 *
	 * @return void
	 */
	private function assertStatus(array $status = [])
	{
		self::assertIsString($status['text'] ?? '');
		self::assertIsInt($status['id'] ?? '');
		// We could probably do more checks here.
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
	 * Get the path to a temporary empty PNG image.
	 *
	 * @return string Path
	 */
	private function getTempImage()
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
	 * Test the api_user() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiUserWithUnallowedUser()
	{
		// self::assertEquals(false, api_user());
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
	 * Test the api_rss_extra() function.
	 *
	 * @return void
	 */
	public function testApiRssExtra()
	{
		/*
		$user_info = ['url' => 'user_url', 'lang' => 'en'];
		$result    = api_rss_extra([], $user_info);
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

	/**
	 * Test the api_get_user() function.
	 *
	 * @return void
	 */
	public function testApiGetUser()
	{
		// $user = api_get_user();
		// self::assertSelfUser($user);
		// self::assertEquals('708fa0', $user['profile_sidebar_fill_color']);
		// self::assertEquals('6fdbe8', $user['profile_link_color']);
		// self::assertEquals('ededed', $user['profile_background_color']);
	}

	/**
	 * Test the api_get_user() function with a Frio schema.
	 *
	 * @return void
	 */
	public function testApiGetUserWithFrioSchema()
	{
		// $pConfig = $this->dice->create(IManagePersonalConfigValues::class);
		// $pConfig->set($this->selfUser['id'], 'frio', 'schema', 'red');
		// $user = api_get_user();
		// self::assertSelfUser($user);
		// self::assertEquals('708fa0', $user['profile_sidebar_fill_color']);
		// self::assertEquals('6fdbe8', $user['profile_link_color']);
		// self::assertEquals('ededed', $user['profile_background_color']);
	}

	/**
	 * Test the api_get_user() function with an empty Frio schema.
	 *
	 * @return void
	 */
	public function testApiGetUserWithEmptyFrioSchema()
	{
		// $pConfig = $this->dice->create(IManagePersonalConfigValues::class);
		// $pConfig->set($this->selfUser['id'], 'frio', 'schema', '---');
		// $user = api_get_user();
		// self::assertSelfUser($user);
		// self::assertEquals('708fa0', $user['profile_sidebar_fill_color']);
		// self::assertEquals('6fdbe8', $user['profile_link_color']);
		// self::assertEquals('ededed', $user['profile_background_color']);
	}

	/**
	 * Test the api_get_user() function with a custom Frio schema.
	 *
	 * @return void
	 */
	public function testApiGetUserWithCustomFrioSchema()
	{
		// $pConfig = $this->dice->create(IManagePersonalConfigValues::class);
		// $pConfig->set($this->selfUser['id'], 'frio', 'schema', '---');
		// $pConfig->set($this->selfUser['id'], 'frio', 'nav_bg', '#123456');
		// $pConfig->set($this->selfUser['id'], 'frio', 'link_color', '#123456');
		// $pConfig->set($this->selfUser['id'], 'frio', 'background_color', '#123456');
		// $user = api_get_user();
		// self::assertSelfUser($user);
		// self::assertEquals('123456', $user['profile_sidebar_fill_color']);
		// self::assertEquals('123456', $user['profile_link_color']);
		// self::assertEquals('123456', $user['profile_background_color']);
	}

	/**
	 * Test the api_get_user() function with an user that is not allowed to use the API.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiGetUserWithoutApiUser()
	{
		// api_get_user() with empty parameters is not used anymore
		/*
		$_SERVER['PHP_AUTH_USER'] = 'Test user';
		$_SERVER['PHP_AUTH_PW']   = 'password';
		BasicAuth::setCurrentUserID();
		self::assertFalse(api_get_user());
		*/
	}

	/**
	 * Test the api_get_user() function with an user ID in a GET parameter.
	 *
	 * @return void
	 */
	public function testApiGetUserWithGetId()
	{
		// self::assertOtherUser(api_get_user());
	}

	/**
	 * Test the api_get_user() function with a wrong user ID in a GET parameter.
	 *
	 * @return void
	 */
	public function testApiGetUserWithWrongGetId()
	{
		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// self::assertOtherUser(api_get_user());
	}

	/**
	 * Test the api_get_user() function with an user name in a GET parameter.
	 *
	 * @return void
	 */
	public function testApiGetUserWithGetName()
	{
		// self::assertSelfUser(api_get_user());
	}

	/**
	 * Test the api_get_user() function with a profile URL in a GET parameter.
	 *
	 * @return void
	 */
	public function testApiGetUserWithGetUrl()
	{
		// self::assertSelfUser(api_get_user());
	}

	/**
	 * Test the api_get_user() function with an user ID in the API path.
	 *
	 * @return void
	 */
	public function testApiGetUserWithNumericCalledApi()
	{
		// global $called_api;
		// $called_api         = ['api_path'];
		// DI::args()->setArgv(['', $this->otherUser['id'] . '.json']);
		// self::assertOtherUser(api_get_user());
	}

	/**
	 * Test the api_get_user() function with the $called_api global variable.
	 *
	 * @return void
	 */
	public function testApiGetUserWithCalledApi()
	{
		// global $called_api;
		// $called_api = ['api', 'api_path'];
		// self::assertSelfUser(api_get_user());
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
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <data>some_data</data>' . "\n" .
			'</root_element>' . "\n",
			DI::apiResponse()->createXML(['data' => ['some_data']], 'root_element')
		);
	}

	/**
	 * Test the BaseApi::createXML() function without any XML namespace.
	 *
	 * @return void
	 */
	public function testApiCreateXmlWithoutNamespaces()
	{
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<ok>' . "\n" .
			'  <data>some_data</data>' . "\n" .
			'</ok>' . "\n",
			DI::apiResponse()->createXML(['data' => ['some_data']], 'ok')
		);
	}

	/**
	 * Test the BaseApi::formatData() function.
	 *
	 * @return void
	 */
	public function testApiFormatData()
	{
		$data = ['some_data'];
		self::assertEquals($data, DI::apiResponse()->formatData('root_element', 'json', $data));
	}

	/**
	 * Test the BaseApi::formatData() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiFormatDataWithXml()
	{
		self::assertEquals(
			'<?xml version="1.0"?>' . "\n" .
			'<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" ' .
			'xmlns:friendica="http://friendi.ca/schema/api/1/" ' .
			'xmlns:georss="http://www.georss.org/georss">' . "\n" .
			'  <data>some_data</data>' . "\n" .
			'</root_element>' . "\n",
			DI::apiResponse()->formatData('root_element', 'xml', ['data' => ['some_data']])
		);
	}

	/**
	 * Test the api_statuses_mediap() function.
	 *
	 * @return void
	 */
	public function testApiStatusesMediap()
	{
		DI::args()->setArgc(2);

		$_FILES         = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png'
			]
		];
		$_GET['status'] = '<b>Status content</b>';

		$result = api_statuses_mediap('json');
		self::assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_mediap() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesMediapWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_statuses_mediap('json');
	}

	/**
	 * Test the api_statuses_update() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdate()
	{
		$_REQUEST['status']                = 'Status content #friendica';
		$_REQUEST['in_reply_to_status_id'] = -1;
		$_REQUEST['lat']                   = 48;
		$_REQUEST['long']                  = 7;
		$_FILES                            = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png'
			]
		];

		$result = api_statuses_update('json');
		self::assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_update() function with an HTML status.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithHtml()
	{
		$_REQUEST['htmlstatus'] = '<b>Status content</b>';

		$result = api_statuses_update('json');
		self::assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_update() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_statuses_update('json');
	}

	/**
	 * Test the api_statuses_update() function with a parent status.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithParent()
	{
		$this->markTestIncomplete('This triggers an exit() somewhere and kills PHPUnit.');
	}

	/**
	 * Test the api_statuses_update() function with a media_ids parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithMediaIds()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_statuses_update() function with the throttle limit reached.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithDayThrottleReached()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_media_upload() function.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testApiMediaUpload()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		api_media_upload();
	}

	/**
	 * Test the api_media_upload() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiMediaUploadWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_media_upload();
	}

	/**
	 * Test the api_media_upload() function with an invalid uploaded media.
	 *
	 * @return void
	 */
	public function testApiMediaUploadWithMedia()
	{
		$this->expectException(\Friendica\Network\HTTPException\InternalServerErrorException::class);
		$_FILES = [
			'media' => [
				'id'       => 666,
				'tmp_name' => 'tmp_name'
			]
		];
		api_media_upload();
	}

	/**
	 * Test the api_media_upload() function with an valid uploaded media.
	 *
	 * @return void
	 */
	public function testApiMediaUploadWithValidMedia()
	{
		$_FILES    = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png'
			]
		];
		$app       = DI::app();
		DI::args()->setArgc(2);

		$result = api_media_upload();
		self::assertEquals('image/png', $result['media']['image']['image_type']);
		self::assertEquals(1, $result['media']['image']['w']);
		self::assertEquals(1, $result['media']['image']['h']);
		self::assertNotEmpty($result['media']['image']['friendica_preview_url']);
	}

	/**
	 * Test the api_statuses_repeat() function.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeat()
	{
		$this->expectException(\Friendica\Network\HTTPException\ForbiddenException::class);
		api_statuses_repeat('json');
	}

	/**
	 * Test the api_statuses_repeat() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_statuses_repeat('json');
	}

	/**
	 * Test the api_statuses_repeat() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithId()
	{
		DI::args()->setArgv(['', '', '', 1]);
		$result = api_statuses_repeat('json');
		self::assertStatus($result['status']);

		// Also test with a shared status
		DI::args()->setArgv(['', '', '', 5]);
		$result = api_statuses_repeat('json');
		self::assertStatus($result['status']);
	}

	/**
	 * Test the api_favorites_create_destroy() function.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroy()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		DI::args()->setArgv(['api', '1.1', 'favorites', 'create']);
		api_favorites_create_destroy('json');
	}

	/**
	 * Test the api_favorites_create_destroy() function with an invalid ID.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithInvalidId()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		DI::args()->setArgv(['api', '1.1', 'favorites', 'create', '12.json']);
		api_favorites_create_destroy('json');
	}

	/**
	 * Test the api_favorites_create_destroy() function with an invalid action.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithInvalidAction()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		DI::args()->setArgv(['api', '1.1', 'favorites', 'change.json']);
		$_REQUEST['id'] = 1;
		api_favorites_create_destroy('json');
	}

	/**
	 * Test the api_favorites_create_destroy() function with the create action.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithCreateAction()
	{
		DI::args()->setArgv(['api', '1.1', 'favorites', 'create.json']);
		$_REQUEST['id'] = 3;
		$result         = api_favorites_create_destroy('json');
		self::assertStatus($result['status']);
	}

	/**
	 * Test the api_favorites_create_destroy() function with the create action and an RSS result.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithCreateActionAndRss()
	{
		DI::args()->setArgv(['api', '1.1', 'favorites', 'create.rss']);
		$_REQUEST['id'] = 3;
		$result         = api_favorites_create_destroy('rss');
		self::assertXml($result, 'status');
	}

	/**
	 * Test the api_favorites_create_destroy() function with the destroy action.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithDestroyAction()
	{
		DI::args()->setArgv(['api', '1.1', 'favorites', 'destroy.json']);
		$_REQUEST['id'] = 3;
		$result         = api_favorites_create_destroy('json');
		self::assertStatus($result['status']);
	}

	/**
	 * Test the api_favorites_create_destroy() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiFavoritesCreateDestroyWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		DI::args()->setArgv(['api', '1.1', 'favorites', 'create.json']);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_favorites_create_destroy('json');
	}



	/**
	 * Test the api_format_messages() function.
	 *
	 * @return void
	 */
	public function testApiFormatMessages()
	{
		$result = api_format_messages(
			['id' => 1, 'uri-id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'uri-id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'uri-id' => 2, 'screen_name' => 'sender_name']
		);
		self::assertEquals('item_title' . "\n" . 'item_body', $result['text']);
		self::assertEquals(1, $result['id']);
		self::assertEquals(2, $result['recipient_id']);
		self::assertEquals(3, $result['sender_id']);
		self::assertEquals('recipient_name', $result['recipient_screen_name']);
		self::assertEquals('sender_name', $result['sender_screen_name']);
	}

	/**
	 * Test the api_format_messages() function with HTML.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithHtmlText()
	{
		$_GET['getText'] = 'html';
		$result          = api_format_messages(
			['id' => 1, 'uri-id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'uri-id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'uri-id' => 3, 'screen_name' => 'sender_name']
		);
		self::assertEquals('item_title', $result['title']);
		self::assertEquals('<strong>item_body</strong>', $result['text']);
	}

	/**
	 * Test the api_format_messages() function with plain text.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithPlainText()
	{
		$_GET['getText'] = 'plain';
		$result          = api_format_messages(
			['id' => 1, 'uri-id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'uri-id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'uri-id' => 3, 'screen_name' => 'sender_name']
		);
		self::assertEquals('item_title', $result['title']);
		self::assertEquals('item_body', $result['text']);
	}

	/**
	 * Test the api_format_messages() function with the getUserObjects GET parameter set to false.
	 *
	 * @return void
	 */
	public function testApiFormatMessagesWithoutUserObjects()
	{
		$_GET['getUserObjects'] = 'false';
		$result                 = api_format_messages(
			['id' => 1, 'uri-id' => 1, 'title' => 'item_title', 'body' => '[b]item_body[/b]'],
			['id' => 2, 'uri-id' => 2, 'screen_name' => 'recipient_name'],
			['id' => 3, 'uri-id' => 3, 'screen_name' => 'sender_name']
		);
		self::assertTrue(!isset($result['sender']));
		self::assertTrue(!isset($result['recipient']));
	}

	/**
	 * Test the api_convert_item() function.
	 *
	 * @return void
	 */
	public function testApiConvertItem()
	{
		/*
		$result = api_convert_item(
			[
				'network' => 'feed',
				'title'   => 'item_title',
				'uri-id'  => 1,
				// We need a long string to test that it is correctly cut
				'body'    => 'perspiciatis impedit voluptatem quis molestiae ea qui ' .
							 'reiciendis dolorum aut ducimus sunt consequatur inventore dolor ' .
							 'officiis pariatur doloremque nemo culpa aut quidem qui dolore ' .
							 'laudantium atque commodi alias voluptatem non possimus aperiam ' .
							 'ipsum rerum consequuntur aut amet fugit quia aliquid praesentium ' .
							 'repellendus quibusdam et et inventore mollitia rerum sit autem ' .
							 'pariatur maiores ipsum accusantium perferendis vel sit possimus ' .
							 'veritatis nihil distinctio qui eum repellat officia illum quos ' .
							 'impedit quam iste esse unde qui suscipit aut facilis ut inventore ' .
							 'omnis exercitationem quo magnam consequatur maxime aut illum ' .
							 'soluta quaerat natus unde aspernatur et sed beatae nihil ullam ' .
							 'temporibus corporis ratione blanditiis perspiciatis impedit ' .
							 'voluptatem quis molestiae ea qui reiciendis dolorum aut ducimus ' .
							 'sunt consequatur inventore dolor officiis pariatur doloremque ' .
							 'nemo culpa aut quidem qui dolore laudantium atque commodi alias ' .
							 'voluptatem non possimus aperiam ipsum rerum consequuntur aut ' .
							 'amet fugit quia aliquid praesentium repellendus quibusdam et et ' .
							 'inventore mollitia rerum sit autem pariatur maiores ipsum accusantium ' .
							 'perferendis vel sit possimus veritatis nihil distinctio qui eum ' .
							 'repellat officia illum quos impedit quam iste esse unde qui ' .
							 'suscipit aut facilis ut inventore omnis exercitationem quo magnam ' .
							 'consequatur maxime aut illum soluta quaerat natus unde aspernatur ' .
							 'et sed beatae nihil ullam temporibus corporis ratione blanditiis',
				'plink'   => 'item_plink'
			]
		);
		self::assertStringStartsWith('item_title', $result['text']);
		self::assertStringStartsWith('<h4>item_title</h4><br>perspiciatis impedit voluptatem', $result['html']);
		*/
	}

	/**
	 * Test the api_convert_item() function with an empty item body.
	 *
	 * @return void
	 */
	public function testApiConvertItemWithoutBody()
	{
		/*
		$result = api_convert_item(
			[
				'network' => 'feed',
				'title'   => 'item_title',
				'uri-id'  => -1,
				'body'    => '',
				'plink'   => 'item_plink'
			]
		);
		self::assertEquals("item_title", $result['text']);
		self::assertEquals('<h4>item_title</h4><br>item_plink', $result['html']);
		*/
	}

	/**
	 * Test the api_convert_item() function with the title in the body.
	 *
	 * @return void
	 */
	public function testApiConvertItemWithTitleInBody()
	{
		/*
		$result = api_convert_item(
			[
				'title'  => 'item_title',
				'body'   => 'item_title item_body',
				'uri-id' => 1,
			]
		);
		self::assertEquals('item_title item_body', $result['text']);
		self::assertEquals('<h4>item_title</h4><br>item_title item_body', $result['html']);
		*/
	}

	/**
	 * Test the api_get_attachments() function.
	 *
	 * @return void
	 */
	public function testApiGetAttachments()
	{
		// $body = 'body';
		// self::assertEmpty(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_attachments() function with an img tag.
	 *
	 * @return void
	 */
	public function testApiGetAttachmentsWithImage()
	{
		// $body = '[img]http://via.placeholder.com/1x1.png[/img]';
		// self::assertIsArray(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_attachments() function with an img tag and an AndStatus user agent.
	 *
	 * @return void
	 */
	public function testApiGetAttachmentsWithImageAndAndStatus()
	{
		// $_SERVER['HTTP_USER_AGENT'] = 'AndStatus';
		// $body                       = '[img]http://via.placeholder.com/1x1.png[/img]';
		// self::assertIsArray(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_entitities() function.
	 *
	 * @return void
	 */
	public function testApiGetEntitities()
	{
		// $text = 'text';
		// self::assertIsArray(api_get_entitities($text, 'bbcode', 0));
	}

	/**
	 * Test the api_get_entitities() function with the include_entities parameter.
	 *
	 * @return void
	 */
	public function testApiGetEntititiesWithIncludeEntities()
	{
		/*
		$_REQUEST['include_entities'] = 'true';
		$text                         = 'text';
		$result                       = api_get_entitities($text, 'bbcode', 0);
		self::assertIsArray($result['hashtags']);
		self::assertIsArray($result['symbols']);
		self::assertIsArray($result['urls']);
		self::assertIsArray($result['user_mentions']);
		*/
	}

	/**
	 * Test the api_format_items_embeded_images() function.
	 *
	 * @return void
	 */
	public function testApiFormatItemsEmbededImages()
	{
		/*
		self::assertEquals(
			'text ' . DI::baseUrl() . '/display/item_guid',
			api_format_items_embeded_images(['guid' => 'item_guid'], 'text data:image/foo')
		);
		*/
	}

	/**
	 * Test the api_format_items_activities() function.
	 *
	 * @return void
	 */
	public function testApiFormatItemsActivities()
	{
		$item   = ['uid' => 0, 'uri-id' => 1];
		$result = DI::friendicaActivities()->createFromUriId($item['uri-id'], $item['uid']);
		self::assertArrayHasKey('like', $result);
		self::assertArrayHasKey('dislike', $result);
		self::assertArrayHasKey('attendyes', $result);
		self::assertArrayHasKey('attendno', $result);
		self::assertArrayHasKey('attendmaybe', $result);
	}

	/**
	 * Test the api_format_items_activities() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiFormatItemsActivitiesWithXml()
	{
		$item   = ['uid' => 0, 'uri-id' => 1];
		$result = DI::friendicaActivities()->createFromUriId($item['uri-id'], $item['uid'], 'xml');
		self::assertArrayHasKey('friendica:like', $result);
		self::assertArrayHasKey('friendica:dislike', $result);
		self::assertArrayHasKey('friendica:attendyes', $result);
		self::assertArrayHasKey('friendica:attendno', $result);
		self::assertArrayHasKey('friendica:attendmaybe', $result);
	}

	/**
	 * Test the api_format_items() function.
	 * @doesNotPerformAssertions
	 */
	public function testApiFormatItems()
	{
		/*
		$items = Post::selectToArray([], ['uid' => 42]);
		foreach ($items as $item) {
			$status = api_format_item($item);
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_format_items() function with an XML result.
	 * @doesNotPerformAssertions
	 */
	public function testApiFormatItemsWithXml()
	{
		/*
		$items = Post::selectToArray([], ['uid' => 42]);
		foreach ($items as $item) {
			$status = api_format_item($item, 'xml');
			self::assertStatus($status);
		}
		*/
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
	 * Test the api_statuses_f() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFWithIncoming()
	{
		// $result = api_statuses_f('incoming');
		// self::assertArrayHasKey('user', $result);
	}


	/**
	 * Test the api_direct_messages_new() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNew()
	{
		$result = api_direct_messages_new('json');
		self::assertNull($result);
	}

	/**
	 * Test the api_direct_messages_new() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_direct_messages_new('json');
	}

	/**
	 * Test the api_direct_messages_new() function with an user ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithUserId()
	{
		$_POST['text']       = 'message_text';
		$_REQUEST['user_id'] = $this->otherUser['id'];
		$result           = api_direct_messages_new('json');
		self::assertEquals(['direct_message' => ['error' => -1]], $result);
	}

	/**
	 * Test the api_direct_messages_new() function with a screen name.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithScreenName()
	{
		$this->app->setLoggedInUserNickname($this->selfUser['nick']);
		$_POST['text']       = 'message_text';
		$_REQUEST['user_id'] = $this->friendUser['id'];
		$result              = api_direct_messages_new('json');
		self::assertStringContainsString('message_text', $result['direct_message']['text']);
		self::assertEquals('selfcontact', $result['direct_message']['sender_screen_name']);
		self::assertEquals(1, $result['direct_message']['friendica_seen']);
	}

	/**
	 * Test the api_direct_messages_new() function with a title.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithTitle()
	{
		$this->app->setLoggedInUserNickname($this->selfUser['nick']);
		$_POST['text']        = 'message_text';
		$_REQUEST['user_id']  = $this->friendUser['id'];
		$_REQUEST['title']    = 'message_title';
		$result            = api_direct_messages_new('json');
		self::assertStringContainsString('message_text', $result['direct_message']['text']);
		self::assertStringContainsString('message_title', $result['direct_message']['text']);
		self::assertEquals('selfcontact', $result['direct_message']['sender_screen_name']);
		self::assertEquals(1, $result['direct_message']['friendica_seen']);
	}

	/**
	 * Test the api_direct_messages_new() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesNewWithRss()
	{
		$this->app->setLoggedInUserNickname($this->selfUser['nick']);
		$_POST['text']       = 'message_text';
		$_REQUEST['user_id'] = $this->friendUser['id'];
		$result              = api_direct_messages_new('rss');
		self::assertXml($result, 'direct-messages');
	}

	/**
	 * Test the api_direct_messages_destroy() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroy()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() function with the friendica_verbose GET param.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithVerbose()
	{
		$_GET['friendica_verbose'] = 'true';
		$result                    = api_direct_messages_destroy('json');
		self::assertEquals(
			[
				'$result' => [
					'result'  => 'error',
					'message' => 'message id or parenturi not specified'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_destroy() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithoutAuthenticatedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithId()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		$_REQUEST['id'] = 1;
		api_direct_messages_destroy('json');
	}

	/**
	 * Test the api_direct_messages_destroy() with a non-zero ID and the friendica_verbose GET param.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithIdAndVerbose()
	{
		$_REQUEST['id']                  = 1;
		$_REQUEST['friendica_parenturi'] = 'parent_uri';
		$_GET['friendica_verbose']       = 'true';
		$result                          = api_direct_messages_destroy('json');
		self::assertEquals(
			[
				'$result' => [
					'result'  => 'error',
					'message' => 'message id not in database'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithCorrectId()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}

	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithSentbox()
	{
		$_REQUEST['page']   = -1;
		$_REQUEST['max_id'] = 10;
		$result             = api_direct_messages_box('json', 'sentbox', 'false');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithConversation()
	{
		$result = api_direct_messages_box('json', 'conversation', 'false');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithAll()
	{
		$result = api_direct_messages_box('json', 'all', 'false');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithInbox()
	{
		$result = api_direct_messages_box('json', 'inbox', 'false');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithVerbose()
	{
		$result = api_direct_messages_box('json', 'sentbox', 'true');
		self::assertEquals(
			[
				'$result' => [
					'result'  => 'error',
					'message' => 'no mails available'
				]
			],
			$result
		);
	}

	/**
	 * Test the api_direct_messages_box() function with a RSS result.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithRss()
	{
		$result = api_direct_messages_box('rss', 'sentbox', 'false');
		self::assertXml($result, 'direct-messages');
	}

	/**
	 * Test the api_direct_messages_box() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithUnallowedUser()
	{
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		api_direct_messages_box('json', 'sentbox', 'false');
	}

	/**
	 * Test the api_direct_messages_sentbox() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesSentbox()
	{
		$result = api_direct_messages_sentbox('json');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_inbox() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesInbox()
	{
		$result = api_direct_messages_inbox('json');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_all() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesAll()
	{
		$result = api_direct_messages_all('json');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_direct_messages_conversation() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesConversation()
	{
		$result = api_direct_messages_conversation('json');
		self::assertArrayHasKey('direct_message', $result);
	}

	/**
	 * Test the api_oauth_request_token() function.
	 *
	 * @return void
	 */
	public function testApiOauthRequestToken()
	{
		$this->markTestIncomplete('exit() kills phpunit as well');
	}

	/**
	 * Test the api_oauth_access_token() function.
	 *
	 * @return void
	 */
	public function testApiOauthAccessToken()
	{
		$this->markTestIncomplete('exit() kills phpunit as well');
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
	 * Test the api_share_as_retweet() function with a valid item.
	 *
	 * @return void
	 */
	public function testApiShareAsRetweetWithValidItem()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_in_reply_to() function with a valid item.
	 *
	 * @return void
	 */
	public function testApiInReplyToWithValidItem()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_clean_plain_items() function.
	 *
	 * @return void
	 */
	public function testApiCleanPlainItems()
	{
		$_REQUEST['include_entities'] = 'true';
		$result                       = api_clean_plain_items('some_text [url="some_url"]some_text[/url]');
		self::assertEquals('some_text [url="some_url"]"some_url"[/url]', $result);
	}

	/**
	 * Test the api_best_nickname() function with contacts.
	 *
	 * @return void
	 */
	public function testApiBestNicknameWithContacts()
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
	 * Test the api_friendica_group_delete() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaGroupDelete()
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
	 * Test the api_friendica_group_update() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaGroupUpdate()
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

	/**
	 * Test the api_friendica_activity() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaActivity()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_notification_seen() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaNotificationSeen()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_direct_messages_setseen() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaDirectMessagesSetseen()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_friendica_direct_messages_search() function.
	 *
	 * @return void
	 */
	public function testApiFriendicaDirectMessagesSearch()
	{
		$this->markTestIncomplete();
	}
}
