<?php

namespace Friendica\Test\API;

class CleanPlainItemsTest extends ApiTest
{
	public function dataCleanItems()
	{
		return [
			'url' => [
				'input' => 'some_text [url="some_url"]some_text[/url]',
				'output' => 'some_text [url="some_url"]"some_url"[/url]',
			],
		];
	}

	/**
	 * Test the api_clean_plain_items() function.
	 * @dataProvider dataCleanItems
	 * @return void
	 */
	public function testDefault($input, $output)
	{
		$this->mockCleanPictureLinks($input, $input, 1);
		$result = ['after' => '', 'text' => $output];
		$this->mockGetAttachmentData($output, $result, 1);

		$_REQUEST['include_entities'] = 'true';
		$result = api_clean_plain_items($input);
		$this->assertEquals($output, $result);
	}
}
