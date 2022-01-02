<?php

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Show;
use Friendica\Test\src\Module\Api\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_users_show() function.
	 *
	 * @return void
	 */
	public function testApiUsersShow()
	{
		$response = (new Show(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();

		$json = $this->toJson($response);

		// We can't use assertSelfUser() here because the user object is missing some properties.
		self::assertEquals(static::SELF_USER['id'], $json->cid);
		self::assertEquals('DFRN', $json->location);
		self::assertEquals(static::SELF_USER['name'], $json->name);
		self::assertEquals(static::SELF_USER['nick'], $json->screen_name);
		self::assertTrue($json->verified);
	}

	/**
	 * Test the api_users_show() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiUsersShowWithXml()
	{
		$response = (new Show(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], [
			'extension' => ICanCreateResponses::TYPE_XML
		]))->run();

		self::assertEquals(ICanCreateResponses::TYPE_XML, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string)$response->getBody(), 'statuses');
	}
}
