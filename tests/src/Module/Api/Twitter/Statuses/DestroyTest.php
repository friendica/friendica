<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Destroy;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class DestroyTest extends ApiTest
{
	/**
	 * Test the api_statuses_destroy() function.
	 *
	 * @return void
	 */
	public function testApiStatusesDestroy()
	{
		$this->expectException(BadRequestException::class);

		(new Destroy(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run();
	}

	/**
	 * Test the api_statuses_destroy() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesDestroyWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// $_SESSION['authenticated'] = false;
		// api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesDestroyWithId()
	{
		$response = (new Destroy(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run([
				'id' => 1
			]);

		$json = $this->toJson($response);

		self::assertEquals(1, $json->id);
		self::assertIsObject($json->user);
		self::assertIsObject($json->friendica_author);
	}
}
