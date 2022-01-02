<?php

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Search;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class SearchTest extends ApiTest
{
	/**
	 * Test the api_users_search() function.
	 *
	 * @return void
	 */
	public function testApiUsersSearch()
	{
		$respone = (new Search(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run([
				'q' => static::OTHER_USER['name']
			]);

		$json = $this->toJson($respone);

		self::assertOtherUser($json[0]);
	}

	/**
	 * Test the api_users_search() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiUsersSearchWithXml()
	{
		$respone = (new Search(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], [
			'extension' => ICanCreateResponses::TYPE_XML
		]))->run([
			'q' => static::OTHER_USER['name']
		]);

		self::assertXml((string)$respone->getBody(), 'users');
	}

	/**
	 * Test the api_users_search() function without a GET q parameter.
	 *
	 * @return void
	 */
	public function testApiUsersSearchWithoutQuery()
	{
		$this->expectException(BadRequestException::class);

		(new Search(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]))
			->run();
	}
}
