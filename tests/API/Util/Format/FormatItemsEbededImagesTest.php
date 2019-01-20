<?php

namespace Friendica\Test\API;

class FormatItemsEbededImagesTest extends ApiTest
{
	/**
	 * Test the api_format_items_embeded_images() function.
	 * @return void
	 */
	public function testDefault()
	{
		$this->assertEquals(
			'text ' . System::baseUrl() . '/display/item_guid',
			api_format_items_embeded_images(['guid' => 'item_guid'], 'text data:image/foo')
		);
	}
}
