<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Update;
use Friendica\Test\src\Module\Api\ApiTest;

class UpdateTest extends ApiTest
{
	/**
	 * Test the api_statuses_update() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdate()
	{
		$_FILES = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png'
			]
		];

		$response = (new Update(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run([
				'status'                => 'Status content #friendica',
				'in_reply_to_status_id' => 0,
				'lat'                   => 48,
				'long'                  => 7,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
		self::assertStringContainsString('Status content #friendica', $json->text);
		self::assertStringContainsString('Status content #', $json->statusnet_html);
	}

	/**
	 * Test the api_statuses_update() function with an HTML status.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithHtml()
	{
		$response = (new Update(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::POST]))
			->run([
				'htmlstatus' => '<b>Status content</b>',
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}

	/**
	 * Test the api_statuses_update() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_statuses_update('json');
		*/
	}

	/**
	 * Test the api_statuses_update() function with a parent status.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithParent()
	{
		$this->markTestIncomplete('This triggers an exit() somewhere and kills PHPUnit.');
	}

	/**
	 * Test the api_statuses_update() function with a media_ids parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithMediaIds()
	{
		$this->markTestIncomplete();
	}

	/**
	 * Test the api_statuses_update() function with the throttle limit reached.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithDayThrottleReached()
	{
		$this->markTestIncomplete();
	}
}
