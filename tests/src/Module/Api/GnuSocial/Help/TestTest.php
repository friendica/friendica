<?php

namespace Friendica\Test\src\Module\Api\GnuSocial\Help;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\GNUSocial\Help\Test;
use Friendica\Test\src\Module\Api\ApiTest;

class TestTest extends ApiTest
{
	public function testJson()
	{
		$response = (new Test(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run();

		$json = $this->toJson($response);

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json']
		], $response->getHeaders());
		self::assertEquals('ok', $json);
	}

	public function testXml()
	{
		$response = (new Test(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']))
			->run();

		self::assertEquals([
			'Content-type'                => ['text/xml'],
			ICanCreateResponses::X_HEADER => ['xml']
		], $response->getHeaders());
		self::assertxml($response->getBody(), 'ok');
	}
}
