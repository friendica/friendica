<?php

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Favorites;
use Friendica\Test\src\Module\Api\ApiTest;

class FavoritesTest extends ApiTest
{
	/**
	 * Test the api_favorites() function.
	 *
	 * @return void
	 */
	public function testApiFavorites()
	{
		$response = (new Favorites(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'page'   => -1,
				'max_id' => 10,
			]);

		$json = $this->toJson($response);

		foreach ($json as $status) {
			$this->assertStatus($status);
		}
	}

	/**
	 * Test the api_favorites() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiFavoritesWithRss()
	{
		$response = (new Favorites(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], [
			'extension' => ICanCreateResponses::TYPE_RSS
		]))->run();

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string)$response->getBody(), 'statuses');
	}

	/**
	 * Test the api_favorites() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiFavoritesWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_favorites('json');
	}
}
