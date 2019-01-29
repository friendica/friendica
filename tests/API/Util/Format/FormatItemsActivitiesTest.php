<?php

namespace Friendica\Test\API;

use Friendica\Test\Util\ApiUserItemDatasetTrait;
use Friendica\Test\Util\Mocks\ItemMockTrait;

class FormatItemsActivitiesTest extends ApiTest
{
	use ApiUserItemDatasetTrait;
	use ItemMockTrait;

	/**
	 * Test the api_format_items_activities() function.
	 * @dataProvider dataApiUserItemFull
	 * @return void
	 */
	public function testDefault($user, $item)
	{
		$this->mockSelectForUser($item['uid'], ['author-id', 'verb'], ['uid' => $item['uid'], 'thr-parent' => $item['uri']], [], ['author-id' => $item['author-id'], 'verb' => $item['verb']], 1);
		$this->mockItemFetch(['author-id' => $item['author-id'], 'verb' => $item['verb']], ['author-id' => $item['author-id'], 'verb' => $item['verb']], 1);

		$this->mockApiGetUser($user, $item['author-id'], null, isset($user['self']), true, 1);

		$result = api_format_items_activities($item);
		$this->assertArrayHasKey('like', $result);
		$this->assertArrayHasKey('dislike', $result);
		$this->assertArrayHasKey('attendyes', $result);
		$this->assertArrayHasKey('attendno', $result);
		$this->assertArrayHasKey('attendmaybe', $result);
	}

	/**
	 * Test the api_format_items_activities() function with an XML result.
	 * @return void
	 */
	public function testWithXml()
	{
		$item = ['uid' => 0, 'uri' => ''];
		$result = api_format_items_activities($item, 'xml');
		$this->assertArrayHasKey('friendica:like', $result);
		$this->assertArrayHasKey('friendica:dislike', $result);
		$this->assertArrayHasKey('friendica:attendyes', $result);
		$this->assertArrayHasKey('friendica:attendno', $result);
		$this->assertArrayHasKey('friendica:attendmaybe', $result);
	}
}
