<?php

namespace Friendica\Test\src\Module\Api\Mastodon\Accounts;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\Accounts\VerifyCredentials;
use Friendica\Test\src\Module\Api\ApiTest;

class VerifyCredentialsTest extends ApiTest
{
	/**
	 * Test the api_account_verify_credentials() function.
	 *
	 * @return void
	 */
	public function testApiAccountVerifyCredentials()
	{
		$response = (new VerifyCredentials(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();

		$json = $this->toJson($response);

		self::assertEquals(48, $json->id);
		self::assertIsArray($json->emojis);
		self::assertIsArray($json->fields);
	}

	/**
	 * Test the api_account_verify_credentials() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiAccountVerifyCredentialsWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs dynamic BasicAuth first');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// $_SESSION['authenticated'] = false;
		// api_account_verify_credentials('json');
	}
}
