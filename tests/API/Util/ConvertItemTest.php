<?php

namespace Friendica\Test\API;

class ConvertItemTest extends ApiTest
{
	/**
	 * Test the api_convert_item() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = api_convert_item(
			[
				'network' => 'feed',
				'title' => 'item_title',
				// We need a long string to test that it is correctly cut
				'body' => 'perspiciatis impedit voluptatem quis molestiae ea qui '.
					'reiciendis dolorum aut ducimus sunt consequatur inventore dolor '.
					'officiis pariatur doloremque nemo culpa aut quidem qui dolore '.
					'laudantium atque commodi alias voluptatem non possimus aperiam '.
					'ipsum rerum consequuntur aut amet fugit quia aliquid praesentium '.
					'repellendus quibusdam et et inventore mollitia rerum sit autem '.
					'pariatur maiores ipsum accusantium perferendis vel sit possimus '.
					'veritatis nihil distinctio qui eum repellat officia illum quos '.
					'impedit quam iste esse unde qui suscipit aut facilis ut inventore '.
					'omnis exercitationem quo magnam consequatur maxime aut illum '.
					'soluta quaerat natus unde aspernatur et sed beatae nihil ullam '.
					'temporibus corporis ratione blanditiis perspiciatis impedit '.
					'voluptatem quis molestiae ea qui reiciendis dolorum aut ducimus '.
					'sunt consequatur inventore dolor officiis pariatur doloremque '.
					'nemo culpa aut quidem qui dolore laudantium atque commodi alias '.
					'voluptatem non possimus aperiam ipsum rerum consequuntur aut '.
					'amet fugit quia aliquid praesentium repellendus quibusdam et et '.
					'inventore mollitia rerum sit autem pariatur maiores ipsum accusantium '.
					'perferendis vel sit possimus veritatis nihil distinctio qui eum '.
					'repellat officia illum quos impedit quam iste esse unde qui '.
					'suscipit aut facilis ut inventore omnis exercitationem quo magnam '.
					'consequatur maxime aut illum soluta quaerat natus unde aspernatur '.
					'et sed beatae nihil ullam temporibus corporis ratione blanditiis',
				'plink' => 'item_plink'
			]
		);
		$this->assertStringStartsWith('item_title', $result['text']);
		$this->assertStringStartsWith('<h4>item_title</h4><br>perspiciatis impedit voluptatem', $result['html']);
	}

	/**
	 * Test the api_convert_item() function with an empty item body.
	 * @return void
	 */
	public function testWithoutBody()
	{
		$result = api_convert_item(
			[
				'network' => 'feed',
				'title' => 'item_title',
				'body' => '',
				'plink' => 'item_plink'
			]
		);
		$this->assertEquals('item_title', $result['text']);
		$this->assertEquals('<h4>item_title</h4><br>item_plink', $result['html']);
	}

	/**
	 * Test the api_convert_item() function with the title in the body.
	 * @return void
	 */
	public function testWithTitleInBody()
	{
		$result = api_convert_item(
			[
				'title' => 'item_title',
				'body' => 'item_title item_body'
			]
		);
		$this->assertEquals('item_title item_body', $result['text']);
		$this->assertEquals('<h4>item_title</h4><br>item_title item_body', $result['html']);
	}
}
