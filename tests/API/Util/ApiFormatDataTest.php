<?php

namespace Friendica\Test\API;

class ApiFormatDataTest extends ApiTest
{
	/**
	 * Test the api_format_data() function.
	 * @return void
	 */
	public function testWithJson()
	{
		$data = ['some_data'];
		$this->assertEquals($data, api_format_data('root_element', 'json', $data));
	}

	/**
	 * Test the api_format_data() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$this->assertEquals(
			'<?xml version="1.0"?>'."\n".
			'<root_element xmlns="http://api.twitter.com" xmlns:statusnet="http://status.net/schema/api/1/" '.
			'xmlns:friendica="http://friendi.ca/schema/api/1/" '.
			'xmlns:georss="http://www.georss.org/georss">'."\n".
			'  <data>some_data</data>'."\n".
			'</root_element>'."\n",
			api_format_data('root_element', 'xml', ['data' => ['some_data']])
		);
	}
}
