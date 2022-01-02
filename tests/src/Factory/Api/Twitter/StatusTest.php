<?php

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Friendica\Activities;
use Friendica\Factory\Api\Twitter\Attachment;
use Friendica\Factory\Api\Twitter\Hashtag;
use Friendica\Factory\Api\Twitter\Media;
use Friendica\Factory\Api\Twitter\Mention;
use Friendica\Factory\Api\Twitter\Status;
use Friendica\Factory\Api\Twitter\Url;
use Friendica\Test\FixtureTest;
use Friendica\Test\src\Module\Api\ApiTest;

class StatusTest extends FixtureTest
{
	protected $statusFactory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->statusFactory = new Status(
			DI::logger(),
			DI::dba(),
			DI::twitterUser(),
			new Hashtag(DI::logger()),
			new Media(DI::logger(), DI::baseUrl()),
			new Url(DI::logger()),
			new Mention(DI::logger(), DI::baseUrl()),
			new Activities(DI::logger(), DI::baseUrl(), DI::twitterUser()),
			new Attachment(DI::logger()));
	}

	/**
	 * Test the api_convert_item() function.
	 *
	 * @return void
	 */
	public function testApiConvertItem()
	{
		$status = $this->statusFactory
			->createFromItemId(13, ApiTest::SELF_USER['id'])
			->toArray();

		self::assertStringStartsWith('item_title', $status['text']);
		self::assertStringStartsWith('<h4>item_title</h4><br>perspiciatis impedit voluptatem', $status['friendica_html']);
	}

	/**
	 * Test the api_convert_item() function with an empty item body.
	 *
	 * @return void
	 */
	public function testApiConvertItemWithoutBody()
	{
		self::markTestIncomplete('Needs a dataset first');

		/*
		$result = api_convert_item(
			[
				'network' => 'feed',
				'title'   => 'item_title',
				'uri-id'  => -1,
				'body'    => '',
				'plink'   => 'item_plink'
			]
		);
		self::assertEquals("item_title", $result['text']);
		self::assertEquals('<h4>item_title</h4><br>item_plink', $result['html']);
		*/
	}

	/**
	 * Test the api_convert_item() function with the title in the body.
	 *
	 * @return void
	 */
	public function testApiConvertItemWithTitleInBody()
	{
		self::markTestIncomplete('Needs a dataset first');

		/*
		$result = api_convert_item(
			[
				'title'  => 'item_title',
				'body'   => 'item_title item_body',
				'uri-id' => 1,
			]
		);
		self::assertEquals('item_title item_body', $result['text']);
		self::assertEquals('<h4>item_title</h4><br>item_title item_body', $result['html']);
		*/
	}

	/**
	 * Test the api_get_entitities() function.
	 *
	 * @return void
	 */
	public function testApiGetEntititiesWithIncludeEntities()
	{
		$status = $this->statusFactory
			->createFromItemId(13, ApiTest::SELF_USER['id'], true)
			->toArray();

		self::assertIsArray($status['entities']);
		self::assertIsArray($status['extended_entities']);
		self::assertIsArray($status['entities']['hashtags']);
		self::assertIsArray($status['entities']['media']);
		self::assertIsArray($status['entities']['urls']);
		self::assertIsArray($status['entities']['user_mentions']);
	}

	/**
	 * Test the api_format_items() function.
	 */
	public function testApiFormatItems()
	{
		$posts = DI::dba()->selectToArray('post-view', ['uri-id']);
		foreach ($posts as $item) {
			$status = $this->statusFactory
				->createFromUriId($item['uri-id'], ApiTest::SELF_USER['id'])
				->toArray();

			self::assertIsInt($status['id']);
			self::assertIsString($status['text']);
		}
	}
}
