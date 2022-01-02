<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Mentions;
use Friendica\Test\src\Module\Api\ApiTest;

class MentionsTest extends ApiTest
{
	/**
	 * Test the api_statuses_mentions() function.
	 *
	 * @return void
	 */
	public function testApiStatusesMentions()
	{
		$response = (new Mentions(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'max_id' => 10
			]);

		$json = $this->toJson($response);

		self::assertEmpty($json);
		// We should test with mentions in the database.
	}

	/**
	 * Test the api_statuses_mentions() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithNegativePage()
	{
		$response = (new Mentions(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'page' => -2
			]);

		$json = $this->toJson($response);

		self::assertEmpty($json);
		// We should test with mentions in the database.
	}

	/**
	 * Test the api_statuses_mentions() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_mentions('json');
	}

	/**
	 * Test the api_statuses_mentions() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithRss()
	{
		$response = (new Mentions(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => ICanCreateResponses::TYPE_RSS]))
			->run([
				'page' => -2
			]);

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string)$response->getBody(), 'statuses');
	}
}
