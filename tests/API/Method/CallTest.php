<?php

namespace Friendica\Test\API;

class CallTest extends ApiTest
{

	/**
	 * Test the api_call() function.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testDefault()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return ['data' => ['some_data']];
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_GET['callback'] = 'callback_name';

		$this->mockConfigGet('system', 'profiler', false, 1);

		$this->app->query_string = 'api_path';
		$this->assertEquals(
			'callback_name(["some_data"])',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with the profiled enabled.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithProfiler()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return ['data' => ['some_data']];
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$this->mockConfigGet('system', 'profiler', true);
		$this->mockConfigGet('rendertime', 'callstack', true);
		$this->app->callstack = [
			'database' => ['some_function' => 200],
			'database_write' => ['some_function' => 200],
			'cache' => ['some_function' => 200],
			'cache_write' => ['some_function' => 200],
			'network' => ['some_function' => 200]
		];
		$this->app->performance['start'] = (float)(microtime(true));

		$this->app->performance["database"] = (float)(microtime(true));
		$this->app->performance["database_write"] = (float)(microtime(true));
		$this->app->performance["cache"] = (float)(microtime(true));
		$this->app->performance["cache_write"] = (float)(microtime(true));
		$this->app->performance["network"] = (float)(microtime(true));
		$this->app->performance["file"] = (float)(microtime(true));

		$this->app->query_string = 'api_path';
		$this->assertEquals(
			'["some_data"]',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function without any result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithNoResult()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return false;
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';

		$this->mockConfigGet('system', 'profiler', false);

		$this->app->query_string = 'api_path';
		$this->assertEquals(
			'{"status":{"error":"Internal Server Error","code":"500 Internal Server Error","request":"api_path"}}',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with an unimplemented API.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithUninplementedApi()
	{
		$this->assertEquals(
			'{"status":{"error":"Not Implemented","code":"501 Not Implemented","request":""}}',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with a JSON result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithJson()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return ['data' => ['some_data']];
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';

		$this->mockConfigGet('system', 'profiler', false);

		$this->app->query_string = 'api_path.json';
		$this->assertEquals(
			'["some_data"]',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with an XML result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithXml()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return 'some_data';
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';

		$this->mockConfigGet('system', 'profiler', false);

		$this->app->query_string = 'api_path.xml';
		$this->assertEquals(
			'some_data',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with an RSS result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithRss()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return 'some_data';
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';

		$this->mockConfigGet('system', 'profiler', false);

		$this->app->query_string = 'api_path.rss';
		$this->assertEquals(
			'<?xml version="1.0" encoding="UTF-8"?>'."\n".
			'some_data',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with an Atom result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithAtom()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'func' => function () {
				return 'some_data';
			}
		];
		$_SERVER['REQUEST_METHOD'] = 'method';

		$this->mockConfigGet('system', 'profiler', false);

		$this->app->query_string = 'api_path.atom';
		$this->assertEquals(
			'<?xml version="1.0" encoding="UTF-8"?>'."\n".
			'some_data',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with an unallowed method.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithWrongMethod()
	{
		global $API;
		$API['api_path'] = ['method' => 'method'];

		$this->app->query_string = 'api_path';
		$this->assertEquals(
			'{"status":{"error":"Method Not Allowed","code":"405 Method Not Allowed","request":"api_path"}}',
			api_call($this->app)
		);
	}

	/**
	 * Test the api_call() function with an unauthorized user.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithWrongAuth()
	{
		global $API;
		$API['api_path'] = [
			'method' => 'method',
			'auth' => true
		];
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SESSION['authenticated'] = false;

		$this->app->query_string = 'api_path';
		$this->assertEquals(
			'{"status":{"error":"This API requires login","code":"401 Unauthorized","request":"api_path"}}',
			api_call($this->app)
		);
	}
}
