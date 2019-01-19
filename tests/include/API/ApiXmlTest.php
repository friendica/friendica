<?php

namespace Friendica\Test\Api;

use Friendica\Test\ApiTest;

class ApiXmlTest extends ApiTest
{
	/**
	 * Test the api_reformat_xml() function.
	 * @return void
	 */
	public function testReformatXml()
	{
		$item = true;
		$key = '';
		$this->assertTrue(api_reformat_xml($item, $key));
		$this->assertEquals('true', $item);
	}

	/**
	 * Test the api_reformat_xml() function with a statusnet_api key.
	 * @return void
	 */
	public function testReformatXmlWithStatusnetKey()
	{
		$item = '';
		$key = 'statusnet_api';
		$this->assertTrue(api_reformat_xml($item, $key));
		$this->assertEquals('statusnet:api', $key);
	}

	/**
	 * Test the api_reformat_xml() function with a friendica_api key.
	 * @return void
	 */
	public function testReformatXmlWithFriendicaKey()
	{
		$item = '';
		$key = 'friendica_api';
		$this->assertTrue(api_reformat_xml($item, $key));
		$this->assertEquals('friendica:api', $key);
	}

	/**
	 * Test the api_create_xml() function.
	 * @return void
	 */
	public function testCreateXml()
	{
		$this->assertEquals(
			'<?xml version="1.0"?>'."\n".
			'<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '.
			'xmlns:friendica="http://friendi.ca/schema/api/1/" '.
			'xmlns:georss="http://www.georss.org/georss">'."\n".
			'  <data>some_data</data>'."\n".
			'</root_element>'."\n",
			api_create_xml(['data' => ['some_data']], 'root_element')
		);
	}

	/**
	 * Test the api_create_xml() function without any XML namespace.
	 * @return void
	 */
	public function testCreateXmlWithoutNamespaces()
	{
		$this->assertEquals(
			'<?xml version="1.0"?>'."\n".
			'<ok>'."\n".
			'  <data>some_data</data>'."\n".
			'</ok>'."\n",
			api_create_xml(['data' => ['some_data']], 'ok')
		);
	}
}
