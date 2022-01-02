<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Show;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_statuses_show() function.
	 *
	 * @return void
	 */
	public function testApiStatusesShow()
	{
		$this->expectException(BadRequestException::class);


		(new Show(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();
	}

	/**
	 * Test the api_statuses_show() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithId()
	{
		$response = (new Show(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'id' => 1
			]);

		$json = $this->toJson($response);

		self::assertIsInt($json->id);
		self::assertIsString($json->text);
	}

	/**
	 * Test the api_statuses_show() function with the conversation parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithConversation()
	{
		$response = (new Show(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'id'           => 1,
				'conversation' => 1
			]);

		$json = $this->toJson($response);

		self::assertIsArray($json);

		foreach ($json as $status) {
			self::assertIsInt($status->id);
			self::assertIsString($status->text);
		}
	}

	/**
	 * Test the api_statuses_show() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_show('json');
	}
}
