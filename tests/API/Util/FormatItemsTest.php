<?php

namespace Friendica\Test\API;

use Friendica\Core\Protocol;

class FormatItemsTest extends ApiTest
{
	/**
	 * Test the api_format_items() function.
	 * @return void
	 */
	public function testDefault()
	{
		$items = [
			[
				'item_network' => 'item_network',
				'source' => 'web',
				'coord' => '5 7',
				'body' => '',
				'verb' => '',
				'author-id' => 43,
				'author-network' => Protocol::DFRN,
				'author-link' => 'http://localhost/profile/othercontact',
				'plink' => '',
			]
		];
		$result = api_format_items($items, ['id' => 0], true);
		foreach ($result as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_format_items() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$items = [
			[
				'coord' => '5 7',
				'body' => '',
				'verb' => '',
				'author-id' => 43,
				'author-network' => Protocol::DFRN,
				'author-link' => 'http://localhost/profile/othercontact',
				'plink' => '',
			]
		];
		$result = api_format_items($items, ['id' => 0], true, 'xml');
		foreach ($result as $status) {
			$this->assertStatus($status);
		}
	}
}
