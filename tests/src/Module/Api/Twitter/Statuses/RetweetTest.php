<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Retweet;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class RetweetTest extends ApiTest
{
	/**
	 * Test the api_statuses_repeat() function.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeat()
	{
		$this->expectException(BadRequestException::class);

		(new Retweet(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run();
	}

	/**
	 * Test the api_statuses_repeat() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// $_SESSION['authenticated'] = false;
		// api_statuses_repeat('json');
	}

	/**
	 * Test the api_statuses_repeat() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithId()
	{
		$response = (new Retweet(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run([
				'id' => 1
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	/**
	 * Test the api_statuses_repeat() function with an shared ID.
	 *
	 * @return void
	 */
	public function testApiStatusesRepeatWithSharedId()
	{
		$response = (new Retweet(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run([
				'id' => 5
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}
}
