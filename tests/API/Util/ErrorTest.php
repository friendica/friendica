<?php

namespace Friendica\Test\API;

use Friendica\Network\HTTPException;

class ErrorTest extends ApiTest
{
	/**
	 * Test the api_error() function with a JSON result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithJson()
	{
		$this->assertEquals(
			'{"status":{"error":"error_message","code":"200 Friendica\\\\Network\\\\HTTP","request":""}}',
			api_error('json', new HTTPException('error_message'))
		);
	}

	/**
	 * Test the api_error() function with an XML result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithXml()
	{
		$this->assertEquals(
			'<?xml version="1.0"?>'."\n".
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '.
			'xmlns:friendica="http://friendi.ca/schema/api/1/" '.
			'xmlns:georss="http://www.georss.org/georss">'."\n".
			'  <error>error_message</error>'."\n".
			'  <code>200 Friendica\Network\HTTP</code>'."\n".
			'  <request/>'."\n".
			'</status>'."\n",
			api_error('xml', new HTTPException('error_message'))
		);
	}

	/**
	 * Test the api_error() function with an RSS result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithRss()
	{
		$this->assertEquals(
			'<?xml version="1.0"?>'."\n".
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '.
			'xmlns:friendica="http://friendi.ca/schema/api/1/" '.
			'xmlns:georss="http://www.georss.org/georss">'."\n".
			'  <error>error_message</error>'."\n".
			'  <code>200 Friendica\Network\HTTP</code>'."\n".
			'  <request/>'."\n".
			'</status>'."\n",
			api_error('rss', new HTTPException('error_message'))
		);
	}

	/**
	 * Test the api_error() function with an Atom result.
	 * @return void
	 * @runInSeparateProcess
	 */
	public function testWithAtom()
	{
		$this->assertEquals(
			'<?xml version="1.0"?>'."\n".
			'<status xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '.
			'xmlns:friendica="http://friendi.ca/schema/api/1/" '.
			'xmlns:georss="http://www.georss.org/georss">'."\n".
			'  <error>error_message</error>'."\n".
			'  <code>200 Friendica\Network\HTTP</code>'."\n".
			'  <request/>'."\n".
			'</status>'."\n",
			api_error('atom', new HTTPException('error_message'))
		);
	}
}
