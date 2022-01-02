<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\UserTimeline;
use Friendica\Test\src\Module\Api\ApiTest;

class UserTimelineTest extends ApiTest
{
	/**
	 * Test the api_statuses_user_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimeline()
	{
		$response = (new UserTimeline(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'user_id'         => 42,
				'max_id'          => 10,
				'exclude_replies' => true,
				'conversation_id' => 7,
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertNotEmpty($json);
		foreach ($json as $status) {
			self::assertIsString($status->text);
			self::assertIsInt($status->id);
		}
	}

	/**
	 * Test the api_statuses_user_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithNegativePage()
	{
		$response = (new UserTimeline(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'user_id' => 42,
				'page'    => -2,
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertNotEmpty($json);
		foreach ($json as $status) {
			self::assertIsString($status->text);
			self::assertIsInt($status->id);
		}
	}

	/**
	 * Test the api_statuses_user_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithRss()
	{
		$response = (new UserTimeline(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], [
			'extension' => ICanCreateResponses::TYPE_RSS
		]))->run();

		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string)$response->getBody(), 'statuses');
	}

	/**
	 * Test the api_statuses_user_timeline() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_user_timeline('json');
	}
}
