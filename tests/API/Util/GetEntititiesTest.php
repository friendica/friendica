<?php

namespace Friendica\Test\API;

class GetEntititiesTest extends ApiTest
{
	/**
	 * Test the api_get_entitities() function.
	 * @return void
	 */
	public function testDefault()
	{
		$text = 'text';
		$this->assertInternalType('array', api_get_entitities($text, 'bbcode'));
	}

	/**
	 * Test the api_get_entitities() function with the include_entities parameter.
	 * @return void
	 */
	public function testWithIncludeEntities()
	{
		$_REQUEST['include_entities'] = 'true';
		$text = 'text';
		$result = api_get_entitities($text, 'bbcode');
		$this->assertInternalType('array', $result['hashtags']);
		$this->assertInternalType('array', $result['symbols']);
		$this->assertInternalType('array', $result['urls']);
		$this->assertInternalType('array', $result['user_mentions']);
	}
}
