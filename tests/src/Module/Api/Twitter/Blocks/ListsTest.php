<?php

namespace Friendica\Test\src\Module\Api\Twitter\Blocks;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Blocks\Lists;
use Friendica\Test\src\Module\Api\ApiTest;

class ListsTest extends ApiTest
{
	/**
	 * Test the api_statuses_f() function.
	 */
	public function testApiStatusesFWithBlocks()
	{
		$response = (new Lists(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
	}

	/**
	 * Test the api_blocks_list() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiBlocksListWithUndefinedCursor()
	{
		self::markTestIncomplete('Needs refactoring of Lists - replace filter_input() with $request parameter checks');

		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_blocks_list('json'));
	}
}
