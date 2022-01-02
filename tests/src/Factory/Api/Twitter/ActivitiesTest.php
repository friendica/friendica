<?php

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Friendica\Activities;
use Friendica\Test\FixtureTest;

class ActivitiesTest extends FixtureTest
{
	/**
	 * Test the api_format_items_activities() function.
	 *
	 * @return void
	 */
	public function testApiFormatItemsActivities()
	{
		$item = ['uid' => 0, 'uri-id' => 1];

		$result = (new Activities(DI::logger(), DI::baseUrl(), DI::twitterUser()))
			->createFromUriId($item['uri-id'], $item['uid']);

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
		$item = ['uid' => 0, 'uri-id' => 1];

		$result = (new Activities(DI::logger(), DI::baseUrl(), DI::twitterUser()))
			->createFromUriId($item['uri-id'], $item['uid'], 'xml');

		self::assertArrayHasKey('friendica:like', $result);
		self::assertArrayHasKey('friendica:dislike', $result);
		self::assertArrayHasKey('friendica:attendyes', $result);
		self::assertArrayHasKey('friendica:attendno', $result);
		self::assertArrayHasKey('friendica:attendmaybe', $result);
	}
}
