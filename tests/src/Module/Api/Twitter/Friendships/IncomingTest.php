<?php

namespace Friendica\Test\src\Module\Api\Twitter\Friendships;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Friendships\Incoming;
use Friendica\Test\src\Module\Api\ApiTest;

class IncomingTest extends ApiTest
{
	/**
	 * Test the api_friendships_incoming() function.
	 *
	 * @return void
	 */
	public function testApiFriendshipsIncoming()
	{
		$response = (new Incoming(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();

		$json = $this->toJson($response);

		self::assertIsArray($json->ids);
	}

	/**
	 * Test the api_friendships_incoming() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiFriendshipsIncomingWithUndefinedCursor()
	{
		self::markTestIncomplete('Needs refactoring of Incoming - replace filter_input() with $request parameter checks');

		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_friendships_incoming('json'));
	}
}
