<?php

namespace Friendica\Test\src\Module\Api\Twitter\Followers;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Followers\Lists;
use Friendica\Test\src\Module\Api\ApiTest;

class ListsTest extends ApiTest
{
	/**
	 * Test the api_statuses_f() function.
	 */
	public function testApiStatusesFWithFollowers()
	{
		$response = (new Lists(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();

		$json = $this->toJson($response);

		self::assertIsArray($json->users);
	}

	/**
	 * Test the api_statuses_followers() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiStatusesFollowersWithUndefinedCursor()
	{
		self::markTestIncomplete('Needs refactoring of Lists - replace filter_input() with $request parameter checks');

		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_statuses_followers('json'));
	}
}
